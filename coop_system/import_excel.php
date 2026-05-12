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
            // Aggressively clean the database mapping string
            $db_map[$m['system_field']] = preg_replace('/[^a-z0-9]/', '', strtolower(trim($m['excel_header_name'])));
        }
    } else {
        // Fallback map just in case the database table is empty
        $db_map = [
            'form_id' => 'formid', 'member_name' => 'membername', 'dob' => 'dateofbirth',
            'birth_place' => 'birthplace', 'civil_status' => 'civilstatus', 'religion' => 'religion',
            'sex' => 'sex', 'tribe' => 'tribe', 'sss_no' => 'sssgsisno', 'tin_no' => 'tinno',
            'postal_code' => 'postalcode', 'address' => 'address', 'business_address' => 'businessofficeaddress',
            'education' => 'educationalattainment', 'employment' => 'presentemploymentbusinessactivities',
            'occupation' => 'occupation', 'income' => 'monthlyincome', 'ben_name' => 'beneficiariesname',
            'ben_dob' => 'beneficiariesdateofbirth', 'ben_rel' => 'relationshiptothemember'
        ];
    }

    // 1. ROBUST DATE PARSER
    function parseDate($input) {
        $input = trim((string)$input);
        if (empty($input)) return null;
        
        if (is_numeric($input)) {
            return date('Y-m-d', Date::excelToTimestamp($input));
        }

        // Clean out rogue commas that break the strtotime parser
        $cleanDate = str_replace(',', ' ', $input);
        // Collapse multiple accidental spaces into a single space
        $cleanDate = preg_replace('/\s+/', ' ', $cleanDate);
        $cleanDate = trim($cleanDate);

        $time = strtotime($cleanDate);
        return ($time !== false && $time > 0) ? date('Y-m-d', $time) : null;
    }

    // 2. THE ULTIMATE NAME SPLITTER
    // Added a parameter $isStrictMember to enforce the new rule
    function splitName($fullName, $isStrictMember = false) {
        $last = ''; $first = ''; $middle = '';
        
        // Remove periods (so "M.I." becomes "MI") and collapse spaces
        $cleanName = preg_replace('/\./', '', $fullName);
        $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));
        
        if (strpos($cleanName, ',') !== false) {
            // FORMAT: "Lastname, Firstname Secondname MI" or "Lastname, Firstname Middlename"
            $parts = explode(',', $cleanName, 2);
            $last = trim($parts[0]);
            
            $fm_parts = explode(' ', trim($parts[1]));
            
            if (count($fm_parts) > 1) {
                // If it is a member, the absolute last word is ALWAYS the middle name.
                if ($isStrictMember) {
                    $middle = array_pop($fm_parts);
                } else {
                    // Original Beneficiary Logic
                    $potential_mi = end($fm_parts);
                    
                    // If the last word is 1 letter (M.I.) OR if there are 3+ words after the comma, we assume the last is a middle name/initial
                    if (strlen($potential_mi) === 1 || count($fm_parts) >= 3) {
                        $middle = array_pop($fm_parts);
                    }
                }
                
                $first = implode(' ', $fm_parts); 
            } else {
                $first = trim($parts[1]);
            }
        } else {
            // FORMAT: "Firstname MI Lastname" or "Firstname Secondname Lastname"
            $name_parts = explode(' ', $cleanName);
            
            // Assume the last word is always the Last Name
            $last = count($name_parts) > 1 ? array_pop($name_parts) : $cleanName;
            
            if (count($name_parts) > 0) {
                if ($isStrictMember) {
                    // If it is a member, the word before the last name is the middle name.
                    $middle = array_pop($name_parts);
                } else {
                    // Original Beneficiary Logic
                    // Check if the word right before the last name is an initial
                    $potential_mi = end($name_parts);
                    if (strlen($potential_mi) === 1) {
                        $middle = array_pop($name_parts);
                    }
                }
            }
            $first = count($name_parts) > 0 ? implode(' ', $name_parts) : '';
        }
        
        return [strtoupper($last), strtoupper($first), strtoupper($middle)];
    }

    $last_inserted_member_id = null;
    // Keep track of the form ID so we can insert it.
    $last_inserted_form_id = null;
    
    // --- DYNAMIC HEADER DETECTION ---
    $excel_header_index_map = [];
    $start_row = 1;
    $form_id_header = $db_map['form_id']; 
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $first_cell_clean = preg_replace('/[^a-z0-9]/', '', strtolower((string)($rows[$i][0] ?? '')));
        
        if ($first_cell_clean === $form_id_header) {
            $start_row = $i + 1;
            foreach($rows[$i] as $col_index => $col_name) {
                // Aggressively clean the excel header to match the db mapping
                $clean_col = preg_replace('/[^a-z0-9]/', '', strtolower((string)$col_name));
                if(!empty($clean_col)) {
                    $excel_header_index_map[$clean_col] = $col_index;
                }
            }
            break;
        }
    }

    // Helper to safely pull mapped columns
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
        $ben_name       = getVal($row, $excel_header_index_map, $db_map, 'ben_name');

        // If BOTH member name and beneficiary name are totally empty, skip this row completely to avoid blank garbage data.
        if (empty($full_name) && empty($ben_name)) {
            continue;
        }

        // -- 1. PROCESS MEMBER (Only if the Member Name column is not empty) --
        if (!empty($full_name)) {
            // Use strict member splitting rule
            list($last_name, $first_name, $middle_name) = splitName($full_name, true);

            // AUTOMATED DUPLICATE CHECKER
            $check_stmt = $conn->prepare("SELECT member_id FROM members WHERE last_name = ? AND first_name = ? AND middle_name = ?");
            $check_stmt->bind_param("sss", $last_name, $first_name, $middle_name);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            if ($check_res->num_rows > 0) {
                // Duplicate found! Skip inserting the member, but save their ID so we can attach their beneficiaries below.
                $last_inserted_member_id = $check_res->fetch_assoc()['member_id'];
                $check_stmt->close();
            } else {
                $check_stmt->close();

                // Not a duplicate, insert new member.
                
                // If form_id is blank, set it to NULL
                $form_id_insert = ($form_id === '') ? null : $form_id;
                
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
                
                $raw_sex = strtolower(getVal($row, $excel_header_index_map, $db_map, 'sex'));
                $sex = '';
                if (strpos($raw_sex, 'female') !== false || $raw_sex === 'f') $sex = 'FEMALE';
                elseif (strpos($raw_sex, 'male') !== false || $raw_sex === 'm') $sex = 'MALE';

                // NOTE: Make sure your `members` table has the `form_id` column as discussed in the previous step.
                $stmt = $conn->prepare("INSERT INTO members (form_id, last_name, first_name, middle_name, date_of_birth, birth_place, civil_status, religion, sex, tribe, sss_gsis_no, tin_no, postal_code, address, business_office_address, educational_attainment, present_employment_business, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssssssssss", $form_id_insert, $last_name, $first_name, $middle_name, $dob, $birth_place, $civil_status, $religion, $sex, $tribe, $sss, $tin, $postal, $address, $business_add, $education, $employment, $occupation, $income);
                $stmt->execute();
                
                $last_inserted_member_id = $stmt->insert_id;
                $stmt->close();
            }
        }

        // -- 2. PROCESS BENEFICIARY (Only if there is a beneficiary name AND we successfully tracked a member ID) --
        if (!empty($ben_name) && $last_inserted_member_id !== null) {
            // Use normal beneficiary splitting rule
            list($ben_last, $ben_first, $ben_middle) = splitName($ben_name, false);

            $expected_ben_dob = $db_map['ben_dob'] ?? '';
            $ben_dob_val = (isset($excel_header_index_map[$expected_ben_dob]) && isset($row[$excel_header_index_map[$expected_ben_dob]])) ? $row[$excel_header_index_map[$expected_ben_dob]] : '';
            $ben_dob = parseDate($ben_dob_val);
            
            $ben_rel = getVal($row, $excel_header_index_map, $db_map, 'ben_rel');

            $stmt_ben = $conn->prepare("INSERT INTO beneficiaries (member_id, last_name, first_name, middle_name, date_of_birth, relationship) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ben->bind_param("isssss", $last_inserted_member_id, $ben_last, $ben_first, $ben_middle, $ben_dob, $ben_rel);
            $stmt_ben->execute();
            $stmt_ben->close();
        }
    }

    echo "<script>alert('Excel Upload Complete! Duplicates were merged.'); window.location.href='index.php';</script>";
}
?>