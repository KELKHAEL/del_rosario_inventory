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
    
    // Security Check: Ensure the table being posted actually exists in our whitelist
    if (array_key_exists($table, $config_tables) && !empty($new_name)) {
        $conn->query("INSERT INTO $table (name) VALUES ('$new_name')");
        echo "<script>window.location.href='database_management.php';</script>";
        exit();
    }
}

// --- HANDLE DELETING ITEM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item'])) {
    $table = $_POST['table_name'];
    $del_id = (int)$_POST['delete_id'];
    
    // Security Check
    if (array_key_exists($table, $config_tables)) {
        $conn->query("DELETE FROM $table WHERE id = $del_id");
        echo "<script>window.location.href='database_management.php';</script>";
        exit();
    }
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
        .db-body {
            padding: 20px;
        }
        .db-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .db-table th, .db-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .db-table td:last-child {
            text-align: right;
        }
        .db-form {
            display: flex;
            gap: 10px;
        }
        .db-form input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
        }
        .btn-sm {
            padding: 6px 10px;
            font-size: 11px;
        }
        .del-btn {
            background: #ffebee;
            color: #c62828;
            border: none;
            border-radius: 4px;
            padding: 5px 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }
        .del-btn:hover {
            background: #c62828;
            color: white;
        }
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

            <div class="db-grid">
                <?php foreach($config_tables as $table_name => $title): ?>
                
                <div class="db-card">
                    <div class="db-header">
                        <?= htmlspecialchars($title) ?>
                    </div>
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
            </div>

        </main>
    </div>

</body>
</html>