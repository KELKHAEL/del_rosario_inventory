<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooperative DBMS</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Using a clean Google Font for better UI/UX -->
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
                <a href="#" class="menu-btn">TRANSACTIONS</a>
                <a href="#" class="menu-btn">INVENTORY MANAGEMENT</a>
                <a href="#" class="menu-btn">DATABASE MANAGEMENT SYSTEM</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <!-- Top Action Bar (Edit/Print/Upload) -->
            <div class="top-action-bar">
                <h1 class="page-title">Membership Management</h1>
                <div class="action-buttons">
                    <!-- Excel Upload Form -->
                    <form action="upload_excel.php" method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="excel_file" accept=".xls,.xlsx" required>
                        <button type="submit" class="btn btn-primary">UPLOAD EXCEL</button>
                    </form>
                    <button class="btn btn-secondary">EDIT</button>
                    <button class="btn btn-secondary">PRINT</button>
                </div>
            </div>

            <!-- This is where the Form or the List will be displayed -->
            <div class="content-display" id="main-display">
                <!-- Content will be injected here in the next step -->
            </div>
        </main>
    </div>

</body>
</html>