<?php 
include 'db.php'; 

// --- CENTRALIZED ARRAY OF MANAGEABLE CONFIG TABLES ---
$config_tables = [
    'config_occupations' => 'Membership: Occupations',
    'config_civil_status' => 'Membership: Civil Status',
    'config_monthly_income' => 'Membership: Monthly Incomes',
    'config_unit_types' => 'Inventory: Unit Types',
    'config_product_categories' => 'Inventory: Product Categories'
];

// --- HANDLE ADDING NEW ITEM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $table = $_POST['table_name'];
    $new_name = trim($conn->real_escape_string($_POST['new_name']));
    
    if (array_key_exists($table, $config_tables) && !empty($new_name)) {
        $conn->query("INSERT INTO $table (name) VALUES ('$new_name')");
        header("Location: database_management.php");
        exit();
    }
}

// --- HANDLE DELETING ITEM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item'])) {
    $table = $_POST['table_name'];
    $del_id = (int)$_POST['delete_id'];
    
    if (array_key_exists($table, $config_tables)) {
        $conn->query("DELETE FROM $table WHERE id = $del_id");
        header("Location: database_management.php");
        exit();
    }
}

// --- HANDLE EXCEL HEADER UPDATES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_excel_header'])) {
    $header_id = (int)$_POST['header_id'];
    $new_header_name = trim(strtolower($conn->real_escape_string($_POST['new_header_name'])));
    
    if (!empty($new_header_name)) {
        $conn->query("UPDATE config_excel_headers SET excel_header_name = '$new_header_name' WHERE id = $header_id");
        header("Location: database_management.php");
        exit();
    }
}

// --- HANDLE SETTINGS UPDATES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_setting'])) {
    $setting_key = $conn->real_escape_string($_POST['setting_key']);
    // Checkboxes only send value if checked. If not set, it means 0 (Off).
    $setting_value = isset($_POST['setting_value']) ? '1' : '0'; 
    
    $conn->query("UPDATE config_inventory_settings SET setting_value = '$setting_value' WHERE setting_key = '$setting_key'");
    header("Location: database_management.php");
    exit();
}

// Fetch current setting for negative stock
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
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .db-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            align-items: start;
        }
        .db-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
            border: 1px solid #eaeaea;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .db-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eaeaea;
            font-weight: 700;
            color: #6a1b9a;
            font-size: 15px;
            letter-spacing: 0.5px;
        }
        .db-body { padding: 20px; }
        .db-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .db-table th, .db-table td { padding: 10px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .db-table td:last-child { text-align: right; }
        .db-form { display: flex; gap: 10px; }
        .db-form input[type="text"] { flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        .btn-sm { padding: 6px 10px; font-size: 11px; }
        .del-btn { background: #ffebee; color: #c62828; border: none; border-radius: 4px; padding: 5px 8px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .del-btn:hover { background: #c62828; color: white; }
        
        /* Toggle Switch Styles */
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;}
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;}
        input:checked + .slider { background-color: #2e7d32; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo">
            </div>
            
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="database_management.php" class="menu-btn active">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-action-bar">
                <div>
                    <h1 class="page-title">System Configuration & Variables</h1>
                    <p style="color: #666; font-size: 14px; margin-top: 5px;">Manage dropdown menus, formats, and acceptable inputs for the entire system.</p>
                </div>
            </div>

            <div class="db-card" style="border-left: 4px solid #f57c00;">
                <div class="db-header" style="color: #f57c00;">System Settings</div>
                <div class="db-body">
                    <form method="POST" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #fafafa; border: 1px solid #eee; border-radius: 6px;">
                        <input type="hidden" name="update_setting" value="1">
                        <input type="hidden" name="setting_key" value="allow_negative_stock">
                        <div>
                            <strong style="font-size: 14px;">Allow Outsourcing Without Stock (Negative Inventory)</strong>
                            <p style="font-size: 12px; color: #777; margin-top: 4px;">If ON, the POS will allow adding items to the cart even if current stock is 0, resulting in negative stock numbers.</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span style="font-weight: bold; color: <?= $allow_negative ? '#2e7d32' : '#c62828' ?>;"><?= $allow_negative ? 'ON' : 'OFF' ?></span>
                            <label class="switch">
                                <input type="checkbox" name="setting_value" <?= $allow_negative ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            <div class="db-grid">
                
                <?php foreach($config_tables as $table_name => $title): ?>
                <div class="db-card">
                    <div class="db-header"><?= htmlspecialchars($title) ?></div>
                    <div class="db-body">
                        <table class="db-table">
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM $table_name ORDER BY name ASC");
                                if ($res && $res->num_rows > 0) {
                                    while($row = $res->fetch_assoc()) {
                                        echo "<tr>
                                                <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                                <td>
                                                    <form method='POST' style='margin:0;' onsubmit='return confirm(\"Are you sure you want to delete this option from the system?\");'>
                                                        <input type='hidden' name='delete_item' value='1'>
                                                        <input type='hidden' name='table_name' value='{$table_name}'>
                                                        <input type='hidden' name='delete_id' value='{$row['id']}'>
                                                        <button type='submit' class='del-btn'>X</button>
                                                    </form>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='2' style='color:#888; text-align:center;'>No data configured yet.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <form method="POST" class="db-form">
                            <input type="hidden" name="add_item" value="1">
                            <input type="hidden" name="table_name" value="<?= $table_name ?>">
                            <input type="text" name="new_name" placeholder="Add new option..." required>
                            <button type="submit" class="btn btn-primary btn-sm">+ ADD</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="db-card" style="grid-column: 1 / -1;">
                    <div class="db-header" style="color: #2e7d32;">Excel Import: Header Mappings</div>
                    <div class="db-body">
                        <p style="font-size: 13px; color: #555; margin-bottom: 15px;">Define the exact column names the system should look for when importing Excel files. Format must be exact (case-insensitive).</p>
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">System Field</th>
                                    <th style="text-align:left;">Expected Excel Header</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM config_excel_headers");
                                if ($res && $res->num_rows > 0) {
                                    while($row = $res->fetch_assoc()) {
                                        echo "<tr>
                                                <td><strong>" . htmlspecialchars($row['system_field']) . "</strong><br><small style='color:#888;'>{$row['description']}</small></td>
                                                <td>
                                                    <form method='POST' class='db-form' style='margin:0;'>
                                                        <input type='hidden' name='update_excel_header' value='1'>
                                                        <input type='hidden' name='header_id' value='{$row['id']}'>
                                                        <input type='text' name='new_header_name' value='" . htmlspecialchars($row['excel_header_name']) . "' required style='padding: 5px; width: 250px;'>
                                                        <button type='submit' class='btn btn-primary btn-sm'>UPDATE</button>
                                                    </form>
                                                </td>
                                                <td></td>
                                              </tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>