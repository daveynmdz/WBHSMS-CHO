<?php
session_start();
require_once 'db.php';

// Only allow logged-in patients
$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : null;
if (!$patient_id) {
    header('Location: patientLogin.html');
    exit();
}

// Fetch patient info
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die('Patient not found.');
}

// Fetch medical history for display
$chronic_illnesses = $pdo->prepare("SELECT illness, year_diagnosed, management FROM chronic_illnesses WHERE patient_id = ?");
$chronic_illnesses->execute([$patient_id]);
$chronic_illnesses = $chronic_illnesses->fetchAll(PDO::FETCH_ASSOC);

$current_medications = $pdo->prepare("SELECT medication, dosage, frequency, prescribed_by FROM current_medications WHERE patient_id = ?");
$current_medications->execute([$patient_id]);
$current_medications = $current_medications->fetchAll(PDO::FETCH_ASSOC);

$family_history = $pdo->prepare("SELECT family_member, `condition`, age_diagnosed, current_status FROM family_history WHERE patient_id = ?");
$family_history->execute([$patient_id]);
$family_history = $family_history->fetchAll(PDO::FETCH_ASSOC);

$past_medical_conditions = $pdo->prepare("SELECT `condition`, year_diagnosed, status FROM past_medical_conditions WHERE patient_id = ?");
$past_medical_conditions->execute([$patient_id]);
$past_medical_conditions = $past_medical_conditions->fetchAll(PDO::FETCH_ASSOC);

$surgical_history = $pdo->prepare("SELECT surgery, year, hospital FROM surgical_history WHERE patient_id = ?");
$surgical_history->execute([$patient_id]);
$surgical_history = $surgical_history->fetchAll(PDO::FETCH_ASSOC);

function h($v)
{
    return htmlspecialchars($v ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Edit Medical History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/patientUI.css">
    <link rel="stylesheet" href="css/patientProfile.css">
    <link rel="stylesheet" href="css/editProfileLayout.css">
    <style>
        /* Inline the card stylings for medical history if needed */
        .history-card-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .history-card {
            background: #f8fafc;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            padding: 1rem 1.5rem;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s;
            border: 1px solid #e5e7eb;
        }

        .history-card-fields {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .history-field {
            flex: 1 1 180px;
            min-width: 150px;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .history-field label {
            font-weight: 500;
            color: #222;
            font-size: 0.98rem;
        }

        .history-field input[type="text"],
        .history-field select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 1rem;
            background: #fff;
            transition: border 0.2s;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            box-sizing: border-box;
        }

        .history-field input[type="text"]:focus,
        .history-field select:focus {
            border-color: #2563eb;
            outline: none;
        }

        .history-field select {
            background-image: url('data:image/svg+xml;utf8,<svg fill="gray" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7.293 7.293a1 1 0 011.414 0L10 8.586l1.293-1.293a1 1 0 111.414 1.414l-2 2a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.2em;
            padding-right: 2.2em;
            cursor: pointer;
        }

        .history-action {
            flex: 0 0 auto;
            align-self: flex-end;
        }

        .btn-delete {
            background: #f3f4f6;
            color: #ef4444;
            border: 1px solid #ef4444;
            border-radius: 6px;
            padding: 0.4rem 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .btn-delete:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        .history-add-btn {
            margin-top: 0.5rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.08);
            transition: background 0.2s;
        }

        .history-add-btn:hover {
            background: #1d4ed8;
        }

        @media (max-width: 700px) {
            .history-card-fields {
                flex-direction: column;
                gap: 0.5rem;
            }

            .history-field {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="edit-profile-topbar">
        <a href="patientHomepage.php" class="topbar-logo">
            <img src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
        <div class="topbar-title" style="color: #ffffff;">Edit Profile - Medical History</div>
        <div class="topbar-userinfo">
            <div class="topbar-usertext">
                <strong style="color: #ffffff;"><?= h($patient['first_name'] . ' ' . $patient['last_name']) ?></strong><br>
                <small style="color: #ffffff;">Patient</small>
            </div>
            <img src="patient_profile_photo.php" alt="User Profile" class="topbar-userphoto" onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';" />
        </div>
    </header>
    <div class="edit-profile-toolbar-flex">
        <button type="button" class="btn btn-cancel floating-back-btn" id="backCancelBtn">&#8592; Back / Cancel</button>
        <!-- Custom Back/Cancel Confirmation Modal -->
        <div id="backCancelModal" class="custom-modal" style="display:none;">
            <div class="custom-modal-content">
                <h3>Cancel Editing?</h3>
                <p>Are you sure you want to go back/cancel? Unsaved changes will be lost.</p>
                <div class="custom-modal-actions">
                    <button type="button" class="btn btn-danger" id="modalCancelBtn">Yes, Cancel</button>
                    <button type="button" class="btn btn-secondary" id="modalStayBtn">Stay</button>
                </div>
            </div>
        </div>
        <div class="edit-profile-reminders">
            <strong>Reminders:</strong>
            <ul style="margin:0.5em 0 0 1.2em; padding:0; list-style:disc;">
                <li>Double-check your information before saving.</li>
                <li>Fields marked with * are required.</li>
                <li>Click 'Save' after editing each section.</li>
            </ul>
        </div>
    </div>
    <div class="profile-card" id="medicalHistorySection">
        <h3>Medical History</h3>
        <!-- Allergies Card List-->
        <div class="history-table-block">
            <h4>Allergies</h4>
            <div id="allergiesTable" class="history-card-list">
                <?php
                $allergens = ['Peanuts', 'Tree Nuts', 'Penicillin', 'Latex', 'Pollen', 'Shellfish', 'Eggs', 'Milk', 'Soy', 'Wheat', 'Other'];
                $reactions = ['Rash', 'Hives', 'Anaphylaxis', 'Swelling', 'Shortness of breath', 'Itching', 'Other'];
                $severities = ['Mild', 'Moderate', 'Severe', 'Other'];
                ?>
                <?php foreach (($allergies ?? [['allergen' => '', 'reaction' => '', 'severity' => '']]) as $idx => $row): ?>
                    <div class="history-card">
                        <div class="history-card-fields">
                            <!-- Allergen Dropdown + Other -->
                            <div class="history-field">
                                <label>Allergen
                                    <select name="allergen[]"
                                        onchange="toggleOtherField(this, 'allergen-other-<?= $idx ?>')">
                                        <option value="">Select Allergen</option>
                                        <?php foreach ($allergens as $option): ?>
                                            <option value="<?= h($option) ?>" <?= h($row['allergen']) === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="allergen_other[]" id="allergen-other-<?= $idx ?>"
                                        placeholder="Please specify"
                                        style="display:<?= h($row['allergen']) === 'Other' ? 'inline-block' : 'none' ?>;"
                                        value="<?= h($row['allergen']) !== '' && !in_array($row['allergen'], $allergens) ? h($row['allergen']) : '' ?>" />
                                </label>
                            </div>
                            <!-- Reaction Dropdown + Other -->
                            <div class="history-field">
                                <label>Reaction
                                    <select name="reaction[]"
                                        onchange="toggleOtherField(this, 'reaction-other-<?= $idx ?>')">
                                        <option value="">Select Reaction</option>
                                        <?php foreach ($reactions as $option): ?>
                                            <option value="<?= h($option) ?>" <?= h($row['reaction']) === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="reaction_other[]" id="reaction-other-<?= $idx ?>"
                                        placeholder="Please specify"
                                        style="display:<?= h($row['reaction']) === 'Other' ? 'inline-block' : 'none' ?>;"
                                        value="<?= h($row['reaction']) !== '' && !in_array($row['reaction'], $reactions) ? h($row['reaction']) : '' ?>" />
                                </label>
                            </div>
                            <!-- Severity Dropdown + Other -->
                            <div class="history-field">
                                <label>Severity
                                    <select name="severity[]">
                                        <option value="">Select Severity</option>
                                        <?php foreach ($severities as $option): if ($option === 'Other') continue; ?>
                                            <option value="<?= h($option) ?>" <?= h($row['severity']) === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="history-field history-action">
                                <button type="button" class="btn btn-delete" onclick="deleteRow(this)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn history-add-btn" onclick="addRow('allergiesTable')">+ Add Allergy</button>
        </div>
        <!-- Chronic Illnesses Card List -->
        <div class="history-table-block">
            <h4>Chronic Illnesses</h4>
            <div id="chronicIllnessesTable" class="history-card-list">
                <?php foreach (($chronic_illnesses ?? [['illness' => '', 'year_diagnosed' => '', 'management' => '']]) as $row): ?>
                    <div class="history-card">
                        <div class="history-card-fields">
                            <div class="history-field">
                                <label>Illness
                                    <input type="text" name="illness[]" value="<?= h($row['illness']) ?>"
                                        placeholder="e.g. Hypertension" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Year Diagnosed
                                    <input type="text" name="year_diagnosed[]" value="<?= h($row['year_diagnosed']) ?>"
                                        placeholder="e.g. 2015" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Management
                                    <input type="text" name="management[]" value="<?= h($row['management']) ?>"
                                        placeholder="e.g. Medication, Diet" />
                                </label>
                            </div>
                            <div class="history-field history-action">
                                <button type="button" class="btn btn-delete" onclick="deleteRow(this)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn history-add-btn" onclick="addRow('chronicIllnessesTable')">+ Add Chronic
                Illness</button>
        </div>
        <!-- Current Medications Card List -->
        <div class="history-table-block">
            <h4>Current Medications</h4>
            <div id="currentMedicationsTable" class="history-card-list">
                <?php foreach (($current_medications ?? [['medication' => '', 'dosage' => '', 'frequency' => '', 'prescribed_by' => '']]) as $row): ?>
                    <div class="history-card">
                        <div class="history-card-fields">
                            <div class="history-field">
                                <label>Medication
                                    <input type="text" name="medication[]" value="<?= h($row['medication']) ?>"
                                        placeholder="e.g. Metformin" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Dosage
                                    <input type="text" name="dosage[]" value="<?= h($row['dosage']) ?>"
                                        placeholder="e.g. 500mg" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Frequency
                                    <input type="text" name="frequency[]" value="<?= h($row['frequency']) ?>"
                                        placeholder="e.g. Twice daily" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Prescribed By
                                    <input type="text" name="prescribed_by[]" value="<?= h($row['prescribed_by']) ?>"
                                        placeholder="e.g. Dr. Smith" />
                                </label>
                            </div>
                            <div class="history-field history-action">
                                <button type="button" class="btn btn-delete" onclick="deleteRow(this)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn history-add-btn" onclick="addRow('currentMedicationsTable')">+ Add
                Medication</button>
        </div>
        <!-- Family History Card List -->
        <div class="history-table-block">
            <h4>Family History</h4>
            <div id="familyHistoryTable" class="history-card-list">
                <?php foreach (($family_history ?? [['family_member' => '', 'condition' => '', 'age_diagnosed' => '', 'current_status' => '']]) as $row): ?>
                    <div class="history-card">
                        <div class="history-card-fields">
                            <div class="history-field">
                                <label>Family Member
                                    <input type="text" name="family_member[]" value="<?= h($row['family_member']) ?>"
                                        placeholder="e.g. Mother" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Condition
                                    <input type="text" name="condition[]" value="<?= h($row['condition']) ?>"
                                        placeholder="e.g. Diabetes" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Age Diagnosed
                                    <input type="text" name="age_diagnosed[]" value="<?= h($row['age_diagnosed']) ?>"
                                        placeholder="e.g. 45" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Current Status
                                    <input type="text" name="current_status[]" value="<?= h($row['current_status']) ?>"
                                        placeholder="e.g. Living" />
                                </label>
                            </div>
                            <div class="history-field history-action">
                                <button type="button" class="btn btn-delete" onclick="deleteRow(this)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn history-add-btn" onclick="addRow('familyHistoryTable')">+ Add Family
                History</button>
        </div>
        <!-- Past Medical Conditions Card List -->
        <div class="history-table-block">
            <h4>Past Medical Conditions</h4>
            <div id="pastMedicalConditionsTable" class="history-card-list">
                <?php foreach (($past_medical_conditions ?? [['condition' => '', 'year_diagnosed' => '', 'status' => '']]) as $row): ?>
                    <div class="history-card">
                        <div class="history-card-fields">
                            <div class="history-field">
                                <label>Condition
                                    <input type="text" name="pmc_condition[]" value="<?= h($row['condition']) ?>"
                                        placeholder="e.g. Asthma" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Year Diagnosed
                                    <input type="text" name="pmc_year_diagnosed[]" value="<?= h($row['year_diagnosed']) ?>"
                                        placeholder="e.g. 2008" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Status
                                    <input type="text" name="pmc_status[]" value="<?= h($row['status']) ?>"
                                        placeholder="e.g. Resolved" />
                                </label>
                            </div>
                            <div class="history-field history-action">
                                <button type="button" class="btn btn-delete" onclick="deleteRow(this)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn history-add-btn" onclick="addRow('pastMedicalConditionsTable')">+ Add Past
                Condition</button>
        </div>
        <!-- Surgical History Card List -->
        <div class="history-table-block">
            <h4>Surgical History</h4>
            <div id="surgicalHistoryTable" class="history-card-list">
                <?php foreach (($surgical_history ?? [['surgery' => '', 'year' => '', 'hospital' => '']]) as $row): ?>
                    <div class="history-card">
                        <div class="history-card-fields">
                            <div class="history-field">
                                <label>Surgery
                                    <input type="text" name="surgery[]" value="<?= h($row['surgery']) ?>"
                                        placeholder="e.g. Appendectomy" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Year
                                    <input type="text" name="surgery_year[]" value="<?= h($row['year']) ?>"
                                        placeholder="e.g. 2012" />
                                </label>
                            </div>
                            <div class="history-field">
                                <label>Hospital
                                    <input type="text" name="hospital[]" value="<?= h($row['hospital']) ?>"
                                        placeholder="e.g. City Hospital" />
                                </label>
                            </div>
                            <div class="history-field history-action">
                                <button type="button" class="btn btn-delete" onclick="deleteRow(this)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn history-add-btn" onclick="addRow('surgicalHistoryTable')">+ Add
                Surgery</button>
        </div>
    </div>
    <style>
        .custom-modal {
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.35);
            display: flex; align-items: center; justify-content: center;
        }
        .custom-modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 2em 2.5em;
            max-width: 350px;
            text-align: center;
            animation: modalFadeIn 0.2s;
        }
        @keyframes modalFadeIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .custom-modal-content h3 {
            margin-top: 0;
            color: #c0392b;
        }
        .custom-modal-content p {
            margin: 1em 0 2em 0;
            color: #444;
        }
        .custom-modal-actions {
            display: flex;
            gap: 1em;
            justify-content: center;
        }
        .btn-danger {
            background: #c0392b;
            color: #fff;
            border: none;
            padding: 0.5em 1.2em;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.15s;
        }
        .btn-danger:hover { background: #a93226; }
        .btn-secondary {
            background: #eaeaea;
            color: #333;
            border: none;
            padding: 0.5em 1.2em;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.15s;
        }
        .btn-secondary:hover { background: #d5d5d5; }
    </style>
    <script>
        // Custom Back/Cancel modal logic
        const backBtn = document.getElementById('backCancelBtn');
        const modal = document.getElementById('backCancelModal');
        const modalCancel = document.getElementById('modalCancelBtn');
        const modalStay = document.getElementById('modalStayBtn');
        backBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
        });
        modalCancel.addEventListener('click', function() {
            window.location.href = 'patientProfile.php';
        });
        modalStay.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        // Close modal on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.style.display = 'none';
        });
    </script>
    <script>
        function addRow(tableId) {
            const list = document.getElementById(tableId);
            const firstCard = list.querySelector('.history-card');
            if (!firstCard) return;
            const newCard = firstCard.cloneNode(true);
            newCard.querySelectorAll('input').forEach(input => input.value = '');
            list.appendChild(newCard);
        }
        function deleteRow(btn) {
            const card = btn.closest('.history-card');
            if (card) {
                const list = card.parentElement;
                if (list.querySelectorAll('.history-card').length > 1) {
                    card.remove();
                } else {
                    alert('You must keep at least one entry.');
                }
            }
        }
        function toggleOtherField(select, otherFieldId) {
            var otherField = document.getElementById(otherFieldId);
            if (select.value === "Other") {
                otherField.style.display = "inline-block";
            } else {
                otherField.style.display = "none";
                otherField.value = "";
            }
        }
    </script>
</body>

</html>