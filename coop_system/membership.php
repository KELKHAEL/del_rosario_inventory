<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Form - Coop DBMS</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">
        <!-- LEFT SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container">
                <h2>LOGO</h2>
            </div>
            <nav class="sidebar-menu">
                <a href="membership.php" class="menu-btn active">MEMBERSHIP FORM</a>
                <a href="index.php" class="menu-btn">TRANSACTIONS</a>
                <a href="#" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT SYSTEM</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <div class="top-action-bar">
                <h1 class="page-title">Create New Membership</h1>
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-secondary">BACK TO LIST</a>
                </div>
            </div>

            <!-- THE MEMBERSHIP FORM -->
            <div class="content-display">
                
                <!-- We will build the intricate form grid here in the next step -->
                <form id="membershipDataForm" action="process_membership.php" method="POST">
                    
                    <h3 style="text-align: center; margin-bottom: 20px;">PURPLE ARMY CONSUMERS COOPERATIVE<br>MEMBERSHIP FORM</h3>
                    
                    <p style="color: red; text-align: center;"><em>[The Form Input Grid will go here]</em></p>

                </form>

            </div>
        </main>
    </div>

</body>
</html>