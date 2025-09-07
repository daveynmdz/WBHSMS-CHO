<?php
session_start();
require_once 'db.php';

// --- Auth & Session ---
if (!isset($_SESSION['employee_id'], $_SESSION['role'])) {
    die('Session missing. Please log in again.');
}
$employee_id = (int) $_SESSION['employee_id'];
$role = $_SESSION['role'];

// --- Get POST data ---
$fields = [
    'patient_id',
    'chief_complaint',
    'symptoms',
    'barangay',
    'referred_to_facility',
    'referred_to_external',
    'assessment',
    'service',
    'ht',
    'wt',
    'bp',
    'hr',
    'rr',
    'temp',
    'reason_referral',
    'reason_referral_other'
];
foreach ($fields as $f) {
    $$f = $_POST[$f] ?? '';
}
$reason_for_referral = ($reason_referral === 'Other' && $reason_referral_other) ? $reason_referral_other : $reason_referral;

// --- Get patient details ---
$patient = null;
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Fetch personal_information for street ---
$personal = [];
$stmt = $pdo->prepare("SELECT * FROM personal_information WHERE patient_id = ? LIMIT 1");
$stmt->execute([$patient_id]);
$personal = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// --- Get facility details ---
$facility_name = '';
$destination_type = '';
if ($referred_to_facility && $referred_to_facility !== 'external') {
    $stmt = $pdo->prepare("SELECT name, type FROM facilities WHERE id = ? LIMIT 1");
    $stmt->execute([$referred_to_facility]);
    $facility_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $facility_name = $facility_row ? $facility_row['name'] : '';
    $type = strtolower($facility_row['type'] ?? '');
    if ($type === 'city' || $type === 'district') {
        $destination_type = $type;
    } else {
        $destination_type = 'city'; // default fallback
    }
} elseif ($referred_to_facility === 'external') {
    $facility_name = $referred_to_external;
    $destination_type = 'external';
}

// --- Age calculation ---
// Note: Use $patient['dob'] (not birthday)
function calculate_age($dob)
{
    if (!$dob)
        return '';
    $dob_dt = new DateTime($dob);
    $now = new DateTime();
    $age = $now->diff($dob_dt)->y;
    return $age;
}
$age = $patient ? calculate_age($patient['dob'] ?? '') : '';

// --- Handle confirmation ---
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_referral'])) {
    // Insert vitals
    $stmt = $pdo->prepare("INSERT INTO vitals (patient_id, ht, wt, bp, hr, rr, temp, date_recorded) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $patient_id,
        $ht,
        $wt,
        $bp,
        $hr,
        $rr,
        $temp
    ]);
    $vitals_id = $pdo->lastInsertId();

    // Insert referral - use patient's barangay, and add destination_type column
    $stmt = $pdo->prepare("INSERT INTO referrals (
        patient_id, barangay, issued_by, referred_to_facility, referred_to_external, chief_complaint, symptoms, assessment, service_id, vitals_id, reason_for_referral, destination_type, status, date_of_referral, referral_num
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), '')");
    $stmt->execute([
        $patient_id,
        $patient['barangay'] ?? '', // Use patient's barangay from patient table
        $employee_id,
        $referred_to_facility === 'external' ? NULL : $referred_to_facility,
        $referred_to_facility === 'external' ? $referred_to_external : NULL,
        $chief_complaint,
        $symptoms,
        $assessment,
        $service,
        $vitals_id,
        $reason_for_referral,
        $destination_type // new column for destination type
    ]);
    $referral_id = $pdo->lastInsertId();

    // Generate and update 7-digit referral_num based on id
    $referral_num = str_pad($referral_id, 7, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("UPDATE referrals SET referral_num = ? WHERE id = ?");
    $stmt->execute([$referral_num, $referral_id]);

    header("Location: verifyReferral.php?success=1&referral_num=$referral_num");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Referral</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/patientUI.css">
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
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 0 auto;
            flex-wrap: wrap;
            box-sizing: border-box;
        }

        .profile-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(60, 74, 123, 0.10);
            padding: 2em 2em 1em 2em;
            margin: 0.5em 0;
            flex: 1 1 100%;
            min-width: 320px;
            max-width: 100%;
            box-sizing: border-box;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }

        .summary-table th,
        .summary-table td {
            padding: 0.5em 0.8em;
            border-bottom: 1px solid #eaeaea;
            text-align: left;
        }

        .summary-table th {
            background: #f2f7fc;
            color: #15537b;
            font-weight: 600;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .btn-group {
            display: flex;
            gap: 1em;
            margin-top: 0em;
            justify-content: flex-end;
        }

        .btn {
            background: #15537b;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7em 1.5em;
            font-weight: 600;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }

        .btn:hover {
            background: #1072c3;
        }

        .alert-success {
            background: #e7f8e8;
            color: #1c7c31;
            border: 1px solid #b7e2c3;
            padding: 0.7em 1em;
            border-radius: 6px;
            font-size: 1em;
            margin-bottom: 1em;
        }

        .alert-error {
            background: #fdeaea;
            color: #c0392b;
            border: 1px solid #f5b7b7;
            padding: 0.7em 1em;
            border-radius: 6px;
            font-size: 1em;
            margin-bottom: 1em;
        }

        .profile-card.three-col {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 2em;
            width: 100%;
            align-items: stretch;
            min-height: 400px;
        }

        .profile-card.three-col .col {
            min-width: 0;
            width: 100%;
            display: flex;
            /*flex-direction: column;*/
            justify-content: stretch;
            height: 100%;
        }

        .profile-card.three-col .col-actions {
            grid-column: 1 / span 3;
            margin-top: 1em;
        }

        @media (max-width: 900px) {
            .profile-card {
                padding: 1em;
            }

            .profile-card.three-col {
                grid-template-columns: 1fr;
            }

            .profile-card.three-col .col-actions {
                grid-column: 1 / span 1;
            }
        }

        @media (max-width: 600px) {
            .referral-container {
                flex-direction: column;
                align-items: stretch;
            }

            .profile-card {
                min-width: 0;
                max-width: 100%;
            }

            .btn-group {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <script>
        function goBackToEdit() {
            document.getElementById('goBackForm').submit();
        }
    </script>
    <form id="goBackForm" method="post" action="createReferral.php" style="display:none;">
        <?php foreach ($fields as $f): ?>
            <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($$f) ?>" />
        <?php endforeach; ?>
    </form>
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
                            // Fetch employee info if not already
                            if (!isset($employee)) {
                                $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ? LIMIT 1");
                                $stmt->execute([$employee_id]);
                                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                            }
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
                            <?php echo htmlspecialchars($employee['employee_number'] ?? ''); ?>
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
    <section class="homepage">
        <h2 class="section-title">Verify Referral</h2>
        <div class="referral-container">
            <?php if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_GET['referral_num'])): ?>
                <div id="referral-success-popup" style="background: #e7f8e8; color: #1c7c31; border: 2px solid #27ae60; border-radius: 12px; box-shadow: 0 4px 18px rgba(60,74,123,0.10); padding: 2em; max-width: 480px; margin: 2em auto; text-align: center; font-size: 1.2em;">
                    <i class="fa-solid fa-circle-check" style="font-size:2em; color:#27ae60;"></i>
                    <h3 style="margin-top:0.5em;">Referral Successfully Created!</h3>
                    <div style="margin:1em 0;">Referral Number: <strong style="font-size:1.5em; color:#15537b;"><?= htmlspecialchars($_GET['referral_num']) ?></strong></div>
                    <div id="countdown-text">Redirecting in <span id="countdown">10</span> seconds...</div>
                </div>
                <script>
                    let seconds = 10;
                    const countdownEl = document.getElementById('countdown');
                    const interval = setInterval(function() {
                        seconds--;
                        countdownEl.textContent = seconds;
                        if (seconds <= 0) {
                            clearInterval(interval);
                            window.location.href = 'employeeReferralIssue.php';
                        }
                    }, 1000);
                </script>
            <?php else: ?>
                <div class="profile-card three-col">
                    <!-- ...existing code for summary and form... -->
                    <div class="col patient-details">
                        <h3>Patient Details</h3>
                        <table class="summary-table">
                            <tr>
                                <th>Patient ID</th>
                                <td><?= htmlspecialchars($patient['username'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Last Name</th>
                                <td><?= htmlspecialchars($patient['last_name'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>First Name</th>
                                <td><?= htmlspecialchars($patient['first_name'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Middle Name</th>
                                <td><?= htmlspecialchars($patient['middle_name'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Suffix</th>
                                <td><?= htmlspecialchars($patient['suffix'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Date of Birth</th>
                                <td><?= htmlspecialchars($patient['dob'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Age</th>
                                <td><?= htmlspecialchars($age) ?></td>
                            </tr>
                            <tr>
                                <th>Street</th>
                                <td><?= htmlspecialchars($personal['street'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>Barangay</th>
                                <td><?= htmlspecialchars($patient['barangay'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th>PhilHealth ID</th>
                                <td><?= htmlspecialchars($patient['philhealth_id'] ?? '') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col patient-vitals">
                        <h3>Patient Vitals</h3>
                        <table class="summary-table">
                            <tr>
                                <th>Height (cm)</th>
                                <td><?= htmlspecialchars($ht) ?></td>
                            </tr>
                            <tr>
                                <th>Weight (kg)</th>
                                <td><?= htmlspecialchars($wt) ?></td>
                            </tr>
                            <tr>
                                <th>Blood Pressure</th>
                                <td><?= htmlspecialchars($bp) ?></td>
                            </tr>
                            <tr>
                                <th>Heart Rate</th>
                                <td><?= htmlspecialchars($hr) ?></td>
                            </tr>
                            <tr>
                                <th>Respiratory Rate</th>
                                <td><?= htmlspecialchars($rr) ?></td>
                            </tr>
                            <tr>
                                <th>Temperature (°C)</th>
                                <td><?= htmlspecialchars($temp) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col referral-details">
                        <h3>Referral Details</h3>
                        <table class="summary-table">
                            <tr>
                                <th>Chief Complaint</th>
                                <td><?= htmlspecialchars($chief_complaint) ?></td>
                            </tr>
                            <tr>
                                <th>Symptoms</th>
                                <td><?= htmlspecialchars($symptoms) ?></td>
                            </tr>
                            <tr>
                                <th>Initial Diagnosis</th>
                                <td><?= htmlspecialchars($assessment) ?></td>
                            </tr>
                            <tr>
                                <th>Referral Destination Facility</th>
                                <td><?= htmlspecialchars($facility_name) ?></td>
                            </tr>
                            <tr>
                                <th>Service</th>
                                <td><?= htmlspecialchars($service) ?></td>
                            </tr>
                            <tr>
                                <th>Reason for Referral</th>
                                <td><?= htmlspecialchars($reason_for_referral) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col col-actions" style="grid-column: 1 / span 3; width: 100%;">
                        <?php if ($success): ?>
                            <div class="alert-success">Referral finalized and saved!</div>
                        <?php elseif ($error): ?>
                            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <?php foreach ($fields as $f): ?>
                                <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($$f) ?>" />
                            <?php endforeach; ?>
                            <input type="hidden" name="confirm_referral" value="1" />
                            <div class="btn-group">
                                <button type="submit" class="btn btn-confirm"><i class="fa-solid fa-check-circle"></i>
                                    Confirm & Finalize Referral</button>
                                <button type="button" class="btn btn-back" onclick="goBackToEdit()"><i
                                        class="fa-solid fa-arrow-left"></i> Go Back & Edit</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>

</html>