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

    <div class="flex h-screen w-full">

        <div id="mobile-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden transition-opacity" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="bg-white w-72 border-r border-gray-200 flex flex-col transition-transform transform -translate-x-full md:translate-x-0 fixed md:relative z-50 h-full shadow-lg md:shadow-none print:hidden">
            <div class="p-6 flex items-center justify-center border-b border-gray-100 relative">
                <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo" class="h-16 w-auto">
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
                <button onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors shadow-sm flex items-center">
                    <i class="fas fa-print mr-2"></i> PRINT
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 print:hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
                        <div class="text-sm font-semibold text-gray-500 mb-1 uppercase">Total Inventory Items</div>
                        <div class="text-3xl font-bold text-primary">
                            <?php 
                            $res = $conn->query("SELECT SUM(current_quantity) as total FROM inventory WHERE current_quantity > 0");
                            echo $res->fetch_assoc()['total'] ?? '0'; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6 flex flex-col bg-red-50">
                        <div class="text-sm font-semibold text-red-500 mb-1 uppercase">Discrepancies (Negatives)</div>
                        <div class="text-3xl font-bold text-red-700">
                            <?php 
                            $res = $conn->query("SELECT COUNT(*) as negs FROM inventory WHERE current_quantity < 0");
                            echo $res->fetch_assoc()['negs'] ?? '0'; 
                            ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-green-200 p-6 flex flex-col bg-green-50">
                        <div class="text-sm font-semibold text-green-600 mb-1 uppercase">Total Value (PHP)</div>
                        <div class="text-3xl font-bold text-green-700">
                            ₱<?php 
                            $res = $conn->query("SELECT SUM(current_quantity * price) as val FROM inventory WHERE current_quantity > 0");
                            echo number_format($res->fetch_assoc()['val'] ?? 0, 2); 
                            ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8 print:hidden">
                    <h4 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">Add (IN) to Inventory</h4>
                    <form action="inventory.php" method="POST" class="flex flex-col lg:flex-row gap-4 items-end">
                        <div class="w-full lg:w-1/4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Product Name</label>
                            <input type="text" name="product_name" placeholder="e.g., Premium Rice" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div class="w-full lg:w-1/5">
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Category</label>
                            <input type="text" name="product_type" placeholder="e.g., Rice, Canned" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div class="w-full lg:w-32">
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Unit</label>
                            <select name="quantity_type" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white">
                                <option value="Sack">Sack</option>
                                <option value="Kilo">Kilo</option>
                                <option value="Pieces">Pieces</option>
                                <option value="Can">Can</option>
                                <option value="Tray">Tray</option>
                                <option value="Bottle">Bottle</option>
                            </select>
                        </div>
                        <div class="w-full lg:w-24">
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Qty</label>
                            <input type="number" name="quantity" value="1" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div class="w-full lg:w-32">
                            <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase">Price (₱)</label>
                            <input type="number" step="0.01" name="price" placeholder="0.00" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <button type="submit" name="add_product" class="bg-primary hover:bg-primaryDark text-white font-semibold py-2 px-4 rounded-md text-sm transition-colors w-full lg:w-auto shadow-sm whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i> ADD
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h4 class="font-bold text-gray-800">Current Stock Directory</h4>
                        <form action="inventory.php" method="GET" class="flex items-center gap-2 text-sm print:hidden w-full sm:w-auto">
                            <label class="font-semibold text-gray-500 whitespace-nowrap">Sort By:</label>
                            <select name="sort" onchange="this.form.submit()" class="rounded-md border border-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary bg-white w-full sm:w-auto">
                                <option value="name_asc" <?= $sort_option == 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                                <option value="qty_desc" <?= $sort_option == 'qty_desc' ? 'selected' : '' ?>>Quantity (High to Low)</option>
                                <option value="qty_asc" <?= $sort_option == 'qty_asc' ? 'selected' : '' ?>>Quantity (Low to High)</option>
                                <option value="price_desc" <?= $sort_option == 'price_desc' ? 'selected' : '' ?>>Price (Highest First)</option>
                            </select>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-bold tracking-wider">Product Name</th>
                                    <th scope="col" class="px-6 py-4 font-bold tracking-wider">Price</th>
                                    <th scope="col" class="px-6 py-4 font-bold tracking-wider">Current Stock</th>
                                    <th scope="col" class="px-6 py-4 font-bold tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 font-bold tracking-wider text-center print:hidden">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php
                                $sql = "SELECT * FROM inventory ORDER BY $order_by";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    $current_category = "";

                                    while($row = $result->fetch_assoc()) {
                                        
                                        // Grouping Logic
                                        if ($current_category !== $row['product_type']) {
                                            $current_category = $row['product_type'];
                                            echo "<tr class='bg-gray-100/50'>
                                                    <td colspan='5' class='px-6 py-3 font-bold text-gray-800 uppercase tracking-wider text-xs border-y border-gray-200'>" . htmlspecialchars($current_category) . "</td>
                                                  </tr>";
                                        }

                                        $qty = $row['current_quantity'];
                                        $unit = $row['quantity_type'];
                                        
                                        // Modern Status Badges
                                        if ($qty < 0) {
                                            $status = "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200'>NEGLECT/REVIEW</span>";
                                        } elseif ($qty == 0) {
                                            $status = "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200'>OUT OF STOCK</span>";
                                        } else {
                                            $status = "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200'>IN STOCK</span>";
                                        }

                                        echo "<tr class='hover:bg-purple-50 transition-colors'>
                                                <td class='px-6 py-4 font-semibold text-gray-900'>" . htmlspecialchars($row['product_name']) . "</td>
                                                <td class='px-6 py-4 font-medium'>₱" . number_format($row['price'], 2) . "</td>
                                                <td class='px-6 py-4'><strong class='text-base text-primary'>{$qty}</strong> <span class='text-gray-400 ml-1'>{$unit}s</span></td>
                                                <td class='px-6 py-4'>{$status}</td>
                                                <td class='px-6 py-4 flex justify-center gap-2 print:hidden'>
                                                    
                                                    <button type='button' class='edit-btn bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-medium py-1 px-3 rounded shadow-sm text-xs transition-colors' 
                                                        data-id='{$row['product_id']}' 
                                                        data-name='" . htmlspecialchars($row['product_name'], ENT_QUOTES) . "' 
                                                        data-qty='{$qty}' 
                                                        data-price='{$row['price']}'>
                                                        <i class='fas fa-edit text-blue-600 mr-1'></i> EDIT
                                                    </button>
                                                    
                                                    <form action='inventory.php' method='POST' class='m-0' onsubmit='return confirm(\"Permanently delete " . htmlspecialchars($row['product_name'], ENT_QUOTES) . "?\");'>
                                                        <input type='hidden' name='delete_id' value='{$row['product_id']}'>
                                                        <button type='submit' name='delete_product' class='bg-white hover:bg-red-50 text-red-600 border border-red-200 font-medium py-1 px-3 rounded shadow-sm text-xs transition-colors'><i class='fas fa-trash-alt mr-1'></i> DEL</button>
                                                    </form>

                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-6 py-12 text-center text-gray-500'>No products in inventory. Add one above.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div id="editModal" class="fixed inset-0 z-[100] hidden">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
        
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-edit text-primary mr-2"></i>Edit Product</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-800 transition-colors" onclick="closeEditModal()">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form action="inventory.php" method="POST" class="p-6 flex flex-col gap-4">
                    <input type="hidden" name="edit_id" id="modal_edit_id">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Product Name</label>
                        <input type="text" name="edit_name" id="modal_edit_name" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Current Stock Quantity</label>
                        <input type="number" name="edit_qty" id="modal_edit_qty" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Price (₱)</label>
                        <input type="number" step="0.01" name="edit_price" id="modal_edit_price" required class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <button type="submit" name="edit_product" class="mt-4 bg-primary hover:bg-primaryDark text-white font-bold py-3 rounded-lg shadow-md transition-colors w-full">
                        SAVE CHANGES
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // Modal Logic
        const editModal = document.getElementById("editModal");
        
        function closeEditModal() {
            editModal.classList.add('hidden');
        }

        document.querySelectorAll(".edit-btn").forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal_edit_id').value = this.getAttribute('data-id');
                document.getElementById('modal_edit_name').value = this.getAttribute('data-name');
                document.getElementById('modal_edit_qty').value = this.getAttribute('data-qty');
                document.getElementById('modal_edit_price').value = parseFloat(this.getAttribute('data-price')).toFixed(2);
                
                editModal.classList.remove('hidden');
            });
        });

        // Auto format price
        document.getElementById('modal_edit_price')?.addEventListener('blur', function() {
            if (this.value) { this.value = parseFloat(this.value).toFixed(2); }
        });
    </script>
</body>
</html>