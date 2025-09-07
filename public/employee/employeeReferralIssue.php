<?php
session_start();
require_once 'db.php';

// --- Auth & Session ---
if (!isset($_SESSION['employee_id'], $_SESSION['role'])) {
    die('Session missing. Please log in again.');
}
$employee_id = (int) $_SESSION['employee_id'];
$role = $_SESSION['role'];

// --- Fetch employee info ---
$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ? LIMIT 1");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Modal variable defaults ---
$age = $age ?? '';
$ht = $ht ?? '';
$wt = $wt ?? '';
$bp = $bp ?? '';
$hr = $hr ?? '';
$rr = $rr ?? '';
$temp = $temp ?? '';
$chief_complaint = $chief_complaint ?? '';
$symptoms = $symptoms ?? '';
$assessment = $assessment ?? '';
$facility_name = $facility_name ?? '';
$service = $service ?? '';
$reason_for_referral = $reason_for_referral ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Referral Issue</title>
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

        .homepage {
            min-height: 80vh;
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

        /* Modal styles */
        .modal[aria-modal="true"] {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.35);
            justify-content: center;
            align-items: center;
        }

        .modal[aria-modal="true"]:focus {
            outline: none;
        }

        .profile-card.three-col {
            display: flex;
            flex-direction: column;
            flex-wrap: wrap;
            gap: 1em;
            box-shadow: 0 8px 32px rgba(60, 74, 123, 0.18);
            border-radius: 18px;
            max-width: 700px;
            width: 98vw;
            min-width: 320px;
            padding: 1.5em 0.5em;
            margin: 2em auto;
            background: #fff;
        }

        .profile-card.three-col .col {
            box-sizing: border-box;
        }

        .modal-content>div:first-child {
            display: flex;
            gap: 1em;
            min-width: 0;
        }

        .modal-content .patient-details,
        .modal-content .patient-vitals {
            flex: 1 1 50%;
            min-width: 220px;
        }

        .modal-content .referral-details {
            flex: 1 1 100%;
            min-width: 220px;
            margin-top: 1em;
        }

        .modal-actions {
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 1em;
            margin-top: 2em;
        }

        .btn-cancel {
            background: #e74c3c;
            color: #fff;
            padding: 0.7em 2em;
            border-radius: 8px;
            border: none;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-cancel:hover,
        .btn-cancel:focus {
            background: #c0392b;
        }

        .btn-update {
            background: #15537b;
            color: #fff;
            padding: 0.7em 2em;
            border-radius: 8px;
            border: none;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-update:hover,
        .btn-update:focus {
            background: #0d3550;
        }

        @media (max-width: 900px) {
            .profile-card.three-col {
                max-width: 98vw;
                padding: 1em 0.5em;
            }

            .modal-content>div:first-child {
                flex-direction: column;
                gap: 1em;
            }

            .modal-content .patient-details,
            .modal-content .patient-vitals {
                min-width: unset;
            }
        }

        @media (max-width: 600px) {
            .profile-card.three-col {
                max-width: 100vw;
                padding: 0.5em 0.2em;
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
            <a href="employeeReferralIssue.php" onclick="closeNav()"><i class="fa-solid fa-share-nodes"></i>
                Referrals</a>
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
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1em;">
            <h2 class="section-title" style="margin-bottom: 0;">Referral Issue</h2>
            <a href="createReferral.php" class="btn btn-confirm"
                style="text-decoration:none; display:inline-flex; align-items:center; gap:0.5em; font-size:1em;">
                <i class="fa-solid fa-plus"></i> Create Referral
            </a>
        </div>
        <div class="profile-card">
            <h3 style="margin-top:0;">Submitted Referrals</h3>
            <table class="summary-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Referral No.</th>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Facility Referred To</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT r.referral_num, p.username, p.last_name, p.first_name, p.middle_name, f.name AS facility, r.status, r.id
                        FROM referrals r
                        JOIN patients p ON r.patient_id = p.id
                        LEFT JOIN facilities f ON r.referred_to_facility = f.id
                        WHERE r.issued_by = ?
                        ORDER BY r.date_of_referral DESC");
                    $stmt->execute([$employee_id]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['referral_num']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . (!empty($row['middle_name']) ? ' ' . $row['middle_name'] : '')) ?>
                            </td>
                            <td><?= htmlspecialchars($row['facility'] ?? 'External') ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                            <td>
                                <button class="btn btn-confirm view-referral-btn"
                                    data-referral-id="<?= htmlspecialchars($row['id']) ?>"
                                    style="padding:0.3em 1em; font-size:0.95em; text-decoration:none;">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>


    <!-- Referral Details Modal -->
    <div id="referralModal" class="modal"
        style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.35); justify-content:center; align-items:center;">
        <div class="profile-card three-col" style="max-width:1100px; width:95vw; position:relative;">
            <button onclick="closeReferralModal()"
                style="position:absolute; top:1em; right:1em; background:none; border:none; font-size:1.5em; color:#15537b; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div id="referralModalContent">
                <div style="text-align:center; padding:2em;">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Patient Referral Modal -->
    <div id="patientReferralModal" class="modal" aria-modal="true" role="dialog" tabindex="-1"
        style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.35); justify-content:center; align-items:flex-start; overflow:auto;">
        <div class="profile-card three-col" style="max-width:700px; width:98vw; min-width:320px; position:relative; box-shadow:0 8px 32px rgba(60,74,123,0.18); border-radius:18px; padding:1.5em 0.5em; margin:2em auto;">
            <button onclick="closeModal('patientReferralModal')" aria-label="Close modal"
                style="position:absolute; top:1em; right:1em; background:none; border:none; font-size:1.5em; color:#15537b; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="modal-content" style="display:flex; flex-wrap:wrap; gap:1em;">
                <div style="display:flex; flex:1 1 100%; gap:1em; min-width:0;">
                    <div class="col patient-details" style="flex:1 1 50%; min-width:220px; box-sizing:border-box;">
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
                    <div class="col patient-vitals" style="flex:1 1 50%; min-width:220px; box-sizing:border-box;">
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
                </div>
                <div class="col referral-details" style="flex:1 1 100%; min-width:220px; box-sizing:border-box; margin-top:1em;">
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
            </div>
            <div class="modal-actions" style="width:100%; display:flex; justify-content:center; gap:1em; margin-top:2em;">
                <button class="btn btn-cancel" style="background:#e74c3c; color:#fff; padding:0.7em 2em; border-radius:8px; border:none; font-size:1em; cursor:pointer;" onclick="cancelReferral()">Cancel Referral</button>
                <button class="btn btn-update" style="background:#15537b; color:#fff; padding:0.7em 2em; border-radius:8px; border:none; font-size:1em; cursor:pointer;" onclick="updateReferralStatus()">Update Status</button>
            </div>
        </div>
    </div>

    <script>
        function closeReferralModal() {
            document.getElementById('referralModal').style.display = 'none';
        }
        document.querySelectorAll('.view-referral-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var referralId = btn.getAttribute('data-referral-id');
                var modal = document.getElementById('referralModal');
                var content = document.getElementById('referralModalContent');
                modal.style.display = 'flex';
                content.innerHTML = '<div style="text-align:center; padding:2em;">Loading...</div>';
                // AJAX to fetch referral details
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajaxGetReferralDetails.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        content.innerHTML = xhr.responseText;
                    } else {
                        content.innerHTML = '<div style="color:red;">Failed to load referral details.</div>';
                    }
                };
                xhr.send('id=' + encodeURIComponent(referralId));
            });
        });

        // Modal open/close logic
        function openModal(id) {
            var modal = document.getElementById(id);
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            modal.focus();
        }
        function closeModal(id) {
            var modal = document.getElementById(id);
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        // Open modal on button click
        document.querySelectorAll('.view-referral-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openModal('patientReferralModal');
            });
        });
        // Accessibility: close modal on overlay click or Esc
        document.getElementById('patientReferralModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('patientReferralModal');
        });
        document.addEventListener('keydown', function(e) {
            var modal = document.getElementById('patientReferralModal');
            if (modal.style.display === 'flex' && e.key === 'Escape') closeModal('patientReferralModal');
        });
        // Button actions (implement AJAX as needed)
        function cancelReferral() {
            alert('Cancel Referral action triggered. Implement AJAX as needed.');
        }
        function updateReferralStatus() {
            alert('Update Status action triggered. Implement AJAX as needed.');
        }
    </script>
</body>

</html>