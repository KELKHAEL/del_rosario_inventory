<?php 
include 'db.php'; 

// --- UNDO & DELETE LOG FUNCTION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_log'])) {
    $del_id = (int)$_POST['delete_product_id'];
    $del_payment = $conn->real_escape_string($_POST['delete_payment']);
    $del_receipt = $conn->real_escape_string($_POST['delete_receipt']);
    
    // Find exactly how much stock was outsourced for this specific transaction
    $sum_res = $conn->query("SELECT SUM(quantity_out) as tot FROM inventory_outsourcing WHERE product_id=$del_id AND payment_method='$del_payment' AND receipt_no='$del_receipt'");
    
    if ($sum_res && $sum_res->num_rows > 0) {
        $tot_to_restore = (int)$sum_res->fetch_assoc()['tot'];
        if ($tot_to_restore > 0) {
            $conn->query("UPDATE inventory SET current_quantity = current_quantity + $tot_to_restore WHERE product_id=$del_id");
        }
    }
    
    $conn->query("DELETE FROM inventory_outsourcing WHERE product_id=$del_id AND payment_method='$del_payment' AND receipt_no='$del_receipt'");
    header("Location: outsourcing_report.php");
    exit();
}

// --- FILTER LOGIC (CALENDAR) ---
$filter_date = $_GET['filter_date'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$sort_option = $_GET['sort'] ?? 'date_desc';
$time_display = "All Time";

// Included the buyer_name and buyer_contact columns
$sql = "SELECT i.product_id, i.product_name, i.product_type, i.price, i.quantity_type, 
               o.payment_method, o.receipt_no, o.buyer_name, o.buyer_contact,
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

// Group by the transaction identifiers (including the new buyer info)
$sql .= " GROUP BY i.product_id, i.product_name, i.product_type, i.price, i.quantity_type, o.payment_method, o.receipt_no, o.buyer_name, o.buyer_contact";

$order_by = "latest_date DESC, i.product_name ASC"; 
if ($sort_option === 'name_asc') $order_by = "i.product_type ASC, i.product_name ASC";
if ($sort_option === 'qty_desc') $order_by = "total_qty_out DESC, i.product_name ASC";
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
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight">Outsourcing & Sales Records</h1>
                </div>
                <button onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors shadow-sm flex items-center">
                    <i class="fas fa-print mr-2"></i> PRINT
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 print:hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-orange-200 p-6 flex flex-col bg-orange-50 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10"><i class="fas fa-box-open text-6xl text-orange-900"></i></div>
                        <div class="text-sm font-semibold text-orange-600 mb-1 uppercase relative z-10">Units Outsourced</div>
                        <div class="text-4xl font-bold text-orange-800 relative z-10"><?= number_format($total_items_out) ?></div>
                        <div class="text-xs text-orange-500 mt-2 font-medium relative z-10"><i class="far fa-calendar-alt mr-1"></i> Timeframe: <?= $time_display ?></div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-green-200 p-6 flex flex-col bg-green-50 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10"><i class="fas fa-money-bill-wave text-6xl text-green-900"></i></div>
                        <div class="text-sm font-semibold text-green-600 mb-1 uppercase relative z-10">Total Value (PHP)</div>
                        <div class="text-4xl font-bold text-green-800 relative z-10">₱<?= number_format($total_value_out, 2) ?></div>
                        <div class="text-xs text-green-500 mt-2 font-medium relative z-10"><i class="far fa-calendar-alt mr-1"></i> Timeframe: <?= $time_display ?></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                    
                    <div class="bg-gray-50 px-6 py-5 border-b border-gray-200 print:hidden">
                        <form action="outsourcing_report.php" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                            
                            <div class="w-full md:w-auto">
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter by Exact Day</label>
                                <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>" onchange="document.getElementById('m_pick').value=''; this.form.submit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                            </div>
                            
                            <div class="w-full md:w-auto flex items-center justify-center font-bold text-gray-400 pb-2">OR</div>
                            
                            <div class="w-full md:w-auto">
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter by Month/Year</label>
                                <input type="month" id="m_pick" name="filter_month" value="<?= htmlspecialchars($filter_month) ?>" onchange="document.querySelector('input[name=\'filter_date\']').value=''; this.form.submit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                            </div>

                            <div class="hidden md:block border-l border-gray-300 h-10 mx-2"></div>

                            <div class="w-full md:w-auto">
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sort By</label>
                                <select name="sort" onchange="this.form.submit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                    <option value="date_desc" <?= $sort_option == 'date_desc' ? 'selected' : '' ?>>Most Recent First</option>
                                    <option value="name_asc" <?= $sort_option == 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                                    <option value="qty_desc" <?= $sort_option == 'qty_desc' ? 'selected' : '' ?>>Highest Quantity</option>
                                </select>
                            </div>

                            <a href="outsourcing_report.php" class="w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md text-sm transition-colors text-center shadow-sm">
                                CLEAR
                            </a>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 font-bold tracking-wider">Date</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Ref / Inv No.</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Buyer Details</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Product Name</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Payment</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Total OUT</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Total Value</th>
                                    <th class="px-6 py-4 font-bold tracking-wider text-right print:hidden">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php
                                if (count($report_data) > 0) {
                                    $current_group = "";
                                    foreach($report_data as $row) {
                                        
                                        // Header Grouping Colors
                                        if ($row['payment_method'] === 'Pay Later') {
                                            $bg_color = 'bg-red-50'; $text_color = 'text-red-700'; $border_color = 'border-red-200';
                                            $row_bg = 'bg-red-50/30 hover:bg-red-50'; // Subtle tint for the data row
                                        } elseif ($row['payment_method'] === 'GCash') {
                                            $bg_color = 'bg-blue-50'; $text_color = 'text-blue-700'; $border_color = 'border-blue-200';
                                            $row_bg = 'hover:bg-blue-50';
                                        } else {
                                            $bg_color = 'bg-orange-50'; $text_color = 'text-orange-700'; $border_color = 'border-orange-200';
                                            $row_bg = 'hover:bg-orange-50';
                                        }

                                        $group_title = strtoupper($row['product_type']) . " - " . strtoupper($row['payment_method']);
                                        
                                        if ($current_group !== $group_title) {
                                            $current_group = $group_title;
                                            echo "<tr class='{$bg_color} border-t-2 {$border_color}'>
                                                    <td colspan='8' class='px-6 py-3 font-extrabold {$text_color} tracking-wider'>{$current_group}</td>
                                                  </tr>";
                                        }
                                        
                                        $date = date('M d, Y', strtotime($row['latest_date']));
                                        $val = $row['total_qty_out'] * $row['price'];

                                        // Dynamic Payment Badges
                                        if ($row['payment_method'] === 'Pay Later') {
                                            $payment_display = "<span class='font-bold text-red-600 mr-2'>PAY LATER</span><span class='inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-600 text-white'>PENDING</span>";
                                            $receipt_display = "<span class='italic font-bold text-red-400'>PENDING</span>";
                                        } else {
                                            $payment_display = "<span class='font-semibold text-gray-800'>" . htmlspecialchars($row['payment_method']) . "</span>";
                                            $receipt_display = "<span class='bg-gray-100 border border-gray-200 px-2.5 py-1 rounded-md text-xs font-mono font-bold text-gray-700'>" . htmlspecialchars($row['receipt_no']) . "</span>";
                                        }

                                        // Buyer details safely constructed
                                        $buyer_info = "<div class='flex flex-col'>";
                                        if (!empty($row['buyer_name'])) {
                                            $buyer_info .= "<span class='font-bold text-gray-800 capitalize'>".htmlspecialchars($row['buyer_name'])."</span>";
                                        }
                                        if (!empty($row['buyer_contact'])) {
                                            $buyer_info .= "<span class='text-xs text-gray-500'>".htmlspecialchars($row['buyer_contact'])."</span>";
                                        }
                                        if (empty($row['buyer_name']) && empty($row['buyer_contact'])) {
                                            $buyer_info .= "<span class='italic text-gray-400'>N/A</span>";
                                        }
                                        $buyer_info .= "</div>";

                                        echo "<tr class='{$row_bg} transition-colors'>
                                                <td class='px-6 py-4 text-gray-500'>{$date}</td>
                                                <td class='px-6 py-4'>{$receipt_display}</td>
                                                <td class='px-6 py-4'>{$buyer_info}</td>
                                                <td class='px-6 py-4 font-bold text-gray-900'>" . htmlspecialchars($row['product_name']) . "</td>
                                                <td class='px-6 py-4'>{$payment_display}</td>
                                                <td class='px-6 py-4'><strong class='text-red-600 text-base'>-{$row['total_qty_out']}</strong> <span class='text-gray-400 text-xs ml-1'>{$row['quantity_type']}s</span></td>
                                                <td class='px-6 py-4 font-bold text-gray-900'>₱" . number_format($val, 2) . "</td>
                                                <td class='px-6 py-4 text-right print:hidden'>
                                                    <form action='outsourcing_report.php' method='POST' class='m-0 inline-block' onsubmit='return confirm(\"Undo this log? The {$row['total_qty_out']} item(s) will be returned to your Master Inventory.\");'>
                                                        <input type='hidden' name='delete_product_id' value='{$row['product_id']}'>
                                                        <input type='hidden' name='delete_payment' value='{$row['payment_method']}'>
                                                        <input type='hidden' name='delete_receipt' value='" . htmlspecialchars($row['receipt_no'], ENT_QUOTES) . "'>
                                                        <button type='submit' name='delete_log' class='bg-white hover:bg-red-50 text-red-600 border border-red-200 font-semibold py-1.5 px-3 rounded shadow-sm text-xs transition-colors'><i class='fas fa-undo-alt mr-1'></i> UNDO</button>
                                                    </form>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='px-6 py-12 text-center text-gray-500'>No records found for this timeframe.</td></tr>";
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
    </script>
</body>
</html>