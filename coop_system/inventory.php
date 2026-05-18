<?php 
session_start();
include 'db.php'; 

// Fetch dynamic dropdown data
$categories = [];
$res_cat = $conn->query("SELECT name FROM config_product_categories ORDER BY name ASC");
if($res_cat) { while($r = $res_cat->fetch_assoc()) { $categories[] = $r['name']; } }

$unit_types = [];
$res_units = $conn->query("SELECT name FROM config_unit_types ORDER BY name ASC");
if($res_units) { while($r = $res_units->fetch_assoc()) { $unit_types[] = $r['name']; } }

// Process Add/Edit/Delete Product
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['delete_product_id'])) {
        $del_id = (int)$_POST['delete_product_id'];
        $conn->query("DELETE FROM inventory WHERE product_id=$del_id");
        $_SESSION['alert_title'] = "Item Deleted";
        $_SESSION['alert_message'] = "The product has been permanently removed from the master inventory.";
        $_SESSION['alert_type'] = "success";
        header("Location: inventory.php");
        exit();
    }
    
    $product_name = $conn->real_escape_string($_POST['product_name']);
    $product_type = $conn->real_escape_string($_POST['product_type']);
    $quantity_type = $conn->real_escape_string($_POST['quantity_type']);
    $price = (float)$_POST['price'];

    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        // Edit existing product (Does NOT update quantity)
        $id = (int)$_POST['product_id'];
        $sql = "UPDATE inventory SET product_name='$product_name', product_type='$product_type', quantity_type='$quantity_type', price='$price' WHERE product_id=$id";
        $conn->query($sql);
        $_SESSION['alert_title'] = "Item Updated";
        $_SESSION['alert_message'] = "The product information has been successfully updated.";
        $_SESSION['alert_type'] = "success";
    } else {
        // Add completely new product
        $current_quantity = (int)$_POST['current_quantity'];
        $sql = "INSERT INTO inventory (product_name, product_type, quantity_type, current_quantity, price) 
                VALUES ('$product_name', '$product_type', '$quantity_type', $current_quantity, '$price')";
        $conn->query($sql);
        $_SESSION['alert_title'] = "Item Added";
        $_SESSION['alert_message'] = "A new product has been successfully added to the master inventory.";
        $_SESSION['alert_type'] = "success";
    }
    header("Location: inventory.php");
    exit();
}

// Process Stock Adjustment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adjust_stock_id'])) {
    $adj_id = (int)$_POST['adjust_stock_id'];
    $adj_amount = (int)$_POST['adjust_amount'];
    
    // We simply mathematically add the amount. If they pass a negative number, it will subtract it.
    $conn->query("UPDATE inventory SET current_quantity = current_quantity + $adj_amount WHERE product_id=$adj_id");
    
    $_SESSION['alert_title'] = "Stock Adjusted";
    $_SESSION['alert_message'] = "The inventory levels have been updated successfully.";
    $_SESSION['alert_type'] = "success";
    header("Location: inventory.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Coop DBMS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], },
                    colors: { primary: '#6a1b9a', primaryDark: '#570591', }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased overflow-hidden">
    
    <?php include 'cover_page.php'; ?>

    <div id="customAlertModal" class="fixed inset-0 z-[1000] hidden items-center justify-center p-4 print:hidden">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity"></div>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all z-10 flex flex-col translate-y-4 opacity-0" id="customAlertBox">
            <div id="customAlertHeader" class="px-6 py-4 flex items-center gap-3 border-b">
                <i id="customAlertIcon" class="fas fa-exclamation-circle text-2xl"></i>
                <h3 id="customAlertTitle" class="text-lg font-bold tracking-tight">Alert</h3>
            </div>
            <div class="p-6 text-gray-600 text-sm leading-relaxed" id="customAlertMessage"></div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                <button id="customAlertBtn" class="bg-primary hover:bg-primaryDark text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md">OK</button>
            </div>
        </div>
    </div>

    <div id="adjustModal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4 print:hidden">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm" onclick="closeAdjustModal()"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md z-10 overflow-hidden transform transition-all">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800"><i class="fas fa-boxes text-primary mr-2"></i>Adjust Stock Level</h3>
                <button onclick="closeAdjustModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="inventory.php" method="POST" class="p-6">
                <input type="hidden" name="adjust_stock_id" id="adj_product_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1" id="adj_product_name_display"></label>
                    <div class="text-xs text-gray-500 mb-4">Current Stock: <span id="adj_current_stock" class="font-bold text-gray-800"></span></div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="adjust_amount" placeholder="e.g. 5 or -3" required class="w-full rounded-md border border-gray-300 px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                    <p class="text-xs text-gray-400 mt-1 italic">Use a positive number to add stock, and a negative number (e.g. -5) to subtract.</p>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeAdjustModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors">CANCEL</button>
                    <button type="submit" class="bg-primary hover:bg-primaryDark text-white font-bold py-2 px-6 rounded-md text-sm transition-colors shadow-md">UPDATE STOCK</button>
                </div>
            </form>
        </div>
    </div>

    <div id="productModal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4 print:hidden">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg z-10 overflow-hidden transform transition-all">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800" id="modalTitle"><i class="fas fa-plus-circle text-primary mr-2"></i>Add New Product</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="inventory.php" method="POST" class="p-6">
                <input type="hidden" name="product_id" id="product_id">
                
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                        <input type="text" name="product_name" id="product_name" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category / Type <span class="text-red-500">*</span></label>
                            <select name="product_type" id="product_type" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white">
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure <span class="text-red-500">*</span></label>
                            <select name="quantity_type" id="quantity_type" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-white">
                                <option value="">Select Unit</option>
                                <?php foreach($unit_types as $ut): ?>
                                    <option value="<?= htmlspecialchars($ut) ?>"><?= htmlspecialchars($ut) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (PHP) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="price" id="price" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                        <div id="initial_qty_group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock <span class="text-red-500">*</span></label>
                            <input type="number" name="current_quantity" id="current_quantity" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button type="button" onclick="closeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors">CANCEL</button>
                    <button type="submit" class="bg-primary hover:bg-primaryDark text-white font-bold py-2 px-6 rounded-md text-sm transition-colors shadow-md"><i class="fas fa-save mr-1"></i> SAVE PRODUCT</button>
                </div>
            </form>
        </div>
    </div>

    <div class="flex h-screen w-full">

        <div id="mobile-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden transition-opacity print:hidden" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="bg-white w-72 border-r border-gray-200 flex flex-col transition-transform transform -translate-x-full md:translate-x-0 fixed md:relative z-50 h-full shadow-lg md:shadow-none print:hidden">
            <div class="p-6 flex items-center justify-center border-b border-gray-100 relative">
                
                <a href="#" onclick="showSplashScreen(); return false;" class="block">
                    <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo" class="w-40 md:w-52 h-auto object-contain py-2 drop-shadow-sm transition-transform hover:scale-105">
                </a>

                <button class="absolute top-4 right-4 md:hidden text-gray-400 hover:text-gray-800" onclick="toggleSidebar()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-4 flex flex-col gap-1">
                <a href="index.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-users w-6"></i> MEMBERSHIP DIRECTORY
                </a>
                <a href="transactions.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-receipt w-6"></i> TRANSACTIONS
                </a>
                <a href="inventory.php" class="flex items-center px-6 py-3 bg-primary text-white font-semibold border-l-4 border-primaryDark">
                    <i class="fas fa-boxes w-6"></i> INVENTORY
                </a>
                <a href="pos.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-shopping-cart w-6"></i> SELL / OUTSOURCE
                </a>
                <a href="outsourcing_report.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-chart-line w-6"></i> OUTSOURCING LOGS
                </a>
                <a href="database_management.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-database w-6"></i> DATABASE SETTINGS
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative w-full">
            
            <header class="bg-white shadow-sm px-4 md:px-8 py-4 flex justify-between items-center z-10 print:hidden">
                <div class="flex items-center gap-4">
                    <button class="text-gray-500 focus:outline-none md:hidden hover:text-primary" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight">Master Inventory</h1>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                
                <?php
                // Fetch Stats
                $stat_total = $conn->query("SELECT COUNT(*) as c FROM inventory")->fetch_assoc()['c'];
                $stat_low = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE current_quantity < 5")->fetch_assoc()['c'];
                $stat_val = $conn->query("SELECT SUM(current_quantity * price) as v FROM inventory WHERE current_quantity > 0")->fetch_assoc()['v'];
                ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 print:hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-purple-200 p-6 flex items-center justify-between border-l-4 border-l-primary">
                        <div>
                            <div class="text-sm font-semibold text-gray-500 uppercase mb-1">Total Products</div>
                            <div class="text-3xl font-bold text-gray-800"><?= $stat_total ?></div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-primary text-xl"><i class="fas fa-box"></i></div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6 flex items-center justify-between border-l-4 border-l-red-500">
                        <div>
                            <div class="text-sm font-semibold text-gray-500 uppercase mb-1">Low / Out of Stock</div>
                            <div class="text-3xl font-bold <?= $stat_low > 0 ? 'text-red-600' : 'text-gray-800' ?>"><?= $stat_low ?></div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 text-xl"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-green-200 p-6 flex items-center justify-between border-l-4 border-l-green-500">
                        <div>
                            <div class="text-sm font-semibold text-gray-500 uppercase mb-1">Est. Inventory Value</div>
                            <div class="text-3xl font-bold text-gray-800">₱<?= number_format($stat_val, 2) ?></div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-500 text-xl"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4 print:hidden">
                    
                    <div class="flex w-full lg:w-1/3 bg-white border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-primary focus-within:border-primary transition-all shadow-sm">
                        <div class="px-3 py-2 text-gray-400 flex items-center justify-center"><i class="fas fa-search"></i></div>
                        <input type="text" id="liveSearch" placeholder="Search Products, Categories..." class="w-full py-2 pr-4 outline-none text-sm text-gray-700 bg-transparent">
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <button onclick="openModal()" class="bg-primary hover:bg-primaryDark text-white font-semibold py-2 px-4 rounded-md text-sm transition-colors shadow-sm w-full sm:w-auto text-center whitespace-nowrap">
                            <i class="fas fa-plus mr-2"></i>ADD PRODUCT
                        </button>
                        <button onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors shadow-sm w-full sm:w-auto text-center border border-gray-300 whitespace-nowrap">
                            <i class="fas fa-print mr-2"></i>PRINT INVENTORY
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 font-bold tracking-wider">Product Name</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Category</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Price (PHP)</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Stock Status</th>
                                    <th class="px-6 py-4 font-bold tracking-wider text-right print:hidden">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="inventoryTableBody">
                                <?php
                                $sql = "SELECT * FROM inventory ORDER BY product_name ASC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        
                                        $stock = $row['current_quantity'];
                                        if ($stock <= 0) {
                                            $stockBadge = "<span class='inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-red-100 text-red-800 border border-red-200'><i class='fas fa-times-circle mr-1'></i> OUT OF STOCK ({$stock})</span>";
                                            $rowBg = "bg-red-50/30"; // Very subtle red tint
                                        } elseif ($stock < 5) {
                                            $stockBadge = "<span class='inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200'><i class='fas fa-exclamation-triangle mr-1'></i> LOW STOCK ({$stock})</span>";
                                            $rowBg = "";
                                        } else {
                                            $stockBadge = "<span class='font-bold text-gray-800 text-base'>{$stock}</span> <span class='text-gray-400 text-xs ml-1'>" . htmlspecialchars($row['quantity_type']) . "s</span>";
                                            $rowBg = "";
                                        }

                                        echo "<tr class='inventory-row {$rowBg} hover:bg-purple-50 transition-colors'>
                                                <td class='px-6 py-4 font-bold text-gray-900 capitalize'>" . htmlspecialchars($row['product_name']) . "</td>
                                                <td class='px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase'>" . htmlspecialchars($row['product_type']) . "</td>
                                                <td class='px-6 py-4 font-semibold text-gray-700'>₱" . number_format($row['price'], 2) . "</td>
                                                <td class='px-6 py-4'>{$stockBadge}</td>
                                                <td class='px-6 py-4 text-right print:hidden'>
                                                    
                                                    <div class='flex justify-end gap-2'>
                                                        <button onclick='openAdjustModal({$row['product_id']}, \"" . addslashes($row['product_name']) . "\", {$stock})' class='bg-white hover:bg-green-50 text-green-600 border border-green-200 font-semibold py-1 px-3 rounded shadow-sm text-xs transition-colors' title='Adjust Stock'>
                                                            <i class='fas fa-plus-minus'></i> STOCK
                                                        </button>

                                                        <button onclick='editProduct({$row['product_id']}, \"" . addslashes($row['product_name']) . "\", \"" . addslashes($row['product_type']) . "\", \"" . addslashes($row['quantity_type']) . "\", {$row['price']})' class='bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 font-semibold py-1 px-3 rounded shadow-sm text-xs transition-colors' title='Edit Details'>
                                                            <i class='fas fa-edit'></i> EDIT
                                                        </button>

                                                        <form action='inventory.php' method='POST' class='m-0 inline-block' onsubmit='return confirm(\"Are you sure you want to completely delete this product? All logs related to it might lose reference.\");'>
                                                            <input type='hidden' name='delete_product_id' value='{$row['product_id']}'>
                                                            <button type='submit' class='bg-white hover:bg-red-50 text-red-600 border border-red-200 font-semibold py-1 px-3 rounded shadow-sm text-xs transition-colors' title='Delete Product'>
                                                                <i class='fas fa-trash-alt'></i>
                                                            </button>
                                                        </form>
                                                    </div>

                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-6 py-12 text-center text-gray-500'>Inventory is empty. Click 'Add Product' to begin.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // --- LIVE SEARCH ---
        document.getElementById('liveSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.inventory-row');

            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // --- PRODUCT MODAL LOGIC ---
        function openModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle text-primary mr-2"></i>Add New Product';
            document.getElementById('product_id').value = '';
            document.getElementById('product_name').value = '';
            document.getElementById('product_type').value = '';
            document.getElementById('quantity_type').value = '';
            document.getElementById('price').value = '';
            
            // Show initial stock input since it's a new product
            const qtyGroup = document.getElementById('initial_qty_group');
            qtyGroup.style.display = 'block';
            document.getElementById('current_quantity').setAttribute('required', 'required');
            document.getElementById('current_quantity').value = '';

            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function editProduct(id, name, type, qtyType, price) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-blue-600 mr-2"></i>Edit Product Details';
            document.getElementById('product_id').value = id;
            document.getElementById('product_name').value = name;
            document.getElementById('product_type').value = type;
            document.getElementById('quantity_type').value = qtyType;
            document.getElementById('price').value = price;

            // Hide initial stock input (stock must be edited via the Adjust Stock button)
            const qtyGroup = document.getElementById('initial_qty_group');
            qtyGroup.style.display = 'none';
            document.getElementById('current_quantity').removeAttribute('required');

            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModal').classList.remove('flex');
        }

        // --- STOCK ADJUSTMENT MODAL LOGIC ---
        function openAdjustModal(id, name, currentStock) {
            document.getElementById('adj_product_id').value = id;
            document.getElementById('adj_product_name_display').innerText = name.toUpperCase();
            document.getElementById('adj_current_stock').innerText = currentStock;
            
            // Reset input
            document.querySelector('input[name="adjust_amount"]').value = '';

            document.getElementById('adjustModal').classList.remove('hidden');
            document.getElementById('adjustModal').classList.add('flex');
        }

        function closeAdjustModal() {
            document.getElementById('adjustModal').classList.add('hidden');
            document.getElementById('adjustModal').classList.remove('flex');
        }


        // --- CUSTOM ALERT LOGIC ---
        let alertRedirectUrl = null;

        function showCustomAlert(title, message, type = 'error', redirectUrl = null) {
            const modal = document.getElementById('customAlertModal');
            const box = document.getElementById('customAlertBox');
            const titleEl = document.getElementById('customAlertTitle');
            const msgEl = document.getElementById('customAlertMessage');
            const iconEl = document.getElementById('customAlertIcon');
            const headerEl = document.getElementById('customAlertHeader');
            const btnEl = document.getElementById('customAlertBtn');

            titleEl.innerText = title;
            msgEl.innerHTML = message;
            alertRedirectUrl = redirectUrl;

            if (type === 'success') {
                iconEl.className = 'fas fa-check-circle text-2xl text-green-500';
                headerEl.className = 'px-6 py-4 flex items-center gap-3 border-b bg-green-50 border-green-100';
                btnEl.className = 'bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md';
            } else if (type === 'info') {
                iconEl.className = 'fas fa-info-circle text-2xl text-blue-500';
                headerEl.className = 'px-6 py-4 flex items-center gap-3 border-b bg-blue-50 border-blue-100';
                btnEl.className = 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md';
            } else {
                iconEl.className = 'fas fa-exclamation-circle text-2xl text-red-500';
                headerEl.className = 'px-6 py-4 flex items-center gap-3 border-b bg-red-50 border-red-100';
                btnEl.className = 'bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-md';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                box.classList.remove('translate-y-4', 'opacity-0');
                box.classList.add('translate-y-0', 'opacity-100');
            }, 10);
        }

        document.getElementById('customAlertBtn').addEventListener('click', function() {
            const modal = document.getElementById('customAlertModal');
            const box = document.getElementById('customAlertBox');
            
            box.classList.remove('translate-y-0', 'opacity-100');
            box.classList.add('translate-y-4', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (alertRedirectUrl) {
                    window.location.href = alertRedirectUrl;
                }
            }, 300);
        });

        // Catch Session Alerts
        <?php if (isset($_SESSION['alert_message'])): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showCustomAlert(
                    "<?= addslashes($_SESSION['alert_title']) ?>", 
                    "<?= addslashes($_SESSION['alert_message']) ?>", 
                    "<?= addslashes($_SESSION['alert_type']) ?>"
                );
            });
            <?php 
            unset($_SESSION['alert_title']);
            unset($_SESSION['alert_message']);
            unset($_SESSION['alert_type']);
            ?>
        <?php endif; ?>
    </script>
</body>
</html>