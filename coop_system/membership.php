<?php 
include 'db.php'; 

// Fetch Dynamic Configurations
// Fail-Safe Configuration Fetcher
function fetchConfig($conn, $table, $default_fallback = []) {
    $data = [];
    try {
        $res = $conn->query("SELECT name FROM $table ORDER BY name ASC");
        if ($res && $res->num_rows > 0) {
            while($row = $res->fetch_assoc()) {
                $data[] = $row['name'];
            }
        } else {
            return $default_fallback; // Return default if empty
        }
    } catch (Exception $e) {
        // If table doesn't exist, don't crash. Just return the default fallback.
        return $default_fallback; 
    }
    return $data;
}

// Fetch dynamic data, with safe defaults if the database tables are missing or empty
$occupations = fetchConfig($conn, 'config_occupations', ['Private Employee', 'Gov\'t Employee', 'Self-Employed', 'Others']);
$incomes = fetchConfig($conn, 'config_monthly_income', ['Below 5,000', '5,000 - 9,999', '10,000+']);
$civil_statuses = fetchConfig($conn, 'config_civil_status', ['Single', 'Married', 'Widowed']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Form - Coop DBMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="img/purplearmy_logo-removebg.png" alt="Coop Logo">
            </div>
            
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn active">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="database_management.php" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Create New Membership</h1>
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-secondary">BACK TO LIST</a>
                </div>
            </div>

            <div class="form-section">
                        <h4>Personal Information</h4>
                        
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Last Name (Surname)</label>
                                <input type="text" name="last_name" pattern="[A-Za-z\s\-]+" title="Letters and spaces only" required>
                            </div>
                            <div class="input-group">
                                <label>First Name (Given Name)</label>
                                <input type="text" name="first_name" pattern="[A-Za-z\s\-]+" title="Letters and spaces only" required>
                            </div>
                            <div class="input-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" pattern="[A-Za-z\s\-]+" title="Letters and spaces only">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="input-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" required>
                            </div>
                            <div class="input-group">
                                <label>Birth Place</label>
                                <input type="text" name="birth_place" pattern="[A-Za-z\s\-]+" title="Letters and spaces only">
                            </div>
                            <div class="input-group">
                                <label>Civil Status</label>
                                <select name="civil_status">
                                    <option value="" disabled selected>Select Status</option>
                                    <?php foreach($civil_statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="input-group">
                                <label>Religion</label>
                                <input type="text" name="religion" pattern="[A-Za-z\s]+" title="Letters only">
                            </div>
                            <div class="input-group">
                                <label>Sex</label>
                                <select name="sex">
                                    <option value="" disabled selected>Select Sex</option>
                                    <option value="MALE">Male</option>
                                    <option value="FEMALE">Female</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Tribe</label>
                                <input type="text" name="tribe" pattern="[A-Za-z\s]+" title="Letters only">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="input-group">
                                <label>SSS/GSIS No.</label>
                                <input type="text" name="sss_gsis_no" pattern="[\d\-]+" title="Numbers and dashes only">
                            </div>
                            <div class="input-group">
                                <label>TIN No.</label>
                                <input type="text" name="tin_no" pattern="[\d\-]+" title="Numbers and dashes only">
                            </div>
                            <div class="input-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" pattern="\d{4}" title="Must be exactly 4 digits" maxlength="4">
                            </div>
                        </div>

                        <div class="form-grid one-col">
                            <div class="input-group">
                                <label>Address</label>
                                <input type="text" name="address" required>
                            </div>
                            <div class="input-group">
                                <label>Business/Office Address</label>
                                <input type="text" name="business_office_address">
                            </div>
                        </div>

                        <div class="form-grid two-col">
                            <div class="input-group">
                                <label>Educational Attainment</label>
                                <input type="text" name="educational_attainment" pattern="[A-Za-z\s\.\-]+" title="Letters and basic punctuation only">
                            </div>
                            <div class="input-group">
                                <label>Present Employment / Business Activities</label>
                                <input type="text" name="present_employment_business" pattern="[A-Za-z\s\.\-]+" title="Letters and basic punctuation only">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Beneficiaries (Optional - Max 20)</h4>
                        <table class="ben-table" id="beneficiaryTable">
                            <thead>
                                <tr>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Date of Birth</th>
                                    <th>Relationship</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="ben-tbody">
                                </tbody>
                        </table>
                        <button type="button" class="btn" id="addBenBtn" style="margin-top: 10px;">+ Add Beneficiary</button>
                    </div>

                    <div class="form-section">
                        <div class="form-grid two-col">
                            
                            <div class="input-group">
                                <h4>Occupation</h4>
                                <div class="radio-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border: 1px solid #ccc; padding: 15px; border-radius: 4px;">
                                    <?php foreach($occupations as $occ): ?>
                                        <label class="radio-item"><input type="radio" name="occupation" value="<?= htmlspecialchars($occ) ?>"> <?= htmlspecialchars($occ) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="input-group">
                                <h4>Monthly Income</h4>
                                <div class="radio-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border: 1px solid #ccc; padding: 15px; border-radius: 4px;">
                                    <?php foreach($incomes as $inc): ?>
                                        <label class="radio-item"><input type="radio" name="monthly_income" value="<?= htmlspecialchars($inc) ?>"> <?= htmlspecialchars($inc) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <hr style="margin: 30px 0; border: 1px solid #ddd;">
                    
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">SAVE MEMBERSHIP RECORD</button>
                    </div>

<script>
    const addBtn = document.getElementById('addBenBtn');
    const tbody = document.getElementById('ben-tbody');
    let rowCount = 0;

    addBtn.addEventListener('click', function() {
        if(rowCount >= 20) {
            alert("Maximum of 20 beneficiaries allowed.");
            return;
        }
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="ben_last_name[]" placeholder="Last Name" required></td>
            <td><input type="text" name="ben_first_name[]" placeholder="First Name" required></td>
            <td><input type="text" name="ben_middle_name[]" placeholder="M.I."></td>
            <td><input type="date" name="ben_dob[]"></td>
            <td><input type="text" name="ben_rel[]" placeholder="e.g. Spouse" required></td>
            <td><button type="button" class="btn-remove-row" title="Remove" onclick="this.closest('tr').remove(); rowCount--;">&#10005;</button></td>
        `;
        tbody.appendChild(tr);
        rowCount++;
    });
</script>

</body>
</html>