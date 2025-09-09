<?php
session_start();
require_once '../../config/db.php';
$patient_id = $_SESSION['patient_id'];

// Get id from session or GET parameter
$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : (isset($_GET['id']) ? $_GET['id'] : null);
if (!$patient_id) {
    die('No patient ID provided.');
}

// Fetch patient info using PDO
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient_row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient_row) {
    die('Patient not found.');
}


// Fetch personal_information
$stmt = $pdo->prepare("SELECT * FROM personal_information WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$personal_information = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch emergency_contact
$stmt = $pdo->prepare("SELECT * FROM emergency_contact WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$emergency_contact = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch lifestyle_info
$stmt = $pdo->prepare("SELECT * FROM lifestyle_info WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$lifestyle_info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$patient = [
    // Compose full name from first, middle, last, and suffix
    "full_name" => trim($patient_row['first_name'] . ' ' . ($patient_row['middle_name'] ?? '') . ' ' . $patient_row['last_name'] . ' ' . ($patient_row['suffix'] ?? '')),
    "patient_id" => $patient_row['id'],
    "username" => $patient_row['username'] ?? '',
    // Age calculation from dob
    "age" => $patient_row['dob'] ? (date('Y') - date('Y', strtotime($patient_row['dob']))) : '',
    "sex" => $patient_row['sex'],
    "dob" => $patient_row['dob'],
    "blood_type" => $personal_information['blood_type'] ?? '',
    "civil_status" => $personal_information['civil_status'] ?? '',
    "religion" => $personal_information['religion'] ?? '',
    "occupation" => $personal_information['occupation'] ?? '',
    "contact" => $patient_row['contact_num'],
    "email" => $patient_row['email'],
    "philhealth_id" => $personal_information['philhealth_id'] ?? '',
    "address" => $personal_information['street'] ?? '',
    "barangay" => $patient_row['barangay'],
    // Emergency contact
    "emergency" => [
        "name" => trim(($emergency_contact['first_name'] ?? '') . ' ' . ($emergency_contact['middle_name'] ?? '') . ' ' . ($emergency_contact['last_name'] ?? '')),
        "relationship" => $emergency_contact['relation'] ?? '',
        "contact" => $emergency_contact['contact_num'] ?? ''
    ],
    // Lifestyle
    "lifestyle" => [
        "smoking" => $lifestyle_info['smoking_status'] ?? '',
        "alcohol" => $lifestyle_info['alcohol_intake'] ?? '',
        "activity" => $lifestyle_info['physical_act'] ?? '',
        "diet" => $lifestyle_info['diet_habit'] ?? ''
    ],
];

// Fetch latest vitals for this patient
$latest_vitals = null;
if (!empty($patient['patient_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->execute([$patient['patient_id']]);
    $latest_vitals = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch medical history using PDO from separate tables
$medical_history = [
    'past_conditions' => [],
    'chronic_illnesses' => [],
    'family_history' => [],
    'surgical_history' => [],
    'allergies' => [],
    'current_medications' => [],
    'immunizations' => []
];

// Past Medical Conditions
$stmt = $pdo->prepare("SELECT `condition`, year_diagnosed, status FROM past_medical_conditions WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['past_conditions'][] = $row;
}

// Chronic Illnesses
$stmt = $pdo->prepare("SELECT illness, year_diagnosed, management FROM chronic_illnesses WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['chronic_illnesses'][] = $row;
}

// Family History
$stmt = $pdo->prepare("SELECT family_member, `condition`, age_diagnosed, current_status FROM family_history WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['family_history'][] = $row;
}

// Surgical History
$stmt = $pdo->prepare("SELECT surgery, year, hospital FROM surgical_history WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['surgical_history'][] = $row;
}

// Current Medications
$stmt = $pdo->prepare("SELECT medication, dosage, frequency, prescribed_by FROM current_medications WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['current_medications'][] = $row;
}

// Allergies
$stmt = $pdo->prepare("SELECT allergen, reaction, severity FROM allergies WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['allergies'][] = $row;
}

// Immunizations
$stmt = $pdo->prepare('SELECT vaccine, year_received, doses_completed, status FROM immunizations WHERE patient_id = ?');
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['immunizations'][] = $row;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login/patientLogin.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Patient Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Sidebar/Nav/Main styles -->
    <link rel="stylesheet" href="../css/patientUI.css">
    <!-- Profile-specific styles -->
    <link rel="stylesheet" href="../css/patientProfile.css">
    <style>
        .custom-modal {
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

        .custom-modal.active {
            display: flex !important;
        }

        .custom-modal .modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18);
            padding: 2em 2.5em;
            max-width: 600px;
            width: 95vw;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .profile-heading-bar {
            flex-wrap: wrap;
            gap: 1em;
        }

        .utility-btn-group {
            display: flex;
            gap: 0.7em;
            flex-wrap: wrap;
        }

        .utility-btn {
            font-size: 1em;
            min-width: 44px;
            transition: background 0.18s;
        }

        .utility-btn:hover {
            filter: brightness(0.93);
        }

        @media (max-width: 700px) {
            .profile-heading-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.7em;
            }

            .utility-btn-group {
                gap: 0.5em;
            }

            .utility-btn {
                padding: 0.5em 0.8em;
                font-size: 0.98em;
            }

            .hide-on-mobile {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile top bar -->
    <div class="mobile-topbar">
        <a href="patientHomepage.php">
            <img id="topbarLogo" class="logo"
                src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
    </div>
    <button class="mobile-toggle" onclick="toggleNav()" aria-label="Toggle Menu">
        <i id="menuIcon" class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay" onclick="closeNav()"></div>
    <nav class="nav" id="sidebar">
        <a href="patientHomepage.php">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527"
                alt="Sidebar Logo" />
        </a>
        <div class="menu">
            <a href="patientsAppointment.php"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="patient/patientsPrescription.php"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
            <a href="patient/patientsLaboratory.php"><i class="fas fa-vials"></i> Laboratory</a>
            <a href="patient/patientsBilling.php"><i class="fas fa-file-invoice-dollar icon"></i> Billing</a>
        </div>
        <div class="user-profile">
            <a href="patientProfile.php" style="text-decoration: none; color: inherit;">
                <div class="user-info">
                    <img src="PhotoController.php?patient_id=<?= urlencode($patient['patient_id']) ?>"
                        alt="User Profile"
                        onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';" />
                    <div class="user-text">
                        <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
                        <small>Patient No.: <?= htmlspecialchars($patient['username']) ?></small>
                    </div>
                    <span class="tooltip">View Profile</span>
                </div>
            </a>
            <div class="user-actions">
                <a href="#"><i class="fas fa-bell"></i> Notifications</a>
                <a href="#"><i class="fas fa-cog"></i> Settings</a>
                <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
            <!-- Logout Confirmation Modal -->
            <div id="logoutModal"
                style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
                <div
                    style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:2em 2.5em;max-width:350px;text-align:center;animation:modalFadeIn 0.2s;">
                    <h3 style="margin-top:0;color:#c0392b;">Sign Out?</h3>
                    <p>Are you sure you want to sign out?</p>
                    <div style="display:flex;gap:1em;justify-content:center;">
                        <button id="logoutConfirm"
                            style="background:#c0392b;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Sign
                            Out</button>
                        <button id="logoutCancel"
                            style="background:#eaeaea;color:#333;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="homepage">
        <div class="profile-heading-bar"
            style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1em;margin-bottom:1.5em;">
            <h1 style="margin:0;font-size:2.2em;letter-spacing:1px;">PATIENT PROFILE</h1>
            <div class="utility-btn-group" style="display:flex;gap:0.7em;flex-wrap:wrap;">
                <button class="utility-btn" onclick="downloadPatientFile()" title="Download Patient File"
                    style="background:#2980b9;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(41,128,185,0.08);cursor:pointer;transition:background 0.18s;">
                    <i class="fas fa-file-download"></i> <span class="hide-on-mobile">Download Patient File</span>
                </button>
                <button class="utility-btn" onclick="downloadPatientID()" title="Download Patient ID Card"
                    style="background:#16a085;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(22,160,133,0.08);cursor:pointer;transition:background 0.18s;">
                    <i class="fas fa-id-card"></i> <span class="hide-on-mobile">Download ID Card</span>
                </button>
                <button class="utility-btn" onclick="openUserSettings()" title="User Settings"
                    style="background:#f39c12;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(243,156,18,0.08);cursor:pointer;transition:background 0.18s;">
                    <i class="fas fa-cog"></i> <span class="hide-on-mobile">User Settings</span>
                </button>
            </div>
        </div>
        <div class="profile-layout" style="max-width: none;">
            <!-- LEFT SIDE -->
            <div class="profile-wrapper">
                <!-- Top Header Card -->
                <div class="profile-header">
                    <img class="profile-photo"
                        src="PhotoController.php?patient_id=<?= urlencode($patient['patient_id']) ?>" alt="User"
                        onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';">
                    <div class="profile-info">
                        <div class="profile-name-number">
                            <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                            <p>Patient Number: <?= htmlspecialchars($patient['username']) ?></p>
                        </div>

                    </div>
                </div>
                <!-- Personal Information -->
                <div class="profile-card">
                    <div class="section-header">
                        <h3>Personal Information</h3>
                        <a href="Profile/patientEditProfile.php" class="btn edit-btn">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                style="height:1em;width:1em;margin-right:0.5em;vertical-align:middle;" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5h2m-1-1v2m10.54 1.46a2.12 2.12 0 00-3 0l-9 9a2 2 0 00-.51 1.07l-1 5a1 1 0 001.21 1.21l5-1a2 2 0 001.07-.51l9-9a2.12 2.12 0 000-3z" />
                            </svg>
                            Edit
                        </a>
                    </div>
                    <div class="info-section">
                        <div class="info-row"><span>AGE:</span><span><?= htmlspecialchars($patient['age']) ?></span>
                        </div>
                        <div class="info-row"><span>SEX:</span><span><?= htmlspecialchars($patient['sex']) ?></span>
                        </div>
                        <div class="info-row"><span>DATE OF
                                BIRTH:</span><span><?= htmlspecialchars($patient['dob']) ?></span></div>
                        <div class="info-row"><span>BLOOD
                                TYPE:</span><span><?= htmlspecialchars($patient['blood_type']) ?></span></div>
                        <div class="info-row"><span>CIVIL
                                STATUS:</span><span><?= htmlspecialchars($patient['civil_status']) ?></span></div>
                        <div class="info-row">
                            <span>RELIGION:</span><span><?= htmlspecialchars($patient['religion']) ?></span>
                        </div>
                        <div class="info-row">
                            <span>OCCUPATION:</span><span><?= htmlspecialchars($patient['occupation']) ?></span>
                        </div>
                        <div class="info-row"><span>CONTACT
                                NO.:</span><span><?= htmlspecialchars($patient['contact']) ?></span></div>
                        <div class="info-row"><span>EMAIL:</span><span><?= htmlspecialchars($patient['email']) ?></span>
                        </div>
                        <div class="info-row"><span>PHILHEALTH
                                ID:</span><span><?= htmlspecialchars($patient['philhealth_id']) ?></span></div>
                        <div class="info-row"><span>HOUSE NO. &
                                STREET:</span><span><?= htmlspecialchars($patient['address']) ?></span></div>
                        <div class="info-row">
                            <span>BARANGAY:</span><span><?= htmlspecialchars($patient['barangay']) ?></span>
                        </div>
                    </div>
                </div>
                <!-- Emergency Contact -->
                <div class="profile-card">
                    <h3>Emergency Contact</h3>
                    <div class="info-section">
                        <div class="info-row">
                            <span>NAME:</span><span><?= htmlspecialchars($patient['emergency']['name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span>RELATIONSHIP:</span><span><?= htmlspecialchars($patient['emergency']['relationship']) ?></span>
                        </div>
                        <div class="info-row"><span>CONTACT
                                NO.:</span><span><?= htmlspecialchars($patient['emergency']['contact']) ?></span></div>
                    </div>
                </div>
                <!-- Lifestyle Information -->
                <div class="profile-card">
                    <h3>Lifestyle Information</h3>
                    <div class="info-section">
                        <div class="info-row"><span>SMOKING
                                STATUS:</span><span><?= htmlspecialchars($patient['lifestyle']['smoking']) ?></span>
                        </div>
                        <div class="info-row"><span>ALCOHOL
                                INTAKE:</span><span><?= htmlspecialchars($patient['lifestyle']['alcohol']) ?></span>
                        </div>
                        <div class="info-row"><span>PHYSICAL
                                ACTIVITY:</span><span><?= htmlspecialchars($patient['lifestyle']['activity']) ?></span>
                        </div>
                        <div class="info-row"><span>DIETARY
                                HABIT:</span><span><?= htmlspecialchars($patient['lifestyle']['diet']) ?></span></div>
                    </div>
                </div>
            </div>
            <!-- RIGHT SIDE -->
            <div class="patient-summary-section">
                <!-- 1. Latest Vitals -->
                <div class="summary-card vitals-section">
                    <div class="section-header">
                        <h2>Latest Vitals</h2>
                        <small><i>
                                <?php
                                if ($latest_vitals && !empty($latest_vitals['recorded_at'])) {
                                    echo "as of " . date("F d, Y h:i A", strtotime($latest_vitals['recorded_at']));
                                } else {
                                    echo "No vitals recorded.";
                                }
                                ?>
                            </i></small>
                    </div>
                    <div class="vitals-grid">
                        <div class="vital-card">
                            <i class="fas fa-ruler-vertical"></i>
                            <div><span class="label">HEIGHT</span><br><strong><?= $latest_vitals ? htmlspecialchars($latest_vitals['ht']) . ' cm' : '-' ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-weight"></i>
                            <div><span class="label">WEIGHT</span><br><strong><?= $latest_vitals ? htmlspecialchars($latest_vitals['wt']) . ' kg' : '-' ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-tachometer-alt"></i>
                            <div><span class="label">BLOOD PRESSURE</span><br><strong><?= $latest_vitals ? htmlspecialchars($latest_vitals['bp']) : '-' ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-heartbeat"></i>
                            <div><span class="label">CARDIAC RATE</span><br><strong><?= $latest_vitals ? htmlspecialchars($latest_vitals['hr']) . ' bpm' : '-' ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-thermometer-half"></i>
                            <div><span class="label">TEMPERATURE</span><br><strong><?= $latest_vitals ? htmlspecialchars($latest_vitals['temp']) . ' °C' : '-' ?></strong></div>
                        </div>
                        <div class="vital-card">
                            <i class="fas fa-lungs"></i>
                            <div><span class="label">RESPIRATORY RATE</span><br><strong><?= $latest_vitals ? htmlspecialchars($latest_vitals['rr']) . ' bpm' : '-' ?></strong></div>
                        </div>
                    </div>
                </div>
                <!-- You may add PHP loops for appointments and medical history as needed -->
                <!-- Example static content for illustration: -->
                <div class="summary-card appointment-section">
                    <div class="section-header">
                        <h2>Latest Appointment</h2>
                        <a href="#" class="view-more-btn">
                            <i class="fas fa-chevron-right"></i> View More
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <td>09:30 AM</td>
                                <td>General Check-up</td>
                                <td>Completed</td>
                                <td>All good</td>
                                </tr>
                                <tr>
                                    <td>2025-03-14</td>
                                    <td>10:15 AM</td>
                                    <td>Blood Test</td>
                                    <td>Completed</td>
                                    <td>Normal</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Medical History Section -->
                <div class="summary-card medical-history-section">
                    <!-- Medical History Header -->
                    <div
                        style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1em; flex-wrap: wrap; gap: 0.5em;">
                        <h2>Medical History</h2>
                        <a href="MedicalHistory/patientEditMedHistory.php" class="btn edit-btn">
                            <!-- Pencil SVG icon (inline) -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                style="height:1em;width:1em;margin-right:0.5em;vertical-align:middle;" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5h2m-1-1v2m10.54 1.46a2.12 2.12 0 00-3 0l-9 9a2 2 0 00-.51 1.07l-1 5a1 1 0 001.21 1.21l5-1a2 2 0 001.07-.51l9-9a2.12 2.12 0 000-3z" />
                            </svg>
                            Edit
                        </a>
                    </div>
                    <!-- Medical History Grid -->
                    <div class="medical-grid">
                        <!-- Past Medical Conditions-->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Past Medical Conditions</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Condition</th>
                                        <th>Year Diagnosed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['past_conditions'])): ?>
                                        <?php foreach (array_slice($medical_history['past_conditions'], 0, 2) as $condition): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($condition['condition']) ?></td>
                                                <td><?= htmlspecialchars($condition['year_diagnosed']) ?></td>
                                                <td><?= htmlspecialchars($condition['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Chronic Illnesses -->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Chronic Illnesses</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Illness</th>
                                        <th>Year Diagnosed</th>
                                        <th>Management</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['chronic_illnesses'])): ?>
                                        <?php foreach (array_slice($medical_history['chronic_illnesses'], 0, 2) as $illness): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($illness['illness']) ?></td>
                                                <td><?= htmlspecialchars($illness['year_diagnosed']) ?></td>
                                                <td><?= htmlspecialchars($illness['management']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <!-- Modal for Chronic Illnesses -->
                            <div id="ciModal" class="custom-modal"
                                style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
                                <div class="modal-content"
                                    style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:2em 2.5em;max-width:600px;width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
                                    <button onclick="closeModal('ciModal')"
                                        style="position:absolute;top:1em;right:1em;background:none;border:none;font-size:1.5em;color:#c0392b;cursor:pointer;">&times;</button>
                                    <h3 style="margin-top:0;color:#333;">Chronic Illnesses</h3>
                                    <table style="width:100%;margin-bottom:1em;">
                                        <thead>
                                            <tr style="background:#f5f5f5;">
                                                <th style="padding:0.5em;">Illness</th>
                                                <th style="padding:0.5em;">Year Diagnosed</th>
                                                <th style="padding:0.5em;">Management</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($medical_history['chronic_illnesses'])): ?>
                                                <?php foreach ($medical_history['chronic_illnesses'] as $illness): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($illness['illness']) ?></td>
                                                        <td><?= htmlspecialchars($illness['year_diagnosed']) ?></td>
                                                        <td><?= htmlspecialchars($illness['management']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" style="text-align:center;color:#888;">No records found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <form method="post" action="add_chronic_illness.php" style="margin-top:1em;">
                                        <h4 style="margin-bottom:0.5em;">Add New Illness</h4>
                                        <div style="display:flex;gap:0.5em;flex-wrap:wrap;">
                                            <input type="text" name="illness" placeholder="Illness" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="number" name="year_diagnosed" placeholder="Year" min="1900"
                                                max="<?= date('Y') ?>" required
                                                style="width:100px;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="text" name="management" placeholder="Management" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                            <button type="submit"
                                                style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Family History -->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Family History</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Family Member</th>
                                        <th>Condition</th>
                                        <th>Age Diagnosed</th>
                                        <th>Current Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['family_history'])): ?>
                                        <?php foreach (array_slice($medical_history['family_history'], 0, 2) as $fh): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fh['family_member']) ?></td>
                                                <td><?= htmlspecialchars($fh['condition']) ?></td>
                                                <td><?= htmlspecialchars($fh['age_diagnosed']) ?></td>
                                                <td><?= htmlspecialchars($fh['current_status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <!-- Modal for Family History -->
                            <div id="fhModal" class="custom-modal"
                                style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
                                <div class="modal-content"
                                    style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:2em 2.5em;max-width:600px;width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
                                    <button onclick="closeModal('fhModal')"
                                        style="position:absolute;top:1em;right:1em;background:none;border:none;font-size:1.5em;color:#c0392b;cursor:pointer;">&times;</button>
                                    <h3 style="margin-top:0;color:#333;">Family History</h3>
                                    <table style="width:100%;margin-bottom:1em;">
                                        <thead>
                                            <tr style="background:#f5f5f5;">
                                                <th style="padding:0.5em;">Family Member</th>
                                                <th style="padding:0.5em;">Condition</th>
                                                <th style="padding:0.5em;">Age Diagnosed</th>
                                                <th style="padding:0.5em;">Current Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($medical_history['family_history'])): ?>
                                                <?php foreach ($medical_history['family_history'] as $fh): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($fh['family_member']) ?></td>
                                                        <td><?= htmlspecialchars($fh['condition']) ?></td>
                                                        <td><?= htmlspecialchars($fh['age_diagnosed']) ?></td>
                                                        <td><?= htmlspecialchars($fh['current_status']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" style="text-align:center;color:#888;">No records found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <form method="post" action="add_family_history.php" style="margin-top:1em;">
                                        <h4 style="margin-bottom:0.5em;">Add New Family History</h4>
                                        <div style="display:flex;gap:0.5em;flex-wrap:wrap;">
                                            <input type="text" name="family_member" placeholder="Family Member" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="text" name="condition" placeholder="Condition" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="number" name="age_diagnosed" placeholder="Age Diagnosed" min="0" max="120"
                                                required style="width:100px;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="text" name="current_status" placeholder="Current Status" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                            <button type="submit"
                                                style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Surgical History -->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Surgical History</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Surgery</th>
                                        <th>Year</th>
                                        <th>Hospital</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['surgical_history'])): ?>
                                        <?php foreach (array_slice($medical_history['surgical_history'], 0, 2) as $surgery): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($surgery['surgery']) ?></td>
                                                <td><?= htmlspecialchars($surgery['year']) ?></td>
                                                <td><?= htmlspecialchars($surgery['hospital']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <!-- Modal for Surgical History -->
                            <div id="shModal" class="custom-modal"
                                style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
                                <div class="modal-content"
                                    style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:2em 2.5em;max-width:600px;width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
                                    <button onclick="closeModal('shModal')"
                                        style="position:absolute;top:1em;right:1em;background:none;border:none;font-size:1.5em;color:#c0392b;cursor:pointer;">&times;</button>
                                    <h3 style="margin-top:0;color:#333;">Surgical History</h3>
                                    <table style="width:100%;margin-bottom:1em;">
                                        <thead>
                                            <tr style="background:#f5f5f5;">
                                                <th style="padding:0.5em;">Surgery</th>
                                                <th style="padding:0.5em;">Year</th>
                                                <th style="padding:0.5em;">Hospital</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($medical_history['surgical_history'])): ?>
                                                <?php foreach ($medical_history['surgical_history'] as $surgery): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($surgery['surgery']) ?></td>
                                                        <td><?= htmlspecialchars($surgery['year']) ?></td>
                                                        <td><?= htmlspecialchars($surgery['hospital']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" style="text-align:center;color:#888;">No records found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <form method="post" action="add_surgical_history.php" style="margin-top:1em;">
                                        <h4 style="margin-bottom:0.5em;">Add New Surgery</h4>
                                        <div style="display:flex;gap:0.5em;flex-wrap:wrap;">
                                            <input type="text" name="surgery" placeholder="Surgery" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="number" name="year" placeholder="Year" min="1900" max="<?= date('Y') ?>"
                                                required style="width:100px;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="text" name="hospital" placeholder="Hospital" required
                                                style="flex:1;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                            <button type="submit"
                                                style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Allergies -->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Allergies</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Allergen</th>
                                        <th>Reaction</th>
                                        <th>Severity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['allergies'])): ?>
                                        <?php foreach (array_slice($medical_history['allergies'], 0, 2) as $idx => $allergy): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($allergy['allergen']) ?></td>
                                                <td><?= htmlspecialchars($allergy['reaction']) ?></td>
                                                <td><?= htmlspecialchars($allergy['severity']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <!-- Modal for Allergies -->
                            <div id="allergyModal" class="custom-modal"
                                style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
                                <div class="modal-content"
                                    style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:2em 2.5em;max-width:600px;width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
                                    <button type="button" onclick="closeModal('allergyModal')"
                                        style="position:absolute;top:1em;right:1em;background:none;border:none;font-size:1.5em;color:#c0392b;cursor:pointer;">&times;</button>
                                    <h3 style="margin-top:0;color:#333;">Allergies</h3>
                                    <table style="width:100%;margin-bottom:1em;">
                                        <thead>
                                            <tr style="background:#f5f5f5;">
                                                <th style="padding:0.5em;">Allergen</th>
                                                <th style="padding:0.5em;">Reaction</th>
                                                <th style="padding:0.5em;">Severity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($medical_history['allergies'])): ?>
                                                <?php foreach ($medical_history['allergies'] as $allergy): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($allergy['allergen']) ?></td>
                                                        <td><?= htmlspecialchars($allergy['reaction']) ?></td>
                                                        <td><?= htmlspecialchars($allergy['severity']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" style="text-align:center;color:#888;">No records found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <form method="post" action="add_allergy.php" style="margin-top:1em;">
                                        <h4 style="margin-bottom:0.5em;">Add New Allergy</h4>
                                        <div style="display:flex;gap:0.5em;flex-wrap:wrap;align-items:flex-start;">
                                            <!-- Allergen Dropdown -->
                                            <div style="flex:1;min-width:150px;">
                                                <select name="allergen_dropdown" id="allergenSelect" required
                                                    onchange="toggleOtherInput(this, 'allergenOtherInput')"
                                                    style="width:100%;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                                    <option value="">Select Allergen</option>
                                                    <option value="Peanuts">Peanuts</option>
                                                    <option value="Penicillin">Penicillin</option>
                                                    <option value="Pollen">Pollen</option>
                                                    <option value="Shellfish">Shellfish</option>
                                                    <option value="Eggs">Eggs</option>
                                                    <option value="Milk">Milk</option>
                                                    <option value="Latex">Latex</option>
                                                    <option value="Insect Stings">Insect Stings</option>
                                                    <option value="Dust Mites">Dust Mites</option>
                                                    <option value="Mold">Mold</option>
                                                    <option value="Others">Others (specify)</option>
                                                </select>
                                                <input type="text" id="allergenOtherInput" placeholder="Specify Allergen"
                                                    style="display:none;margin-top:0.3em;width:100%;padding:0.5em;border:1px solid #ccc;border-radius:5px;" />
                                            </div>
                                            <!-- Reaction Dropdown -->
                                            <div style="flex:1;min-width:150px;">
                                                <select name="reaction_dropdown" id="reactionSelect" required
                                                    onchange="toggleOtherInput(this, 'reactionOtherInput')"
                                                    style="width:100%;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                                    <option value="">Select Reaction</option>
                                                    <option value="Rash">Rash</option>
                                                    <option value="Anaphylaxis">Anaphylaxis</option>
                                                    <option value="Swelling">Swelling</option>
                                                    <option value="Hives">Hives</option>
                                                    <option value="Itching">Itching</option>
                                                    <option value="Shortness of Breath">Shortness of Breath</option>
                                                    <option value="Vomiting">Vomiting</option>
                                                    <option value="Others">Others (specify)</option>
                                                </select>
                                                <input type="text" id="reactionOtherInput" placeholder="Specify Reaction"
                                                    style="display:none;margin-top:0.3em;width:100%;padding:0.5em;border:1px solid #ccc;border-radius:5px;" />
                                            </div>
                                            <!-- Severity Dropdown -->
                                            <div style="flex:1;min-width:120px;">
                                                <select name="severity" required
                                                    style="width:100%;padding:0.5em;border:1px solid #ccc;border-radius:5px;">
                                                    <option value="">Select Severity</option>
                                                    <option value="Mild">Mild</option>
                                                    <option value="Moderate">Moderate</option>
                                                    <option value="Severe">Severe</option>
                                                </select>
                                            </div>
                                            <input type="hidden" name="allergen" id="allergenFinal" />
                                            <input type="hidden" name="reaction" id="reactionFinal" />
                                            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                            <button type="submit"
                                                style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                                        </div>
                                        <script>
                                            function toggleOtherInput(selectElem, inputId) {
                                                var input = document.getElementById(inputId);
                                                if (selectElem.value === 'Others') {
                                                    input.style.display = 'block';
                                                    input.required = true;
                                                } else {
                                                    input.style.display = 'none';
                                                    input.required = false;
                                                }
                                            }
                                            // On submit, set hidden allergen and reaction fields to the correct value
                                            var allergyForm = document.currentScript.parentElement.parentElement;
                                            allergyForm.onsubmit = function(e) {
                                                var allergenSel = document.getElementById('allergenSelect');
                                                var allergenOther = document.getElementById('allergenOtherInput');
                                                var allergenFinal = document.getElementById('allergenFinal');
                                                allergenFinal.value = (allergenSel.value === 'Others') ? allergenOther.value : allergenSel.value;
                                                var reactionSel = document.getElementById('reactionSelect');
                                                var reactionOther = document.getElementById('reactionOtherInput');
                                                var reactionFinal = document.getElementById('reactionFinal');
                                                reactionFinal.value = (reactionSel.value === 'Others') ? reactionOther.value : reactionSel.value;
                                                // Prevent submit if required fields are empty
                                                if (!allergenFinal.value || !reactionFinal.value) {
                                                    alert('Please fill out all required fields.');
                                                    return false;
                                                }
                                                return true;
                                            }
                                        </script>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Current Medications -->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Current Medications</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Prescribed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['current_medications'])): ?>
                                        <?php foreach (array_slice($medical_history['current_medications'], 0, 2) as $med): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($med['medication']) ?></td>
                                                <td><?= htmlspecialchars($med['dosage']) ?></td>
                                                <td><?= htmlspecialchars($med['frequency']) ?></td>
                                                <td><?= htmlspecialchars($med['prescribed_by']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Immunizations -->
                        <div class="medical-card">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <h4>Immunizations</h4>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Vaccine</th>
                                        <th>Year Received</th>
                                        <th>Doses Completed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medical_history['immunizations'])): ?>
                                        <?php foreach (array_slice($medical_history['immunizations'], 0, 2) as $imm): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($imm['vaccine']) ?></td>
                                                <td><?= htmlspecialchars($imm['year_received']) ?></td>
                                                <td><?= htmlspecialchars($imm['doses_completed']) ?></td>
                                                <td><?= htmlspecialchars($imm['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        function downloadPatientFile() {
            // TODO: Implement file download logic
            alert('Download Patient File feature coming soon.');
        }

        function downloadPatientID() {
            // TODO: Implement ID card download logic
            alert('Download Patient ID Card feature coming soon.');
        }

        function openUserSettings() {
            // TODO: Implement user settings modal or redirect
            alert('User Settings feature coming soon.');
        }
        // Logout popup logic
        document.addEventListener('DOMContentLoaded', function() {
            var logoutBtn = document.getElementById('logoutBtn');
            var logoutModal = document.getElementById('logoutModal');
            var logoutConfirm = document.getElementById('logoutConfirm');
            var logoutCancel = document.getElementById('logoutCancel');
            if (logoutBtn && logoutModal) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    logoutModal.style.display = 'flex';
                });
            }
            if (logoutCancel) {
                logoutCancel.addEventListener('click', function() {
                    logoutModal.style.display = 'none';
                });
            }
            if (logoutConfirm) {
                logoutConfirm.addEventListener('click', function() {
                    window.location.href = '?logout=1';
                });
            }
            // Close modal on outside click
            if (logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === logoutModal) logoutModal.style.display = 'none';
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
            if (topbarLogo) topbarLogo.style.display = isOpen ? 'none' : 'block';
            menuIcon.classList.toggle('fa-bars', !isOpen);
            menuIcon.classList.toggle('fa-times', isOpen);
        }

        function closeNav() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            if (document.getElementById('topbarLogo')) document.getElementById('topbarLogo').style.display = 'block';
            const menuIcon = document.getElementById('menuIcon');
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
    </script>
</body>

</html>