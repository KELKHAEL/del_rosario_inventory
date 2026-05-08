<?php
include 'db.php';
require 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['excel_file'])) {
    
    $fileTmpPath = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($fileTmpPath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // 1. FUZZY MATCHING FUNCTION
    function fuzzyMatch($input, $validArray, $default = 'Others') {
        if (empty(trim((string)$input))) return $default;
        
        $cleanInput = preg_replace('/[^a-z0-9]/', '', strtolower($input)); 
        
        foreach ($validArray as $valid) {
            $cleanValid = preg_replace('/[^a-z0-9]/', '', strtolower($valid));
            if ($cleanInput === $cleanValid) {
                return $valid;
            }
        }
        
        foreach ($validArray as $valid) {
            $cleanValid = preg_replace('/[^a-z0-9]/', '', strtolower($valid));
            if (strpos($cleanInput, $cleanValid) !== false) {
                return $valid;
            }
        }
        
        return $default;
    }

    // 2. ROBUST DATE PARSER
    function parseDate($input) {
        $input = trim((string)$input);
        if (empty($input)) return null;
        
        if (is_numeric($input)) {
            return date('Y-m-d', Date::excelToTimestamp($input));
        }

        $cleanDate = preg_replace('/[^a-zA-Z0-9\s,\/\-]/', '', $input);
        $time = strtotime($cleanDate);
        return ($time !== false && $time > 0) ? date('Y-m-d', $time) : null;
    }

    // 3. INTELLIGENT NAME SPLITTER (Updated for "Last, First Middle" format for both)
    function splitName($fullName) {
        $last = ''; $first = ''; $middle = '';
        $cleanName = preg_replace('/\s+/', ' ', trim($fullName));
        
        if (strpos($cleanName, ',') !== false) {
            // Split by comma: "Baturiano", "Milagrosa Otacan" or "Noel P."
            $parts = explode(',', $cleanName, 2);
            $last = trim($parts[0]);
            
            // Now look at the first name part
            $fm_parts = explode(' ', trim($parts[1]));
            
            // If there's more than one word in the first name section, the last word is the middle name/initial
            if (count($fm_parts) > 1) {
                $middle = array_pop($fm_parts); 
                $first = implode(' ', $fm_parts); 
            } else {
                $first = trim($parts[1]);
            }
        } else {
            // Fallback if no comma is used
            $name_parts = explode(' ', $cleanName);
            $last = count($name_parts) > 1 ? array_shift($name_parts) : $cleanName;
            $middle = count($name_parts) > 1 ? array_pop($name_parts) : '';
            $first = count($name_parts) > 0 ? implode(' ', $name_parts) : '';
        }
        
        return [$last, $first, $middle];
    }

    $valid_occupations = ['Private Employee', "Gov't Employee", 'Self-Employed', 'Farmer', 'Pensioner', 'Student', 'House Keeper', 'Fisher folk', 'Entrepreneur/Vendor', 'Others'];
    $valid_civil_status = ['Single', 'Married', 'Widowed', 'Separated'];

    $last_inserted_member_id = null;
    
    // Dynamically find the headers
    $header_map = [];
    $start_row = 1;
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        if (!empty($rows[$i][0]) && strtolower(trim((string)$rows[$i][0])) === 'form id') {
            $start_row = $i + 1;
            foreach($rows[$i] as $col_index => $col_name) {
                $clean_col = strtolower(trim(preg_replace('/\s+/', ' ', (string)$col_name)));
                if(!empty($clean_col)) {
                    $header_map[$clean_col] = $col_index;
                }
            }
            break;
        }
    }

    // Helper to safely pull mapped columns
    function getVal($row, $map, ...$possibleKeys) {
        foreach ($possibleKeys as $key) {
            if (isset($map[$key]) && isset($row[$map[$key]])) {
                $val = trim((string)$row[$map[$key]]);
                if ($val !== '') return $val;
            }
        }
        return '';
    }

    // Loop through Excel Rows
    for ($i = $start_row; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!is_array($row)) continue;

        $form_id        = getVal($row, $header_map, 'form id');
        $full_name      = getVal($row, $header_map, 'member name', 'name', 'full name');
        $dob            = parseDate(getVal($row, $header_map, 'date of birth'));
        $birth_place    = getVal($row, $header_map, 'birth place');
        $civil_status   = getVal($row, $header_map, 'civil status');
        $religion       = getVal($row, $header_map, 'religion');
        $tribe          = getVal($row, $header_map, 'tribe');
        $sss            = preg_replace('/[^0-9\-]/', '', getVal($row, $header_map, 'sss/gsis no.', 'sss no'));
        $tin            = preg_replace('/[^0-9\-]/', '', getVal($row, $header_map, 'tin no.', 'tin'));
        $postal         = preg_replace('/[^0-9]/', '', getVal($row, $header_map, 'postal code'));
        $address        = getVal($row, $header_map, 'address');
        $business_add   = getVal($row, $header_map, 'business - office address', 'business address');
        $education      = getVal($row, $header_map, 'educational attainment');
        $employment     = getVal($row, $header_map, 'present employment/business activities', 'employment');
        $occupation     = getVal($row, $header_map, 'occupation');
        $income         = getVal($row, $header_map, 'monthly income');
        
        $ben_name       = getVal($row, $header_map, 'beneficiaries names', 'beneficiaries', 'beneficiary name');
        // Very aggressive header search for the Beneficiary DOB
        $ben_dob        = parseDate(getVal($row, $header_map, 'beneficiaries date of birth', 'beneficiary date of birth', 'date of birth (beneficiary)', 'dob'));
        $ben_rel        = getVal($row, $header_map, 'relationship to the member', 'relationship');

        // Bulletproof Sex parsing
        $raw_sex = strtolower(getVal($row, $header_map, 'sex'));
        $sex = '';
        if (strpos($raw_sex, 'female') !== false || $raw_sex === 'f') $sex = 'FEMALE';
        elseif (strpos($raw_sex, 'male') !== false || $raw_sex === 'm') $sex = 'MALE';

        // -- 1. INSERT NEW MEMBER --
        if (!empty($full_name)) {
            list($last_name, $first_name, $middle_name) = splitName($full_name);

            $stmt = $conn->prepare("INSERT INTO members (last_name, first_name, middle_name, date_of_birth, birth_place, civil_status, religion, sex, tribe, sss_gsis_no, tin_no, postal_code, address, business_office_address, educational_attainment, present_employment_business, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssssssss", $last_name, $first_name, $middle_name, $dob, $birth_place, $civil_status, $religion, $sex, $tribe, $sss, $tin, $postal, $address, $business_add, $education, $employment, $occupation, $income);
            $stmt->execute();
            
            $last_inserted_member_id = $stmt->insert_id;
            $stmt->close();
        }

        // -- 2. INSERT BENEFICIARY --
        if (!empty($ben_name) && $last_inserted_member_id !== null) {
            list($ben_last, $ben_first, $ben_middle) = splitName($ben_name);

            $stmt_ben = $conn->prepare("INSERT INTO beneficiaries (member_id, last_name, first_name, middle_name, date_of_birth, relationship) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ben->bind_param("isssss", $last_inserted_member_id, $ben_last, $ben_first, $ben_middle, $ben_dob, $ben_rel);
            $stmt_ben->execute();
            $stmt_ben->close();
        }
    }

    echo "<script>alert('Excel Upload & Scanning Complete!'); window.location.href='index.php';</script>";
}
?>