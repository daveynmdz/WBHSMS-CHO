<?php
session_start();
require_once 'db.php';

// --- Auth & Session ---
if (!isset($_SESSION['employee_id'], $_SESSION['role'])) {
    die('Session missing. Please log in again.');
}
$employee_id = (int)$_SESSION['employee_id'];
$role = $_SESSION['role'];
$employee_barangay = $_SESSION['barangay'] ?? '';
$employee_district = $_SESSION['district'] ?? '';

$success = false;
$error = '';

// --- DB Connect (PDO) ---
$pdo = new PDO('mysql:host=localhost;dbname=wbhsms_database', 'root', '@Dav200110');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Get employee info ---
$employee = null;
$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ? LIMIT 1");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

$full_name = $employee 
        ? $employee['last_name'] . ', ' . $employee['first_name'] . 
            (!empty($employee['middle_name']) ? " " . $employee['middle_name'] : "")
        : 'Employee Not Found';

// --- Patient Search: fetch patients based on search filters and role ---
$patients = [];
$where = [];
$params = [];
/*if ($role === 'Barangay Health Worker' || $role === 'BHW') {
    $where[] = 'barangay = ?';
    $params[] = $employee_barangay;
} elseif ($role === 'District Health Officer' || $role === 'DHO') {
    $where[] = 'facility_id IN (SELECT id FROM facilities WHERE district = ?)';
    $params[] = $employee_district;
}*/
// Search filters
if (!empty($_GET['search_last_name'])) {
    $where[] = 'last_name LIKE ?';
    $params[] = '%' . $_GET['search_last_name'] . '%';
}
if (!empty($_GET['search_first_name'])) {
    $where[] = 'first_name LIKE ?';
    $params[] = '%' . $_GET['search_first_name'] . '%';
}
if (!empty($_GET['search_barangay'])) {
    $where[] = 'barangay = ?';
    $params[] = $_GET['search_barangay'];
}
if (!empty($_GET['search_patient_id'])) {
    $where[] = '(id = ? OR username LIKE ?)';
    $params[] = $_GET['search_patient_id'];
    $params[] = '%' . $_GET['search_patient_id'] . '%';
}
$sql = "SELECT id, username, CONCAT(last_name, ', ', first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', middle_name), ''), IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), '')) AS full_name, barangay FROM patients";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY last_name, first_name LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Referral destination facilities ---
$facilities = [];
$stmt = $pdo->prepare("SELECT id, name, type FROM facilities WHERE type IN ('District Health Office','City Health Office','Hospital') ORDER BY name");
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Handle form submit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $chief_complaint = trim($_POST['chief_complaint'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $barangay = $_POST['barangay'] ?? '';
    $referred_to_facility = $_POST['referred_to_facility'] ?? '';
    $referred_to_external = trim($_POST['referred_to_external'] ?? '');
    $assessment = trim($_POST['assessment'] ?? '');
    $service_id = $_POST['service'] ?? NULL;
    // vitals_id should be set after vitals insert, for now set NULL
    $vitals_id = NULL;
    $reason_referral = $_POST['reason_referral'] ?? '';
    $reason_referral_other = trim($_POST['reason_referral_other'] ?? '');
    $reason_for_referral = ($reason_referral === 'Other' && $reason_referral_other) ? $reason_referral_other : $reason_referral;

    // Validation
    if (!$patient_id || !$barangay || !$chief_complaint || 
        (!$referred_to_facility && !$referred_to_external)) {
        $error = 'Please fill in all required fields.';
    } else {
        // If external, referred_to_facility is NULL
        $facility_id = $referred_to_facility === 'external' ? NULL : $referred_to_facility;
        $external_name = $referred_to_facility === 'external' ? $referred_to_external : NULL;
        $stmt = $pdo->prepare(
            "INSERT INTO referrals 
            (patient_id, barangay, issued_by, referred_to_facility, referred_to_external, chief_complaint, symptoms, assessment, service_id, vitals_id, reason_for_referral, status, date_of_referral)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        $stmt->execute([
            $patient_id,
            $barangay,
            $employee_id,
            $facility_id,
            $external_name,
            $chief_complaint,
            $symptoms,
            $assessment,
            $service_id,
            $vitals_id,
            $reason_for_referral
        ]);
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Patient Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Sidebar/Nav/Main styles -->
    <link rel="stylesheet" href="css/patientUI.css">
    <!-- Profile-specific styles -->
    <link rel="stylesheet" href="css/patientProfile.css">
    <style>
        .section-title {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 1em;
            color: #15537b;
            text-align: left;
            padding-left: 0.3em;
        }
        .referral-container {
            display: flex;
            gap: 2em;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        .profile-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(60,74,123,0.10);
            padding: 2em 2em 1em 2em;
            margin: 0.5em 0;
            flex: 1 1 420px;
            min-width: 320px;
            max-width: 560px;
        }
        .patient-search-card {
            margin-right: 1em;
        }
        .referral-form-card {
            margin-left: 1em;
        }
        .patient-table-wrapper {
            max-height: 280px;
            overflow-y: auto;
            margin-bottom: 0.5em;
        }
        .patient-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1em;
        }
        .patient-table th, .patient-table td {
            padding: 0.5em 0.8em;
            border-bottom: 1px solid #eaeaea;
            text-align: left;
        }
        .patient-table th {
            background: #f2f7fc;
            color: #15537b;
            font-weight: 600;
        }
        .patient-table tr:last-child td {
            border-bottom: none;
        }
        .patient-table input[type="radio"] {
            accent-color: #15537b;
        }
        .patient-table tbody tr:hover {
            background: #f5faff;
        }
        .filter-group.filter-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
        }
        .filter-row {
            flex: 1 1 160px;
            min-width: 120px;
            display: flex;
            flex-direction: column;
            margin-bottom: 0.5em;
        }
        .filter-actions {
            width: 100%;
            display: flex;
            gap: 0.6em;
            margin-top: 0.5em;
        }
        .referral-form .form-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 1em;
        }
        .referral-form label {
            font-weight: 500;
            margin-top: 0.5em;
            margin-bottom: 0.2em;
            color: #333;
        }
        .referral-form input, .referral-form select, .referral-form textarea {
            border: 1px solid #d7e0eb;
            border-radius: 6px;
            padding: 0.7em;
            font-size: 1em;
            outline: none;
            background: #f9fbfe;
            transition: border-color 0.2s;
        }
        .referral-form input:focus, .referral-form select:focus, .referral-form textarea:focus {
            border-color: #15537b;
            background: #fff;
        }
        .referral-form .btn-success {
            background: #15537b;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7em 1.5em;
            font-weight: 600;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.2s;
        }
        .referral-form .btn-success:hover {
            background: #1072c3;
        }
        .alert {
            margin-bottom: 1em;
            padding: 0.7em 1em;
            border-radius: 6px;
            font-size: 1em;
        }
        .alert-success {
            background: #e7f8e8;
            color: #1c7c31;
            border: 1px solid #b7e2c3;
        }
        .alert-error {
            background: #fdeaea;
            color: #c0392b;
            border: 1px solid #f5b7b7;
        }
        .required {
            color: #c0392b;
            font-weight: bold;
        }
        @media (max-width: 900px) {
            .referral-container {
                flex-direction: column;
                gap: 1.2em;
                align-items: stretch;
            }
            .patient-search-card, .referral-form-card {
                margin: 0;
                max-width: 100%;
                min-width: 0;
            }
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-row label[for="birthday"] {
            width: auto;
            min-width: fit-content;
            padding-left: 0.5em;
            margin-right: 0.3em;
            font-weight: 500;
            text-align: right;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.35);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay[style*="display: flex"] {
            display: flex !important;
        }

        .modal-overlay .modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18);
            padding: 2em 2.5em;
            max-width: 600px;
            width: 95vw;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: auto;
        }
    </style>
</head>

<body>
    <div class="mobile-topbar">
        <a href="employeeHomepage.php">
            <img id="topbarLogo" class="logo"
                src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
    </div>
    <button class="mobile-toggle" onclick="toggleNav()" aria-label="Toggle Menu">
        <i id="menuIcon" class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay" onclick="closeNav()"></div>
    <nav class="nav" id="sidebar">
        <a href="employeeHomepage.php">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527"
                alt="Sidebar Logo" />
        </a>
        <div class="menu">
            <a href="employeePatientMgmt.php" onclick="closeNav()"><i class="fa-solid fa-folder-closed"></i> Patient
                Records</a>
            <a href="employeeReferralIssue.php" onclick="closeNav()"><i class="fa-solid fa-share-nodes"></i> Referrals</a>

        </div>
        <div class="user-profile">
            <a href="#" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <img src="https://i.pravatar.cc/100?img=5" alt="User Profile" />
                    <div class="user-text">
                        <strong>
                            <?php 
                                if ($employee) {
                                    echo htmlspecialchars($employee['last_name'] . ', ' . $employee['first_name'] . (!empty($employee['middle_name']) ? ' ' . $employee['middle_name'] : ''));
                                } else {
                                    echo 'Employee Not Found';
                                }
                            ?>
                        </strong>
                        <small>
                            <?php echo htmlspecialchars($role); ?>
                        </small>
                        <small>Employee #
                            <?php echo htmlspecialchars($employee ? $employee['employee_number'] : $employee_number); ?>
                        </small>
                    </div>
                    <span class="tooltip">View Profile</span>
                </div>
            </a>
            <div class="user-actions">
                <a href="#" onclick="closeNav()"><i class="fas fa-bell"></i> Notifications</a>
                <a href="#" onclick="closeNav()"><i class="fas fa-cog"></i> Settings</a>
                <a href="#" onclick="showLogoutModal(event)"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
    </nav>
    <!-- Custom Logout Modal -->
    <div id="logoutModal"
        style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
        <div
            style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:2em 2.5em;max-width:350px;text-align:center;animation:modalFadeIn 0.2s;">
            <h3 style="margin-top:0;color:#c0392b;">Sign Out?</h3>
            <p>Are you sure you want to sign out?</p>
            <div style="display:flex;gap:1em;justify-content:center;">
                <button id="logoutConfirm"
                    style="background:#c0392b;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;" onclick="confirmLogout()">Sign Out</button>
                <button id="logoutCancel"
                    style="background:#eaeaea;color:#333;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;" onclick="closeLogoutModal()">Cancel</button>
            </div>
        </div>
    </div>

<section class="homepage">
    <h2 class="section-title">Create Patient Referral</h2>
    <div class="referral-container">
        <!-- Patient Search Profile Card -->
        <div class="profile-card patient-search-card" style="background:#fff;border-radius:14px;box-shadow:0 4px 18px rgba(60,74,123,0.10);padding:2em 2em 1em 2em;margin:0.5em 0;flex:1 1 420px;min-width:320px;max-width:560px;">
            <h3 style="font-size:1.3em;font-weight:600;color:#15537b;margin-bottom:1em;"><i class="fa-solid fa-users"></i> Patient Search</h3>
            <form class="filter-group filter-flex" method="get" autocomplete="off" style="margin-bottom:1em;gap:1em;">
                <div class="filter-row" style="flex:1 1 160px;min-width:120px;display:flex;flex-direction:column;margin-bottom:0.5em;">
                    <label for="search_last_name" style="font-weight:500;margin-bottom:0.2em;color:#333;">Last Name</label>
                    <input type="text" id="search_last_name" name="search_last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($_GET['search_last_name'] ?? ''); ?>" style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;outline:none;background:#f9fbfe;transition:border-color 0.2s;">
                </div>
                <div class="filter-row" style="flex:1 1 160px;min-width:120px;display:flex;flex-direction:column;margin-bottom:0.5em;">
                    <label for="search_first_name" style="font-weight:500;margin-bottom:0.2em;color:#333;">First Name</label>
                    <input type="text" id="search_first_name" name="search_first_name" placeholder="First Name" value="<?php echo htmlspecialchars($_GET['search_first_name'] ?? ''); ?>" style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;outline:none;background:#f9fbfe;transition:border-color 0.2s;">
                </div>
                <div class="filter-row" style="flex:1 1 160px;min-width:120px;display:flex;flex-direction:column;margin-bottom:0.5em;">
                    <label for="search_barangay" style="font-weight:500;margin-bottom:0.2em;color:#333;">Barangay</label>
                    <select id="search_barangay" name="search_barangay" style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;outline:none;background:#f9fbfe;transition:border-color 0.2s;">
                        <option value="">-- Select Barangay --</option>
                        <?php
                        $barangay_list = [
                            'Brgy. Assumption',
                            'Brgy. Avanceña',
                            'Brgy. Cacub',
                            'Brgy. Caloocan',
                            'Brgy. Carpenter Hill',
                            'Brgy. Concepcion',
                            'Brgy. Esperanza',
                            'Brgy. General Paulino Santos',
                            'Brgy. Mabini',
                            'Brgy. Magsaysay',
                            'Brgy. Mambucal',
                            'Brgy. Morales',
                            'Brgy. Namnama',
                            'Brgy. New Pangasinan',
                            'Brgy. Paraiso',
                            'Brgy. Rotonda',
                            'Brgy. San Isidro',
                            'Brgy. San Roque',
                            'Brgy. San Jose',
                            'Brgy. Sta. Cruz',
                            'Brgy. Sto. Niño',
                            'Brgy. Saravia',
                            'Brgy. Topland',
                            'Brgy. Zone 1',
                            'Brgy. Zone 2',
                            'Brgy. Zone 3',
                            'Brgy. Zone 4'
                        ];
                        $selected_barangay = $_GET['search_barangay'] ?? '';
                        foreach ($barangay_list as $brgy) {
                            $selected = ($selected_barangay === $brgy) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($brgy)."\" $selected>".htmlspecialchars($brgy)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-row" style="flex:1 1 160px;min-width:120px;display:flex;flex-direction:column;margin-bottom:0.5em;">
                    <label for="search_patient_id" style="font-weight:500;margin-bottom:0.2em;color:#333;">Patient ID</label>
                    <input type="text" id="search_patient_id" name="search_patient_id" placeholder="Patient ID" value="<?php echo htmlspecialchars($_GET['search_patient_id'] ?? ''); ?>" style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;outline:none;background:#f9fbfe;transition:border-color 0.2s;">
                </div>
                <div class="filter-actions" style="width:100%;display:flex;gap:0.6em;margin-top:0.5em;justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="background:#15537b;color:#fff;border:none;border-radius:6px;padding:0.7em 1.5em;font-weight:600;cursor:pointer;font-size:1em;transition:background 0.2s;"><i class="fa fa-search"></i> Search</button>
                    <button type="button" class="btn btn-default" style="background:#eaeaea;color:#333;border:none;border-radius:6px;padding:0.7em 1.5em;font-weight:600;cursor:pointer;font-size:1em;transition:background 0.2s;" onclick="clearFilterFields()"><i class="fa fa-eraser"></i> Clear</button>
                </div>
            </form>
            <div class="patient-table-wrapper" style="max-height:280px;overflow-y:auto;margin-bottom:0.5em;">
                <table class="patient-table" style="width:100%;border-collapse:collapse;font-size:1em;">
                    <thead>
                        <tr>
                            <th style="background:#f2f7fc;color:#15537b;font-weight:600;padding:0.5em 0.8em;"> </th>
                            <th style="background:#f2f7fc;color:#15537b;font-weight:600;padding:0.5em 0.8em;">Patient ID</th>
                            <th style="background:#f2f7fc;color:#15537b;font-weight:600;padding:0.5em 0.8em;">Full Name</th>
                            <th style="background:#f2f7fc;color:#15537b;font-weight:600;padding:0.5em 0.8em;">Barangay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $row): ?>
                        <tr style="border-bottom:1px solid #eaeaea;">
                            <td style="padding:0.5em 0.8em;">
                                <input type="radio" name="patient_id_select" value="<?php echo $row['id']; ?>"
                                    <?php if (isset($_POST['patient_id']) && $_POST['patient_id'] == $row['id']) echo 'checked'; ?>
                                    onclick="togglePatientSelect(this, '<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['barangay']); ?>', '<?php echo htmlspecialchars($row['username']); ?>')"
                                >
                            </td>
                            <td style="padding:0.5em 0.8em;"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td style="padding:0.5em 0.8em;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td style="padding:0.5em 0.8em;"><?php echo htmlspecialchars($row['barangay']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($patients)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#aaa;padding:0.5em 0.8em;">No patients found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Referral Form Profile Card -->
        <div class="profile-card referral-form-card">
            <h3 style="margin-bottom: 1em;"><i class="fa-solid fa-share-nodes"></i> Patient Referral Form</h3>
            <?php if ($success): ?>
                <div class="alert alert-success">Referral created successfully!</div>
            <?php elseif ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="referral-form" method="post" action="verifyReferral.php" autocomplete="off">
                <input type="hidden" id="selectedPatientId" name="patient_id" value="<?php echo htmlspecialchars($_POST['patient_id'] ?? ''); ?>" required>
                <div class="form-row">
                    <label for="patientIdField">Patient ID <span class="required">*</span></label>
                    <input type="text" id="patientIdField" name="patient_id_display" value="<?php echo htmlspecialchars($_POST['patient_id'] ?? ''); ?>" disabled style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;outline:none;background:#f0f0f0;transition:border-color 0.2s;">
                </div>
                <div class="form-row">
                    <label for="barangayField">Barangay</label>
                    <select id="barangayField" name="barangay" required disabled style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;outline:none;background:#f9fbfe;transition:border-color 0.2s;">
                        <option value="">-- Select Barangay --</option>
                        <?php
                        $barangay_list = [
                            'Brgy. Assumption',
                            'Brgy. Avanceña',
                            'Brgy. Cacub',
                            'Brgy. Caloocan',
                            'Brgy. Carpenter Hill',
                            'Brgy. Concepcion',
                            'Brgy. Esperanza',
                            'Brgy. General Paulino Santos',
                            'Brgy. Mabini',
                            'Brgy. Magsaysay',
                            'Brgy. Mambucal',
                            'Brgy. Morales',
                            'Brgy. Namnama',
                            'Brgy. New Pangasinan',
                            'Brgy. Paraiso',
                            'Brgy. Rotonda',
                            'Brgy. San Isidro',
                            'Brgy. San Roque',
                            'Brgy. San Jose',
                            'Brgy. Sta. Cruz',
                            'Brgy. Sto. Niño',
                            'Brgy. Saravia',
                            'Brgy. Topland',
                            'Brgy. Zone 1',
                            'Brgy. Zone 2',
                            'Brgy. Zone 3',
                            'Brgy. Zone 4'
                        ];
                        $selected_barangay = $_POST['barangay'] ?? '';
                        foreach ($barangay_list as $brgy) {
                            $selected = ($selected_barangay === $brgy) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($brgy)."\" $selected>".htmlspecialchars($brgy)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-row" style="font-size:0.9em;color:#555;margin-bottom:1em;">
                    <em>Select a patient from the left to autofill the Patient ID and Barangay fields.</em>
                </div>
                <div class="form-row">
                    <label style="font-weight:500;margin-bottom:0.2em;color:#333;">Patient Vitals</label>
                    <div style="display:flex;flex-wrap:wrap;gap:1em;">
                        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:0.5em;">
                            <i class="fa-solid fa-ruler-vertical" title="Height" style="color:#15537b;font-size:1.2em;padding:0 0.5em;"></i>
                            <input type="number" name="ht" id="vitalHt" placeholder="Height (cm)" min="0" step="0.1" required value="<?php echo htmlspecialchars($_POST['ht'] ?? ''); ?>" style="width:100%;border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        </div>
                        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:0.5em;">
                            <i class="fa-solid fa-weight-scale" title="Weight" style="color:#15537b;font-size:1.2em;padding:0 0.5em;"></i>
                            <input type="number" name="wt" id="vitalWt" placeholder="Weight (kg)" min="0" step="0.1" required value="<?php echo htmlspecialchars($_POST['wt'] ?? ''); ?>" style="width:100%;border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        </div>
                        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:0.5em;">
                            <i class="fa-solid fa-heart-pulse" title="Blood Pressure" style="color:#c0392b;font-size:1.2em;padding:0 0.5em;"></i>
                            <input type="text" name="bp" id="vitalBp" placeholder="Blood Pressure (e.g. 120/80)" maxlength="10" required pattern="^\d{2,3}/\d{2,3}$" value="<?php echo htmlspecialchars($_POST['bp'] ?? ''); ?>" style="width:100%;border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        </div>
                        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:0.5em;">
                            <i class="fa-solid fa-heart" title="Heart Rate" style="color:#c0392b;font-size:1.2em;padding:0 0.5em;"></i>
                            <input type="number" name="hr" id="vitalHr" placeholder="Heart Rate (bpm)" min="0" max="300" value="<?php echo htmlspecialchars($_POST['hr'] ?? ''); ?>" style="width:100%;border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        </div>
                        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:0.5em;">
                            <i class="fa-solid fa-lungs" title="Respiratory Rate" style="color:#15537b;font-size:1.2em;padding:0 0.5em;"></i>
                            <input type="number" name="rr" id="vitalRr" placeholder="Respiratory Rate" min="0" max="100" value="<?php echo htmlspecialchars($_POST['rr'] ?? ''); ?>" style="width:100%;border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        </div>
                        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:0.5em;">
                            <i class="fa-solid fa-temperature-half" title="Temperature" style="color:#e67e22;font-size:1.2em;padding:0 0.5em;"></i>
                            <input type="number" name="temp" id="vitalTemp" placeholder="Temperature (°C)" min="30" max="45" step="0.1" required value="<?php echo htmlspecialchars($_POST['temp'] ?? ''); ?>" style="width:100%;border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <label for="chief_complaint">Chief Complaint <span class="required">*</span></label>
                    <input type="text" id="chief_complaint" name="chief_complaint" maxlength="255" required value="<?php echo htmlspecialchars($_POST['chief_complaint'] ?? ''); ?>">
                </div>
                <div class="form-row">
                    <label for="symptoms">Symptoms</label>
                    <div id="symptomFields">
                        <?php
                        // Prepopulate symptoms from POST if available
                        $symptom_options = [
                            'Fever', 'Cough', 'Shortness of breath', 'Headache', 'Fatigue', 'Sore throat', 'Diarrhea', 'Nausea', 'Vomiting', 'Chest pain', 'Abdominal pain', 'Rash', 'Joint pain', 'Muscle pain', 'Loss of taste', 'Loss of smell', 'Dizziness', 'Palpitations', 'Swelling', 'Bleeding', 'Others'
                        ];
                        $symptoms_post = isset($_POST['symptoms']) ? explode('||', $_POST['symptoms']) : [''];
                        foreach ($symptoms_post as $idx => $symptom_val) {
                            $is_other = !in_array($symptom_val, $symptom_options) && $symptom_val !== '';
                        ?>
                        <div class="symptom-row" style="display:flex;align-items:center;gap:0.5em;margin-bottom:0.5em;">
                            <select name="symptom_select[]" class="symptom-select" onchange="handleSymptomChange(this)" style="flex:1;">
                                <option value="">-- Select Symptom --</option>
                                <?php foreach ($symptom_options as $opt) {
                                    $selected = ($symptom_val === $opt) ? 'selected' : '';
                                    echo "<option value=\"".htmlspecialchars($opt)."\" $selected>".htmlspecialchars($opt)."</option>";
                                } ?>
                                <option value="Others" <?php echo $is_other ? 'selected' : ''; ?>>Others</option>
                            </select>
                            <input type="text" name="symptom_other[]" class="symptom-other" placeholder="Specify symptom" style="flex:1;display:<?php echo $is_other ? 'inline-block' : 'none'; ?>;" value="<?php echo $is_other ? htmlspecialchars($symptom_val) : ''; ?>">
                            <button type="button" class="remove-symptom-btn" onclick="removeSymptomField(this)" style="background:#eaeaea;color:#333;border:none;border-radius:6px;padding:0.3em 0.8em;font-weight:600;cursor:pointer;font-size:1em;">Remove</button>
                        </div>
                        <?php } ?>
                    </div>
                    <button type="button" id="addSymptomBtn" onclick="addSymptomField()" style="background:#15537b;color:#fff;border:none;border-radius:6px;padding:0.5em 1em;font-weight:600;cursor:pointer;font-size:1em;margin-bottom:0.5em;">Add Symptom</button>
                </div>
                <div class="form-row">
                    <label for="assessment">Initial Diagnosis</label>
                    <input type="text" id="assessment" name="assessment" maxlength="255" placeholder="Enter initial diagnosis" value="<?php echo htmlspecialchars($_POST['assessment'] ?? ''); ?>" style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                <div class="form-row">
                    <label for="facilitySelect">Referral Destination <span class="required">*</span></label>
                    <select id="facilitySelect" name="referred_to_facility" onchange="toggleExternalField()" required>
                        <option value="">-- Select Facility --</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?php echo $facility['id']; ?>"
                                <?php if (isset($_POST['referred_to_facility']) && $_POST['referred_to_facility'] == $facility['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($facility['name']); ?> (<?php echo htmlspecialchars($facility['type']); ?>)
                            </option>
                        <?php endforeach; ?>
                        <option value="external"
                            <?php if (isset($_POST['referred_to_facility']) && $_POST['referred_to_facility'] == 'external') echo 'selected'; ?>>
                            External Facility
                        </option>
                    </select>
                </div>
                <div class="form-row" id="externalFacilityLabel" style="display:<?php echo (isset($_POST['referred_to_facility']) && $_POST['referred_to_facility'] == 'external') ? 'block' : 'none'; ?>;">
                    <label for="externalFacilityInput">If external, specify destination</label>
                    <input type="text" id="externalFacilityInput" name="referred_to_external"
                        value="<?php echo htmlspecialchars($_POST['referred_to_external'] ?? ''); ?>"
                        <?php echo (isset($_POST['referred_to_facility']) && $_POST['referred_to_facility'] == 'external') ? 'required' : ''; ?>>
                </div>
                <div class="form-row">
                    <label for="serviceSelect">Select Service</label>
                    <select id="serviceSelect" name="service" required style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        <!-- Options will be populated by JS -->
                    </select>
                </div>
                <div class="form-row">
                    <label for="reasonReferral">Reason for Referral</label>
                    <select id="reasonReferral" name="reason_referral" required style="border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;">
                        <option value="">-- Select Reason --</option>
                        <option value="Further evaluation/diagnosis">Further evaluation/diagnosis</option>
                        <option value="Specialized medical care">Specialized medical care</option>
                        <option value="Laboratory or diagnostic testing">Laboratory or diagnostic testing</option>
                        <option value="Imaging/radiology services">Imaging/radiology services</option>
                        <option value="Surgical intervention">Surgical intervention</option>
                        <option value="Management of chronic disease">Management of chronic disease</option>
                        <option value="Emergency care">Emergency care</option>
                        <option value="Unavailable medication/treatment at referring facility">Unavailable medication/treatment at referring facility</option>
                        <option value="Vaccination services">Vaccination services</option>
                        <option value="Dental services">Dental services</option>
                        <option value="Family planning counseling/procedures">Family planning counseling/procedures</option>
                        <option value="TB DOTS treatment/assessment">TB DOTS treatment/assessment</option>
                        <option value="Animal bite management">Animal bite management</option>
                        <option value="Mental health assessment">Mental health assessment</option>
                        <option value="Maternal/child health services">Maternal/child health services</option>
                        <option value="Rehabilitation/physical therapy">Rehabilitation/physical therapy</option>
                        <option value="Social services/support">Social services/support</option>
                        <option value="Health certificate or medical document request">Health certificate or medical document request</option>
                        <option value="Follow-up care">Follow-up care</option>
                        <option value="Second opinion">Second opinion</option>
                        <option value="Patient request">Patient request</option>
                        <option value="Other">Other (please specify)</option>
                    </select>
                    <input type="text" id="reasonReferralOther" name="reason_referral_other" placeholder="Please specify..." style="margin-top:0.5em;<?php echo (isset($_POST['reason_referral']) && $_POST['reason_referral'] === 'Other') ? 'display:block;' : 'display:none;'; ?>border:1px solid #d7e0eb;border-radius:6px;padding:0.7em;font-size:1em;" value="<?php echo htmlspecialchars($_POST['reason_referral_other'] ?? ''); ?>" <?php echo (isset($_POST['reason_referral']) && $_POST['reason_referral'] === 'Other') ? 'required' : ''; ?> />
                </div>
                <button type="submit" class="btn btn-success" style="margin-top:1em;">Submit Referral</button>
            </form>
        </div>
    </div>
</section>
    <script>
        // Service options
        const serviceOptions = [
            { id: 1, name: 'Primary Care', available_at_city: 1, available_at_district: 1, available_at_barangay: 1 },
            { id: 2, name: 'Dental Services', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 3, name: 'TB DOTS', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 4, name: 'Vaccination Services', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 5, name: 'HEMS', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 6, name: 'Family Planning Services', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 7, name: 'Animal Bite Treatment', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 8, name: 'Laboratory Test', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 },
            { id: 9, name: 'Medical Document Request', available_at_city: 1, available_at_district: 0, available_at_barangay: 0 }
        ];

        // Facility type mapping
        const facilityTypeMap = {
            'City Health Office': 'city',
            'District Health Office': 'district',
            'Hospital': 'city', // treat hospital as city for service availability
            'Barangay Health Station': 'barangay',
            'Barangay': 'barangay',
            'external': 'external'
        };

        function getFacilityTypeById(facilityId) {
            // Get facility type from PHP array
            if (facilityId === 'external') return 'external';
            var facilities = <?php echo json_encode($facilities); ?>;
            for (var i = 0; i < facilities.length; i++) {
                if (facilities[i].id == facilityId) {
                    return facilityTypeMap[facilities[i].type] || 'city';
                }
            }
            return 'city';
        }

        function populateServiceDropdown(facilityId) {
            var select = document.getElementById('serviceSelect');
            if (!select) return;
            select.innerHTML = '';
            let availableServices = [];
            // If facilityId is '2' or '3', only show Primary Care
            if (facilityId === '2' || facilityId === '3') {
                availableServices = serviceOptions.filter(opt => opt.id === 1);
            } else {
                var facilityType = getFacilityTypeById(facilityId);
                if (facilityType === 'external' || facilityType === 'city' || facilityType === 'district') {
                    availableServices = serviceOptions.filter(opt => opt.available_at_city);
                } else if (facilityType === 'barangay') {
                    availableServices = serviceOptions.filter(opt => opt.id === 1);
                }
            }
            // Add a default option
            var defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = '-- Select Service --';
            select.appendChild(defaultOpt);
            availableServices.forEach(function(opt) {
                var option = document.createElement('option');
                option.value = opt.id;
                option.textContent = opt.name;
                select.appendChild(option);
            });
        }

        // Initial population on page load
        document.addEventListener('DOMContentLoaded', function () {
            var facilitySelect = document.getElementById('facilitySelect');
            // Populate for initial value
            var selectedFacility = facilitySelect ? facilitySelect.value : '';
            populateServiceDropdown(selectedFacility);
            // Listen for facility changes
            if (facilitySelect) {
                facilitySelect.addEventListener('change', function() {
                    var val = this.value;
                    populateServiceDropdown(val);
                });
            }
        });
        function handleSymptomChange(select) {
            var otherInput = select.parentNode.querySelector('.symptom-other');
            if (select.value === 'Others') {
                otherInput.style.display = 'inline-block';
                otherInput.required = true;
                otherInput.focus();
            } else {
                otherInput.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        }

        function addSymptomField() {
            var symptomOptions = [
                'Fever', 'Cough', 'Shortness of breath', 'Headache', 'Fatigue', 'Sore throat', 'Diarrhea', 'Nausea', 'Vomiting', 'Chest pain', 'Abdominal pain', 'Rash', 'Joint pain', 'Muscle pain', 'Loss of taste', 'Loss of smell', 'Dizziness', 'Palpitations', 'Swelling', 'Bleeding', 'Others'
            ];
            var container = document.getElementById('symptomFields');
            var div = document.createElement('div');
            div.className = 'symptom-row';
            div.style = 'display:flex;align-items:center;gap:0.5em;margin-bottom:0.5em;';
            var select = document.createElement('select');
            select.name = 'symptom_select[]';
            select.className = 'symptom-select';
            select.style = 'flex:1;';
            select.onchange = function() { handleSymptomChange(this); };
            var defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = '-- Select Symptom --';
            select.appendChild(defaultOpt);
            symptomOptions.forEach(function(opt) {
                var o = document.createElement('option');
                o.value = opt;
                o.textContent = opt;
                select.appendChild(o);
            });
            var otherOpt = document.createElement('option');
            otherOpt.value = 'Others';
            otherOpt.textContent = 'Others';
            select.appendChild(otherOpt);
            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'symptom_other[]';
            input.className = 'symptom-other';
            input.placeholder = 'Specify symptom';
            input.style = 'flex:1;display:none;';
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-symptom-btn';
            removeBtn.textContent = 'Remove';
            removeBtn.style = 'background:#eaeaea;color:#333;border:none;border-radius:6px;padding:0.3em 0.8em;font-weight:600;cursor:pointer;font-size:1em;';
            removeBtn.onclick = function() { removeSymptomField(removeBtn); };
            div.appendChild(select);
            div.appendChild(input);
            div.appendChild(removeBtn);
            container.appendChild(div);
        }

        function removeSymptomField(btn) {
            var container = document.getElementById('symptomFields');
            var rows = container.querySelectorAll('.symptom-row');
            if (rows.length > 1) {
                btn.parentNode.remove();
            } else {
                // Only one left, just clear its values
                var select = btn.parentNode.querySelector('.symptom-select');
                var input = btn.parentNode.querySelector('.symptom-other');
                select.selectedIndex = 0;
                input.value = '';
                input.style.display = 'none';
            }
        }

        // On submit, concatenate all symptoms into one hidden field
        document.addEventListener('DOMContentLoaded', function () {
            // Modal logic ...existing code...

            // Symptoms logic
            var form = document.querySelector('.referral-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var rows = document.querySelectorAll('#symptomFields .symptom-row');
                    var symptoms = [];
                    rows.forEach(function(row) {
                        var select = row.querySelector('.symptom-select');
                        var input = row.querySelector('.symptom-other');
                        if (select.value === 'Others' && input.value.trim() !== '') {
                            symptoms.push(input.value.trim());
                        } else if (select.value && select.value !== 'Others') {
                            symptoms.push(select.value);
                        }
                    });
                    // Remove any previous hidden field
                    var prev = form.querySelector('input[name="symptoms"]');
                    if (prev) prev.remove();
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'symptoms';
                    hidden.value = symptoms.join('||');
                    form.appendChild(hidden);
                });
            }
        });

        function togglePatientSelect(radio, id, barangay) {
            // If already selected, deselect
            var usernameField = document.getElementById('patientIdField');
            if (radio.checked && radio.getAttribute('data-selected') === 'true') {
                radio.checked = false;
                radio.setAttribute('data-selected', 'false');
                document.getElementById('selectedPatientId').value = '';
                document.getElementById('barangayField').selectedIndex = 0;
                if (usernameField) usernameField.value = '';
            } else {
                // Deselect all radios
                var radios = document.getElementsByName('patient_id_select');
                radios.forEach(function(r) { r.setAttribute('data-selected', 'false'); });
                radio.checked = true;
                radio.setAttribute('data-selected', 'true');
                document.getElementById('selectedPatientId').value = id;
                // Set barangay dropdown to match patient
                var barangaySelect = document.getElementById('barangayField');
                for (var i = 0; i < barangaySelect.options.length; i++) {
                    if (barangaySelect.options[i].value === barangay) {
                        barangaySelect.selectedIndex = i;
                        break;
                    }
                }
                // Set Patient ID display field to username
                if (usernameField) usernameField.value = arguments[3] || '';
            }
        }
        function updateBarangay() {
            var select = document.getElementById('patientSelect');
            var barangay = select.options[select.selectedIndex].getAttribute('data-barangay') || '';
            document.getElementById('barangayField').value = barangay;
        }
        function toggleExternalField() {
            var facilitySelect = document.getElementById('facilitySelect');
            var externalLabel = document.getElementById('externalFacilityLabel');
            var externalInput = document.getElementById('externalFacilityInput');
            if (facilitySelect.value === 'external') {
                externalLabel.style.display = 'block';
                externalInput.required = true;
            } else {
                externalLabel.style.display = 'none';
                externalInput.required = false;
                externalInput.value = '';
            }
        }
                // Report Modal logic
        function openReportModal() {
            document.getElementById('reportModal').style.display = 'flex';
        }
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }
        // Close modal on outside click
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('reportModal');
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) modal.style.display = 'none';
                });
            }
        });        


function toggleNav() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const topbarLogo = document.getElementById('topbarLogo');
            const menuIcon = document.getElementById('menuIcon');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            const isOpen = sidebar.classList.contains('open');
            topbarLogo.style.display = isOpen ? 'none' : 'block';
            menuIcon.classList.toggle('fa-bars', !isOpen);
            menuIcon.classList.toggle('fa-times', isOpen);
        }
        function closeNav() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            document.getElementById('topbarLogo').style.display = 'block';
            const menuIcon = document.getElementById('menuIcon');
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }
        // Custom Logout Modal logic
        function showLogoutModal(e) {
            e.preventDefault();
            closeNav();
            document.getElementById('logoutModal').style.display = 'flex';
        }
        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }
        function confirmLogout() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'employeeLogin.php', true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // Optionally check responseText for success
                        window.location.href = 'employeeLogin.php';
                    } else {
                        alert('Logout failed. Please try again.');
                    }
                }
            };
            xhr.send();
        }

        // Clear all filter fields
        function clearFilterFields() {
            var form = document.querySelector('.filter-group.filter-flex');
            if (!form) return;
            var inputs = form.querySelectorAll('input[type="text"], input[type="date"], select');
            inputs.forEach(function (input) {
                if (input.tagName.toLowerCase() === 'select') {
                    input.selectedIndex = 0;
                } else {
                    input.value = '';
                }
            });
        }
    </script>
</body>
</html>