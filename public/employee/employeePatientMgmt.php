<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['employee_id'])) {
    die('Session employee_id missing. Please log in again.');
}
$employee_id = isset($_SESSION['employee_id']) ? (int) trim($_SESSION['employee_id']) : null;
$employee = null;
if ($employee_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=wbhsms_database', 'root', '@Dav200110');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('SELECT * FROM employees WHERE employee_id = ? LIMIT 1');
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $employee = null;
    }
}
if ($employee) {
    $full_name = $employee['last_name'] . ', ' . $employee['first_name'];
    if (!empty($employee['middle_name'])) {
        $full_name .= ' ' . $employee['middle_name'];
    }
    $defaults = [
        'name' => $full_name,
        'employee_number' => $employee['employee_number'],
        'role' => isset($employee['role']) ? $employee['role'] : '',
    ];
} else {
    $defaults = [
        'name' => 'Employee Not Found',
        'employee_number' => '',
        'role' => '',
    ];
}
// Handle search query
// Handle search query (filter)
// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if (!empty($_GET['patient_id'])) {
    $where[] = 'p.username LIKE ?';
    $params[] = '%' . trim($_GET['patient_id']) . '%';
}
if (!empty($_GET['last_name'])) {
    $where[] = 'p.last_name LIKE ?';
    $params[] = '%' . trim($_GET['last_name']) . '%';
}
if (!empty($_GET['first_name'])) {
    $where[] = 'p.first_name LIKE ?';
    $params[] = '%' . trim($_GET['first_name']) . '%';
}
if (!empty($_GET['filter_barangay'])) {
    $where[] = 'p.barangay = ?';
    $params[] = trim($_GET['filter_barangay']);
}
if (!empty($_GET['dob'])) {
    $where[] = 'p.dob = ?';
    $params[] = trim($_GET['dob']);
}
$baseSql = "FROM patients p LEFT JOIN personal_information pi ON p.id = pi.patient_id";
$sql = "SELECT p.id AS patient_id, p.username, p.last_name, p.first_name, p.barangay, p.email, p.dob, pi.profile_photo $baseSql";
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY p.last_name, p.first_name LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countSql = "SELECT COUNT(*) $baseSql";
if (count($where) > 0) {
    $countSql .= ' WHERE ' . implode(' AND ', $where);
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalPatients = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalPatients / $perPage));
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

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
                            <?php echo htmlspecialchars($defaults['name']); ?>
                        </strong>
                        <small>
                            <?php echo htmlspecialchars($defaults['role']); ?>
                        </small>
                        <small>Employee #
                            <?php echo htmlspecialchars($defaults['employee_number']); ?>
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

    <section class="homepage patient-dashboard">
        <div class="dashboard-header-flex"
            style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;gap:1em;flex-wrap:wrap;">
            <h1 style="margin:0;"><i class="fa-solid fa-users"></i> PATIENT RECORDS</h1>
            <div class="export-group">
                <button class="export-btn report" onclick="openReportModal()"><i class="fa-solid fa-file-export"></i>
                    Create Report</button>
            </div>

            <!-- Export Modal -->
            <div id="reportModal" class="custom-modal" style="display:none;">
                <div class="modal-content" style="max-width:350px;text-align:center;">
                    <h2 style="margin-top:0;">Export Patient Records</h2>
                    <div style="margin:1.5em 0;display:flex;flex-direction:column;gap:1em;">
                        <button class="export-btn csv"><i class="fa-solid fa-file-csv"></i> CSV</button>
                        <button class="export-btn excel"><i class="fa-solid fa-file-excel"></i> Excel</button>
                        <button class="export-btn pdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                    </div>
                    <button onclick="closeReportModal()" class="export-btn" style="background:#eaeaea;color:#333;"><i
                            class="fa-solid fa-xmark"></i> Cancel</button>
                </div>
            </div>
        </div>
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card total">
                <div class="icon"><i class="fa-solid fa-users"></i></div>
                <div class="count" id="totalPatients">
                    <?php
                    // Get total patient count from database
                    try {
                        $totalCountStmt = $pdo->query('SELECT COUNT(*) FROM patients');
                        $actualTotalPatients = (int) $totalCountStmt->fetchColumn();
                        echo $actualTotalPatients;
                    } catch (PDOException $e) {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="label">Total Patient Records</div>
            </div>
            <div class="summary-card today">
                <div class="icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="count" id="todayPatients">18</div>
                <div class="label">Today's Patients</div>
            </div>
            <div class="summary-card week">
                <div class="icon"><i class="fa-solid fa-calendar-week"></i></div>
                <div class="count" id="weekPatients">74</div>
                <div class="label">This Week's Patients</div>
            </div>
            <div class="summary-card new">
                <div class="icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="count" id="newPatients">9</div>
                <div class="label">New Patients</div>
            </div>
        </div>
        <!-- Filter & Export -->
        <div class="dashboard-controls">
            <form method="get" class="filter-group filter-flex" style="width:100%;">
                <div class="filter-row">
                    <input type="text" name="patient_id"
                        value="<?= isset($_GET['patient_id']) ? htmlspecialchars($_GET['patient_id']) : '' ?>"
                        placeholder="Patient ID" class="filter-search" autocomplete="off">
                    <input type="text" name="last_name"
                        value="<?= isset($_GET['last_name']) ? htmlspecialchars($_GET['last_name']) : '' ?>"
                        placeholder="Last Name" class="filter-search" autocomplete="off">
                    <input type="text" name="first_name"
                        value="<?= isset($_GET['first_name']) ? htmlspecialchars($_GET['first_name']) : '' ?>"
                        placeholder="First Name" class="filter-search" autocomplete="off">
                </div>
                <div class="filter-row">
                    <select name="filter_barangay" class="filter-dropdown">
                        <option value="">All Barangays</option>
                        <!-- TODO: Populate barangay options dynamically -->
                        <option value="" disabled>Select a barangay</option>
                        <option>Brgy. Assumption</option>
                        <option>Brgy. Avanceña</option>
                        <option>Brgy. Cacub</option>
                        <option>Brgy. Caloocan</option>
                        <option>Brgy. Carpenter Hill</option>
                        <option>Brgy. Concepcion</option>
                        <option>Brgy. Esperanza</option>
                        <option>Brgy. General Paulino Santos</option>
                        <option>Brgy. Mabini</option>
                        <option>Brgy. Magsaysay</option>
                        <option>Brgy. Mambucal</option>
                        <option>Brgy. Morales</option>
                        <option>Brgy. Namnama</option>
                        <option>Brgy. New Pangasinan</option>
                        <option>Brgy. Paraiso</option>
                        <option>Brgy. Rotonda</option>
                        <option>Brgy. San Isidro</option>
                        <option>Brgy. San Roque</option>
                        <option>Brgy. San Jose</option>
                        <option>Brgy. Sta. Cruz</option>
                        <option>Brgy. Sto. Niño</option>
                        <option>Brgy. Saravia</option>
                        <option>Brgy. Topland</option>
                        <option>Brgy. Zone 1</option>
                        <option>Brgy. Zone 2</option>
                        <option>Brgy. Zone 3</option>
                        <option>Brgy. Zone 4</option>
                    </select>
                    <label for="dob" style="font-weight:500;margin-right:0em;text-align:right;">Birthday:</label>
                    <input type="date" id="dob" name="dob" class="filter-date" placeholder="Birthday"
                        value="<?= isset($_GET['dob']) ? htmlspecialchars($_GET['dob']) : '' ?>">
                    <button type="submit" class="filter-btn"><i class="fa fa-filter"></i> Filter</button>
                    <button type="button" class="filter-btn" style="background:#eaeaea;color:#333;margin-left:0.5em;"
                        onclick="clearFilterFields()"><i class="fa fa-times"></i> Clear All</button>
                </div>
            </form>
        </div>
        <!-- Patients Table -->
        <div class="table-responsive patient-table-section">
            <table class="patient-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Patient ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Last Visit</th>
                        <th>Date of Birth</th>
                        <th>Barangay</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($patients) > 0): ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>
                                    <img class="profile-photo"
                                        src="<?= !empty($patient['profile_photo']) ? 'patient_profile_photo.php?patient_id=' . urlencode($patient['patient_id']) : 'https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172' ?>"
                                        alt="User"
                                        onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';">
                                </td>
                                <td><?= htmlspecialchars($patient['username']) ?></td>
                                <td><?= htmlspecialchars($patient['last_name']) ?></td>
                                <td><?= htmlspecialchars($patient['first_name']) ?></td>
                                <td><span class="placeholder">-</span></td>
                                <td><?= !empty($patient['dob']) ? htmlspecialchars($patient['dob']) : '-' ?></td>
                                </td>
                                <td><?= htmlspecialchars($patient['barangay']) ?></td>
                                <td>
                                    <button class="action-btn view" title="View"
                                        onclick="window.location.href='employeeViewPatientProfile.php?patient_id=<?= urlencode($patient['patient_id']) ?>'">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    <button class="action-btn archive" title="Archive"><i class="fa-solid fa-box-archive"></i>
                                        Archive</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center;color:#888;">No patients found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination -->
            <div class="pagination">
                <?php
                // Build query string for filters except page
                $queryParams = $_GET;
                unset($queryParams['page']);
                $baseQuery = http_build_query($queryParams);
                ?>
                <button class="page-btn prev" <?= $page <= 1 ? 'disabled' : '' ?>
                    onclick="location.href='?<?= $baseQuery ?>&page=<?= $page - 1 ?>'">Previous</button>
                <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
                <button class="page-btn next" <?= $page >= $totalPages ? 'disabled' : '' ?>
                    onclick="location.href='?<?= $baseQuery ?>&page=<?= $page + 1 ?>'">Next</button>
            </div>
        </div>
    </section>
    <style>
        .patient-dashboard {
            min-height: 100vh;
            padding-bottom: 2em;
        }

        .summary-cards {
            display: flex;
            gap: 1.5em;
            margin-bottom: 2em;
            flex-wrap: wrap;
        }

        .summary-card {
            flex: 1;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 1.5em 1.2em;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 180px;
        }

        .summary-card .icon {
            font-size: 2em;
            margin-bottom: 0.5em;
        }

        .summary-card.total {
            border-left: 6px solid #2980b9;
        }

        .summary-card.today {
            border-left: 6px solid #27ae60;
        }

        .summary-card.week {
            border-left: 6px solid #f39c12;
        }

        .summary-card.new {
            border-left: 6px solid #8e44ad;
        }

        .summary-card .count {
            font-size: 2.1em;
            font-weight: 700;
            margin-bottom: 0.2em;
        }

        .summary-card .label {
            font-size: 1.05em;
            color: #555;
        }

        .dashboard-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2em;
            flex-wrap: wrap;
            gap: 1em;
        }

        .filter-group.filter-flex {
            display: flex;
            flex-direction: column;
            gap: 0.7em;
            width: 100%;
        }

        .filter-row {
            display: flex;
            gap: 0.3em;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
        }

        .filter-row>* {
            flex: 1 1 auto;
            min-width: 0;
            width: auto;
        }

        .filter-row .filter-btn {
            flex: 0 0 auto;
        }

        @media (max-width: 900px) {
            .filter-group.filter-flex {
                flex-direction: column;
                gap: 0.7em;
            }

            .filter-row {
                flex-direction: column;
                gap: 0.7em;
                align-items: stretch;
            }
        }

        @media (max-width: 700px) {
            .filter-group.filter-flex {
                gap: 0.5em;
            }

            .filter-row {
                gap: 0.5em;
            }
        }

        .filter-row label {
            flex: 0 0 auto;
        }

        .filter-search,
        .filter-dropdown,
        .filter-date {
            padding: 0.6em 1em;
            border-radius: 7px;
            border: 1px solid #ccc;
            font-size: 1em;
            background: #fff;
        }

        .filter-btn {
            background: #2980b9;
            color: #fff;
            border: none;
            padding: 0.6em 1.2em;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
        }

        .filter-btn:hover {
            background: #2471a3;
        }

        .export-group {
            display: flex;
            gap: 0.5em;
        }

        .export-btn {
            background: #fff;
            border: 1px solid #eaeaea;
            color: #2980b9;
            padding: 0.6em 1.2em;
            border-radius: 7px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(41, 128, 185, 0.07);
            transition: background 0.18s, color 0.18s;
        }

        .export-btn.csv {
            color: #2980b9;
        }

        .export-btn.excel {
            color: #27ae60;
        }

        .export-btn.pdf {
            color: #c0392b;
        }

        .export-btn:hover {
            background: #f0f6ff;
        }

        .patient-table-section {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 1.5em;
        }

        .patient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5em;
        }

        .patient-table th,
        .patient-table td {
            padding: 0.9em 0.5em;
            border-bottom: 1px solid #eaeaea;
            text-align: left;
            font-size: 1em;
        }

        .patient-table th {
            background: #f5f7fa;
            font-weight: 600;
        }

        .patient-table tr:hover {
            background: #f0f6ff;
        }

        .patient-photo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #eaeaea;
            background: #fafafa;
        }

        .action-btn {
            border: none;
            border-radius: 6px;
            padding: 0.5em 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-right: 0.4em;
            font-size: 0.98em;
            transition: background 0.18s;
        }

        .action-btn.view {
            background: #2980b9;
            color: #fff;
        }

        .action-btn.view:hover {
            background: #2471a3;
        }

        .action-btn.archive {
            background: #eaeaea;
            color: #333;
        }

        .action-btn.archive:hover {
            background: #c0392b;
            color: #fff;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1em;
            margin-top: 1em;
        }

        .page-btn {
            border: none;
            background: #eaeaea;
            color: #333;
            border-radius: 6px;
            padding: 0.5em 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
        }

        .page-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .page-btn.next {
            background: #2980b9;
            color: #fff;
        }

        .page-btn.next:hover:not(:disabled) {
            background: #2471a3;
        }

        .page-info {
            font-size: 1em;
            color: #555;
        }

        @media (max-width: 900px) {
            .summary-cards {
                flex-direction: column;
                gap: 1em;
            }

            .dashboard-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 0.7em;
            }

            .patient-table-section {
                padding: 1em;
            }
        }

        @media (max-width: 700px) {
            .summary-card {
                min-width: 140px;
                padding: 1em 0.7em;
            }

            .patient-table th,
            .patient-table td {
                padding: 0.5em 0.2em;
                font-size: 0.98em;
            }

            .action-btn {
                padding: 0.4em 0.7em;
                font-size: 0.95em;
            }
        }
    </style>
    <script>
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
            // AJAX POST to employeeLogin.php, then redirect to employeeLogin.php on success
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'employeeLogin.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200 && xhr.responseText.trim() === 'OK') {
                        window.location.href = 'employeeLogin.php';
                    } else {
                        // Fallback: try normal redirect
                        window.location.href = 'employeeLogin.php';
                    }
                }
            };
            xhr.send('logout=1');
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