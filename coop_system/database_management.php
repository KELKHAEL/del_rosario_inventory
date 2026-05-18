<?php 
session_start();
include 'db.php'; 

// --- HANDLE FORM SUBMISSIONS (ADD / DELETE / UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $msg = "";
        
        try {
            // Handle Additions
            if ($action === 'add_occ' && !empty($_POST['new_occupation'])) {
                $stmt = $conn->prepare("INSERT INTO config_occupations (name) VALUES (?)");
                $stmt->bind_param("s", trim($_POST['new_occupation']));
                $stmt->execute();
                $msg = "Occupation successfully added.";
            } elseif ($action === 'add_inc' && !empty($_POST['new_income'])) {
                $stmt = $conn->prepare("INSERT INTO config_monthly_income (name) VALUES (?)");
                $stmt->bind_param("s", trim($_POST['new_income']));
                $stmt->execute();
                $msg = "Income bracket successfully added.";
            } elseif ($action === 'add_civ' && !empty($_POST['new_civil'])) {
                $stmt = $conn->prepare("INSERT INTO config_civil_status (name) VALUES (?)");
                $stmt->bind_param("s", trim($_POST['new_civil']));
                $stmt->execute();
                $msg = "Civil status successfully added.";
            } elseif ($action === 'add_cat' && !empty($_POST['new_cat'])) {
                $stmt = $conn->prepare("INSERT INTO config_product_categories (name) VALUES (?)");
                $stmt->bind_param("s", trim($_POST['new_cat']));
                $stmt->execute();
                $msg = "Product category successfully added.";
            } elseif ($action === 'add_unit' && !empty($_POST['new_unit'])) {
                $stmt = $conn->prepare("INSERT INTO config_unit_types (name) VALUES (?)");
                $stmt->bind_param("s", trim($_POST['new_unit']));
                $stmt->execute();
                $msg = "Unit type successfully added.";
            }
            
            // Handle Deletions
            elseif ($action === 'del_occ' && isset($_POST['id'])) {
                $stmt = $conn->prepare("DELETE FROM config_occupations WHERE id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $msg = "Occupation removed.";
            } elseif ($action === 'del_inc' && isset($_POST['id'])) {
                $stmt = $conn->prepare("DELETE FROM config_monthly_income WHERE id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $msg = "Income bracket removed.";
            } elseif ($action === 'del_civ' && isset($_POST['id'])) {
                $stmt = $conn->prepare("DELETE FROM config_civil_status WHERE id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $msg = "Civil status removed.";
            } elseif ($action === 'del_cat' && isset($_POST['id'])) {
                $stmt = $conn->prepare("DELETE FROM config_product_categories WHERE id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $msg = "Product category removed.";
            } elseif ($action === 'del_unit' && isset($_POST['id'])) {
                $stmt = $conn->prepare("DELETE FROM config_unit_types WHERE id = ?");
                $stmt->bind_param("i", $_POST['id']);
                $stmt->execute();
                $msg = "Unit type removed.";
            }

            // Handle Edits (Updates)
            elseif ($action === 'edit_occ' && isset($_POST['id']) && !empty($_POST['edit_name'])) {
                $stmt = $conn->prepare("UPDATE config_occupations SET name = ? WHERE id = ?");
                $stmt->bind_param("si", trim($_POST['edit_name']), $_POST['id']);
                $stmt->execute();
                $msg = "Occupation updated.";
            } elseif ($action === 'edit_inc' && isset($_POST['id']) && !empty($_POST['edit_name'])) {
                $stmt = $conn->prepare("UPDATE config_monthly_income SET name = ? WHERE id = ?");
                $stmt->bind_param("si", trim($_POST['edit_name']), $_POST['id']);
                $stmt->execute();
                $msg = "Income bracket updated.";
            } elseif ($action === 'edit_civ' && isset($_POST['id']) && !empty($_POST['edit_name'])) {
                $stmt = $conn->prepare("UPDATE config_civil_status SET name = ? WHERE id = ?");
                $stmt->bind_param("si", trim($_POST['edit_name']), $_POST['id']);
                $stmt->execute();
                $msg = "Civil status updated.";
            } elseif ($action === 'edit_cat' && isset($_POST['id']) && !empty($_POST['edit_name'])) {
                $stmt = $conn->prepare("UPDATE config_product_categories SET name = ? WHERE id = ?");
                $stmt->bind_param("si", trim($_POST['edit_name']), $_POST['id']);
                $stmt->execute();
                $msg = "Product category updated.";
            } elseif ($action === 'edit_unit' && isset($_POST['id']) && !empty($_POST['edit_name'])) {
                $stmt = $conn->prepare("UPDATE config_unit_types SET name = ? WHERE id = ?");
                $stmt->bind_param("si", trim($_POST['edit_name']), $_POST['id']);
                $stmt->execute();
                $msg = "Unit type updated.";
            }
            
            // Handle Inventory Settings Toggle
            elseif ($action === 'update_inv_settings') {
                $allow_neg = isset($_POST['allow_negative']) ? '1' : '0';
                $stmt = $conn->prepare("UPDATE config_inventory_settings SET setting_value = ? WHERE setting_key = 'allow_negative_stock'");
                $stmt->bind_param("s", $allow_neg);
                $stmt->execute();
                $msg = "Inventory permissions updated successfully.";
            }

            if ($msg !== "") {
                $_SESSION['alert_title'] = "Success";
                $_SESSION['alert_message'] = $msg;
                $_SESSION['alert_type'] = "success";
            }
            
        } catch (Exception $e) {
            $_SESSION['alert_title'] = "Database Error";
            $_SESSION['alert_message'] = "An error occurred: " . $e->getMessage();
            $_SESSION['alert_type'] = "error";
        }

        header("Location: database_management.php");
        exit();
    }
}

// --- FETCH CURRENT DATA ---
function fetchTable($conn, $table) {
    $data = [];
    try {
        $res = $conn->query("SELECT * FROM $table ORDER BY id ASC");
        if ($res && $res->num_rows > 0) {
            while($row = $res->fetch_assoc()) { $data[] = $row; }
        }
    } catch (Exception $e) { /* Table might not exist yet */ }
    return $data;
}

$occupations = fetchTable($conn, 'config_occupations');
$incomes = fetchTable($conn, 'config_monthly_income');
$civil_statuses = fetchTable($conn, 'config_civil_status');
$categories = fetchTable($conn, 'config_product_categories');
$unit_types = fetchTable($conn, 'config_unit_types');
$excel_headers = fetchTable($conn, 'config_excel_headers');

$setting_res = $conn->query("SELECT setting_value FROM config_inventory_settings WHERE setting_key = 'allow_negative_stock'");
$allow_negative = 0;
if ($setting_res && $setting_res->num_rows > 0) {
    $allow_negative = (int)$setting_res->fetch_assoc()['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Coop DBMS</title>
    
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
    <style>
        input:checked ~ .toggle-bg { background-color: #6a1b9a; }
        input:checked ~ .toggle-dot { transform: translateX(100%); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased overflow-hidden">

    <div id="customAlertModal" class="fixed inset-0 z-[1000] hidden items-center justify-center p-4">
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

    <div class="flex h-screen w-full">

        <div id="mobile-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden transition-opacity" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="bg-white w-72 border-r border-gray-200 flex flex-col transition-transform transform -translate-x-full md:translate-x-0 fixed md:relative z-50 h-full shadow-lg md:shadow-none">
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
                <a href="inventory.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-boxes w-6"></i> INVENTORY
                </a>
                <a href="pos.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-shopping-cart w-6"></i> SELL / OUTSOURCE
                </a>
                <a href="outsourcing_report.php" class="flex items-center px-6 py-3 text-gray-600 hover:bg-purple-50 hover:text-primary font-semibold transition-colors">
                    <i class="fas fa-chart-line w-6"></i> OUTSOURCING LOGS
                </a>
                <a href="database_management.php" class="flex items-center px-6 py-3 bg-primary text-white font-semibold border-l-4 border-primaryDark">
                    <i class="fas fa-database w-6"></i> DATABASE SETTINGS
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative w-full">
            
            <header class="bg-white shadow-sm px-4 md:px-8 py-4 flex justify-between items-center z-10">
                <div class="flex items-center gap-4">
                    <button class="text-gray-500 focus:outline-none md:hidden hover:text-primary" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight">Database Settings</h1>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-7xl mx-auto">
                    
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                            <button onclick="switchTab('membership')" id="btn-membership" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-primary text-primary">
                                <i class="fas fa-user-edit mr-2"></i>Membership Form Settings
                            </button>
                            <button onclick="switchTab('inventory')" id="btn-inventory" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-boxes mr-2"></i>Inventory Settings
                            </button>
                            <button onclick="switchTab('excel')" id="btn-excel" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-file-excel mr-2"></i>Excel Imports
                            </button>
                        </nav>
                    </div>

                    <div id="tab-membership" class="tab-content grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[400px]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-briefcase text-primary mr-2"></i>Occupations</h3>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4">
                                <ul class="divide-y divide-gray-100">
                                    <?php foreach($occupations as $occ): ?>
                                        <li class="py-2 group">
                                            <div id="view_occ_<?= $occ['id'] ?>" class="flex justify-between items-center w-full">
                                                <span class="text-sm text-gray-700"><?= htmlspecialchars($occ['name']) ?></span>
                                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button type="button" onclick="toggleEdit('occ_<?= $occ['id'] ?>')" class="text-blue-500 hover:text-blue-700 transition-colors"><i class="fas fa-edit text-xs"></i></button>
                                                    <form method="POST" class="inline m-0">
                                                        <input type="hidden" name="action" value="del_occ">
                                                        <input type="hidden" name="id" value="<?= $occ['id'] ?>">
                                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this occupation?')"><i class="fas fa-trash-alt text-xs"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" id="edit_occ_<?= $occ['id'] ?>" class="hidden flex gap-2 w-full mt-1">
                                                <input type="hidden" name="action" value="edit_occ">
                                                <input type="hidden" name="id" value="<?= $occ['id'] ?>">
                                                <input type="text" name="edit_name" value="<?= htmlspecialchars($occ['name']) ?>" required class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                                                <button type="submit" class="text-green-600 hover:text-green-800 transition-colors"><i class="fas fa-check"></i></button>
                                                <button type="button" onclick="toggleEdit('occ_<?= $occ['id'] ?>')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times"></i></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="action" value="add_occ">
                                    <input type="text" name="new_occupation" placeholder="New Occupation..." required class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit" class="bg-primary hover:bg-primaryDark text-white px-3 py-1.5 rounded-md text-sm font-semibold transition-colors"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[400px]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-wallet text-green-600 mr-2"></i>Monthly Incomes</h3>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4">
                                <ul class="divide-y divide-gray-100">
                                    <?php foreach($incomes as $inc): ?>
                                        <li class="py-2 group">
                                            <div id="view_inc_<?= $inc['id'] ?>" class="flex justify-between items-center w-full">
                                                <span class="text-sm text-gray-700"><?= htmlspecialchars($inc['name']) ?></span>
                                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button type="button" onclick="toggleEdit('inc_<?= $inc['id'] ?>')" class="text-blue-500 hover:text-blue-700 transition-colors"><i class="fas fa-edit text-xs"></i></button>
                                                    <form method="POST" class="inline m-0">
                                                        <input type="hidden" name="action" value="del_inc">
                                                        <input type="hidden" name="id" value="<?= $inc['id'] ?>">
                                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this income bracket?')"><i class="fas fa-trash-alt text-xs"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" id="edit_inc_<?= $inc['id'] ?>" class="hidden flex gap-2 w-full mt-1">
                                                <input type="hidden" name="action" value="edit_inc">
                                                <input type="hidden" name="id" value="<?= $inc['id'] ?>">
                                                <input type="text" name="edit_name" value="<?= htmlspecialchars($inc['name']) ?>" required class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-green-600">
                                                <button type="submit" class="text-green-600 hover:text-green-800 transition-colors"><i class="fas fa-check"></i></button>
                                                <button type="button" onclick="toggleEdit('inc_<?= $inc['id'] ?>')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times"></i></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="action" value="add_inc">
                                    <input type="text" name="new_income" placeholder="e.g. 15,000 - 20,000" required class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-sm font-semibold transition-colors"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[400px]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-ring text-blue-500 mr-2"></i>Civil Status</h3>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4">
                                <ul class="divide-y divide-gray-100">
                                    <?php foreach($civil_statuses as $civ): ?>
                                        <li class="py-2 group">
                                            <div id="view_civ_<?= $civ['id'] ?>" class="flex justify-between items-center w-full">
                                                <span class="text-sm text-gray-700"><?= htmlspecialchars($civ['name']) ?></span>
                                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button type="button" onclick="toggleEdit('civ_<?= $civ['id'] ?>')" class="text-blue-500 hover:text-blue-700 transition-colors"><i class="fas fa-edit text-xs"></i></button>
                                                    <form method="POST" class="inline m-0">
                                                        <input type="hidden" name="action" value="del_civ">
                                                        <input type="hidden" name="id" value="<?= $civ['id'] ?>">
                                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this status?')"><i class="fas fa-trash-alt text-xs"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" id="edit_civ_<?= $civ['id'] ?>" class="hidden flex gap-2 w-full mt-1">
                                                <input type="hidden" name="action" value="edit_civ">
                                                <input type="hidden" name="id" value="<?= $civ['id'] ?>">
                                                <input type="text" name="edit_name" value="<?= htmlspecialchars($civ['name']) ?>" required class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                <button type="submit" class="text-green-600 hover:text-green-800 transition-colors"><i class="fas fa-check"></i></button>
                                                <button type="button" onclick="toggleEdit('civ_<?= $civ['id'] ?>')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times"></i></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="action" value="add_civ">
                                    <input type="text" name="new_civil" placeholder="New Status..." required class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm font-semibold transition-colors"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div id="tab-inventory" class="tab-content hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[400px]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-tags text-orange-500 mr-2"></i>Product Categories</h3>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4">
                                <ul class="divide-y divide-gray-100">
                                    <?php foreach($categories as $cat): ?>
                                        <li class="py-2 group">
                                            <div id="view_cat_<?= $cat['id'] ?>" class="flex justify-between items-center w-full">
                                                <span class="text-sm text-gray-700"><?= htmlspecialchars($cat['name']) ?></span>
                                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button type="button" onclick="toggleEdit('cat_<?= $cat['id'] ?>')" class="text-blue-500 hover:text-blue-700 transition-colors"><i class="fas fa-edit text-xs"></i></button>
                                                    <form method="POST" class="inline m-0">
                                                        <input type="hidden" name="action" value="del_cat">
                                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this category?')"><i class="fas fa-trash-alt text-xs"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" id="edit_cat_<?= $cat['id'] ?>" class="hidden flex gap-2 w-full mt-1">
                                                <input type="hidden" name="action" value="edit_cat">
                                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                <input type="text" name="edit_name" value="<?= htmlspecialchars($cat['name']) ?>" required class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500">
                                                <button type="submit" class="text-green-600 hover:text-green-800 transition-colors"><i class="fas fa-check"></i></button>
                                                <button type="button" onclick="toggleEdit('cat_<?= $cat['id'] ?>')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times"></i></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="action" value="add_cat">
                                    <input type="text" name="new_cat" placeholder="e.g. Canned Goods" required class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold transition-colors"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[400px]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-weight-hanging text-teal-500 mr-2"></i>Inventory Units</h3>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4">
                                <ul class="divide-y divide-gray-100">
                                    <?php foreach($unit_types as $unit): ?>
                                        <li class="py-2 group">
                                            <div id="view_unit_<?= $unit['id'] ?>" class="flex justify-between items-center w-full">
                                                <span class="text-sm text-gray-700"><?= htmlspecialchars($unit['name']) ?></span>
                                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button type="button" onclick="toggleEdit('unit_<?= $unit['id'] ?>')" class="text-blue-500 hover:text-blue-700 transition-colors"><i class="fas fa-edit text-xs"></i></button>
                                                    <form method="POST" class="inline m-0">
                                                        <input type="hidden" name="action" value="del_unit">
                                                        <input type="hidden" name="id" value="<?= $unit['id'] ?>">
                                                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this unit type?')"><i class="fas fa-trash-alt text-xs"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" id="edit_unit_<?= $unit['id'] ?>" class="hidden flex gap-2 w-full mt-1">
                                                <input type="hidden" name="action" value="edit_unit">
                                                <input type="hidden" name="id" value="<?= $unit['id'] ?>">
                                                <input type="text" name="edit_name" value="<?= htmlspecialchars($unit['name']) ?>" required class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-teal-500">
                                                <button type="submit" class="text-green-600 hover:text-green-800 transition-colors"><i class="fas fa-check"></i></button>
                                                <button type="button" onclick="toggleEdit('unit_<?= $unit['id'] ?>')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times"></i></button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="action" value="add_unit">
                                    <input type="text" name="new_unit" placeholder="e.g. Box, Liter" required class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white px-3 py-1.5 rounded-md text-sm font-semibold transition-colors"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-red-200 flex flex-col h-[400px]">
                            <div class="p-4 border-b border-gray-100 bg-red-50 rounded-t-xl">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-cogs text-red-500 mr-2"></i>System Controls</h3>
                            </div>
                            <div class="flex-1 p-6">
                                <form method="POST" class="flex flex-col gap-5 h-full">
                                    <input type="hidden" name="action" value="update_inv_settings">
                                    
                                    <div>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" name="allow_negative" value="1" class="sr-only" <?= $allow_negative === 1 ? 'checked' : '' ?>>
                                                <div class="block bg-gray-300 w-12 h-7 rounded-full transition-colors toggle-bg"></div>
                                                <div class="toggle-dot absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform"></div>
                                            </div>
                                            <div class="text-sm font-bold text-gray-800">Allow Negative Stock</div>
                                        </label>
                                        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                            If enabled, the POS will allow you to check out and outsource items even if the current master inventory is at 0. This creates discrepancies that you must review later.
                                        </p>
                                    </div>

                                    <button type="submit" class="mt-auto bg-gray-800 hover:bg-gray-900 text-white py-2 px-4 rounded-md text-sm font-semibold transition-colors w-full shadow-md"><i class="fas fa-save mr-2"></i>SAVE CONTROLS</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div id="tab-excel" class="tab-content hidden">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col">
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl flex justify-between items-center">
                                <h3 class="font-bold text-gray-800"><i class="fas fa-file-excel text-green-700 mr-2"></i>Excel Import Mapping Definitions</h3>
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-semibold border border-yellow-200">Advanced Config</span>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <p class="text-xs text-gray-500 mb-4">These define what column names the system looks for when uploading an Excel file. Our new system auto-detects aliases, but you can override them here if needed.</p>
                                
                                <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
                                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2 font-semibold">System Database Field</th>
                                            <th class="px-4 py-2 font-semibold">Expected Excel Header Name</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php if(empty($excel_headers)): ?>
                                            <tr><td colspan="2" class="px-4 py-4 text-center text-gray-400 italic">No custom mappings defined. Using system defaults.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($excel_headers as $eh): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-mono text-xs text-primary"><?= htmlspecialchars($eh['system_field']) ?></td>
                                                    <td class="px-4 py-2 font-medium text-gray-800"><?= htmlspecialchars($eh['excel_header_name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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

        // --- INLINE EDIT TOGGLE LOGIC ---
        function toggleEdit(id) {
            const viewDiv = document.getElementById('view_' + id);
            const editForm = document.getElementById('edit_' + id);
            
            if (viewDiv.classList.contains('hidden')) {
                viewDiv.classList.remove('hidden');
                editForm.classList.add('hidden');
            } else {
                viewDiv.classList.add('hidden');
                editForm.classList.remove('hidden');
                // Focus the input field automatically when opening edit mode
                editForm.querySelector('input[name="edit_name"]').focus();
            }
        }

        // --- TAB LOGIC ---
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = "tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            });

            document.getElementById('tab-' + tabId).classList.remove('hidden');
            const activeBtn = document.getElementById('btn-' + tabId);
            activeBtn.className = "tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-primary text-primary";
            localStorage.setItem('activeDbTab', tabId);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const activeTab = localStorage.getItem('activeDbTab') || 'membership';
            switchTab(activeTab);
        });

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