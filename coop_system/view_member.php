<?php 
include 'db.php'; 

// Fetch the member ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid Member ID'); window.location.href='index.php';</script>";
    exit();
}

$member_id = (int)$_GET['id'];

// 1. Fetch Member Data
$stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member_result = $stmt->get_result();

if ($member_result->num_rows === 0) {
    echo "<script>alert('Member not found!'); window.location.href='index.php';</script>";
    exit();
}
$member = $member_result->fetch_assoc();
$stmt->close();

// 2. Fetch Beneficiaries Data
$stmt_ben = $conn->prepare("SELECT * FROM beneficiaries WHERE member_id = ?");
$stmt_ben->bind_param("i", $member_id);
$stmt_ben->execute();
$beneficiaries_result = $stmt_ben->get_result();
$beneficiaries = [];
while($b_row = $beneficiaries_result->fetch_assoc()) {
    $beneficiaries[] = $b_row;
}
$stmt_ben->close();

// Formatted Data
$formatted_id = "#26-" . str_pad($member['member_id'], 3, '0', STR_PAD_LEFT);
$dob = !empty($member['date_of_birth']) ? date('F d, Y', strtotime($member['date_of_birth'])) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Member - <?= htmlspecialchars($member['last_name']) ?></title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        .a4-paper {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
            color: #000;
            position: relative;
        }

        /* --- PERFECTED HEADER LAYOUT --- */
        .coop-header-container {
            position: relative;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Top Left: Logo */
        .coop-header-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 85px;
            height: auto;
        }

        /* Center: Text */
        .coop-header-text {
            text-align: center;
        }

        .coop-header-text h2 {
            margin: auto;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
        }
        
        .coop-header-text h5 {
            margin: 2px 0;
            font-size: 11px;
            font-weight: normal;
        }

        /* Top Right: 1x1 Photo Box */
        .photo-box {
            position: absolute;
            right: 0;
            top: 0;
            width: 1in;
            height: 1in;
            border: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #555;
            text-align: center;
        }
        
        /* The Title Block Below Header */
        .title-block {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .form-no-text { 
            position: absolute;
            left: 0;
            top: 0;
            font-weight: bold; 
            font-size: 14px;
            text-align: left;
        }

        .title-block h3 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
            text-decoration: underline;
        }

        /* ------------------------------- */
        
        .section-header { background-color: #570591; color: white; padding: 5px 10px; font-size: 14px; font-weight: bold; text-transform: uppercase; margin-top: 20px;}
        
        .form-row { display: flex; border-left: 1px solid #000; border-bottom: 1px solid #000; border-right: 1px solid #000; }
        .form-row:first-of-type { border-top: 1px solid #000; }
        
        .form-col { flex: 1; padding: 5px 8px; border-right: 1px solid #000; display: flex; flex-direction: column; }
        .form-col:last-child { border-right: none; }
        
        .field-label { font-size: 10px; color: #555; text-transform: uppercase; margin-bottom: 3px; }
        .field-value { font-size: 14px; font-weight: bold; text-transform: uppercase; min-height: 18px;}

        .paper-table { width: 100%; border-collapse: collapse; margin-top: 5px; border: 1px solid #000;}
        .paper-table th, .paper-table td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px; }
        .paper-table th { background-color: #f0f0f0; text-transform: uppercase; font-size: 10px; }

        .certification-section {
            margin-top: 40px;
            font-size: 14px;
        }
        .certification-text {
            text-indent: 40px;
            line-height: 1.6;
            margin-bottom: 50px;
        }
        .signature-wrapper {
            display: flex;
            justify-content: flex-end;
        }
        .sig-box { 
            width: 300px; 
            text-align: center; 
        }
        .sig-line { 
            border-bottom: 1px solid #000; 
            height: 20px; 
            margin-bottom: 5px; 
        }
        .sig-label { 
            font-size: 12px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }

        /* PRINT MEDIA QUERY */
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .sidebar { display: none !important; }
            .top-action-bar { display: none !important; }
            .main-content { padding: 0 !important; margin: 0 !important; overflow: visible !important; }
            .a4-paper { 
                width: 100% !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important; 
                border: none !important; 
            }
            @page { size: portrait; margin: 10mm; }
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container"><img src="img/purplearmy_logo-removebg.png" alt="Coop Logo"></div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-btn active">MEMBERSHIP DIRECTORY</a>
                <a href="transactions.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="pos.php" class="menu-btn">SELL / OUTSOURCE (CART)</a>
                <a href="outsourcing_report.php" class="menu-btn">OUTSOURCING LOGS</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT</a>
            </nav>
        </aside>

        <main class="main-content">
            
            <div class="top-action-bar">
                <h1 class="page-title">View Member Record</h1>
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">&larr; BACK</a>
                    <button class="btn btn-primary" onclick="window.print()" style="background-color: #570591;">PRINT FORM</button>
                </div>
            </div>

            <div class="a4-paper">
                
                <div class="coop-header-container">
                    <img src="img/purplearmy_logo-removebg.png" alt="Purple Army Logo" class="coop-header-logo">
                    
                    <div class="coop-header-text">
                        <h2>PURPLE ARMY CONSUMERS COOPERATIVE</h2>
                        <h5>428 A Soriano Highway, Amaya II, Tanza, Cavite</h5>
                        <h5>purplearmycooperative@gmail.com</h5>
                        <h5>09338243704/09569447343</h5>
                    </div>
                    
                    <div class="photo-box">1" x 1"<br>Photo</div>
                </div>

                <div class="title-block">
                    <div class="form-no-text">Form No. <span style="text-decoration: underline; font-weight: normal;"><?= $formatted_id ?></span></div>
                    <h3>MEMBERSHIP PROFILE</h3>
                </div>

                <div class="section-header">I. Personal Information</div>
                <div class="form-row" style="border-top: 1px solid #000;">
                    <div class="form-col"><span class="field-label">Last Name (Surname)</span><span class="field-value"><?= htmlspecialchars($member['last_name']) ?></span></div>
                    <div class="form-col"><span class="field-label">First Name</span><span class="field-value"><?= htmlspecialchars($member['first_name']) ?></span></div>
                    <div class="form-col"><span class="field-label">Middle Name</span><span class="field-value"><?= htmlspecialchars($member['middle_name']) ?></span></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><span class="field-label">Date of Birth</span><span class="field-value"><?= $dob ?></span></div>
                    <div class="form-col" style="flex: 1.5;"><span class="field-label">Birth Place</span><span class="field-value"><?= htmlspecialchars($member['birth_place']) ?></span></div>
                    <div class="form-col"><span class="field-label">Civil Status</span><span class="field-value"><?= htmlspecialchars($member['civil_status']) ?></span></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><span class="field-label">Religion</span><span class="field-value"><?= htmlspecialchars($member['religion']) ?></span></div>
                    <div class="form-col"><span class="field-label">Sex</span><span class="field-value"><?= htmlspecialchars($member['sex']) ?></span></div>
                    <div class="form-col"><span class="field-label">Tribe</span><span class="field-value"><?= htmlspecialchars($member['tribe']) ?></span></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><span class="field-label">SSS / GSIS No.</span><span class="field-value"><?= htmlspecialchars($member['sss_gsis_no']) ?></span></div>
                    <div class="form-col"><span class="field-label">TIN No.</span><span class="field-value"><?= htmlspecialchars($member['tin_no']) ?></span></div>
                    <div class="form-col"><span class="field-label">Postal Code</span><span class="field-value"><?= htmlspecialchars($member['postal_code']) ?></span></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><span class="field-label">Address</span><span class="field-value"><?= htmlspecialchars($member['address']) ?></span></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><span class="field-label">Business / Office Address</span><span class="field-value"><?= htmlspecialchars($member['business_office_address']) ?></span></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><span class="field-label">Educational Attainment</span><span class="field-value"><?= htmlspecialchars($member['educational_attainment']) ?></span></div>
                    <div class="form-col"><span class="field-label">Present Employment / Business Activities</span><span class="field-value"><?= htmlspecialchars($member['present_employment_business']) ?></span></div>
                </div>

                <div class="section-header">II. Beneficiaries</div>
                <table class="paper-table">
                    <thead>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>M.I.</th>
                            <th>Date of Birth</th>
                            <th>Relationship</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($beneficiaries) > 0): ?>
                            <?php foreach($beneficiaries as $ben): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ben['last_name']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($ben['first_name']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($ben['middle_name'] ?? '') ?></strong></td>
                                    <td><strong><?= !empty($ben['date_of_birth']) ? date('M d, Y', strtotime($ben['date_of_birth'])) : '' ?></strong></td>
                                    <td><strong><?= htmlspecialchars($ben['relationship']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #888;">No beneficiaries listed.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="section-header">III. Occupation & Income</div>
                <div class="form-row" style="border-top: 1px solid #000;">
                    <div class="form-col"><span class="field-label">Occupation</span><span class="field-value"><?= htmlspecialchars($member['occupation']) ?></span></div>
                    <div class="form-col"><span class="field-label">Monthly Income</span><span class="field-value"><?= htmlspecialchars($member['monthly_income']) ?></span></div>
                </div>

                <div class="certification-section">
                    <p class="certification-text">
                        I hereby certify that the above information is true and correct, signed this ____ day of __________________ , _______.
                    </p>
                    <div class="signature-wrapper">
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <div class="sig-label">Signature over printed name</div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>