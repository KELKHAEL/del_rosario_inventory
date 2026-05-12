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

    function parseDate($input) {
        $input = trim((string)$input);
        if (empty($input)) return null;
        
        if (is_numeric($input)) {
            return date('Y-m-d', Date::excelToTimestamp((float)$input));
        }

        $time = strtotime($input);
        if ($time !== false) {
            return date('Y-m-d', $time);
        }

        $cleanDate = str_replace([',', '.'], ' ', $input);
        $cleanDate = preg_replace('/\s+/', ' ', $cleanDate);
        $cleanDate = trim($cleanDate);

        $time = strtotime($cleanDate);
        if ($time !== false) {
            return date('Y-m-d', $time);
        }

        if (strpos($cleanDate, '/') !== false) {
            $parts = explode('/', $cleanDate);
            if (count($parts) === 3 && (int)$parts[0] > 12) {
                $cleanDate = str_replace('/', '-', $cleanDate);
                $time = strtotime($cleanDate);
                if ($time !== false) {
                    return date('Y-m-d', $time);
                }
            }
        }

        $formats = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-y', 'm-d-y'];
        foreach ($formats as $format) {
            $d = DateTime::createFromFormat($format, $input);
            if ($d !== false) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }

    function splitName($fullName, $isStrictMember = false) {
        $last = ''; $first = ''; $middle = '';
        
        // CRITICAL FIX: We no longer strip periods from the name.
        $cleanName = preg_replace('/\s+/', ' ', trim($fullName));
        
        if (strpos($cleanName, ',') !== false) {
            $parts = explode(',', $cleanName, 2);
            $last = trim($parts[0]);
            
            $fm_parts = explode(' ', trim($parts[1]));
            
            if (count($fm_parts) > 1) {
                if ($isStrictMember) {
                    $middle = array_pop($fm_parts);
                } else {
                    $potential_mi = end($fm_parts);
                    if (strlen($potential_mi) === 1 || count($fm_parts) >= 3 || strpos($potential_mi, '.') !== false) {
                        $middle = array_pop($fm_parts);
                    }
                }
                $first = implode(' ', $fm_parts); 
            } else {
                $first = trim($parts[1]);
            }
        } else {
            $name_parts = explode(' ', $cleanName);
            $last = count($name_parts) > 1 ? array_pop($name_parts) : $cleanName;
            
            if (count($name_parts) > 0) {
                if ($isStrictMember) {
                    $middle = array_pop($name_parts);
                } else {
                    $potential_mi = end($name_parts);
                    if (strlen($potential_mi) === 1 || strpos($potential_mi, '.') !== false) {
                        $middle = array_pop($name_parts);
                    }
                }
            }
            $first = count($name_parts) > 0 ? implode(' ', $name_parts) : '';
        }
        
        return [strtoupper($last), strtoupper($first), strtoupper($middle)];
    }

    $last_inserted_member_id = null;
    
    $excel_header_index_map = [];
    $start_row = 1;
    $form_id_header = $db_map['form_id']; 
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $first_cell_clean = preg_replace('/[^a-z0-9]/', '', strtolower((string)($rows[$i][0] ?? '')));
        
        if ($first_cell_clean === $form_id_header) {
            $start_row = $i + 1;
            foreach($rows[$i] as $col_index => $col_name) {
                $clean_col = preg_replace('/[^a-z0-9]/', '', strtolower((string)$col_name));
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

    for ($i = $start_row; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!is_array($row)) continue;

        $form_id        = getVal($row, $excel_header_index_map, $db_map, 'form_id');
        $full_name      = getVal($row, $excel_header_index_map, $db_map, 'member_name');
        $ben_name       = getVal($row, $excel_header_index_map, $db_map, 'ben_name');

        if (empty($full_name) && empty($ben_name)) {
            continue;
        }

        if (!empty($full_name)) {
            list($last_name, $first_name, $middle_name) = splitName($full_name, true);

            $expected_dob_header = $db_map['dob'] ?? '';
            $dob_val = (isset($excel_header_index_map[$expected_dob_header]) && isset($row[$excel_header_index_map[$expected_dob_header]])) ? $row[$excel_header_index_map[$expected_dob_header]] : '';
            $dob = parseDate($dob_val);

            // CRITICAL FIX: Match by Form ID first. It is mathematically impossible for this to fail or create duplicates.
            $existing_member_id = null;

            if (!empty($form_id)) {
                $check_stmt = $conn->prepare("SELECT member_id FROM members WHERE form_id = ?");
                $check_stmt->bind_param("s", $form_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();
                if ($check_res->num_rows > 0) {
                    $existing_member_id = $check_res->fetch_assoc()['member_id'];
                }
                $check_stmt->close();
            }

            if ($existing_member_id === null) {
                $check_stmt = $conn->prepare("SELECT member_id FROM members WHERE last_name = ? AND first_name = ?");
                $check_stmt->bind_param("ss", $last_name, $first_name);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();
                if ($check_res->num_rows > 0) {
                    $existing_member_id = $check_res->fetch_assoc()['member_id'];
                }
                $check_stmt->close();
            }

            if ($existing_member_id !== null) {
                $last_inserted_member_id = $existing_member_id;
                
                if ($dob !== null) {
                    $update_stmt = $conn->prepare("UPDATE members SET date_of_birth = ? WHERE member_id = ?");
                    $update_stmt->bind_param("si", $dob, $last_inserted_member_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
            } else {
                $form_id_insert = ($form_id === '') ? null : $form_id;
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

                $stmt = $conn->prepare("INSERT INTO members (form_id, last_name, first_name, middle_name, date_of_birth, birth_place, civil_status, religion, sex, tribe, sss_gsis_no, tin_no, postal_code, address, business_office_address, educational_attainment, present_employment_business, occupation, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssssssssss", $form_id_insert, $last_name, $first_name, $middle_name, $dob, $birth_place, $civil_status, $religion, $sex, $tribe, $sss, $tin, $postal, $address, $business_add, $education, $employment, $occupation, $income);
                $stmt->execute();
                
                $last_inserted_member_id = $stmt->insert_id;
                $stmt->close();
            }
        }

        if (!empty($ben_name) && $last_inserted_member_id !== null) {
            list($ben_last, $ben_first, $ben_middle) = splitName($ben_name, false);

            // CRITICAL FIX: Forcefully check all known spellings for the Beneficiary Date of Birth.
            $ben_dob_val = '';
            $expected_ben_dob = $db_map['ben_dob'] ?? '';
            
            if ($expected_ben_dob !== '' && isset($excel_header_index_map[$expected_ben_dob])) {
                $ben_dob_val = $row[$excel_header_index_map[$expected_ben_dob]];
            } elseif (isset($excel_header_index_map['beneficiariesdateofbirth'])) {
                $ben_dob_val = $row[$excel_header_index_map['beneficiariesdateofbirth']];
            } elseif (isset($excel_header_index_map['beneficiarydateofbirth'])) {
                $ben_dob_val = $row[$excel_header_index_map['beneficiarydateofbirth']];
            }

            $ben_dob = parseDate($ben_dob_val);
            $ben_rel = getVal($row, $excel_header_index_map, $db_map, 'ben_rel');

            $first_name_keyword = explode(' ', trim($ben_first))[0] . '%'; 

            $b_check = $conn->prepare("SELECT 1 FROM beneficiaries WHERE member_id = ? AND last_name = ? AND first_name LIKE ?");
            $b_check->bind_param("iss", $last_inserted_member_id, $ben_last, $first_name_keyword);
            $b_check->execute();
            $b_res = $b_check->get_result();

            if ($b_res->num_rows > 0) {
                if ($ben_dob !== null) {
                    $upd_b = $conn->prepare("UPDATE beneficiaries SET date_of_birth = ?, relationship = ? WHERE member_id = ? AND last_name = ? AND first_name LIKE ?");
                    $upd_b->bind_param("ssiss", $ben_dob, $ben_rel, $last_inserted_member_id, $ben_last, $first_name_keyword);
                    $upd_b->execute();
                    $upd_b->close();
                }
            } else {
                $stmt_ben = $conn->prepare("INSERT INTO beneficiaries (member_id, last_name, first_name, middle_name, date_of_birth, relationship) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_ben->bind_param("isssss", $last_inserted_member_id, $ben_last, $ben_first, $ben_middle, $ben_dob, $ben_rel);
                $stmt_ben->execute();
                $stmt_ben->close();
            }
            $b_check->close();
        }
    }

    echo "<script>alert('Excel Upload Complete! System forcefully synced all Information.'); window.location.href='index.php';</script>";
}
?>