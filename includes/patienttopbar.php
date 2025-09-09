<?php
session_start();
require_once '../config/db.php';
// Only allow logged-in patients
/*$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : null;
if (!$patient_id) {
    header('Location: login/patientLogin.php');
    exit();
}
// Fetch patient info
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die('Patient not found.');
}*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Patient Sidebar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #222;
            background-image: url('https://ik.imagekit.io/wbhsmslogo/Blue%20Minimalist%20Background.png?updatedAt=1752410073954');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }

        .topbar {
            width: auto;
            min-width: 0;
            background: #03045e;
            box-shadow: 0 2px 8px #0001;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.7rem 2rem 0.7rem 1.2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* Equal spacing for topbar children */
        .edit-profile-topbar>div {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-profile-topbar .topbar-logo {
            justify-content: flex-start;
        }

        .edit-profile-topbar .topbar-userinfo {
            justify-content: flex-end;
        }

        /* Responsive logo: use <picture> for mobile swap */
        .topbar-logo img.responsive-logo {
            height: 48px;
            width: auto;
            display: block;
        }

        .topbar-title {
            flex: 1;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: #0077b6;
            letter-spacing: 0.5px;
        }

        .topbar-userinfo {
            display: flex;
            max-width: 185px;
            align-items: center;
            justify-content: flex-end;
            gap: 0.7rem;
        }

        .topbar-userphoto {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #eee;
        }

        .topbar-usertext {
            flex-direction: column;
            align-items: flex-end;
            font-size: 1rem;
            text-align: right;
        }

        .topbar-usertext strong {
            color: #222;
        }

        .topbar-usertext small {
            color: #888;
        }

        main.patientsidebar-main {
            min-height: 90vh;
            width: 100vw;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 0;
        }

        .patientsidebar-content {
            width: 100%;
            max-width: 1200px;
            min-height: 60vh;
        }

        @media (max-width: 900px) {
            .edit-profile-topbar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
                padding: 0.7rem 0.5rem;
            }

            .topbar-title {
                font-size: 1.2rem;
                margin: 0.2rem 0;
            }

            .topbar-userinfo {
                justify-content: flex-end;
            }
        }

        @media (max-width: 600px) {
            .topbar-usertext {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Topbar -->
    <header class="topbar">
        <div>
            <a href="patientHomepage.php" class="topbar-logo">
                <picture>
                    <source media="(max-width: 600px)"
                        srcset="https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128">
                    <img src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527"
                        alt="City Health Logo" class="responsive-logo" />
                </picture>
            </a>
        </div>
        <div class="topbar-title" style="color: #ffffff;">Edit Patient Profile</div>
        <div class="topbar-userinfo">
            <div class="topbar-usertext">
                <strong style="color: #ffffff;">
                    <?php
                    if (isset($patient) && !empty($patient['first_name']) && !empty($patient['last_name'])) {
                        echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']);
                    } else {
                        echo 'John Doe';
                    }
                    ?>
                </strong><br>
                <small style="color: #ffffff;">Patient</small>
            </div>
            <img src="<?php
                if (isset($patient_id) && !empty($patient_id)) {
                    echo 'patient_profile_photo.php?patient_id=' . urlencode($patient_id);
                } else {
                    echo 'https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';
                }
            ?>" alt="User Profile"
                class="topbar-userphoto"
                onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';" />
        </div>
    </header>

    <!-- Main Content Area with background -->
    <main class="patientsidebar-main">
        <!-- Slot for main content goes here -->
        <div class="patientsidebar-content">
            <!-- Main content placeholder -->
        </div>
    </main>
</body>

</html>