<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Coop DBMS</title>
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
                <a href="index.php" class="menu-btn">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn active">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            
            <div class="top-action-bar">
                <h1 class="page-title">Transaction Records</h1>
                <div class="action-buttons">
                    <!-- Excel Upload Simulator -->
                    <form action="#" method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="excel_file" accept=".xls,.xlsx" required>
                        <button type="submit" class="btn btn-primary" onclick="alert('Excel Parsing script will be connected here later!')">UPLOAD EXCEL</button>
                    </form>
                    <button class="btn btn-secondary" onclick="alert('Add Manual Transaction Modal coming soon.')">+ ADD MANUAL</button>
                    <button class="btn btn-secondary">PRINT REPORT</button>
                </div>
            </div>

            <div class="content-display">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Member Name</th>
                                <th>Transaction Type</th>
                                <th>Amount (PHP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all transactions from the database, ordered by newest first
                            $sql = "SELECT * FROM transactions ORDER BY transaction_date DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    
                                    // Make the badges look nice based on transaction type
                                    $badgeClass = ($row['transaction_type'] == 'SHARE') ? 'badge-share' : 'badge-fee';
                                    
                                    echo "<tr>
                                            <td>#" . str_pad($row['transaction_id'], 5, '0', STR_PAD_LEFT) . "</td>
                                            <td>" . date('M d, Y', strtotime($row['transaction_date'])) . "</td>
                                            <td>" . htmlspecialchars($row['member_name']) . "</td>
                                            <td><span class='" . $badgeClass . "'>" . htmlspecialchars($row['transaction_type']) . "</span></td>
                                            <td>₱" . number_format($row['amount'], 2) . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color:#888;'>No transactions found. Upload an Excel file to begin.</td></tr>";
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