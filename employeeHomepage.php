<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['employee_id'])) {
    die('Session employee_id missing. Please log in again.');
}
$employee_id = isset($_SESSION['employee_id']) ? (int) trim($_SESSION['employee_id']) : null;
// Debug: log the processed employee_id value
error_log('Processed employee_id: ' . $employee_id);

$employee = null;
if ($employee_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=wbhsms_database', 'root', '');
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
        'notifications' => [],
        'activity_log' => [],
    ];
} else if (isset($_SESSION['employee_last_name'], $_SESSION['employee_first_name'])) {
    // Fallback to session if DB lookup fails but session has name info
    $full_name = $_SESSION['employee_last_name'] . ', ' . $_SESSION['employee_first_name'];
    if (!empty($_SESSION['employee_middle_name'])) {
        $full_name .= ' ' . $_SESSION['employee_middle_name'];
    }
    $defaults = [
        'name' => $full_name,
        'employee_number' => isset($_SESSION['employee_number']) ? $_SESSION['employee_number'] : '',
        'role' => isset($_SESSION['role']) ? $_SESSION['role'] : '',
        'notifications' => [],
        'activity_log' => [],
    ];
} else {
    $defaults = [
        'name' => 'Employee Not Found',
        'employee_number' => '',
        'role' => '',
        'notifications' => [],
        'activity_log' => [],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CHO Koronadal Employee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/patientUI.css">
    <link rel="stylesheet" href="css/patientHomepage.css">
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
            <a href="#" onclick="closeNav()"><i class="fa-solid fa-folder-closed"></i> Patient Records</a>

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
    <div id="logoutModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h2>Sign Out</h2>
            <p>Are you sure you want to sign out?</p>
            <div class="modal-actions">
                <button onclick="confirmLogout()" class="btn btn-danger">Sign Out</button>
                <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <section class="homepage">
        <h1>Welcome to the <strong>CITY HEALTH OFFICE OF KORONADAL's</strong> Employee Portal,
            <?php echo htmlspecialchars($defaults['name']); ?>!
        </h1>
        <div class="card-container">
            <h3>What would you like to do?</h3>
            <div class="card-button-container">
                <a href="#" class="card-button blue-card">
                    <i class="fas fa-calendar-check icon"></i>
                    <h3>View Appointments</h3>
                    <p>See your scheduled appointments.</p>
                </a>
                <a href="#" class="card-button purple-card">
                    <i class="fas fa-prescription-bottle-alt icon"></i>
                    <h3>Manage Prescriptions</h3>
                    <p>Review and update prescriptions.</p>
                </a>
                <a href="#" class="card-button orange-card">
                    <i class="fas fa-vials icon"></i>
                    <h3>Lab Results</h3>
                    <p>Access laboratory test results.</p>
                </a>
                <a href="#" class="card-button teal-card">
                    <i class="fas fa-file-invoice-dollar icon"></i>
                    <h3>Billing</h3>
                    <p>Manage billing and payments.</p>
                </a>
            </div>
        </div>
        <div class="info-layout">
            <div class="left-column">

            </div>
            <div class="right-column">
                <div class="card-section notification-card">
                    <div class="section-header">
                        <h3>Notifications</h3>
                        <a href="#" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="scroll-wrapper">
                        <div class="scroll-table">
                            <table class="notification-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Date</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Example notification row -->
                                    <tr>
                                        <td>2025-08-26</td>
                                        <td>Welcome to the Employee Portal!</td>
                                        <td><span class="status read">Read</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="fade-bottom"></div>
                    </div>
                </div>
                <div class="card-section activity-log-card">
                    <div class="section-header">
                        <h3>Activity Log</h3>
                        <a href="#" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="scroll-wrapper">
                        <div class="scroll-log">
                            <ul class="activity-log">
                                <li>2025-08-26 - Logged in to the Employee Portal</li>
                            </ul>
                        </div>
                        <div class="fade-bottom"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
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
            // AJAX call to employeeLogout.php, then redirect to employeeLogin.php
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'employeeLogout.php', true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    window.location.href = 'employeeLogin.php';
                }
            };
            xhr.send();
        }
    </script>
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
            padding: 2rem 2.5rem;
            text-align: center;
            min-width: 300px;
            max-width: 90vw;
        }

        .modal-content h2 {
            margin-top: 0;
            color: #d9534f;
        }

        .modal-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-danger {
            background: #d9534f;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c9302c;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }
    </style>
    <noscript>
        <p style="text-align:center; color:red;">This site requires JavaScript to function properly. Please enable it in
            your browser.</p>
    </noscript>
</body>

</html>