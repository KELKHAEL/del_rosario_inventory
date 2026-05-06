<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Directory - Coop DBMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">
        <!-- LEFT SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container"><h2>LOGO</h2></div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn active">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="#" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT SYSTEM</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Membership Management</h1>
                
                <div class="action-buttons">
                    <!-- Excel Upload Form -->
                    <form action="#" method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="excel_file" accept=".xls,.xlsx" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="submit" class="btn btn-primary" onclick="alert('Excel Parsing script will be attached here.')">UPLOAD EXCEL</button>
                    </form>
                    
                    <!-- Add New Member Button routes to your form -->
                    <a href="membership.php" class="btn btn-primary" style="text-decoration: none;">+ ADD NEW MEMBER</a>
                </div>
            </div>

            <!-- THE DATA TABLE -->
            <div class="content-display">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name (Last, First, Middle)</th>
                                <th>Date of Birth</th>
                                <th>Civil Status</th>
                                <th>Occupation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all members from the database
                            $sql = "SELECT * FROM members ORDER BY member_id DESC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // Format the ID to look like the paper form (e.g., #26-001)
                                    $formatted_id = "#26-" . str_pad($row['member_id'], 3, '0', STR_PAD_LEFT);
                                    
                                    // Construct the full name
                                    $full_name = htmlspecialchars($row['last_name'] . ", " . $row['first_name'] . " " . $row['middle_name']);
                                    
                                    // Format the date
                                    $dob = date('M d, Y', strtotime($row['date_of_birth']));

                                    echo "<tr>
                                            <td><strong>{$formatted_id}</strong></td>
                                            <td>{$full_name}</td>
                                            <td>{$dob}</td>
                                            <td>" . htmlspecialchars($row['civil_status']) . "</td>
                                            <td>" . htmlspecialchars($row['occupation']) . "</td>
                                            <td>
                                                <button class='btn btn-secondary' style='padding: 5px 10px; font-size: 12px;'>EDIT</button>
                                                <button class='btn btn-secondary' style='padding: 5px 10px; font-size: 12px;'>PRINT</button>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; padding: 40px; color:#888;'>No members found. Click '+ Add New Member' or Upload an Excel file to start.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>