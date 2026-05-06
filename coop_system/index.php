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
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container">
                <img src="img/logo-removebg.png" alt="Coop Logo">
            </div>
            
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn active">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Membership Management</h1>
                
                <div class="action-buttons">
                    <form action="import_excel.php" method="POST" enctype="multipart/form-data" class="upload-form" style="display: flex; gap: 10px; align-items: center;">
                        <input type="file" name="excel_file" accept=".xls,.xlsx" required>
                        <button type="submit" class="btn btn-primary">UPLOAD EXCEL</button>
                    </form>
                    
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
                                <th>Member Name</th>
                                <th>Sex</th>
                                <th>Occupation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM members ORDER BY member_id DESC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $formatted_id = "#26-" . str_pad($row['member_id'], 3, '0', STR_PAD_LEFT);
                                    
                                    // Construct the full name (Last Name, First Name format)
                                    $full_name = htmlspecialchars($row['last_name'] . ", " . $row['first_name'] . " " . $row['middle_name']);
                                    
                                    // Clean up empty spaces if middle name is missing
                                    $full_name = trim(str_replace('  ', ' ', $full_name));

                                    echo "<tr>
                                            <td><strong>{$formatted_id}</strong></td>
                                            <td style='text-transform: capitalize;'>{$full_name}</td>
                                            <td>" . htmlspecialchars($row['sex'] ?? 'N/A') . "</td>
                                            <td>" . htmlspecialchars($row['occupation'] ?? 'N/A') . "</td>
                                            <td style='display: flex; gap: 8px;'>
                                                <a href='view_member.php?id={$row['member_id']}' class='btn btn-secondary' style='padding: 5px 10px; font-size: 12px; text-decoration: none;'>VIEW</a>
                                                <button class='btn btn-secondary' style='padding: 5px 10px; font-size: 12px;'>EDIT</button>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding: 60px; color:#888;'>No members found. Click '+ Add New Member' or Upload an Excel file to start.</td></tr>";
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