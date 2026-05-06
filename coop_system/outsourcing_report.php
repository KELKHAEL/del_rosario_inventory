<?php 
include 'db.php'; 

// 1. FILTER LOGIC (CALENDAR)
$filter_date = $_GET['filter_date'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$sort_option = $_GET['sort'] ?? 'name_asc';

// Display variable for the UI
$time_display = "All Time";

// Base SQL query joining tables, using SUM() to merge identical items
$sql = "SELECT i.product_id, i.product_name, i.product_type, i.price, i.quantity_type, 
               SUM(o.quantity_out) as total_qty_out, 
               MAX(o.record_date) as latest_date 
        FROM inventory_outsourcing o
        JOIN inventory i ON o.product_id = i.product_id 
        WHERE 1=1";

// Apply Calendar Filters
if (!empty($filter_date)) {
    $f_date = $conn->real_escape_string($filter_date);
    $sql .= " AND DATE(o.record_date) = '$f_date'";
    $time_display = date('F d, Y', strtotime($f_date));
} elseif (!empty($filter_month)) {
    $f_month = $conn->real_escape_string($filter_month);
    $year = date('Y', strtotime($f_month));
    $month = date('m', strtotime($f_month));
    $sql .= " AND YEAR(o.record_date) = '$year' AND MONTH(o.record_date) = '$month'";
    $time_display = date('F Y', strtotime($f_month));
}

// MERGE IDENTICAL ITEMS (Group By)
$sql .= " GROUP BY i.product_id, i.product_name, i.product_type, i.price, i.quantity_type";

// Apply Sorting (Always sort by category first to keep the layout clean)
$order_by = "i.product_type ASC, i.product_name ASC"; // Default
if ($sort_option === 'qty_desc') $order_by = "i.product_type ASC, total_qty_out DESC";
if ($sort_option === 'date_desc') $order_by = "i.product_type ASC, latest_date DESC";

$sql .= " ORDER BY $order_by";
$result = $conn->query($sql);

// Calculate Dashboard Totals
$total_items_out = 0;
$total_value_out = 0;
$report_data = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $report_data[] = $row;
        $total_items_out += $row['total_qty_out'];
        $total_value_out += ($row['total_qty_out'] * $row['price']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outsourcing Reports - Coop DBMS</title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">
        <!-- LEFT SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container"><h2>LOGO</h2></div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="outsourcing_report.php" class="menu-btn active" style="background-color: #f57c00; border-color: #f57c00; color: white;">OUTSOURCING LOGS</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT SYSTEM</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Outsourcing & Sales Records</h1>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="window.print()">PRINT REPORT</button>
                </div>
            </div>

            <!-- REPORT DASHBOARD CARDS -->
            <div class="stat-cards">
                <div class="stat-card" style="border-left-color: #f57c00;">
                    <div class="stat-title">Total Units Outsourced</div>
                    <div class="stat-value">
                        <?php echo number_format($total_items_out); ?>
                    </div>
                    <div style="font-size: 12px; color: #888; margin-top: 5px;">Timeframe: <?php echo $time_display; ?></div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-title">Total Outsourced Value (PHP)</div>
                    <div class="stat-value">
                        ₱<?php echo number_format($total_value_out, 2); ?>
                    </div>
                    <div style="font-size: 12px; color: #888; margin-top: 5px;">Timeframe: <?php echo $time_display; ?></div>
                </div>
            </div>

            <!-- DATA TABLE WITH CALENDAR FILTERS -->
            <div class="content-display" style="padding: 0; overflow: hidden;">
                
                <div style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; flex-direction: column; gap: 15px;">
                    <h4 style="margin: 0; color: #444; font-size: 16px;">Merged Outsourcing Directory</h4>
                    
                    <!-- CALENDAR FILTER FORM -->
                    <form action="outsourcing_report.php" method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        
                        <!-- Single Day Picker -->
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 12px; font-weight: 600; color: #555;">Filter by Exact Day:</label>
                            <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>" 
                                   onchange="document.getElementById('month_picker').value=''; this.form.submit()" 
                                   style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit;">
                        </div>

                        <!-- Month/Year Picker -->
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 12px; font-weight: 600; color: #555;">Or Filter by Month & Year:</label>
                            <input type="month" id="month_picker" name="filter_month" value="<?= htmlspecialchars($filter_month) ?>" 
                                   onchange="document.querySelector('input[name=\'filter_date\']').value=''; this.form.submit()" 
                                   style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit;">
                        </div>

                        <!-- Sorting -->
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 12px; font-weight: 600; color: #555;">Sort Results:</label>
                            <select name="sort" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit;">
                                <option value="name_asc" <?= $sort_option == 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                                <option value="qty_desc" <?= $sort_option == 'qty_desc' ? 'selected' : '' ?>>Highest Quantity First</option>
                                <option value="date_desc" <?= $sort_option == 'date_desc' ? 'selected' : '' ?>>Most Recently Outsourced</option>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div style="display: flex; align-items: flex-end; padding-bottom: 2px;">
                            <a href="outsourcing_report.php" class="btn btn-secondary" style="text-decoration: none; font-size: 12px; padding: 9px 15px; border-radius: 4px;">CLEAR FILTERS</a>
                        </div>
                    </form>
                </div>

                <div class="data-table-container" style="margin-top: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Latest Record</th>
                                <th>Product Name</th>
                                <th>Unit Price</th>
                                <th>Total Qty OUT</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($report_data) > 0) {
                                $current_category = "";

                                foreach($report_data as $row) {
                                    
                                    // Grouping Logic by Category
                                    if ($current_category !== $row['product_type']) {
                                        $current_category = $row['product_type'];
                                        echo "<tr class='category-header' style='background-color: #fff3e0; border-top: 2px solid #f57c00;'>
                                                <td colspan='5' style='color: #e65100;'>" . strtoupper(htmlspecialchars($current_category)) . "</td>
                                              </tr>";
                                    }

                                    // Display logic
                                    $date = date('M d, Y', strtotime($row['latest_date']));
                                    $total_row_value = $row['total_qty_out'] * $row['price'];

                                    echo "<tr>
                                            <td style='padding-left: 30px; color: #666;'>{$date}</td>
                                            <td><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>
                                            <td>₱" . number_format($row['price'], 2) . "</td>
                                            <td><strong style='font-size: 16px; color: #d32f2f;'>-" . $row['total_qty_out'] . "</strong> <span style='color: #888;'>" . $row['quantity_type'] . "s</span></td>
                                            <td><strong>₱" . number_format($total_row_value, 2) . "</strong></td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding: 60px; color:#888;'>No outsourcing records found for this calendar timeframe.</td></tr>";
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