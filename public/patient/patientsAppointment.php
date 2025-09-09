<?php
// Start session and initialize database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php';

// Optionally, fetch patient data for use in sidebar
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

// Fetch appointments and referrals
$appointments = [];
$referrals = [];
if ($patient_id) {
    // Fetch appointments
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC");
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch latest referrals
    $stmt = $pdo->prepare("SELECT * FROM referrals WHERE patient_id = ? ORDER BY date_of_referral DESC LIMIT 5");
    $stmt->execute([$patient_id]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Appointments</title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link rel="stylesheet" href="/WBHSMS-CHO/public/css/patientSidebar.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .homepage {
            margin-left: 250px;
            min-height: 100vh;
            max-height: 100vh;
            width: auto;
            overflow-y: auto;
            padding: 30px 30px 30px 30px;
            color: #03045e;
            box-sizing: border-box;
        }

        .page-heading-bar {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1em;
            margin-bottom: 1.5em;
        }

        @media screen and (max-width: 768px) {
            .homepage {
                margin-left: 0;
                padding: 15px 15px 15px 15px;
            }

            .page-heading-bar {
                flex-direction: column;
                align-items: flex-start;
            }

        }

        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }


        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            margin-block-end: 0.83em;
            margin-inline-start: 0px;
            margin-inline-end: 0px;
            font-weight: bold;
            unicode-bidi: isolate;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background: #eee;
            font-weight: 700;
            color: #1a1a2e;
            /*border-bottom: 2px solid #000000ff;*/
            letter-spacing: 0.5px;
            font-size: 1.08em;
            box-shadow: 0 5px 5px rgba(25, 118, 210, 0.04);
            padding: 1.1em 0.9em;
        }

        .table td {
            vertical-align: middle;
            font-size: 1.03rem;
            padding: 0.95em 0.9em;
            border-bottom: 1px solid #f0f4f8;
            background: #fff;
            color: #222;
            transition: background 0.15s;
        }

        .table tbody tr {
            transition: box-shadow 0.18s, background 0.18s;
        }

        .table tbody tr:hover {
            background: #e3f2fd33;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.07);
        }

        .btn-primary {
            background: linear-gradient(90deg, #1976d2 60%, #64b5f6 100%);
            border: none;
        }

        .btn-outline-primary {
            border-color: #1976d2;
            color: #1976d2;
        }

        .btn-outline-primary:hover {
            background: #1976d2;
            color: #fff;
        }

        .badge {
            font-size: 0.95em;
            padding: 0.5em 0.8em;
            border-radius: 8px;
        }

        .card-title {
            color: #1976d2;
            font-weight: 600;
        }

        .card-text {
            color: #333;
        }

        .modal-content {
            border-radius: 16px;
        }

        @media (max-width: 768px) {
            .homepage {
                margin-top: 16px;
                padding: 0 4px;
            }

            .card {
                border-radius: 12px;
            }

            .table th,
            .table td {
                font-size: 0.95rem;
            }
        }

        .modal-content {
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(25, 118, 210, 0.18);
            border: none;
            background: #fff;
            padding: 0;
            transition: box-shadow 0.2s;
        }

        .modal-lg {
            max-width: 700px;
        }

        .modal-header {
            border-bottom: 1px solid #e3f2fd;
            background: linear-gradient(90deg, #e3f2fd 70%, #bbdefb 100%);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            padding: 1.2em 1.5em 1em 1.5em;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            color: #1976d2;
            font-weight: 700;
            font-size: 1.35em;
            margin: 0;
        }

        .modal-body {
            padding: 1.5em;
            background: #f8fafd;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .modal.fade {
            display: none;
        }

        .modal.show {
            display: block;
            background: rgba(30, 42, 70, 0.18);
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.3em;
            color: #1976d2;
            opacity: 0.7;
            transition: opacity 0.18s;
        }

        .btn-close:hover {
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include '../../includes/patientsidebar.php'; ?>


    <section class="homepage">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="page-heading-bar"
                style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1em;margin-bottom:1.5em;">
                <h2 style="margin:0;font-size:2.2em;letter-spacing:1px;font-size:xx-large;">PATIENT APPOINTMENTS</h2>
                <div class="utility-btn-group" style="display:flex;gap:0.7em;flex-wrap:wrap;">
                    <button class="utility-btn" title="Create Referral"
                        style="background:#16a085;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(243,156,18,0.08);cursor:pointer;transition:background 0.18s;"
                        onclick="window.location.href='patientCreateReferral.php'" title="Create Referral">
                        <i class="fas fa-paper-plane"></i> <span class="hide-on-mobile">Create Appointment</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Appointments Table -->
        <div class="card">
            <div class="card-header">
                <h3>Upcoming & Past Appointments</h3>
                <a href="patientViewAppointment.php" class="utility-btn"
                    style="background:#1976d2;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(25,118,210,0.08);cursor:pointer;transition:background 0.18s;text-decoration:none;">
                    <i class="fas fa-arrow-right"></i> <span class="hide-on-mobile">View More</span>
                </a>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Facility</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td><?= htmlspecialchars($appt['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($appt['facility']) ?></td>
                            <td><?= htmlspecialchars($appt['service']) ?></td>
                            <td>
                                <span class="badge bg-<?= $appt['status'] == 'active' ? 'success' : ($appt['status'] == 'cancelled' ? 'danger' : 'secondary') ?>">
                                    <?= ucfirst($appt['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="patientViewAppointment.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="text-align:center;">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Referrals Summary -->
        <div class="card">
            <div class="card-header">
                <h3>Latest Referrals</h3>
                <a href="patientViewAppointment.php" class="utility-btn"
                    style="background:#1976d2;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(25,118,210,0.08);cursor:pointer;transition:background 0.18s;text-decoration:none;">
                    <i class="fas fa-arrow-right"></i> <span class="hide-on-mobile">View More</span>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Destination</th>
                            <th>Complaint</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referrals as $ref): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($ref['date_of_referral'])) ?></td>
                                <td><?= htmlspecialchars($ref['destination_type'] === 'external' ? $ref['referred_to_external'] : $ref['referred_to_facility']) ?></td>
                                <td><?= htmlspecialchars($ref['chief_complaint']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $ref['status'] == 'active' ? 'success' : ($ref['status'] == 'cancelled' ? 'danger' : ($ref['status'] == 'completed' ? 'primary' : 'secondary')) ?>">
                                        <?= ucfirst($ref['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#refModal<?= $ref['id'] ?>">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($referrals)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No referrals found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Referral Modal (inside foreach, hidden by default, opens on button click) -->
                <div class="modal fade" id="refModal<?= $ref['id'] ?>" tabindex="-1" aria-labelledby="refModalLabel<?= $ref['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="card-header">
                                <h4 class="modal-title" id="refModalLabel<?= $ref['id'] ?>" style="margin-bottom: 20px;">
                                    View Referral #<?= htmlspecialchars($ref['referral_num']) ?>
                                </h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered table-striped" style="background:#f8fafd;border-radius:10px;">
                                    <tbody>
                                        <tr>
                                            <th style="width:200px;background:#e3f2fd;color:#1976d2;">Referral Number</th>
                                            <td><?= htmlspecialchars($ref['referral_num']) ?></td>
                                        </tr>
                                        <tr>
                                            <th style="background:#e3f2fd;color:#1976d2;">Date of Referral</th>
                                            <td><?= date('M d, Y h:i A', strtotime($ref['date_of_referral'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th style="background:#e3f2fd;color:#1976d2;">Destination</th>
                                            <td><?= htmlspecialchars($ref['destination_type'] === 'external' ? $ref['referred_to_external'] : $ref['referred_to_facility']) ?></td>
                                        </tr>
                                        <tr>
                                            <th style="background:#e3f2fd;color:#1976d2;">Status</th>
                                            <td><?= ucfirst($ref['status']) ?></td>
                                        </tr>
                                        <tr>
                                            <th style="background:#e3f2fd;color:#1976d2;">Chief Complaint</th>
                                            <td><?= htmlspecialchars($ref['chief_complaint']) ?></td>
                                        </tr>
                                        <tr>
                                            <th style="background:#e3f2fd;color:#1976d2;">Reason for Referral</th>
                                            <td><?= htmlspecialchars($ref['reason_for_referral']) ?></td>
                                        </tr>
                                        <tr>
                                            <th style="background:#e3f2fd;color:#1976d2;">Assessment</th>
                                            <td><?= htmlspecialchars($ref['assessment']) ?></td>
                                        </tr>
                                        <!-- Add more fields as needed -->
                                    </tbody>
                                </table>
                                <div class="card-header" style="display:flex;justify-content:flex-end; margin-top:20px; gap:10px;">
                                    <button class="utility-btn" type="button" data-bs-dismiss="modal" title="Close"
                                        style="background:#b0bec5;color:#222;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(176,190,197,0.08);cursor:pointer;transition:background 0.18s;">
                                        <i class="fas fa-times"></i> <span class="hide-on-mobile">Close</span>
                                    </button>
                                    <button class="utility-btn" title="Create Referral"
                                        style="background:#16a085;color:#fff;border:none;padding:0.6em 1.2em;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:0.5em;box-shadow:0 2px 8px rgba(243,156,18,0.08);cursor:pointer;transition:background 0.18s;"
                                        onclick="window.location.href='patientCreateReferral.php'" title="Create Referral">
                                        <i class="fas fa-paper-plane"></i> <span class="hide-on-mobile">Create Appointment</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>
</body>
<script>
    // Bootstrap modals are triggered by data-bs-toggle/data-bs-target, but you can also open/close programmatically:
    function openReferralModal(referralId) {
        var modalEl = document.getElementById('refModal' + referralId);
        if (modalEl) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    function closeReferralModal(referralId) {
        var modalEl = document.getElementById('refModal' + referralId);
        if (modalEl) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    }
</script>

</html>