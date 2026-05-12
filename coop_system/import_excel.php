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

    // --- FETCH EXCEL HEADER MAPPINGS FROM DATABASE ---
    $db_map = [];
    $res_map = $conn->query("SELECT system_field, excel_header_name FROM config_excel_headers");
    
    if ($res_map && $res_map->num_rows > 0) {
        while($m = $res_map->fetch_assoc()) {
            $db_map[$m['system_field']] = preg_replace('/[^a-z0-9]/', '', strtolower(trim($m['excel_header_name'])));
        }
    } else {
        $db_map = [
            'form_id' => 'formid', 'member_name' => 'membername', 'dob' => 'dateofbirth',
            'birth_place' => 'birthplace', 'civil_status' => 'civilstatus', 'religion' => 'religion',
            'sex' => 'sex', 'tribe' => 'tribe', 'sss_no' => 'sssgsisno', 'tin_no' => 'tinno',
            'postal_code' => 'postalcode', 'address' => 'address', 'business_address' => 'businessofficeaddress',
            'education' => 'educationalattainment', 'employment' => 'presentemploymentbusinessactivities',
            'occupation' => 'occupation', 'income' => 'monthlyincome', 'ben_name' => 'beneficiariesnames',
            'ben_dob' => 'beneficiariesdateofbirth', 'ben_rel' => 'relationshiptothemember'
        ];
    }

    // 1. FUZZY MATCHING FUNCTION
    function fuzzyMatch($input, $validArray, $default = 'Others') {
        if (empty(trim((string)$input))) return strtoupper($default);
        
        $cleanInput = preg_replace('/[^a-z0-9]/', '', strtolower($input)); 
        
        foreach ($validArray as $valid) {
            $cleanValid = preg_replace('/[^a-z0-9]/', '', strtolower($valid));
            if ($cleanInput === $cleanValid) return strtoupper($valid);
        }
        foreach ($validArray as $valid) {
            $cleanValid = preg_replace('/[^a-z0-9]/', '', strtolower($valid));
            if (strpos($cleanInput, $cleanValid) !== false) return strtoupper($valid);
        }
        return strtoupper($default);
    }

    // 2. ROBUST DATE PARSER (Accepts "January, 1, 2000", "January 1, 2000", etc.)
    function parseDate($input) {
        $input = trim((string)$input);
        if (empty($input)) return null;
        
        if (is_numeric($input)) {
            return date('Y-m-d', Date::excelToTimestamp($input));
        }

        // Clean out commas that confuse the PHP strtotime parser
        $cleanDate = str_replace(',', ' ', $input);
        // Collapse multiple accidental spaces into a single space
        $cleanDate = preg_replace('/\s+/', ' ', $cleanDate);
        $cleanDate = trim($cleanDate);

        $time = strtotime($cleanDate);
        return ($time !== false && $time > 0) ? date('Y-m-d', $time) : null;
    }

    // 3. INTELLIGENT NAME SPLITTER
    // $isStrictMember flag ensures the absolute last word is ALWAYS the Middle Name for Members
    function splitName($fullName, $isStrictMember = false) {
        $last = ''; $first = ''; $middle = '';
        $cleanName = preg_replace('/\s+/', ' ', trim($fullName));
        
        if (strpos($cleanName, ',') !== false) {
            $parts = explode(',', $cleanName, 2);
            $last = trim($parts[0]);
            
            $fm_parts = explode(' ', trim($parts[1]));
            
            if (count($fm_parts) > 1) {
                if ($isStrictMember) {
                    // STRICT RULE: For Members, the absolute last word is ALWAYS the Middle Name/Initial
                    $middle = array_pop($fm_parts);
                } else {
                    // Original Beneficiary Rule: Only pop if it's 1 letter
                    $potential_mi = end($fm_parts);
                    $clean_mi = preg_replace('/[^a-zA-Z]/', '', $potential_mi);
                    if (strlen($clean_mi) === 1) {
                        $middle = array_pop($fm_parts); 
                    }
                }
                $first = implode(' ', $fm_parts); 
            } else {
                $first = trim($parts[1]);
            }
        } else {
            // Fallback if no comma at all
            $name_parts = explode(' ', $cleanName);
            $last = count($name_parts) > 1 ? array_shift($name_parts) : $cleanName;
            
            if (count($name_parts) > 0) {
                if ($isStrictMember) {
                     // STRICT RULE
                     $middle = array_pop($name_parts);
                } else {
                    // Original Beneficiary Rule
                    $potential_mi = end($name_parts);
                    $clean_mi = preg_replace('/[^a-zA-Z]/', '', $potential_mi);
                    if (strlen($clean_mi) === 1) {
                        $middle = array_pop($name_parts);
                    }
                }
            }
            $first = count($name_parts) > 0 ? implode(' ', $name_parts) : '';
        }
        
        return [strtoupper($last), strtoupper($first), strtoupper($middle)];
    }

    $last_inserted_member_id = null;
    
    // Dynamically find the headers
    $excel_header_index_map = [];
    $start_row = 1;
    $form_id_header = strtolower($db_map['form_id']); 
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        if (!empty($rows[$i][0]) && strtolower(trim((string)$rows[$i][0])) === $form_id_header) {
            $start_row = $i + 1;
            foreach($rows[$i] as $col_index => $col_name) {
                $clean_col = strtolower(trim(preg_replace('/\s+/', ' ', (string)$col_name)));
                if(!empty($clean_col)) {
                    $excel_header_index_map[$clean_col] = $col_index;
                }
            }
            break;
        }
    }

    function getVal($row, $excel_map, $db_map, $system_field) {
        $expected_header = $db_map[$system_field] ?? '';
        if ($expected_header !== '' && isset($excel_map[$expected_header]) && isset($row[$excel_map[$expected_header]])) {
            $val = trim((string)$row[$excel_map[$expected_header]]);
            if ($val !== '') return strtoupper($val); 
        }
        return '';
    }

    // Loop through Excel Rows
    for ($i = $start_row; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!is_array($row)) continue;

        $form_id        = getVal($row, $excel_header_index_map, $db_map, 'form_id');
        $full_name      = getVal($row, $excel_header_index_map, $db_map, 'member_name');
        
        $expected_dob_header = $db_map['dob'] ?? '';
        $dob_val = (isset($excel_header_index_map[$expected_dob_header]) && isset($row[$excel_header_index_map[$expected_dob_header]])) ? $row[$excel_header_index_map[$expected_dob_header]] : '';
        $dob = parseDate($dob_val);
        
        $birth_place    = getVal($row, $excel_header_index_map, $db_map, 'birth_place');
        $civil_status   = getVal($row, $excel_header_index_map, $db_map, 'civil_status');
        $religion       = getVal($row, $excel_header_index_map, $db_map, 'religion');
        $tribe          = getVal($row, $excel_header_index_map, $db_map, 'tribe');
        $sss            = preg_replace('/[^0-9\-]/', '', getVal($row, $excel_header_index_map, $db_map, 'sss_no'));
        $tin            = preg_replace('/[^0-9\-]/', '', getVal($row, $excel_header_index_map, $db_map, 'tin_no'));
        $postal         = preg_replace('/[^0-9]/', '', getVal($row, $excel_header_index_map, $db_map, 'postal_code'));
        $address        = getVal($row, $excel_header_index_map, $db_map, 'address');
        $business_add   = getVal($row, $excel_header_index_map, $db_map, 'business_address');
        $education      = getVal($row, $excel_header_index_map, $db_map, 'education');
        $employment     = getVal($row, $excel_header_index_map, $db_map, 'employment');
        $occupation     = getVal($row, $excel_header_index_map, $db_map, 'occupation');
        $income         = getVal($row, $excel_header_index_map, $db_map, 'income');
        
        $ben_name       = getVal($row, $excel_header_index_map, $db_map, 'ben_name');
        
        $expected_ben_dob = $db_map['ben_dob'] ?? '';
        $ben_dob_val = (isset($excel_header_index_map[$expected_ben_dob]) && isset($row[$excel_header_index_map[$expected_ben_dob]])) ? $row[$excel_header_index_map[$expected_ben_dob]] : '';
        $ben_dob = parseDate($ben_dob_val);
        
        $ben_rel        = getVal($row, $excel_header_index_map, $db_map, 'ben_rel');

        $raw_sex = strtolower(getVal($row, $excel_header_index_map, $db_map, 'sex'));
        $sex = '';
        if (strpos($raw_sex, 'female') !== false || $raw_sex === 'f') $sex = 'FEMALE';
        elseif (strpos($raw_sex, 'male') !== false || $raw_sex === 'm') $sex = 'MALE';

        // -- 1. INSERT NEW MEMBER --
        if (!empty($full_name)) {
            // Passing 'true' enforces the strict rule: last word is ALWAYS middle name.
            list($last_name, $first_name, $middle_name) = splitName($full_name, true);

            $stmt = $conn->prepare("INSERT INTO members (last_name, first_name, middle_name, date_of_birth, birth_place, civil_status, religion, sex, tribe, sss_gsis_no, tin_no, postal_code, address, business_office_address, educational_attainment, present_employment_business, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssssssss", $last_name, $first_name, $middle_name, $dob, $birth_place, $civil_status, $religion, $sex, $tribe, $sss, $tin, $postal, $address, $business_add, $education, $employment, $occupation, $income);
            $stmt->execute();
            
            $last_inserted_member_id = $stmt->insert_id;
            $stmt->close();
        }

        // -- 2. INSERT BENEFICIARY --
        if (!empty($ben_name) && $last_inserted_member_id !== null) {
            // Original logic preserved for beneficiaries
            list($ben_last, $ben_first, $ben_middle) = splitName($ben_name, false);

            $stmt_ben = $conn->prepare("INSERT INTO beneficiaries (member_id, last_name, first_name, middle_name, date_of_birth, relationship) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ben->bind_param("isssss", $last_inserted_member_id, $ben_last, $ben_first, $ben_middle, $ben_dob, $ben_rel);
            $stmt_ben->execute();
            $stmt_ben->close();
        }
    }

    echo "<script>alert('Excel Upload & Scanning Complete!'); window.location.href='index.php';</script>";
}
?>