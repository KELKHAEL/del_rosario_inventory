<?php 
include 'db.php'; 

// 1. ADD / MERGE PRODUCT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['product_name']);
    $type = $conn->real_escape_string($_POST['product_type']);
    $qty_type = $conn->real_escape_string($_POST['quantity_type']);
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $check_sql = "SELECT product_id FROM inventory WHERE product_name='$name' AND product_type='$type' AND quantity_type='$qty_type'";
    $check_res = $conn->query($check_sql);

    if ($check_res && $check_res->num_rows > 0) {
        $update_sql = "UPDATE inventory SET current_quantity = current_quantity + $qty, price = '$price' WHERE product_name='$name' AND product_type='$type' AND quantity_type='$qty_type'";
        $conn->query($update_sql);
    } else {
        $insert_sql = "INSERT INTO inventory (product_name, product_type, quantity_type, current_quantity, price) 
                VALUES ('$name', '$type', '$qty_type', '$qty', '$price')";
        $conn->query($insert_sql);
    }
    header("Location: inventory.php"); 
    exit();
}

// 2. EDIT PRODUCT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_product'])) {
    $id = (int)$_POST['edit_id'];
    $name = $conn->real_escape_string($_POST['edit_name']);
    $qty = (int)$_POST['edit_qty'];
    $price = (float)$_POST['edit_price'];

    $conn->query("UPDATE inventory SET product_name='$name', current_quantity='$qty', price='$price' WHERE product_id=$id");
    header("Location: inventory.php"); 
    exit();
}

// 3. DELETE PRODUCT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    $id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM inventory WHERE product_id=$id");
    header("Location: inventory.php"); 
    exit();
}

// 4. SORTING LOGIC
$sort_option = $_GET['sort'] ?? 'name_asc';
$order_by = "product_type ASC, product_name ASC"; 

if ($sort_option === 'qty_asc') $order_by = "product_type ASC, current_quantity ASC";
if ($sort_option === 'qty_desc') $order_by = "product_type ASC, current_quantity DESC";
if ($sort_option === 'price_desc') $order_by = "product_type ASC, price DESC";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Coop DBMS</title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
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
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn active">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Master Inventory Database</h1>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="window.print()">PRINT REPORT</button>
                </div>
            </div>

            <!-- REPORT DASHBOARD CARDS -->
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

            <!-- CREATE PRODUCT FORM -->
            <div class="form-panel">
                <h4>Add (IN) to Inventory</h4>
                <form action="inventory.php" method="POST" class="inline-form">
                    <div class="input-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" placeholder="e.g., Premium Rice" required>
                    </div>
                    <div class="input-group">
                        <label>Category/Type</label>
                        <input type="text" name="product_type" placeholder="e.g., Rice, Canned" required>
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
                        <label>Qty (To Add)</label>
                        <input type="number" name="quantity" value="1" required>
                    </div>
                    <div class="input-group">
                        <label>Price (₱)</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary" style="padding: 13px 20px;">+ ADD ITEM</button>
                </form>
            </div>

            <!-- DATA TABLE WITH FILTERS -->
            <div class="content-display" style="padding: 0; overflow: hidden;">
                
                <div style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0; color: #444; font-size: 16px;">Current Stock Directory</h4>
                    <form action="inventory.php" method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <label style="font-size: 13px; font-weight: 600; color: #555;">Sort By:</label>
                        <select name="sort" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="name_asc" <?= $sort_option == 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                            <option value="qty_desc" <?= $sort_option == 'qty_desc' ? 'selected' : '' ?>>Quantity (High to Low)</option>
                            <option value="qty_asc" <?= $sort_option == 'qty_asc' ? 'selected' : '' ?>>Quantity (Low to High)</option>
                            <option value="price_desc" <?= $sort_option == 'price_desc' ? 'selected' : '' ?>>Price (Highest First)</option>
                        </select>
                    </form>
                </div>

                <div class="data-table-container" style="margin-top: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM inventory ORDER BY $order_by";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                $current_category = "";

                                while($row = $result->fetch_assoc()) {
                                    
                                    // Grouping Logic
                                    if ($current_category !== $row['product_type']) {
                                        $current_category = $row['product_type'];
                                        echo "<tr class='category-header'>
                                                <td colspan='5'>" . strtoupper(htmlspecialchars($current_category)) . "</td>
                                              </tr>";
                                    }

                                    $qty = $row['current_quantity'];
                                    $unit = $row['quantity_type'];
                                    
                                    // Status Badges
                                    if ($qty < 0) {
                                        $status = "<span class='badge-danger' style='background: #ffebee; color: #c62828;'>NEGLECT / REVIEW</span>";
                                    } elseif ($qty == 0) {
                                        $status = "<span class='badge-danger' style='background: #fff3e0; color: #e65100;'>OUT OF STOCK</span>";
                                    } else {
                                        $status = "<span class='badge-success'>IN STOCK</span>";
                                    }

                                    echo "<tr>
                                            <td style='padding-left: 30px;'><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>
                                            <td>₱" . number_format($row['price'], 2) . "</td>
                                            <td><strong style='font-size: 16px;'>{$qty}</strong> <span style='color: #888;'>{$unit}s</span></td>
                                            <td>{$status}</td>
                                            <td style='display: flex; gap: 8px;'>
                                                
                                                <!-- EDIT BUTTON -->
                                                <button type='button' class='btn btn-secondary edit-btn' 
                                                    data-id='{$row['product_id']}' 
                                                    data-name='" . htmlspecialchars($row['product_name'], ENT_QUOTES) . "' 
                                                    data-qty='{$qty}' 
                                                    data-price='{$row['price']}'
                                                    style='padding: 5px 10px; font-size: 12px;'>EDIT</button>
                                                
                                                <!-- DELETE BUTTON -->
                                                <form action='inventory.php' method='POST' style='margin:0;' onsubmit='return confirm(\"Are you sure you want to permanently delete " . htmlspecialchars($row['product_name'], ENT_QUOTES) . "?\");'>
                                                    <input type='hidden' name='delete_id' value='{$row['product_id']}'>
                                                    <button type='submit' name='delete_product' class='btn btn-danger' style='padding: 5px 10px; font-size: 12px;'>DELETE</button>
                                                </form>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding: 60px; color:#888;'>No products in inventory. Add one above.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- EDIT PRODUCT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product Details</h3>
                <span class="close-modal edit-close">&times;</span>
            </div>
            <form action="inventory.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="edit_id" id="modal_edit_id">
                
                <div class="input-group">
                    <label>Product Name</label>
                    <input type="text" name="edit_name" id="modal_edit_name" required>
                </div>
                <div class="input-group">
                    <label>Current Stock Quantity</label>
                    <input type="number" name="edit_qty" id="modal_edit_qty" required>
                </div>
                <div class="input-group">
                    <label>Price Amount (₱)</label>
                    <input type="number" step="0.01" name="edit_price" id="modal_edit_price" required>
                </div>
                
                <button type="submit" name="edit_product" class="btn btn-primary" style="margin-top: 10px; padding: 12px;">SAVE CHANGES</button>
            </form>
        </div>
    </div>

    <!-- GUARANTEED WORKING MODAL SCRIPT -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Auto format price input
            const editPriceInput = document.getElementById('modal_edit_price');
            if(editPriceInput) {
                editPriceInput.addEventListener('blur', function() {
                    if (this.value) { this.value = parseFloat(this.value).toFixed(2); }
                });
            }

            // Edit Modal logic
            const editModal = document.getElementById("editModal");
            
            document.querySelectorAll(".edit-btn").forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('modal_edit_id').value = this.getAttribute('data-id');
                    document.getElementById('modal_edit_name').value = this.getAttribute('data-name');
                    document.getElementById('modal_edit_qty').value = this.getAttribute('data-qty');
                    document.getElementById('modal_edit_price').value = parseFloat(this.getAttribute('data-price')).toFixed(2);
                    editModal.style.display = "block";
                });
            });

            document.querySelector(".edit-close").addEventListener('click', () => editModal.style.display = "none");
            
            // Close when clicking outside the box
            window.addEventListener('click', function(event) {
                if (event.target === editModal) {
                    editModal.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>