<?php
include 'db.php';
// Require PhpSpreadsheet (Ensure you have installed it via Composer)
require 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['excel_file'])) {
    
    $fileTmpPath = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($fileTmpPath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // 1. FUZZY MATCHING FUNCTION (Handles typos, double spaces, commas, wrong caps)
    function fuzzyMatch($input, $validArray, $default = 'Others') {
        if (empty($input)) return $default;
        // Strip everything except letters and numbers, make lowercase
        $cleanInput = preg_replace('/[^a-z0-9]/', '', strtolower($input)); 
        
        foreach ($validArray as $valid) {
            $cleanValid = preg_replace('/[^a-z0-9]/', '', strtolower($valid));
            if ($cleanInput === $cleanValid || strpos($cleanInput, $cleanValid) !== false) {
                return $valid;
            }
        }
        return $default;
    }

    // 2. ROBUST DATE PARSER (Turns "June, 9, 2004" into standard DB "2004-06-09")
    function parseDate($input) {
        if (empty($input)) return null;
        // Remove weird characters but keep letters, numbers, spaces, slashes, dashes
        $cleanDate = preg_replace('/[^a-zA-Z0-9\s,\/\-]/', '', $input);
        $time = strtotime($cleanDate);
        return ($time !== false) ? date('Y-m-d', $time) : null;
    }

    // Predefined Valid Options based on your HTML Form
    $valid_occupations = ['Private Employee', "Gov't Employee", 'Self-Employed', 'Farmer', 'Pensioner', 'Student', 'House Keeper', 'Fisher folk', 'Entrepreneur/Vendor', 'Others'];
    $valid_civil_status = ['Single', 'Married', 'Widowed', 'Separated'];
    $valid_sex = ['MALE', 'FEMALE'];

    // State Machine Tracking Variable
    $last_inserted_member_id = null;

    // Loop through Excel Rows (Assuming Row 0 is Headers, start at Row 1)
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        // Ensure row has data to prevent breaking
        if (!isset($row[1])) continue; 

        // Extracting Data (Assuming strict column indexes based on your screenshot)
        // Adjust these numbers [0, 1, 2...] if your Excel columns are in a different order
        $form_id        = trim($row[0]); 
        $full_name      = trim($row[1]); 
        $dob            = parseDate($row[2]); 
        $birth_place    = trim($row[3]); 
        $civil_status   = fuzzyMatch($row[4], $valid_civil_status, ''); 
        $religion       = trim($row[5]); 
        $sex            = fuzzyMatch($row[6], $valid_sex, ''); 
        $tribe          = trim($row[7]); 
        $sss            = preg_replace('/[^0-9\-]/', '', trim($row[8])); // Only numbers/dashes
        $tin            = preg_replace('/[^0-9\-]/', '', trim($row[9])); 
        $postal         = preg_replace('/[^0-9]/', '', trim($row[10])); 
        $address        = trim($row[11]); 
        $business_add   = trim($row[12]); 
        $education      = trim($row[13]); 
        $employment     = trim($row[14]); 
        
        $ben_name       = trim($row[15]); 
        $ben_dob        = parseDate($row[16]); 
        $ben_rel        = trim($row[17]); 
        $occupation     = fuzzyMatch($row[18], $valid_occupations, 'Others'); 
        $income         = trim($row[19]); 

        // -- THE FILL-DOWN ALGORITHM --
        // If Name is NOT empty, this is a brand new member!
        if (!empty($full_name)) {
            
            // Name splitting logic (Basic approximation)
            $name_parts = explode(' ', $full_name);
            $last_name = array_pop($name_parts);
            $first_name = implode(' ', $name_parts);

            // Insert into Database
            $stmt = $conn->prepare("INSERT INTO members (last_name, first_name, date_of_birth, birth_place, civil_status, religion, sex, tribe, sss_gsis_no, tin_no, postal_code, address, business_office_address, educational_attainment, present_employment_business, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssssssssss", $last_name, $first_name, $dob, $birth_place, $civil_status, $religion, $sex, $tribe, $sss, $tin, $postal, $address, $business_add, $education, $employment, $occupation, $income);
            $stmt->execute();
            
            // Store the ID so subsequent rows (beneficiaries) can attach to it
            $last_inserted_member_id = $stmt->insert_id;
            $stmt->close();
        }

        // If Beneficiary Name is NOT empty, attach it to the LAST known Member
        // This solves the merged/blank row problem flawlessly!
        if (!empty($ben_name) && $last_inserted_member_id !== null) {
            
            $ben_parts = explode(' ', $ben_name);
            $ben_last = array_pop($ben_parts);
            $ben_first = implode(' ', $ben_parts);

            $stmt_ben = $conn->prepare("INSERT INTO beneficiaries (member_id, last_name, first_name, date_of_birth, relationship) VALUES (?, ?, ?, ?, ?)");
            $stmt_ben->bind_param("issss", $last_inserted_member_id, $ben_last, $ben_first, $ben_dob, $ben_rel);
            $stmt_ben->execute();
            $stmt_ben->close();
        }
    }

    echo "<script>alert('Excel Upload & Scanning Complete!'); window.location.href='index.php';</script>";
}
?>