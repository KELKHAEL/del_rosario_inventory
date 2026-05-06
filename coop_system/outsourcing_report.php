<?php 
include 'db.php'; 

// --- NEW: UNDO & DELETE LOG FUNCTION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_log'])) {
    $del_id = (int)$_POST['delete_product_id'];
    $del_payment = $conn->real_escape_string($_POST['delete_payment']);
    
    // 1. Find exactly how much stock was outsourced for this specific product + payment combo
    $sum_res = $conn->query("SELECT SUM(quantity_out) as tot FROM inventory_outsourcing WHERE product_id=$del_id AND payment_method='$del_payment'");
    
    if ($sum_res && $sum_res->num_rows > 0) {
        $tot_to_restore = (int)$sum_res->fetch_assoc()['tot'];
        
        // 2. Return the stock to the master inventory (Undo the sale)
        if ($tot_to_restore > 0) {
            $conn->query("UPDATE inventory SET current_quantity = current_quantity + $tot_to_restore WHERE product_id=$del_id");
        }
    }
    
    // 3. Delete the records from the log to wipe the slate clean
    $conn->query("DELETE FROM inventory_outsourcing WHERE product_id=$del_id AND payment_method='$del_payment'");
    
    header("Location: outsourcing_report.php");
    exit();
}

// --- FILTER LOGIC (CALENDAR) ---
$filter_date = $_GET['filter_date'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$sort_option = $_GET['sort'] ?? 'name_asc';
$time_display = "All Time";

// Base SQL query
$sql = "SELECT i.product_id, i.product_name, i.product_type, i.price, i.quantity_type, o.payment_method,
               SUM(o.quantity_out) as total_qty_out, MAX(o.record_date) as latest_date 
        FROM inventory_outsourcing o
        JOIN inventory i ON o.product_id = i.product_id 
        WHERE 1=1";

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

// Group by both product and payment method
$sql .= " GROUP BY i.product_id, i.product_name, i.product_type, i.price, i.quantity_type, o.payment_method";

// Sort by category, then payment method
$order_by = "i.product_type ASC, o.payment_method ASC, i.product_name ASC"; 
if ($sort_option === 'qty_desc') $order_by = "i.product_type ASC, o.payment_method ASC, total_qty_out DESC";
if ($sort_option === 'date_desc') $order_by = "i.product_type ASC, o.payment_method ASC, latest_date DESC";
$sql .= " ORDER BY $order_by";

$result = $conn->query($sql);

$total_items_out = 0; $total_value_out = 0; $report_data = [];
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
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container"><h2>LOGO</h2></div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn" style="background-color: #2e7d32; border-color: #2e7d32; color: white;">SELL / OUTSOURCE</a>
                <a href="outsourcing_report.php" class="menu-btn active" style="background-color: #f57c00; border-color: #f57c00; color: white;">OUTSOURCING LOGS</a>
                <a href="inventory.php" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Outsourcing & Sales Records</h1>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="window.print()">PRINT REPORT</button>
                </div>
            </div>

            <!-- DASHBOARD CARDS -->
            <div class="stat-cards">
                <div class="stat-card" style="border-left-color: #f57c00;">
                    <div class="stat-title">Units Outsourced</div>
                    <div class="stat-value"><?= number_format($total_items_out) ?></div>
                    <div style="font-size: 12px; color: #888; margin-top: 5px;">Timeframe: <?= $time_display ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-title">Total Value (PHP)</div>
                    <div class="stat-value">₱<?= number_format($total_value_out, 2) ?></div>
                    <div style="font-size: 12px; color: #888; margin-top: 5px;">Timeframe: <?= $time_display ?></div>
                </div>
            </div>

            <!-- FILTERS AND TABLE -->
            <div class="content-display" style="padding: 0; overflow: hidden;">
                
                <div style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee;">
                    <form action="outsourcing_report.php" method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                        <div class="input-group">
                            <label style="font-size: 12px; font-weight: 600; color: #555;">Filter by Exact Day:</label>
                            <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>" onchange="document.getElementById('m_pick').value=''; this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        </div>
                        <div class="input-group">
                            <label style="font-size: 12px; font-weight: 600; color: #555;">Or Filter by Month/Year:</label>
                            <input type="month" id="m_pick" name="filter_month" value="<?= htmlspecialchars($filter_month) ?>" onchange="document.querySelector('input[name=\'filter_date\']').value=''; this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        </div>
                        <div class="input-group">
                            <label style="font-size: 12px; font-weight: 600; color: #555;">Sort By:</label>
                            <select name="sort" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                                <option value="name_asc" <?= $sort_option == 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                                <option value="qty_desc" <?= $sort_option == 'qty_desc' ? 'selected' : '' ?>>Highest Quantity</option>
                                <option value="date_desc" <?= $sort_option == 'date_desc' ? 'selected' : '' ?>>Most Recently Outsourced</option>
                            </select>
                        </div>
                        <a href="outsourcing_report.php" class="btn btn-secondary" style="padding: 9px 15px; font-size: 12px; text-decoration: none;">CLEAR FILTERS</a>
                    </form>
                </div>

                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Latest Record</th>
                                <th>Product Name</th>
                                <th>Unit Price</th>
                                <th>Payment</th>
                                <th>Total OUT</th>
                                <th>Total Value</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($report_data) > 0) {
                                $current_group = "";
                                foreach($report_data as $row) {
                                    
                                    // Colored Grouping Header based on Payment Method
                                    $group_title = strtoupper($row['product_type']) . " - " . strtoupper($row['payment_method']);
                                    if ($current_group !== $group_title) {
                                        $current_group = $group_title;
                                        $bg_color = ($row['payment_method'] == 'GCash') ? '#e3f2fd' : '#fff3e0';
                                        $text_color = ($row['payment_method'] == 'GCash') ? '#1565c0' : '#e65100';
                                        echo "<tr class='category-header' style='background-color: {$bg_color}; border-top: 2px solid {$text_color};'>
                                                <td colspan='7' style='color: {$text_color}; font-weight: 800; padding: 12px 20px;'>{$current_group}</td>
                                              </tr>";
                                    }
                                    
                                    $date = date('M d, Y', strtotime($row['latest_date']));
                                    $val = $row['total_qty_out'] * $row['price'];
                                    
                                    echo "<tr>
                                            <td style='padding-left: 30px; color: #666;'>{$date}</td>
                                            <td><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>
                                            <td>₱" . number_format($row['price'], 2) . "</td>
                                            <td><strong>" . htmlspecialchars($row['payment_method']) . "</strong></td>
                                            <td><strong style='color: #d32f2f; font-size: 16px;'>-{$row['total_qty_out']}</strong> <span style='color: #888;'>{$row['quantity_type']}s</span></td>
                                            <td><strong>₱" . number_format($val, 2) . "</strong></td>
                                            <td style='text-align: right;'>
                                                
                                                <!-- NEW: UNDO & DELETE BUTTON -->
                                                <form action='outsourcing_report.php' method='POST' style='margin:0;' onsubmit='return confirm(\"Are you sure you want to completely undo this log? The {$row['total_qty_out']} item(s) will be returned to your Master Inventory.\");'>
                                                    <input type='hidden' name='delete_product_id' value='{$row['product_id']}'>
                                                    <input type='hidden' name='delete_payment' value='{$row['payment_method']}'>
                                                    <button type='submit' name='delete_log' class='btn btn-danger' style='padding: 5px 10px; font-size: 12px;'>UNDO & DELETE</button>
                                                </form>

                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; padding: 60px; color:#888;'>No records found for this timeframe.</td></tr>";
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