<?php
// Connect to the database
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capture Member Data (Using real_escape_string to prevent basic SQL injection)
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $dob = $conn->real_escape_string($_POST['date_of_birth']);
    $birth_place = $conn->real_escape_string($_POST['birth_place']);
    $civil_status = $conn->real_escape_string($_POST['civil_status']);
    $sss = $conn->real_escape_string($_POST['sss_gsis_no']);
    $tin = $conn->real_escape_string($_POST['tin_no']);
    $postal = $conn->real_escape_string($_POST['postal_code']);
    $address = $conn->real_escape_string($_POST['address']);

    // 2. Insert the main member
    $sql_member = "INSERT INTO members (last_name, first_name, middle_name, date_of_birth, birth_place, civil_status, sss_gsis_no, tin_no, postal_code, address) 
                   VALUES ('$last_name', '$first_name', '$middle_name', '$dob', '$birth_place', '$civil_status', '$sss', '$tin', '$postal', '$address')";

    if ($conn->query($sql_member) === TRUE) {
        // Get the Auto-Increment ID of the member we just saved
        $new_member_id = $conn->insert_id;

        // 3. Process Beneficiaries (if any exist)
        if (isset($_POST['ben_last_name']) && !empty($_POST['ben_last_name'][0])) {
            $ben_count = count($_POST['ben_last_name']);
            
            for ($i = 0; $i < $ben_count; $i++) {
                $b_last = $conn->real_escape_string($_POST['ben_last_name'][$i]);
                $b_first = $conn->real_escape_string($_POST['ben_first_name'][$i]);
                $b_mid = $conn->real_escape_string($_POST['ben_middle_name'][$i]);
                $b_dob = $conn->real_escape_string($_POST['ben_dob'][$i]);
                $b_rel = $conn->real_escape_string($_POST['ben_rel'][$i]);

                // Only insert if the row isn't blank
                if($b_last != "" && $b_first != "") {
                    $sql_ben = "INSERT INTO beneficiaries (member_id, last_name, first_name, middle_name, date_of_birth, relationship) 
                                VALUES ('$new_member_id', '$b_last', '$b_first', '$b_mid', '$b_dob', '$b_rel')";
                    $conn->query($sql_ben);
                }
            }
        }
        
        // Success! Redirect back to the form with a success message
        echo "<script>alert('Membership successfully saved!'); window.location.href='membership.php';</script>";
    } else {
        echo "Error: " . $sql_member . "<br>" . $conn->error;
    }
    
    $conn->close();
}
?>