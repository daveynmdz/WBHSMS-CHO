<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: employeeLogin.php');
    exit();
}
require_once 'db.php';

// Fetch employee info
$employee_id = $_SESSION['employee_id'];
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Example: You may want to fetch notifications, activity log, etc. For now, use placeholders.
$defaults = [
    'name' => $employee['name'] ?? 'Employee',
    'employee_number' => $employee['employee_number'] ?? '',
    'latest_appointment' => [
        'date' => '2025-08-26',
        'complaint' => 'N/A',
        'diagnosis' => 'N/A',
        'treatment' => 'N/A',
        'height' => 'N/A',
        'weight' => 'N/A',
        'bp' => 'N/A',
        'cardiac_rate' => 'N/A',
        'temperature' => 'N/A',
        'resp_rate' => 'N/A',
    ],
    'notifications' => [],
    'activity_log' => [],
];
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
            <a href="#" onclick="closeNav()"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="#" onclick="closeNav()"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
            <a href="#" onclick="closeNav()"><i class="fas fa-vials"></i> Laboratory</a>
            <a href="#" onclick="closeNav()"><i class="fas fa-file-invoice-dollar icon"></i> Billing</a>
        </div>
        <div class="user-profile">
            <a href="#" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <img src="https://i.pravatar.cc/100?img=5" alt="User Profile" />
                    <div class="user-text">
                        <strong>
                            <?php echo htmlspecialchars($defaults['name']); ?>
                        </strong>
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
                <a href="logout.php" onclick="closeNav()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
    </nav>
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
                <div class="card-section latest-appointment collapsible">
                    <div class="section-header">
                        <h3>Latest Appointment</h3>
                        <a href="#" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="appointment-layout">
                        <div class="appointment-details">
                            <div class="detail-box">
                                <span class="label">Date:</span>
                                <span class="value">
                                    <?php echo htmlspecialchars($defaults['latest_appointment']['date']); ?>
                                </span>
                            </div>
                            <div class="detail-box">
                                <span class="label">Chief Complaint:</span>
                                <span class="value">
                                    <?php echo htmlspecialchars($defaults['latest_appointment']['complaint']); ?>
                                </span>
                            </div>
                            <div class="detail-box">
                                <span class="label">Diagnosis:</span>
                                <span class="value">
                                    <?php echo htmlspecialchars($defaults['latest_appointment']['diagnosis']); ?>
                                </span>
                            </div>
                            <div class="detail-box">
                                <span class="label">Treatment:</span>
                                <span class="value">
                                    <?php echo htmlspecialchars($defaults['latest_appointment']['treatment']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="appointment-vitals">
                            <div class="vital-box"><i class="fas fa-ruler-vertical"></i> <strong>Height:</strong>
                                <?php echo htmlspecialchars($defaults['latest_appointment']['height']); ?> cm
                            </div>
                            <div class="vital-box"><i class="fas fa-weight"></i> <strong>Weight:</strong>
                                <?php echo htmlspecialchars($defaults['latest_appointment']['weight']); ?> kg
                            </div>
                            <div class="vital-box"><i class="fas fa-tachometer-alt"></i> <strong>BP:</strong>
                                <?php echo htmlspecialchars($defaults['latest_appointment']['bp']); ?> mmHg
                            </div>
                            <div class="vital-box"><i class="fas fa-heartbeat"></i> <strong>Cardiac Rate:</strong>
                                <?php echo htmlspecialchars($defaults['latest_appointment']['cardiac_rate']); ?> bpm
                            </div>
                            <div class="vital-box"><i class="fas fa-thermometer-half"></i> <strong>Temperature:</strong>
                                <?php echo htmlspecialchars($defaults['latest_appointment']['temperature']); ?>°C
                            </div>
                            <div class="vital-box"><i class="fas fa-lungs"></i> <strong>Resp. Rate:</strong>
                                <?php echo htmlspecialchars($defaults['latest_appointment']['resp_rate']); ?> brpm
                            </div>
                        </div>
                    </div>
                </div>
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
    </script>
    <noscript>
        <p style="text-align:center; color:red;">This site requires JavaScript to function properly. Please enable it in
            your browser.</p>
    </noscript>
</body>
</html>
