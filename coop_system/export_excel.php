<?php
include 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. Set the Column Headers
$headers = [
    'Form ID', 'Last Name', 'First Name', 'Middle Name', 'Date of Birth', 
    'Birth Place', 'Civil Status', 'Religion', 'Sex', 'Tribe', 
    'SSS / GSIS No', 'TIN No', 'Postal Code', 'Address', 'Business / Office Address', 
    'Educational Attainment', 'Present Employment / Business', 'Occupation', 'Monthly Income',
    'Beneficiaries (Name - Relationship)' // <-- NEW HEADER ADDED
];

$columnLetter = 'A';
foreach ($headers as $header) {
    // Set header text
    $sheet->setCellValue($columnLetter . '1', $header);
    
    // Make header bold
    $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
    
    // Auto-size the columns to make it readable
    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
    
    $columnLetter++;
}

// 2. Fetch Members Data
$sql = "SELECT * FROM members ORDER BY member_id DESC";
$result = $conn->query($sql);

$rowNumber = 2; // Start adding data from the second row

// Prepare a statement outside the loop to fetch beneficiaries efficiently
$stmt_ben = $conn->prepare("SELECT last_name, first_name, middle_name, relationship FROM beneficiaries WHERE member_id = ?");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        // Populate the Excel file. 
        $sheet->setCellValue('A' . $rowNumber, $row['form_id']);
        $sheet->setCellValue('B' . $rowNumber, $row['last_name']);
        $sheet->setCellValue('C' . $rowNumber, $row['first_name']);
        $sheet->setCellValue('D' . $rowNumber, $row['middle_name']);
        $sheet->setCellValue('E' . $rowNumber, $row['date_of_birth']);
        $sheet->setCellValue('F' . $rowNumber, $row['birth_place']);
        $sheet->setCellValue('G' . $rowNumber, $row['civil_status']);
        $sheet->setCellValue('H' . $rowNumber, $row['religion']);
        $sheet->setCellValue('I' . $rowNumber, $row['sex']);
        $sheet->setCellValue('J' . $rowNumber, $row['tribe']);
        $sheet->setCellValue('K' . $rowNumber, $row['sss_gsis_no']);
        $sheet->setCellValue('L' . $rowNumber, $row['tin_no']);
        $sheet->setCellValue('M' . $rowNumber, $row['postal_code']);
        $sheet->setCellValue('N' . $rowNumber, $row['address']);
        $sheet->setCellValue('O' . $rowNumber, $row['business_office_address']);
        $sheet->setCellValue('P' . $rowNumber, $row['educational_attainment']);
        $sheet->setCellValue('Q' . $rowNumber, $row['present_employment_business']);
        $sheet->setCellValue('R' . $rowNumber, $row['occupation']);
        $sheet->setCellValue('S' . $rowNumber, $row['monthly_income']);
        
        // --- FETCH AND FORMAT BENEFICIARIES ---
        $ben_list = [];
        $stmt_ben->bind_param("i", $row['member_id']);
        $stmt_ben->execute();
        $ben_result = $stmt_ben->get_result();
        
        while($ben = $ben_result->fetch_assoc()) {
            // Format: "LASTNAME, FIRSTNAME MI (Relationship)"
            $b_name = trim($ben['last_name'] . ', ' . $ben['first_name'] . ' ' . $ben['middle_name']);
            $b_rel = !empty($ben['relationship']) ? $ben['relationship'] : 'N/A';
            $ben_list[] = $b_name . ' (' . $b_rel . ')';
        }
        
        // Join all beneficiaries with a line break (\n)
        $ben_string = implode("\n", $ben_list);
        
        // Set the value in column T
        $sheet->setCellValue('T' . $rowNumber, $ben_string);
        // Turn on "Wrap Text" for this cell so the newlines format correctly in Excel
        $sheet->getStyle('T' . $rowNumber)->getAlignment()->setWrapText(true);
        
        $rowNumber++;
    }
}

if (isset($stmt_ben)) {
    $stmt_ben->close();
}

// 3. Set the HTTP headers to trigger an Excel file download
$date_today = date('Y-m-d');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Member_Directory_Export_' . $date_today . '.xlsx"');
header('Cache-Control: max-age=0');

// 4. Save and export
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;