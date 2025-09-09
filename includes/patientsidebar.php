<?php
// Start session and fetch patient data if available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : null;
$patient = null;
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}
$defaults = [
    'name' => $patient ? $patient['first_name'] . ' ' . $patient['last_name'] : 'Jane Doe',
    'patient_number' => $patient ? $patient['username'] : '000000'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CHO Koronadal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/patientSidebar.css">
</head>

<body>
    <nav class="nav" id="sidebar">
        <a href="patientHomepage.php">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="Sidebar Logo" />
        </a>
        <div class="menu">
            <a href="patientsAppointment.php" onclick="closeNav()"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="patient/patientsPrescription.php" onclick="closeNav()"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
            <a href="public/patient/patientsLaboratory.php" onclick="closeNav()"><i class="fas fa-vials"></i> Laboratory</a>
            <a href="public/patient/patientsBilling.php" onclick="closeNav()"><i class="fas fa-file-invoice-dollar icon"></i> Billing</a>
        </div>
        <div class="user-profile">
            <a href="patientProfile.php" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <img class="profile-photo" src="<?php
                                                    if ($patient_id) {
                                                        echo '/WBHSMS-CHO/public/patient/PhotoController.php?patient_id=' . urlencode($patient_id);
                                                    } else {
                                                        echo 'https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';
                                                    }
                                                    ?>" alt="User" onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';">
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
    <div class="mobile-topbar">
        <a href="patientHomepage.php">
            <img id="topbarLogo" class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
        <button class="mobile-toggle" onclick="toggleNav()" aria-label="Toggle Menu">
            <i id="menuIcon" class="fas fa-bars"></i>
        </button>
    </div>

    <div class="overlay" id="overlay" onclick="closeNav()"></div>
    <?php
    // <section class="homepage">
    //     <!-- Main content goes here -->
    // </section>
    ?>
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
            // AJAX call to ../public/patient/patientLogout.php, then redirect to ../public/patient/login/patientLogin.php
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../public/patient/patientLogout.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    window.location.href = '../public/patient/login/patientLogin.php';
                }
            };
            xhr.send();
        }
    </script>
</body>

</html>