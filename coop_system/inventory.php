<?php 
include 'db.php'; 

// CREATE FUNCTION: Handle adding a new product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['product_name']);
    $type = $conn->real_escape_string($_POST['product_type']);
    $qty_type = $conn->real_escape_string($_POST['quantity_type']);
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $sql = "INSERT INTO inventory (product_name, product_type, quantity_type, current_quantity, price) 
            VALUES ('$name', '$type', '$qty_type', '$qty', '$price')";
    $conn->query($sql);
    header("Location: inventory.php"); // Refresh page
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Coop DBMS</title>
    <!-- The ?v= php echo time() forces the browser to NEVER cache this file -->
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
                <a href="inventory.php" class="menu-btn active">INVENTORY MANAGEMENT</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT SYSTEM</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Inventory & Stock Reports</h1>
                <div class="action-buttons">
                    <button class="btn btn-secondary">PRINT REPORT</button>
                </div>
            </div>

            <!-- 1. REPORT DASHBOARD CARDS -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-title">Total Inventory Items</div>
                    <div class="stat-value">
                        <?php 
                        $res = $conn->query("SELECT SUM(current_quantity) as total FROM inventory WHERE current_quantity > 0");
                        echo $res->fetch_assoc()['total'] ?? '0'; 
                        ?>
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-title">Discrepancies (Negatives)</div>
                    <div class="stat-value text-danger">
                        <?php 
                        $res = $conn->query("SELECT COUNT(*) as negs FROM inventory WHERE current_quantity < 0");
                        echo $res->fetch_assoc()['negs'] ?? '0'; 
                        ?>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-title">Total Value (PHP)</div>
                    <div class="stat-value">
                        ₱<?php 
                        $res = $conn->query("SELECT SUM(current_quantity * price) as val FROM inventory WHERE current_quantity > 0");
                        echo number_format($res->fetch_assoc()['val'] ?? 0, 2); 
                        ?>
                    </div>
                </div>
            </div>

            <!-- 2. CREATE PRODUCT FORM (HORIZONTAL) -->
            <div class="form-panel">
                <h4>Add New Inventory Item</h4>
                <form action="inventory.php" method="POST" class="inline-form">
                    <div class="input-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" placeholder="e.g., Premium Rice 911" required>
                    </div>
                    <div class="input-group">
                        <label>Category/Type</label>
                        <input type="text" name="product_type" placeholder="e.g., Rice, Canned Goods" required>
                    </div>
                    <div class="input-group">
                        <label>Unit</label>
                        <select name="quantity_type" required>
                            <option value="Sack">Sack</option>
                            <option value="Kilo">Kilo</option>
                            <option value="Pieces">Pieces</option>
                            <option value="Can">Can</option>
                            <option value="Tray">Tray</option>
                            <option value="Bottle">Bottle</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Qty</label>
                        <input type="number" name="quantity" value="1" required>
                    </div>
                    <div class="input-group">
                        <label>Price (₱)</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary" style="padding: 13px 20px;">+ ADD ITEM</button>
                </form>
            </div>

            <!-- 3. DATA TABLE -->
            <div class="content-display" style="padding: 0; overflow: hidden;">
                <div class="data-table-container" style="margin-top: 0;">
                    <table class="data-table">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM inventory ORDER BY product_name ASC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $qty = $row['current_quantity'];
                                    $unit = $row['quantity_type'];
                                    
                                    if ($qty < 0) {
                                        $status = "<span class='badge-danger' style='background: #ffebee; color: #c62828; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 12px;'>NEGLECT / REVIEW</span>";
                                    } elseif ($qty == 0) {
                                        $status = "<span class='badge-danger' style='background: #fff3e0; color: #e65100; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 12px;'>OUT OF STOCK</span>";
                                    } else {
                                        $status = "<span class='badge-success' style='background: #e8f5e9; color: #2e7d32; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 12px;'>IN STOCK</span>";
                                    }

                                    echo "<tr>
                                            <td><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>
                                            <td style='color: #666;'>" . htmlspecialchars($row['product_type']) . "</td>
                                            <td>₱" . number_format($row['price'], 2) . "</td>
                                            <td><strong style='font-size: 16px;'>{$qty}</strong> <span style='color: #888;'>{$unit}s</span></td>
                                            <td>{$status}</td>
                                            <td>
                                                <button class='btn btn-secondary' style='padding: 5px 10px; font-size: 12px;' onclick='alert(\"Edit modal coming soon\")'>EDIT</button>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; padding: 60px; color:#888; background: #fff;'>No products in inventory. Add one above.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>

<script src="js/scripts.js"></script>

</html>