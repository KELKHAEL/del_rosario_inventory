<?php 
include 'db.php'; 

// --- HANDLE FORM SUBMISSIONS (ADD / DELETE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Handle Additions
        if ($action === 'add_occ' && !empty($_POST['new_occupation'])) {
            $stmt = $conn->prepare("INSERT INTO config_occupations (name) VALUES (?)");
            $stmt->bind_param("s", trim($_POST['new_occupation']));
            $stmt->execute();
        } elseif ($action === 'add_inc' && !empty($_POST['new_income'])) {
            $stmt = $conn->prepare("INSERT INTO config_monthly_income (name) VALUES (?)");
            $stmt->bind_param("s", trim($_POST['new_income']));
            $stmt->execute();
        } elseif ($action === 'add_civ' && !empty($_POST['new_civil'])) {
            $stmt = $conn->prepare("INSERT INTO config_civil_status (name) VALUES (?)");
            $stmt->bind_param("s", trim($_POST['new_civil']));
            $stmt->execute();
        }
        
        // Handle Deletions
        elseif ($action === 'del_occ' && isset($_POST['id'])) {
            $stmt = $conn->prepare("DELETE FROM config_occupations WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
        } elseif ($action === 'del_inc' && isset($_POST['id'])) {
            $stmt = $conn->prepare("DELETE FROM config_monthly_income WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
        } elseif ($action === 'del_civ' && isset($_POST['id'])) {
            $stmt = $conn->prepare("DELETE FROM config_civil_status WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
        }

        // Redirect to prevent form resubmission on refresh
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
$excel_headers = fetchTable($conn, 'config_excel_headers');
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
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased overflow-hidden">

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
                
                <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[400px]">
                        <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                            <h3 class="font-bold text-gray-800"><i class="fas fa-briefcase text-primary mr-2"></i>Occupations</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4">
                            <ul class="divide-y divide-gray-100">
                                <?php foreach($occupations as $occ): ?>
                                    <li class="py-2 flex justify-between items-center group">
                                        <span class="text-sm text-gray-700"><?= htmlspecialchars($occ['name']) ?></span>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="del_occ">
                                            <input type="hidden" name="id" value="<?= $occ['id'] ?>">
                                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this occupation?')"><i class="fas fa-trash-alt text-xs"></i></button>
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
                                    <li class="py-2 flex justify-between items-center group">
                                        <span class="text-sm text-gray-700"><?= htmlspecialchars($inc['name']) ?></span>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="del_inc">
                                            <input type="hidden" name="id" value="<?= $inc['id'] ?>">
                                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this income bracket?')"><i class="fas fa-trash-alt text-xs"></i></button>
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
                                    <li class="py-2 flex justify-between items-center group">
                                        <span class="text-sm text-gray-700"><?= htmlspecialchars($civ['name']) ?></span>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="del_civ">
                                            <input type="hidden" name="id" value="<?= $civ['id'] ?>">
                                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors" onclick="return confirm('Delete this status?')"><i class="fas fa-trash-alt text-xs"></i></button>
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

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col md:col-span-2 lg:col-span-3">
                        <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl flex justify-between items-center">
                            <h3 class="font-bold text-gray-800"><i class="fas fa-file-excel text-green-700 mr-2"></i>Excel Import Mapping Definitions</h3>
                            <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-semibold border border-yellow-200">Advanced Config</span>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <p class="text-xs text-gray-500 mb-4">These define what column names the system looks for when uploading an Excel file. Modifying these incorrectly may break the Excel Upload tool.</p>
                            
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