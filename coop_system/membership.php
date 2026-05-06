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
        <!-- LEFT SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container">
                <h2>LOGO</h2>
            </div>
            <nav class="sidebar-menu">
                <a href="membership.php" class="menu-btn active">MEMBERSHIP FORM</a>
                <a href="index.php" class="menu-btn">TRANSACTIONS</a>
                <a href="inventory.php" class="menu-btn">INVENTORY MANAGEMENT</a>
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

            <!-- MEMBER INFO SECTION -->
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
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
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

                    <!-- BENEFICIARIES SECTION -->
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
                                <!-- JS injects rows here -->
                            </tbody>
                        </table>
                        <button type="button" class="btn" id="addBenBtn" style="margin-top: 10px;">+ Add Beneficiary</button>
                    </div>

                    <!-- OCCUPATION AND INCOME SECTION -->
                    <div class="form-section">
                        <div class="form-grid two-col">
                            
                            <!-- Occupation Radio Group -->
                            <div class="input-group">
                                <h4>Occupation</h4>
                                <div class="radio-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border: 1px solid #ccc; padding: 15px; border-radius: 4px;">
                                    <label class="radio-item"><input type="radio" name="occupation" value="Private Employee"> Private Employee</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Gov't Employee"> Gov't Employee</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Self-Employed"> Self-Employed</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Farmer"> Farmer</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Pensioner"> Pensioner</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Student"> Student</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="House Keeper"> House Keeper</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Fisher folk"> Fisher folk</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Entrepreneur/Vendor"> Entrepreneur/Vendor</label>
                                    <label class="radio-item"><input type="radio" name="occupation" value="Others"> Others</label>
                                </div>
                            </div>

                            <!-- Monthly Income Radio Group -->
                            <div class="input-group">
                                <h4>Monthly Income</h4>
                                <div class="radio-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border: 1px solid #ccc; padding: 15px; border-radius: 4px;">
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="0-999"> 0 - 999</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="2,000-2,999"> 2,000 - 2,999</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="4,000-4,999"> 4,000 - 4,999</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="10,000-15,000"> 10,000 - 15,000</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="1,000-1,999"> 1,000 - 1,999</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="3,000-3,999"> 3,000 - 3,999</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="5,000-9,999"> 5,000 - 9,999</label>
                                    <label class="radio-item"><input type="radio" name="monthly_income" value="15,000+"> 15,000 +</label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <hr style="margin: 30px 0; border: 1px solid #ddd;">
                    
                    <!-- SAVE BUTTON -->
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">SAVE MEMBERSHIP RECORD</button>
                    </div>

<!-- SCRIPT FOR DYNAMIC BENEFICIARIES -->
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
            <td><input type="text" name="ben_last_name[]" pattern="[A-Za-z\\s\\-]+" title="Letters only" required></td>
            <td><input type="text" name="ben_first_name[]" pattern="[A-Za-z\\s\\-]+" title="Letters only" required></td>
            <td><input type="text" name="ben_middle_name[]" pattern="[A-Za-z\\s\\-]+"></td>
            <td><input type="date" name="ben_dob[]"></td>
            <td><input type="text" name="ben_rel[]" pattern="[A-Za-z\\s]+" title="Letters only" required></td>
            <td style="text-align: center;"><button type="button" class="btn btn-danger" style="background-color: #c62828; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;" onclick="this.parentElement.parentElement.remove(); rowCount--;">X</button></td>
        `;
        tbody.appendChild(tr);
        rowCount++;
    });
</script>

</body>
</html>