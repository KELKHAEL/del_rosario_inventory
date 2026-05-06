<?php
include 'db.php';
// Require PhpSpreadsheet
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
            if ($cleanInput === $cleanValid || strpos($cleanInput, $cleanValid) !== false) {
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
        return ($time !== false) ? date('Y-m-d', $time) : null;
    }

    $valid_occupations = ['Private Employee', "Gov't Employee", 'Self-Employed', 'Farmer', 'Pensioner', 'Student', 'House Keeper', 'Fisher folk', 'Entrepreneur/Vendor', 'Others'];
    $valid_civil_status = ['Single', 'Married', 'Widowed', 'Separated'];
    $valid_sex = ['MALE', 'FEMALE'];

    $last_inserted_member_id = null;

    // --- FORBIDDEN HEADERS LIST ---
    $forbidden_headers = ['name', 'full name', 'member name', 'beneficiaries', 'beneficiaries names', 'beneficiary name', 'form id'];

    // --- DYNAMIC HEADER DETECTION ---
    $start_row = 1;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $col1 = trim(strtolower((string)($rows[$i][1] ?? '')));
        if (in_array($col1, $forbidden_headers)) {
            $start_row = $i + 1; 
            break;
        }
    }

    // Loop through Excel Rows
    for ($i = $start_row; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!is_array($row)) continue;

        // --- PERFECTED COLUMN MAPPING ---
        $form_id        = trim((string)($row[0] ?? '')); 
        $full_name      = trim((string)($row[1] ?? '')); 
        $dob            = parseDate($row[2] ?? ''); 
        $birth_place    = trim((string)($row[3] ?? '')); 
        $civil_status   = fuzzyMatch($row[4] ?? '', $valid_civil_status, ''); 
        $religion       = trim((string)($row[5] ?? '')); 
        $sex            = fuzzyMatch($row[6] ?? '', $valid_sex, ''); 
        $tribe          = trim((string)($row[7] ?? '')); 
        $sss            = preg_replace('/[^0-9\-]/', '', trim((string)($row[8] ?? ''))); 
        $tin            = preg_replace('/[^0-9\-]/', '', trim((string)($row[9] ?? ''))); 
        $postal         = preg_replace('/[^0-9]/', '', trim((string)($row[10] ?? ''))); 
        $address        = trim((string)($row[11] ?? '')); 
        $business_add   = trim((string)($row[12] ?? '')); 
        $education      = trim((string)($row[13] ?? '')); 
        $employment     = trim((string)($row[14] ?? '')); 
        
        // Beneficiaries are in Columns P, Q, R (Indexes 15, 16, 17)
        $ben_name       = trim((string)($row[15] ?? '')); 
        $ben_dob        = parseDate($row[16] ?? ''); 
        $ben_rel        = trim((string)($row[17] ?? '')); 

        // Occupation and Income are in Columns S, T (Indexes 18, 19)
        $occupation     = fuzzyMatch($row[18] ?? '', $valid_occupations, 'Others'); 
        $income         = trim((string)($row[19] ?? '')); 

        $clean_check_name = trim(strtolower(preg_replace('/\s+/', ' ', $full_name)));
        $clean_check_ben = trim(strtolower(preg_replace('/\s+/', ' ', $ben_name)));

        // -- 1. INSERT NEW MEMBER --
        if (!empty($full_name) && !in_array($clean_check_name, $forbidden_headers)) {
            
            // Member Name splitting (Format: "Last Name, First Name")
            $full_name_clean = preg_replace('/\s+/', ' ', trim($full_name));
            if (strpos($full_name_clean, ',') !== false) {
                $parts = explode(',', $full_name_clean, 2);
                $last_name = trim($parts[0]);
                $first_name = trim($parts[1]);
            } else {
                $name_parts = explode(' ', $full_name_clean);
                $last_name = count($name_parts) > 1 ? array_shift($name_parts) : $full_name_clean;
                $first_name = count($name_parts) > 0 ? implode(' ', $name_parts) : '';
            }

            $stmt = $conn->prepare("INSERT INTO members (last_name, first_name, date_of_birth, birth_place, civil_status, religion, sex, tribe, sss_gsis_no, tin_no, postal_code, address, business_office_address, educational_attainment, present_employment_business, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssssss", $last_name, $first_name, $dob, $birth_place, $civil_status, $religion, $sex, $tribe, $sss, $tin, $postal, $address, $business_add, $education, $employment, $occupation, $income);
            $stmt->execute();
            
            // Save ID for beneficiaries
            $last_inserted_member_id = $stmt->insert_id;
            $stmt->close();
        }

        // -- 2. INSERT BENEFICIARY --
        if (!empty($ben_name) && !in_array($clean_check_ben, $forbidden_headers) && $last_inserted_member_id !== null) {
            
            // Beneficiary Name parsing (Format: "First Name Last Name")
            $ben_name_clean = preg_replace('/\s+/', ' ', trim($ben_name));
            if (strpos($ben_name_clean, ',') !== false) {
                $parts = explode(',', $ben_name_clean, 2);
                $ben_last = trim($parts[0]);
                $ben_first = trim($parts[1]);
            } else {
                $ben_parts = explode(' ', $ben_name_clean);
                $ben_last = count($ben_parts) > 1 ? array_pop($ben_parts) : $ben_name_clean;
                $ben_first = count($ben_parts) > 0 ? implode(' ', $ben_parts) : '';
            }

            $stmt_ben = $conn->prepare("INSERT INTO beneficiaries (member_id, last_name, first_name, date_of_birth, relationship) VALUES (?, ?, ?, ?, ?)");
            $stmt_ben->bind_param("issss", $last_inserted_member_id, $ben_last, $ben_first, $ben_dob, $ben_rel);
            $stmt_ben->execute();
            $stmt_ben->close();
        }
    }

    echo "<script>alert('Excel Upload & Scanning Complete!'); window.location.href='index.php';</script>";
}
?>