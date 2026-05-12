<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Directory - Coop DBMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modern Search Bar Styles */
        .search-container {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 4px;
            width: 300px;
        }
        .search-container input {
            border: none;
            outline: none;
            padding: 8px 10px;
            width: 100%;
            font-size: 13px;
        }
        .search-container span {
            background: #6a1b9a;
            color: white;
            border-radius: 4px;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo">
            </div>
            
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn active">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="database_management.php" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Membership Management</h1>
                
                <div class="action-buttons">
                    
                    <div class="search-container">
                        <input type="text" id="liveSearch" placeholder="Search Name, ID, Occupation...">
                        <span>SEARCH</span>
                    </div>

                    <form action="import_excel.php" method="POST" enctype="multipart/form-data" class="upload-form" style="display: flex; gap: 10px; align-items: center; margin-left: 15px;">
                        <input type="file" name="excel_file" accept=".xls,.xlsx" required>
                        <button type="submit" class="btn btn-primary" style="white-space: nowrap;">UPLOAD EXCEL</button>
                    </form>
                    
                    <a href="export_excel.php" class="btn btn-primary" style="text-decoration: none; background-color: #107c41; border: none; margin-left: 10px; white-space: nowrap;">EXPORT EXCEL</a>
                    
                    <a href="membership.php" class="btn btn-primary" style="text-decoration: none; margin-left: 10px; white-space: nowrap;">+ ADD NEW MEMBER</a>
                </div>
            </div>

            <div class="content-display">
                <div class="data-table-container">
                    <table class="data-table" id="membersTable">
                        <thead>
                            <tr>
                                <th>Form ID</th>
                                <th>Member Name</th>
                                <th>Sex</th>
                                <th>Occupation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch ALL members (Filtering is now handled by JavaScript instantly)
                            $sql = "SELECT * FROM members ORDER BY member_id DESC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    
                                    // Display the actual form_id from the database. 
                                    // If it's NULL or empty, it stays blank.
                                    $display_id = !empty($row['form_id']) ? htmlspecialchars($row['form_id']) : '';
                                    
                                    // Construct the full name (Last Name, First Name format)
                                    $full_name = htmlspecialchars($row['last_name'] . ", " . $row['first_name'] . " " . $row['middle_name']);
                                    $full_name = trim(str_replace('  ', ' ', $full_name));

                                    echo "<tr class='member-row'>
                                            <td><strong>{$display_id}</strong></td>
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
                                echo "<tr id='noDataRow'><td colspan='5' style='text-align:center; padding: 60px; color:#888;'>No members found. Click '+ Add New Member' or Upload an Excel file to start.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('liveSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.member-row');

            rows.forEach(row => {
                // Grab all the text inside the row (ID, Name, Sex, Occupation)
                let rowText = row.textContent.toLowerCase();
                
                // If the text contains what the user typed, show it. Otherwise, hide it.
                if (rowText.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

</body>
</html>