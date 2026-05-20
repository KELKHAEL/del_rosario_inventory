<?php 
session_start();
include 'db.php'; 

// --- AUTO-UPGRADE DATABASE SCHEMA FOR RECONCILIATION ---
$check_col = $conn->query("SHOW COLUMNS FROM inventory_outsourcing LIKE 'status'");
if ($check_col->num_rows == 0) {
    $conn->query("ALTER TABLE inventory_outsourcing ADD COLUMN status VARCHAR(50) DEFAULT 'COMPLETED' AFTER buyer_contact");
    $conn->query("ALTER TABLE inventory_outsourcing ADD COLUMN quantity_returned INT(11) DEFAULT 0 AFTER status");
}
// Automatically catch any new "Others" (Bazaars) dispatched from POS and flag them as PENDING
$conn->query("UPDATE inventory_outsourcing SET status = 'PENDING' WHERE payment_method = 'Others' AND status = 'COMPLETED' AND quantity_returned = 0");

// --- PROCESS RECONCILIATION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reconcile_record_id'])) {
    $rec_id = (int)$_POST['reconcile_record_id'];
    $prod_id = (int)$_POST['product_id'];
    $qty_sold = (int)$_POST['qty_sold'];
    $qty_returned = (int)$_POST['qty_returned'];

    // 1. Return unsold stock to the master inventory naturally!
    if ($qty_returned > 0) {
        $conn->query("UPDATE inventory SET current_quantity = current_quantity + $qty_returned WHERE product_id = $prod_id");
    }

    // 2. Update the log to RECONCILED with the true sold/returned numbers
    $conn->query("UPDATE inventory_outsourcing SET status = 'RECONCILED', quantity_out = $qty_sold, quantity_returned = $qty_returned WHERE record_id = $rec_id");

    $_SESSION['alert_title'] = "Event Reconciled";
    $_SESSION['alert_message'] = "Stock has been successfully reconciled! <strong>{$qty_returned} items</strong> were returned to the master inventory.";
    $_SESSION['alert_type'] = "success";
    header("Location: outsourcing_report.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outsourcing & Events - Coop DBMS</title>
    
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

    <div id="reconcileModal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4 print:hidden">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm" onclick="closeReconcileModal()"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md z-10 overflow-hidden transform transition-all">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 flex justify-between items-center">
                <h3 class="font-bold text-blue-800"><i class="fas fa-clipboard-check mr-2"></i>Reconcile Event Stock</h3>
                <button onclick="closeReconcileModal()" class="text-blue-400 hover:text-blue-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="outsourcing_report.php" method="POST" class="p-6">
                <input type="hidden" name="reconcile_record_id" id="rec_record_id">
                <input type="hidden" name="product_id" id="rec_product_id">
                <input type="hidden" id="rec_total_val">
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Product Dispatched</div>
                    <div class="font-bold text-gray-800 text-lg capitalize" id="rec_product_name"></div>
                    <div class="mt-2 text-sm text-gray-600">Total Items Taken: <span id="rec_total_qty" class="font-black text-primary"></span></div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div>
                        <label class="block text-sm font-bold text-green-700 mb-1">Items Sold</label>
                        <input type="number" name="qty_sold" id="qty_sold" required min="0" oninput="calculateReturn()" class="w-full rounded-md border-2 border-green-300 px-4 py-3 text-xl font-bold focus:outline-none focus:border-green-500 text-center bg-green-50 text-green-900 shadow-inner">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-blue-700 mb-1">Items Returned</label>
                        <input type="number" name="qty_returned" id="qty_returned" required readonly class="w-full rounded-md border-2 border-blue-300 px-4 py-3 text-xl font-bold focus:outline-none bg-blue-50 text-blue-900 text-center cursor-not-allowed shadow-inner">
                    </div>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeReconcileModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors">CANCEL</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md text-sm transition-colors shadow-md"><i class="fas fa-check mr-1"></i> FINALIZE RETURN</button>
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
                <a href="inventory.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-boxes w-6"></i> INVENTORY
                </a>
                <a href="pos.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-shopping-cart w-6"></i> SELL / OUTSOURCE
                </a>
                <a href="outsourcing_report.php" class="flex items-center px-6 py-3 bg-primary text-white font-semibold border-l-4 border-primaryDark">
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight">Outsourcing & Events</h1>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">

                <?php
                // Fetch Dashboard Stats
                $stat_pending = $conn->query("SELECT COUNT(*) as c FROM inventory_outsourcing WHERE status = 'PENDING'")->fetch_assoc()['c'];
                $stat_reconciled = $conn->query("SELECT COUNT(*) as c FROM inventory_outsourcing WHERE status = 'RECONCILED'")->fetch_assoc()['c'];
                $stat_total = $conn->query("SELECT SUM(quantity_out) as c FROM inventory_outsourcing WHERE status != 'PENDING'")->fetch_assoc()['c'];
                if (!$stat_total) $stat_total = 0;
                ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 print:hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-orange-200 p-6 flex items-center justify-between border-l-4 border-l-orange-500">
                        <div>
                            <div class="text-sm font-semibold text-gray-500 uppercase mb-1">Pending Returns</div>
                            <div class="text-3xl font-black <?= $stat_pending > 0 ? 'text-orange-600' : 'text-gray-800' ?>"><?= $stat_pending ?></div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 text-xl"><i class="fas fa-clock"></i></div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-blue-200 p-6 flex items-center justify-between border-l-4 border-l-blue-500">
                        <div>
                            <div class="text-sm font-semibold text-gray-500 uppercase mb-1">Reconciled Events</div>
                            <div class="text-3xl font-black text-gray-800"><?= $stat_reconciled ?></div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 text-xl"><i class="fas fa-clipboard-check"></i></div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-green-200 p-6 flex items-center justify-between border-l-4 border-l-green-500">
                        <div>
                            <div class="text-sm font-semibold text-gray-500 uppercase mb-1">Total Sold Items</div>
                            <div class="text-3xl font-black text-gray-800"><?= number_format($stat_total) ?></div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-500 text-xl"><i class="fas fa-boxes"></i></div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4 print:hidden">
                    
                    <div class="flex w-full lg:w-1/3 bg-white border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-primary shadow-sm">
                        <div class="px-3 py-2 text-gray-400 flex items-center justify-center"><i class="fas fa-search"></i></div>
                        <input type="text" id="logSearch" placeholder="Search events, products, buyers..." class="w-full py-2 pr-4 outline-none text-sm text-gray-700 bg-transparent">
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto items-center">
                        <div class="flex items-center gap-2 w-full sm:w-auto bg-white border border-gray-300 rounded-lg px-3 py-1 shadow-sm">
                            <i class="fas fa-calendar-alt text-gray-400"></i>
                            <input type="date" id="dateFilterStart" class="outline-none text-sm text-gray-700 bg-transparent cursor-pointer">
                            <span class="text-gray-400 text-xs">to</span>
                            <input type="date" id="dateFilterEnd" class="outline-none text-sm text-gray-700 bg-transparent cursor-pointer">
                            <button onclick="clearDateFilter()" class="text-gray-400 hover:text-red-500 transition-colors ml-1" title="Clear Date Filter"><i class="fas fa-times-circle"></i></button>
                        </div>
                        
                        <button onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors shadow-sm border border-gray-300 w-full sm:w-auto whitespace-nowrap">
                            <i class="fas fa-print mr-2"></i>PRINT REPORT
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 font-bold tracking-wider">Date</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Event / Buyer Name</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Product Taken</th>
                                    <th class="px-6 py-4 font-bold tracking-wider text-center">Status</th>
                                    <th class="px-6 py-4 font-bold tracking-wider text-right print:hidden">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="logTableBody">
                                <?php
                                // Order by PENDING first so they are immediately visible at the top
                                $sql = "SELECT io.*, i.product_name 
                                        FROM inventory_outsourcing io 
                                        LEFT JOIN inventory i ON io.product_id = i.product_id 
                                        ORDER BY CASE WHEN io.status = 'PENDING' THEN 1 ELSE 2 END, io.record_date DESC, io.record_id DESC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        
                                        $raw_date = $row['record_date'];
                                        $date = date('M d, Y', strtotime($raw_date));
                                        $name = htmlspecialchars($row['buyer_name']);
                                        $product = htmlspecialchars($row['product_name']);
                                        $status = $row['status'];
                                        
                                        if ($status === 'PENDING') {
                                            $badge = "<span class='bg-orange-100 text-orange-800 px-2.5 py-1 rounded text-[10px] font-bold uppercase border border-orange-200'>PENDING RETURN</span>";
                                            $qty_text = "<span class='font-bold text-gray-800'>{$row['quantity_out']}</span> taken";
                                            $action_btn = "<button onclick='openReconcileModal({$row['record_id']}, {$row['product_id']}, \"" . addslashes($product) . "\", {$row['quantity_out']})' class='bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white py-1 px-3 rounded text-xs font-bold transition-colors shadow-sm'><i class='fas fa-clipboard-check mr-1'></i> RECONCILE</button>";
                                            $row_bg = "bg-orange-50/20";
                                        } else if ($status === 'RECONCILED') {
                                            $badge = "<span class='bg-blue-100 text-blue-800 px-2.5 py-1 rounded text-[10px] font-bold uppercase border border-blue-200'>RECONCILED</span>";
                                            // Showing clearly what was sold vs what was returned
                                            $total_taken = $row['quantity_out'] + $row['quantity_returned'];
                                            $qty_text = "<span class='text-gray-500'>{$total_taken} Total</span> | <span class='font-bold text-green-600'>{$row['quantity_out']} Sold</span> | <span class='font-bold text-blue-500'>{$row['quantity_returned']} Returned</span>";
                                            $action_btn = "<span class='text-gray-300 text-xs'><i class='fas fa-check'></i></span>";
                                            $row_bg = "";
                                        } else {
                                            $badge = "<span class='bg-green-100 text-green-800 px-2.5 py-1 rounded text-[10px] font-bold uppercase border border-green-200'>COMPLETED</span>";
                                            $qty_text = "<span class='font-bold text-gray-800'>{$row['quantity_out']}</span> Sold";
                                            $action_btn = "<span class='text-gray-300 text-xs'><i class='fas fa-check'></i></span>";
                                            $row_bg = "";
                                        }

                                        echo "<tr class='log-row hover:bg-purple-50 transition-colors {$row_bg}' data-date='{$raw_date}'>
                                                <td class='px-6 py-4 font-medium text-gray-500'>{$date}</td>
                                                <td class='px-6 py-4 font-bold text-gray-900 capitalize'>{$name}</td>
                                                <td class='px-6 py-4 text-gray-700'>
                                                    <div class='font-bold text-primary'>{$product}</div>
                                                    <div class='text-xs mt-0.5'>{$qty_text}</div>
                                                </td>
                                                <td class='px-6 py-4 text-center'>{$badge}</td>
                                                <td class='px-6 py-4 text-right print:hidden'>{$action_btn}</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-6 py-12 text-center text-gray-500'>No outsourcing or event logs found.</td></tr>";
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

        // --- UNIFIED SEARCH & DATE FILTER LOGIC ---
        function filterTable() {
            let searchText = document.getElementById('logSearch').value.toLowerCase();
            let startDate = document.getElementById('dateFilterStart').value;
            let endDate = document.getElementById('dateFilterEnd').value;
            let rows = document.querySelectorAll('.log-row');

            rows.forEach(row => {
                let textMatch = row.textContent.toLowerCase().includes(searchText);
                let dateMatch = true;

                if (startDate || endDate) {
                    let rowDateStr = row.dataset.date; // e.g. "2024-05-20"
                    
                    if (rowDateStr) {
                        let rowDate = new Date(rowDateStr);
                        rowDate.setHours(0,0,0,0);
                        
                        if (startDate) {
                            let sDate = new Date(startDate);
                            sDate.setHours(0,0,0,0);
                            if (rowDate < sDate) dateMatch = false;
                        }
                        
                        if (endDate) {
                            let eDate = new Date(endDate);
                            eDate.setHours(0,0,0,0);
                            if (rowDate > eDate) dateMatch = false;
                        }
                    }
                }

                row.style.display = (textMatch && dateMatch) ? '' : 'none';
            });
        }

        document.getElementById('logSearch').addEventListener('keyup', filterTable);
        document.getElementById('dateFilterStart').addEventListener('change', filterTable);
        document.getElementById('dateFilterEnd').addEventListener('change', filterTable);

        function clearDateFilter() {
            document.getElementById('dateFilterStart').value = '';
            document.getElementById('dateFilterEnd').value = '';
            filterTable();
        }

        // --- RECONCILE MODAL LOGIC ---
        function openReconcileModal(recordId, productId, productName, totalQty) {
            document.getElementById('rec_record_id').value = recordId;
            document.getElementById('rec_product_id').value = productId;
            document.getElementById('rec_product_name').innerText = productName;
            document.getElementById('rec_total_qty').innerText = totalQty;
            document.getElementById('rec_total_val').value = totalQty;
            
            document.getElementById('qty_sold').value = totalQty;
            document.getElementById('qty_returned').value = 0;
            
            document.getElementById('reconcileModal').classList.remove('hidden');
            document.getElementById('reconcileModal').classList.add('flex');
        }

        function closeReconcileModal() {
            document.getElementById('reconcileModal').classList.add('hidden');
            document.getElementById('reconcileModal').classList.remove('flex');
        }

        function calculateReturn() {
            let total = parseInt(document.getElementById('rec_total_val').value);
            let soldInput = document.getElementById('qty_sold');
            let sold = parseInt(soldInput.value) || 0;
            
            if(sold > total) {
                soldInput.value = total;
                sold = total;
            }
            if(sold < 0) {
                soldInput.value = 0;
                sold = 0;
            }
            document.getElementById('qty_returned').value = total - sold;
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