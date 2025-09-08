<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: patientLogin.php');
    exit();
}

require_once '../../config/db.php';
$patient_id = $_SESSION['patient_id'];

// Defaults
$defaults = [
    'name' => 'Patient',
    'patient_number' => '-',
    'latest_appointment' => [
        'date' => '-',
        'complaint' => '-',
        'diagnosis' => '-',
        'treatment' => '-',
        'height' => '-',
        'weight' => '-',
        'bp' => '-',
        'cardiac_rate' => '-',
        'temperature' => '-',
        'resp_rate' => '-',
    ],
    'notifications' => [],
    'activity_log' => []
];

// Query patient info
$stmt = $pdo->prepare('SELECT last_name, first_name, middle_name, suffix, username FROM patients WHERE id = ?');
$stmt->execute([$patient_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $full_name = $row['first_name'];
    if (!empty($row['middle_name'])) $full_name .= ' ' . $row['middle_name'];
    $full_name .= ' ' . $row['last_name'];
    if (!empty($row['suffix'])) $full_name .= ' ' . $row['suffix'];
    $defaults['name'] = trim($full_name);
    $defaults['patient_number'] = $row['username'];
}


// The following sections are wrapped in try/catch so the homepage loads even if those tables do not exist yet.
try {
    // Query latest appointment
    $stmt = $pdo->prepare('SELECT date, complaint, diagnosis, treatment, height, weight, bp, cardiac_rate, temperature, resp_rate FROM appointments WHERE patient_id = ? ORDER BY date DESC LIMIT 1');
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $defaults['latest_appointment'] = [
            'date' => date('F d, Y', strtotime($row['date'])),
            'complaint' => $row['complaint'],
            'diagnosis' => $row['diagnosis'],
            'treatment' => $row['treatment'],
            'height' => $row['height'],
            'weight' => $row['weight'],
            'bp' => $row['bp'],
            'cardiac_rate' => $row['cardiac_rate'],
            'temperature' => $row['temperature'],
            'resp_rate' => $row['resp_rate']
        ];
    }
} catch (PDOException $e) {
    // Table does not exist, skip
}

try {
    // Query notifications
    $stmt = $pdo->prepare('SELECT date, description, status FROM notifications WHERE patient_id = ? ORDER BY date DESC LIMIT 5');
    $stmt->execute([$patient_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $defaults['notifications'][] = [
            'date' => date('m/d/Y', strtotime($row['date'])),
            'description' => $row['description'],
            'status' => $row['status']
        ];
    }
} catch (PDOException $e) {
    // Table does not exist, skip
}

try {
    // Query activity log
    $stmt = $pdo->prepare('SELECT activity, date FROM activity_log WHERE patient_id = ? ORDER BY date DESC LIMIT 5');
    $stmt->execute([$patient_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $defaults['activity_log'][] = [
            'activity' => $row['activity'],
            'date' => date('m/d/Y', strtotime($row['date']))
        ];
    }
} catch (PDOException $e) {
    // Table does not exist, skip
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CHO Koronadal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/patientUI.css">
    <link rel="stylesheet" href="../css/patientHomepage.css">
</head>
<body>
    <div class="mobile-topbar">
        <a href="patientHomepage.php">
            <img id="topbarLogo" class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
    </div>
    <button class="mobile-toggle" onclick="toggleNav()" aria-label="Toggle Menu">
        <i id="menuIcon" class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay" onclick="closeNav()"></div>
    <nav class="nav" id="sidebar">
        <a href="patientHomepage.php">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="Sidebar Logo" />
        </a>
        <div class="menu">
            <a href="#" onclick="closeNav()"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="#" onclick="closeNav()"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
            <a href="#" onclick="closeNav()"><i class="fas fa-vials"></i> Laboratory</a>
            <a href="#" onclick="closeNav()"><i class="fas fa-file-invoice-dollar icon"></i> Billing</a>
        </div>
        <div class="user-profile">
            <a href="patientProfile.php" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <img class="profile-photo" src="PhotoController.php?patient_id=<?= urlencode($patient_id) ?>" alt="User" onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';">
                    <div class="user-text">
                        <strong><?= htmlspecialchars($defaults['name']) ?></strong>
                        <small>Patient No.: <?= htmlspecialchars($defaults['patient_number']) ?></small>
                    </div>
                    <span class="tooltip">View Profile</span>
                </div>
            </a>
            <div class="user-actions">
                <a href="patientUINotifications.html" onclick="closeNav()"><i class="fas fa-bell"></i> Notifications</a>
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
        <h1>Welcome to the <strong>CITY HEALTH OFFICE OF KORONADAL's</strong> Official Website, <?php echo htmlspecialchars($defaults['name']); ?>!</h1>
        <div class="card-container">
            <h3>What would you like to do?</h3>
            <div class="card-button-container">
                <a href="#" class="card-button blue-card">
                    <i class="fas fa-calendar-check icon"></i>
                    <h3>Set an Appointment</h3>
                    <p>Schedule a consultation or check-up.</p>
                </a>
                <a href="#" class="card-button purple-card">
                    <i class="fas fa-prescription-bottle-alt icon"></i>
                    <h3>View Prescription</h3>
                    <p>Access your prescribed medications.</p>
                </a>
                <a href="#" class="card-button orange-card">
                    <i class="fas fa-vials icon"></i>
                    <h3>View Lab Test Results</h3>
                    <p>Check your latest lab test findings.</p>
                </a>
                <a href="#" class="card-button teal-card">
                    <i class="fas fa-file-invoice-dollar icon"></i>
                    <h3>View Billing</h3>
                    <p>Review your billing and payments.</p>
                </a>
            </div>
        </div>
        <div class="info-layout">
            <div class="left-column">
                <div class="card-section latest-appointment collapsible">
                    <div class="section-header">
                        <h3>Latest Appointment</h3>
                        <a href="patientUIAppointments.html" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="appointment-layout">
                        <div class="appointment-details">
                            <div class="detail-box">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo htmlspecialchars($defaults['latest_appointment']['date']); ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="label">Chief Complaint:</span>
                                <span class="value"><?php echo htmlspecialchars($defaults['latest_appointment']['complaint']); ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="label">Diagnosis:</span>
                                <span class="value"><?php echo htmlspecialchars($defaults['latest_appointment']['diagnosis']); ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="label">Treatment:</span>
                                <span class="value"><?php echo htmlspecialchars($defaults['latest_appointment']['treatment']); ?></span>
                            </div>
                        </div>
                        <div class="appointment-vitals">
                            <div class="vital-box"><i class="fas fa-ruler-vertical"></i> <strong>Height:</strong> <?php echo htmlspecialchars($defaults['latest_appointment']['height']); ?> cm</div>
                            <div class="vital-box"><i class="fas fa-weight"></i> <strong>Weight:</strong> <?php echo htmlspecialchars($defaults['latest_appointment']['weight']); ?> kg</div>
                            <div class="vital-box"><i class="fas fa-tachometer-alt"></i> <strong>BP:</strong> <?php echo htmlspecialchars($defaults['latest_appointment']['bp']); ?> mmHg</div>
                            <div class="vital-box"><i class="fas fa-heartbeat"></i> <strong>Cardiac Rate:</strong> <?php echo htmlspecialchars($defaults['latest_appointment']['cardiac_rate']); ?> bpm</div>
                            <div class="vital-box"><i class="fas fa-thermometer-half"></i> <strong>Temperature:</strong> <?php echo htmlspecialchars($defaults['latest_appointment']['temperature']); ?>°C</div>
                            <div class="vital-box"><i class="fas fa-lungs"></i> <strong>Resp. Rate:</strong> <?php echo htmlspecialchars($defaults['latest_appointment']['resp_rate']); ?> brpm</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="right-column">
                <div class="card-section notification-card">
                    <div class="section-header">
                        <h3>Notifications</h3>
                        <a href="patientUINotifications.html" class="view-more-btn">
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
                                    <?php foreach ($defaults['notifications'] as $notif): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($notif['date']); ?></td>
                                        <td><?php echo htmlspecialchars($notif['description']); ?></td>
                                        <td><span class="status <?php echo $notif['status'] === 'read' ? 'read' : 'unread'; ?>"><?php echo ucfirst($notif['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="fade-bottom"></div>
                    </div>
                </div>
                <div class="card-section activity-log-card">
                    <div class="section-header">
                        <h3>Activity Log</h3>
                        <a href="patientUINotifications.html" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="scroll-wrapper">
                        <div class="scroll-log">
                            <ul class="activity-log">
                                <?php foreach ($defaults['activity_log'] as $log): ?>
                                <li><?php echo htmlspecialchars($log['date']); ?> - <?php echo htmlspecialchars($log['activity']); ?></li>
                                <?php endforeach; ?>
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
            // AJAX call to patientLogout.php, then redirect to patientLogin.php
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'patientLogout.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    window.location.href = '../patient/login/patientLogin.php';
                }
            };
            xhr.send();
        }
    </script>
    <style>
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .modal-content {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.15);
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
        <p style="text-align:center; color:red;">This site requires JavaScript to function properly. Please enable it in your browser.</p>
    </noscript>
</body>
</html>