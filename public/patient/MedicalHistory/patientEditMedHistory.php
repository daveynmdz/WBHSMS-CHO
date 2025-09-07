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
// Add immunizations to the medical_history array
$medical_history = [
    'past_conditions' => [],
    'chronic_illnesses' => [],
    'family_history' => [],
    'surgical_history' => [],
    'allergies' => [],
    'current_medications' => [],
    'immunizations' => []
];
// Immunizations
$stmt = $pdo->prepare("SELECT id, vaccine, year_received, doses_completed, status FROM immunizations WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['immunizations'][] = $row;
}

// Past Medical Conditions
$stmt = $pdo->prepare("SELECT id, `condition`, year_diagnosed, status FROM past_medical_conditions WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['past_conditions'][] = $row;
}

// Chronic Illnesses
$stmt = $pdo->prepare("SELECT id, illness, year_diagnosed, management FROM chronic_illnesses WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['chronic_illnesses'][] = $row;
}

// Family History
$stmt = $pdo->prepare("SELECT id, family_member, `condition`, age_diagnosed, current_status FROM family_history WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['family_history'][] = $row;
}

// Surgical History
$stmt = $pdo->prepare("SELECT id, surgery, year, hospital FROM surgical_history WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['surgical_history'][] = $row;
}

// Current Medications
$stmt = $pdo->prepare("SELECT id, medication, dosage, frequency, prescribed_by FROM current_medications WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['current_medications'][] = $row;
}

// Allergies
$stmt = $pdo->prepare("SELECT id, allergen, reaction, severity FROM allergies WHERE patient_id = ?");
$stmt->execute([$patient_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medical_history['allergies'][] = $row;
}

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
    <link rel="stylesheet" href="css/patientMedHistory.css">
    <link rel="stylesheet" href="css/patientTopbar.css">
    <style>
        html,
        body {
            height: auto !important;
            overflow-y: auto !important;
        }

        body {
            /* Remove forced scrollbars on body */
            overflow: visible !important;
        }

        .homepage {
            overflow: visible !important;
            height: auto !important;
        }

        /* Remove forced height/overflow on .profile-wrapper if present */
        .profile-wrapper {
            overflow: visible !important;
            height: auto !important;
        }

        .reminders-box {
            background: #fffbe6;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            padding: 1em 1.5em;
            margin: 0 auto 2em auto;
            width: auto;
            color: #856404;
            font-size: 1.05em;
            box-shadow: 0 2px 8px rgba(255, 229, 143, 0.08);
        }

        .reminders-box ul {
            margin: 0.5em 0 0 1.2em;
            padding: 0;
            list-style: disc;
        }
    </style>
</head>

<body>
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
                    <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>
                </strong><br>
                <small style="color: #ffffff;">Patient</small>
            </div>
            <img src="patient_profile_photo.php?patient_id=<?= urlencode($patient_id) ?>" alt="User Profile" class="topbar-userphoto"
                            onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';" />
        </div>
    </header>

    <section class="homepage">
        <div class="edit-profile-toolbar-flex">
            <button type="button" class="btn btn-cancel floating-back-btn" id="backCancelBtn">&#8592; Back /
                Cancel</button>
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
        </div>
        <div class="profile-wrapper">
            <div class="reminders-box">
                <strong>Reminders:</strong>
                <ul>
                    <li>Double-check your information before saving.</li>
                    <li>Fields marked with * are required.</li>
                    <li>Click 'Save' after editing each section.</li>
                    <li>To edit your name, date of birth, age, sex, contact number, or email, please go to User
                        Settings.</li>
                </ul>
            </div>

            <!-- Allergies Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;">Allergies</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Allergen</th>
                                <th style="padding:0.5em;">Reaction</th>
                                <th style="padding:0.5em;">Severity</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['allergies'])): ?>
                                <?php foreach ($medical_history['allergies'] as $idx => $allergy): ?>
                                    <tr>
                                        <td><?= h($allergy['allergen']) ?></td>
                                        <td><?= h($allergy['reaction']) ?></td>
                                        <td><?= h($allergy['severity']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditAllergyModal('editAllergyModal<?= $idx ?>', <?= htmlspecialchars(json_encode($allergy), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('allergies', <?= h($allergy['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup"
                                                id="custom-delete-popup-allergies-<?= h($allergy['id']) ?>"
                                                style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div
                                                    style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div
                                                        style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">
                                                        Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete
                                                        this allergy record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button"
                                                            style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;"
                                                            onclick="proceedDelete('allergies', <?= h($allergy['id']) ?>, this)">Delete</button>
                                                        <button type="button"
                                                            style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;"
                                                            onclick="closeCustomDeletePopup('custom-delete-popup-allergies-<?= h($allergy['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Allergy Modal -->
                                    <div id="editAllergyModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Allergy</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="allergies">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($allergy['id']) ? h($allergy['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Allergen*
                                                    <select name="allergen_dropdown" id="edit-allergen-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-allergen-other-input-<?= $idx ?>')">
                                                        <option value="">Select Allergen</option>
                                                        <option value="Peanuts">Peanuts</option>
                                                        <option value="Tree Nuts (Almonds, Walnuts, Cashews, Pistachios)">Tree
                                                            Nuts (Almonds, Walnuts, Cashews, Pistachios)</option>
                                                        <option value="Shellfish (Shrimp, Crab, Lobster)">Shellfish (Shrimp,
                                                            Crab, Lobster)</option>
                                                        <option value="Fish (Salmon, Tuna, Cod)">Fish (Salmon, Tuna, Cod)
                                                        </option>
                                                        <option value="Eggs">Eggs</option>
                                                        <option value="Milk / Dairy">Milk / Dairy</option>
                                                        <option value="Soy">Soy</option>
                                                        <option value="Wheat / Gluten">Wheat / Gluten</option>
                                                        <option value="Sesame">Sesame</option>
                                                        <option value="Penicillin">Penicillin</option>
                                                        <option value="Amoxicillin">Amoxicillin</option>
                                                        <option value="Sulfa Drugs">Sulfa Drugs</option>
                                                        <option value="NSAIDs (Ibuprofen, Naproxen)">NSAIDs (Ibuprofen,
                                                            Naproxen)</option>
                                                        <option value="Aspirin">Aspirin</option>
                                                        <option value="Cephalosporins">Cephalosporins</option>
                                                        <option value="Anesthetics">Anesthetics</option>
                                                        <option value="Pollen (Grass, Tree, Weed)">Pollen (Grass, Tree, Weed)
                                                        </option>
                                                        <option value="Dust Mites">Dust Mites</option>
                                                        <option value="Mold / Fungi">Mold / Fungi</option>
                                                        <option value="Animal Dander (Cat, Dog, Rodent)">Animal Dander (Cat,
                                                            Dog, Rodent)</option>
                                                        <option value="Latex">Latex</option>
                                                        <option value="Cockroach">Cockroach</option>
                                                        <option value="Insect Stings (Bee, Wasp, Hornet)">Insect Stings (Bee,
                                                            Wasp, Hornet)</option>
                                                        <option value="Nickel / Metal">Nickel / Metal</option>
                                                        <option value="Perfumes / Fragrances">Perfumes / Fragrances</option>
                                                        <option value="Food Additives (MSG, Artificial Colors, Preservatives)">
                                                            Food Additives (MSG, Artificial Colors, Preservatives)</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="allergen_other"
                                                        id="edit-allergen-other-input-<?= $idx ?>"
                                                        placeholder="Specify Allergen"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Reaction*
                                                    <select name="reaction_dropdown" id="edit-reaction-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-reaction-other-input-<?= $idx ?>')">
                                                        <option value="">Select Reaction</option>
                                                        <option value="Rash">Rash</option>
                                                        <option value="Anaphylaxis">Anaphylaxis</option>
                                                        <option value="Itching">Itching</option>
                                                        <option value="Swelling">Swelling</option>
                                                        <option value="Nausea">Nausea</option>
                                                        <option value="Hives">Hives</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="reaction_other"
                                                        id="edit-reaction-other-input-<?= $idx ?>"
                                                        placeholder="Specify Reaction"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Severity*
                                                    <select name="severity" id="edit-severity-<?= $idx ?>" required>
                                                        <option value="">Select Severity</option>
                                                        <option value="Mild">Mild</option>
                                                        <option value="Moderate">Moderate</option>
                                                        <option value="Severe">Severe</option>
                                                    </select>
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editAllergyModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        // Custom Delete Popup Logic
                                        function openCustomDeletePopup(table, id, btn) {
                                            // Close any open popups first
                                            document.querySelectorAll('.custom-delete-popup').forEach(function (popup) { popup.style.display = 'none'; });
                                            var popupId = 'custom-delete-popup-' + table + '-' + id;
                                            var popup = document.getElementById(popupId);
                                            if (popup) {
                                                popup.style.display = 'flex';
                                            }
                                        }
                                        function closeCustomDeletePopup(popupId) {
                                            var popup = document.getElementById(popupId);
                                            if (popup) {
                                                popup.style.display = 'none';
                                            }
                                        }
                                        function proceedDelete(table, id, btn) {
                                            // AJAX delete logic (same as confirmDelete, but with custom popup)
                                            var xhr = new XMLHttpRequest();
                                            xhr.open('POST', 'delete_medical_history.php', true);
                                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                            xhr.onreadystatechange = function () {
                                                if (xhr.readyState === 4) {
                                                    if (xhr.status === 200) {
                                                        try {
                                                            var res = JSON.parse(xhr.responseText);
                                                            if (res.success) {
                                                                // Remove the row from the table
                                                                var row = btn.closest('tr');
                                                                if (row) row.remove();
                                                            } else {
                                                                alert(res.error || 'Failed to delete record.');
                                                            }
                                                        } catch (e) {
                                                            alert('Failed to delete record.');
                                                        }
                                                    } else {
                                                        alert('Failed to delete record.');
                                                    }
                                                    // Always close the popup
                                                    closeCustomDeletePopup('custom-delete-popup-' + table + '-' + id);
                                                }
                                            };
                                            xhr.send('table=' + encodeURIComponent(table) + '&id=' + encodeURIComponent(id));
                                        }
                                        function openEditAllergyModal(modalId, allergy) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editAllergyModal', '');
                                            // Allergen
                                            var allergenSel = document.getElementById('edit-allergen-select-' + idx);
                                            var allergenOther = document.getElementById('edit-allergen-other-input-' + idx);
                                            allergenSel.value = '';
                                            allergenOther.style.display = 'none';
                                            allergenOther.value = '';
                                            var foundAllergen = false;
                                            for (var i = 0; i < allergenSel.options.length; i++) {
                                                if (allergenSel.options[i].value === allergy.allergen) {
                                                    allergenSel.selectedIndex = i;
                                                    foundAllergen = true;
                                                    break;
                                                }
                                            }
                                            if (!foundAllergen && allergy.allergen) {
                                                allergenSel.value = 'Others';
                                                allergenOther.style.display = 'block';
                                                allergenOther.value = allergy.allergen;
                                            }
                                            // Reaction
                                            var reactionSel = document.getElementById('edit-reaction-select-' + idx);
                                            var reactionOther = document.getElementById('edit-reaction-other-input-' + idx);
                                            reactionSel.value = '';
                                            reactionOther.style.display = 'none';
                                            reactionOther.value = '';
                                            var foundReaction = false;
                                            for (var j = 0; j < reactionSel.options.length; j++) {
                                                if (reactionSel.options[j].value === allergy.reaction) {
                                                    reactionSel.selectedIndex = j;
                                                    foundReaction = true;
                                                    break;
                                                }
                                            }
                                            if (!foundReaction && allergy.reaction) {
                                                reactionSel.value = 'Others';
                                                reactionOther.style.display = 'block';
                                                reactionOther.value = allergy.reaction;
                                            }
                                            // Severity
                                            document.getElementById('edit-severity-' + idx).value = allergy.severity || '';
                                        }
                                        function editToggleOtherInput(selectElem, inputId) {
                                            var input = document.getElementById(inputId);
                                            if (selectElem.value === 'Others') {
                                                input.style.display = 'block';
                                                input.required = true;
                                            } else {
                                                input.style.display = 'none';
                                                input.required = false;
                                                input.value = '';
                                            }
                                        }
                                        function closeModal(id) {
                                            document.getElementById(id).style.display = 'none';
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Allergy Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="allergies">
                        <h4>Add New Allergy</h4>
                        <label>Allergen*
                            <select name="allergen_dropdown" id="add-allergen-select" required
                                onchange="editToggleOtherInput(this, 'add-allergen-other-input')">
                                <option value="">Select Allergen</option>
                                <option value="Peanuts">Peanuts</option>
                                <option value="Tree Nuts (Almonds, Walnuts, Cashews, Pistachios)">Tree Nuts (Almonds,
                                    Walnuts, Cashews, Pistachios)</option>
                                <option value="Shellfish (Shrimp, Crab, Lobster)">Shellfish (Shrimp, Crab, Lobster)
                                </option>
                                <option value="Fish (Salmon, Tuna, Cod)">Fish (Salmon, Tuna, Cod)</option>
                                <option value="Eggs">Eggs</option>
                                <option value="Milk / Dairy">Milk / Dairy</option>
                                <option value="Soy">Soy</option>
                                <option value="Wheat / Gluten">Wheat / Gluten</option>
                                <option value="Sesame">Sesame</option>
                                <option value="Penicillin">Penicillin</option>
                                <option value="Amoxicillin">Amoxicillin</option>
                                <option value="Sulfa Drugs">Sulfa Drugs</option>
                                <option value="NSAIDs (Ibuprofen, Naproxen)">NSAIDs (Ibuprofen, Naproxen)</option>
                                <option value="Aspirin">Aspirin</option>
                                <option value="Cephalosporins">Cephalosporins</option>
                                <option value="Anesthetics">Anesthetics</option>
                                <option value="Pollen (Grass, Tree, Weed)">Pollen (Grass, Tree, Weed)</option>
                                <option value="Dust Mites">Dust Mites</option>
                                <option value="Mold / Fungi">Mold / Fungi</option>
                                <option value="Animal Dander (Cat, Dog, Rodent)">Animal Dander (Cat, Dog, Rodent)
                                </option>
                                <option value="Latex">Latex</option>
                                <option value="Cockroach">Cockroach</option>
                                <option value="Insect Stings (Bee, Wasp, Hornet)">Insect Stings (Bee, Wasp, Hornet)
                                </option>
                                <option value="Nickel / Metal">Nickel / Metal</option>
                                <option value="Perfumes / Fragrances">Perfumes / Fragrances</option>
                                <option value="Food Additives (MSG, Artificial Colors, Preservatives)">Food Additives
                                    (MSG, Artificial Colors, Preservatives)</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="allergen_other" id="add-allergen-other-input"
                                placeholder="Specify Allergen" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Reaction*
                            <select name="reaction_dropdown" id="add-reaction-select" required
                                onchange="editToggleOtherInput(this, 'add-reaction-other-input')">
                                <option value="">Select Reaction</option>
                                <option value="Rash">Rash</option>
                                <option value="Anaphylaxis">Anaphylaxis</option>
                                <option value="Itching">Itching</option>
                                <option value="Swelling">Swelling</option>
                                <option value="Nausea">Nausea</option>
                                <option value="Hives">Hives</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="reaction_other" id="add-reaction-other-input"
                                placeholder="Specify Reaction" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Severity*
                            <select name="severity" id="add-severity" required>
                                <option value="">Select Severity</option>
                                <option value="Mild">Mild</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Severe">Severe</option>
                            </select>
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit"
                            style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                    </form>
                </div>
            </div>
            <!-- Past Medical Condition Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;"><i class="fas fa-notes-medical icon"></i> Past Medical
                        Conditions</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Condition</th>
                                <th style="padding:0.5em;">Year Diagnosed</th>
                                <th style="padding:0.5em;">Status</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['past_conditions'])): ?>
                                <?php foreach ($medical_history['past_conditions'] as $idx => $cond): ?>
                                    <tr>
                                        <td><?= h($cond['condition']) ?></td>
                                        <td><?= h($cond['year_diagnosed']) ?></td>
                                        <td><?= h($cond['status']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditPastCondModal('editPastCondModal<?= $idx ?>', <?= htmlspecialchars(json_encode($cond), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('past_medical_conditions', <?= h($cond['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup" id="custom-delete-popup-past_medical_conditions-<?= h($cond['id']) ?>" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete this past medical condition record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button" style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="proceedDelete('past_medical_conditions', <?= h($cond['id']) ?>, this)">Delete</button>
                                                        <button type="button" style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="closeCustomDeletePopup('custom-delete-popup-past_medical_conditions-<?= h($cond['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Past Medical Condition Modal -->
                                    <div id="editPastCondModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Past Medical Condition</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="past_medical_conditions">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($cond['id']) ? h($cond['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Condition*
                                                    <select name="condition_dropdown" id="edit-condition-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-condition-other-input-<?= $idx ?>')">
                                                        <option value="">Select Condition</option>
                                                        <option value="Cancer">Cancer</option>
                                                        <option value="Stroke">Stroke</option>
                                                        <option value="Heart Attack">Heart Attack</option>
                                                        <option value="Tuberculosis">Tuberculosis</option>
                                                        <option value="Pneumonia">Pneumonia</option>
                                                        <option value="Peptic Ulcer Disease">Peptic Ulcer Disease</option>
                                                        <option value="Rheumatic Heart Disease">Rheumatic Heart Disease</option>
                                                        <option value="Hepatitis (B/C)">Hepatitis (B/C)</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="condition_other"
                                                        id="edit-condition-other-input-<?= $idx ?>"
                                                        placeholder="Specify Condition"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Year Diagnosed*
                                                    <input type="number" name="year_diagnosed" min="1900" max="<?= date('Y') ?>"
                                                        value="<?= h($cond['year_diagnosed']) ?>" required style="width:100%;">
                                                </label><br>
                                                <label>Status*
                                                    <select name="status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="Active" <?= h($cond['status']) == 'Active' ? 'selected' : '' ?>>
                                                            Active</option>
                                                        <option value="Resolved" <?= h($cond['status']) == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                        <option value="Unknown" <?= h($cond['status']) == 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                                                    </select>
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editPastCondModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function openEditPastCondModal(modalId, cond) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editPastCondModal', '');
                                            var condSel = document.getElementById('edit-condition-select-' + idx);
                                            var condOther = document.getElementById('edit-condition-other-input-' + idx);
                                            condSel.value = '';
                                            condOther.style.display = 'none';
                                            condOther.value = '';
                                            var foundCond = false;
                                            for (var i = 0; i < condSel.options.length; i++) {
                                                if (condSel.options[i].value === cond.condition) {
                                                    condSel.selectedIndex = i;
                                                    foundCond = true;
                                                    break;
                                                }
                                            }
                                            if (!foundCond && cond.condition) {
                                                condSel.value = 'Others';
                                                condOther.style.display = 'block';
                                                condOther.value = cond.condition;
                                            }
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Past Medical Condition Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="past_medical_conditions">
                        <h4>Add New Past Medical Condition</h4>
                        <label>Condition*
                            <select name="condition_dropdown" id="add-condition-select" required
                                onchange="editToggleOtherInput(this, 'add-condition-other-input')">
                                <option value="">Select Condition</option>
                                <option value="Cancer">Cancer</option>
                                <option value="Stroke">Stroke</option>
                                <option value="Heart Attack">Heart Attack</option>
                                <option value="Tuberculosis">Tuberculosis</option>
                                <option value="Pneumonia">Pneumonia</option>
                                <option value="Peptic Ulcer Disease">Peptic Ulcer Disease</option>
                                <option value="Rheumatic Heart Disease">Rheumatic Heart Disease</option>
                                <option value="Hepatitis (B/C)">Hepatitis (B/C)</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="condition_other" id="add-condition-other-input"
                                placeholder="Specify Condition" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Year Diagnosed*
                            <input type="number" name="year_diagnosed" min="1900" max="<?= date('Y') ?>" required
                                style="width:100%;">
                        </label><br>
                        <label>Status*
                            <select name="status" required>
                                <option value="">Select Status</option>
                                <option value="Active">Active</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit" class="action-btn" style="margin-top:0.5em;"><i
                                class="fas fa-plus icon"></i> Add</button>
                    </form>
                </div>
            </div>
            <!-- Chronic Illnesses Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;">Chronic Illnesses</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Illness</th>
                                <th style="padding:0.5em;">Year Diagnosed</th>
                                <th style="padding:0.5em;">Management</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['chronic_illnesses'])): ?>
                                <?php foreach ($medical_history['chronic_illnesses'] as $idx => $ill): ?>
                                    <tr>
                                        <td><?= h($ill['illness']) ?></td>
                                        <td><?= h($ill['year_diagnosed']) ?></td>
                                        <td><?= h($ill['management']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditChronicIllModal('editChronicIllModal<?= $idx ?>', <?= htmlspecialchars(json_encode($ill), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('chronic_illnesses', <?= h($ill['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup" id="custom-delete-popup-chronic_illnesses-<?= h($ill['id']) ?>" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete this chronic illness record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button" style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="proceedDelete('chronic_illnesses', <?= h($ill['id']) ?>, this)">Delete</button>
                                                        <button type="button" style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="closeCustomDeletePopup('custom-delete-popup-chronic_illnesses-<?= h($ill['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Chronic Illness Modal -->
                                    <div id="editChronicIllModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Chronic Illness</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="chronic_illnesses">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($ill['id']) ? h($ill['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Illness*
                                                    <select name="illness_dropdown" id="edit-illness-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-illness-other-input-<?= $idx ?>')">
                                                        <option value="">Select Illness</option>
                                                        <option value="Diabetes Mellitus">Diabetes Mellitus</option>
                                                        <option value="Asthma">Asthma</option>
                                                        <option value="COPD">COPD</option>
                                                        <option value="Cancer">Cancer</option>
                                                        <option value="Heart Disease">Heart Disease</option>
                                                        <option value="Kidney Disease">Kidney Disease</option>
                                                        <option value="Others">Others (specify)</option>
                                                        <option value="Hypertension">Hypertension</option>
                                                        <option value="Diabetes Mellitus">Diabetes Mellitus</option>
                                                        <option value="Asthma">Asthma</option>
                                                        <option value="COPD">COPD</option>
                                                        <option value="Epilepsy">Epilepsy</option>
                                                        <option value="Thyroid Disorder">Thyroid Disorder</option>
                                                        <option value="HIV/AIDS">HIV/AIDS</option>
                                                        <option value="Chronic Kidney Disease">Chronic Kidney Disease</option>
                                                        <option value="Coronary Artery Disease">Coronary Artery Disease</option>
                                                        <option value="Congestive Heart Failure">Congestive Heart Failure
                                                        </option>
                                                        <option value="Osteoarthritis">Osteoarthritis</option>
                                                        <option value="Rheumatoid Arthritis">Rheumatoid Arthritis</option>
                                                        <option value="Parkinson’s Disease">Parkinson’s Disease</option>
                                                        <option value="Dementia/Alzheimer’s">Dementia/Alzheimer’s</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="illness_other"
                                                        id="edit-illness-other-input-<?= $idx ?>" placeholder="Specify Illness"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Year Diagnosed*
                                                    <input type="number" name="year_diagnosed" min="1900" max="<?= date('Y') ?>"
                                                        value="<?= h($ill['year_diagnosed']) ?>" required style="width:100%;">
                                                </label><br>
                                                <label>Management*
                                                    <input type="text" name="management" value="<?= h($ill['management']) ?>"
                                                        required style="width:100%;">
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editChronicIllModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function openEditChronicIllModal(modalId, ill) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editChronicIllModal', '');
                                            var illSel = document.getElementById('edit-illness-select-' + idx);
                                            var illOther = document.getElementById('edit-illness-other-input-' + idx);
                                            illSel.value = '';
                                            illOther.style.display = 'none';
                                            illOther.value = '';
                                            var foundIll = false;
                                            for (var i = 0; i < illSel.options.length; i++) {
                                                if (illSel.options[i].value === ill.illness) {
                                                    illSel.selectedIndex = i;
                                                    foundIll = true;
                                                    break;
                                                }
                                            }
                                            if (!foundIll && ill.illness) {
                                                illSel.value = 'Others';
                                                illOther.style.display = 'block';
                                                illOther.value = ill.illness;
                                            }
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Chronic Illness Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="chronic_illnesses">
                        <h4>Add New Chronic Illness</h4>
                        <label>Illness*
                            <select name="illness_dropdown" id="add-illness-select" required
                                onchange="editToggleOtherInput(this, 'add-illness-other-input')">
                                <option value="">Select Illness</option>
                                <option value="Diabetes Mellitus">Diabetes Mellitus</option>
                                <option value="Asthma">Asthma</option>
                                <option value="COPD">COPD</option>
                                <option value="Cancer">Cancer</option>
                                <option value="Heart Disease">Heart Disease</option>
                                <option value="Kidney Disease">Kidney Disease</option>
                                <option value="Others">Others (specify)</option>
                                <option value="Hypertension">Hypertension</option>
                                <option value="Diabetes Mellitus">Diabetes Mellitus</option>
                                <option value="Asthma">Asthma</option>
                                <option value="COPD">COPD</option>
                                <option value="Epilepsy">Epilepsy</option>
                                <option value="Thyroid Disorder">Thyroid Disorder</option>
                                <option value="HIV/AIDS">HIV/AIDS</option>
                                <option value="Chronic Kidney Disease">Chronic Kidney Disease</option>
                                <option value="Coronary Artery Disease">Coronary Artery Disease</option>
                                <option value="Congestive Heart Failure">Congestive Heart Failure</option>
                                <option value="Osteoarthritis">Osteoarthritis</option>
                                <option value="Rheumatoid Arthritis">Rheumatoid Arthritis</option>
                                <option value="Parkinson’s Disease">Parkinson’s Disease</option>
                                <option value="Dementia/Alzheimer’s">Dementia/Alzheimer’s</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="illness_other" id="add-illness-other-input"
                                placeholder="Specify Illness" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Year Diagnosed*
                            <input type="number" name="year_diagnosed" min="1900" max="<?= date('Y') ?>" required
                                style="width:100%;">
                        </label><br>
                        <label>Management*
                            <input type="text" name="management" required style="width:100%;">
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit"
                            style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                    </form>
                </div>
            </div>
            <!-- Family History Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;">Family History</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Family Member</th>
                                <th style="padding:0.5em;">Condition</th>
                                <th style="padding:0.5em;">Age Diagnosed</th>
                                <th style="padding:0.5em;">Current Status</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['family_history'])): ?>
                                <?php foreach ($medical_history['family_history'] as $idx => $fh): ?>
                                    <tr>
                                        <td><?= h($fh['family_member']) ?></td>
                                        <td><?= h($fh['condition']) ?></td>
                                        <td><?= h($fh['age_diagnosed']) ?></td>
                                        <td><?= h($fh['current_status']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditFamilyHistModal('editFamilyHistModal<?= $idx ?>', <?= htmlspecialchars(json_encode($fh), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('family_history', <?= h($fh['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup" id="custom-delete-popup-family_history-<?= h($fh['id']) ?>" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete this family history record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button" style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="proceedDelete('family_history', <?= h($fh['id']) ?>, this)">Delete</button>
                                                        <button type="button" style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="closeCustomDeletePopup('custom-delete-popup-family_history-<?= h($fh['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Family History Modal -->
                                    <div id="editFamilyHistModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Family History</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="family_history">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($fh['id']) ? h($fh['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Family Member*
                                                    <select name="family_member_dropdown"
                                                        id="edit-family-member-select-<?= $idx ?>" required
                                                        onchange="editToggleOtherInput(this, 'edit-family-member-other-input-<?= $idx ?>')">
                                                        <option value="">Select Family Member</option>
                                                        <option value="Father">Father</option>
                                                        <option value="Mother">Mother</option>
                                                        <option value="Sibling">Sibling</option>
                                                        <option value="Cousin">Cousin</option>
                                                        <option value="Aunt">Aunt</option>
                                                        <option value="Uncle">Uncle</option>
                                                        <option value="Grandparent">Grandparent</option>
                                                        <option value="Child">Child</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="family_member_other"
                                                        id="edit-family-member-other-input-<?= $idx ?>"
                                                        placeholder="Specify Family Member"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Condition*
                                                    <select name="condition_dropdown"
                                                        id="edit-family-condition-select-<?= $idx ?>" required
                                                        onchange="editToggleOtherInput(this, 'edit-family-condition-other-input-<?= $idx ?>')">
                                                        <option value="">Select Condition</option>
                                                        <option value="Hypertension">Hypertension</option>
                                                        <option value="Diabetes Mellitus">Diabetes Mellitus</option>
                                                        <option value="Cancer">Cancer</option>
                                                        <option value="Heart Disease">Heart Disease</option>
                                                        <option value="Stroke">Stroke</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="condition_other"
                                                        id="edit-family-condition-other-input-<?= $idx ?>"
                                                        placeholder="Specify Condition"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Age Diagnosed*
                                                    <input type="number" name="age_diagnosed" min="0"
                                                        value="<?= h($fh['age_diagnosed']) ?>" required style="width:100%;">
                                                </label><br>
                                                <label>Current Status*
                                                    <select name="current_status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="Living" <?= h($fh['current_status']) == 'Living' ? 'selected' : '' ?>>Living
                                                        </option>
                                                        <option value="Deceased" <?= h($fh['current_status']) == 'Deceased' ? 'selected' : '' ?>>Deceased
                                                        </option>
                                                        <option value="Unknown" <?= h($fh['current_status']) == 'Unknown' ? 'selected' : '' ?>>Unknown
                                                        </option>
                                                    </select>
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editFamilyHistModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function openEditFamilyHistModal(modalId, fh) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editFamilyHistModal', '');
                                            // Family Member
                                            var famSel = document.getElementById('edit-family-member-select-' + idx);
                                            var famOther = document.getElementById('edit-family-member-other-input-' + idx);
                                            famSel.value = '';
                                            famOther.style.display = 'none';
                                            famOther.value = '';
                                            var foundFam = false;
                                            for (var i = 0; i < famSel.options.length; i++) {
                                                if (famSel.options[i].value === fh.family_member) {
                                                    famSel.selectedIndex = i;
                                                    foundFam = true;
                                                    break;
                                                }
                                            }
                                            if (!foundFam && fh.family_member) {
                                                famSel.value = 'Others';
                                                famOther.style.display = 'block';
                                                famOther.value = fh.family_member;
                                            }
                                            // Condition
                                            var condSel = document.getElementById('edit-family-condition-select-' + idx);
                                            var condOther = document.getElementById('edit-family-condition-other-input-' + idx);
                                            condSel.value = '';
                                            condOther.style.display = 'none';
                                            condOther.value = '';
                                            var foundCond = false;
                                            for (var j = 0; j < condSel.options.length; j++) {
                                                if (condSel.options[j].value === fh.condition) {
                                                    condSel.selectedIndex = j;
                                                    foundCond = true;
                                                    break;
                                                }
                                            }
                                            if (!foundCond && fh.condition) {
                                                condSel.value = 'Others';
                                                condOther.style.display = 'block';
                                                condOther.value = fh.condition;
                                            }
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Family History Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="family_history">
                        <h4>Add New Family History</h4>
                        <label>Family Member*
                            <select name="family_member_dropdown" id="add-family-member-select" required
                                onchange="editToggleOtherInput(this, 'add-family-member-other-input')">
                                <option value="">Select Family Member</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Cousin">Cousin</option>
                                <option value="Aunt">Aunt</option>
                                <option value="Uncle">Uncle</option>
                                <option value="Grandparent">Grandparent</option>
                                <option value="Child">Child</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="family_member_other" id="add-family-member-other-input"
                                placeholder="Specify Family Member" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Condition*
                            <select name="condition_dropdown" id="add-family-condition-select" required
                                onchange="editToggleOtherInput(this, 'add-family-condition-other-input')">
                                <option value="">Select Condition</option>
                                <option value="Hypertension">Hypertension</option>
                                <option value="Diabetes Mellitus">Diabetes Mellitus</option>
                                <option value="Cancer">Cancer</option>
                                <option value="Heart Disease">Heart Disease</option>
                                <option value="Stroke">Stroke</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="condition_other" id="add-family-condition-other-input"
                                placeholder="Specify Condition" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Age Diagnosed*
                            <input type="number" name="age_diagnosed" min="0" required style="width:100%;">
                        </label><br>
                        <label>Current Status*
                            <select name="current_status" required>
                                <option value="">Select Status</option>
                                <option value="Living">Living</option>
                                <option value="Deceased">Deceased</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit"
                            style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                    </form>
                </div>
            </div>
            <!-- Surgical History Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;">Surgical History</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Surgery</th>
                                <th style="padding:0.5em;">Year</th>
                                <th style="padding:0.5em;">Hospital</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['surgical_history'])): ?>
                                <?php foreach ($medical_history['surgical_history'] as $idx => $surg): ?>
                                    <tr>
                                        <td><?= h($surg['surgery']) ?></td>
                                        <td><?= h($surg['year']) ?></td>
                                        <td><?= h($surg['hospital']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditSurgHistModal('editSurgHistModal<?= $idx ?>', <?= htmlspecialchars(json_encode($surg), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('surgical_history', <?= h($surg['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup" id="custom-delete-popup-surgical_history-<?= h($surg['id']) ?>" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete this surgical history record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button" style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="proceedDelete('surgical_history', <?= h($surg['id']) ?>, this)">Delete</button>
                                                        <button type="button" style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="closeCustomDeletePopup('custom-delete-popup-surgical_history-<?= h($surg['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Surgical History Modal -->
                                    <div id="editSurgHistModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Surgical History</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="surgical_history">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($surg['id']) ? h($surg['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Surgery*
                                                    <select name="surgery_dropdown" id="edit-surgery-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-surgery-other-input-<?= $idx ?>')"
                                                        style="width:100%;">
                                                        <option value="">Select Surgery</option>
                                                        <option value="Appendectomy">Appendectomy</option>
                                                        <option value="Cholecystectomy">Cholecystectomy</option>
                                                        <option value="Caesarean Section">Caesarean Section</option>
                                                        <option value="Hernia Repair">Hernia Repair</option>
                                                        <option value="Tonsillectomy">Tonsillectomy</option>
                                                        <option value="Mastectomy">Mastectomy</option>
                                                        <option value="Coronary Bypass">Coronary Bypass</option>
                                                        <option value="Hip Replacement">Hip Replacement</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="surgery_other"
                                                        id="edit-surgery-other-input-<?= $idx ?>" placeholder="Specify Surgery"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Year*
                                                    <input type="number" name="year" min="1900" max="<?= date('Y') ?>"
                                                        value="<?= h($surg['year']) ?>" required style="width:100%;">
                                                </label><br>
                                                <label>Hospital*
                                                    <select name="hospital_dropdown" id="edit-hospital-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-hospital-other-input-<?= $idx ?>')"
                                                        style="width:100%;">
                                                        <option value="">Select Hospital</option>
                                                        <option value="South Cotabato Provincial Hospital">South Cotabato Provincial Hospital</option>
                                                        <option value="Dr. Arturo P. Pingoy Medical Center (DAPPMC)">Dr. Arturo P. Pingoy Medical Center (DAPPMC)</option>
                                                        <option value="Allah Valley Medical Specialists' Center, Inc. (AVMSCI)">Allah Valley Medical Specialists' Center, Inc. (AVMSCI)</option>
                                                        <option value="Socomedics Medical Center">Socomedics Medical Center</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="hospital_other"
                                                        id="edit-hospital-other-input-<?= $idx ?>"
                                                        placeholder="Specify Hospital"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editSurgHistModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function openEditSurgHistModal(modalId, surg) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editSurgHistModal', '');
                                            // Surgery Dropdown
                                            var surgSel = document.getElementById('edit-surgery-select-' + idx);
                                            var surgOther = document.getElementById('edit-surgery-other-input-' + idx);
                                            surgSel.value = '';
                                            surgOther.style.display = 'none';
                                            surgOther.value = '';
                                            var foundSurg = false;
                                            for (var i = 0; i < surgSel.options.length; i++) {
                                                if (surgSel.options[i].value === surg.surgery) {
                                                    surgSel.selectedIndex = i;
                                                    foundSurg = true;
                                                    break;
                                                }
                                            }
                                            if (!foundSurg && surg.surgery) {
                                                surgSel.value = 'Others';
                                                surgOther.style.display = 'block';
                                                surgOther.value = surg.surgery;
                                            }
                                            // Year
                                            var yearInput = document.querySelector('#editSurgHistModal' + idx + ' input[name="year"]');
                                            if (yearInput) yearInput.value = surg.year || '';
                                            // Hospital Dropdown
                                            var hospSel = document.getElementById('edit-hospital-select-' + idx);
                                            var hospOther = document.getElementById('edit-hospital-other-input-' + idx);
                                            hospSel.value = '';
                                            hospOther.style.display = 'none';
                                            hospOther.value = '';
                                            var foundHosp = false;
                                            for (var j = 0; j < hospSel.options.length; j++) {
                                                if (hospSel.options[j].value === surg.hospital) {
                                                    hospSel.selectedIndex = j;
                                                    foundHosp = true;
                                                    break;
                                                }
                                            }
                                            if (!foundHosp && surg.hospital) {
                                                hospSel.value = 'Others';
                                                hospOther.style.display = 'block';
                                                hospOther.value = surg.hospital;
                                            }
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Surgical History Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="surgical_history">
                        <h4>Add New Surgical History</h4>
                        <label>Surgery*
                            <select name="surgery_dropdown" id="add-surgery-select" required
                                onchange="editToggleOtherInput(this, 'add-surgery-other-input')" style="width:100%;">
                                <option value="">Select Surgery</option>
                                <option value="Appendectomy">Appendectomy</option>
                                <option value="Cholecystectomy">Cholecystectomy</option>
                                <option value="Caesarean Section">Caesarean Section</option>
                                <option value="Hernia Repair">Hernia Repair</option>
                                <option value="Tonsillectomy">Tonsillectomy</option>
                                <option value="Mastectomy">Mastectomy</option>
                                <option value="Coronary Bypass">Coronary Bypass</option>
                                <option value="Hip Replacement">Hip Replacement</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="surgery_other" id="add-surgery-other-input"
                                placeholder="Specify Surgery" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Year*
                            <input type="number" name="year" min="1900" max="<?= date('Y') ?>" required
                                style="width:100%;">
                        </label><br>
                        <label>Hospital*
                            <select name="hospital_dropdown" id="add-hospital-select" required
                                onchange="editToggleOtherInput(this, 'add-hospital-other-input')" style="width:100%;">
                                <option value="">Select Hospital</option>
                                <option value="South Cotabato Provincial Hospital">South Cotabato Provincial Hospital
                                </option>
                                <option value="Dr. Arturo P. Pingoy Medical Center (DAPPMC)">Dr. Arturo P. Pingoy
                                    Medical Center (DAPPMC)</option>
                                <option value="Allah Valley Medical Specialists' Center, Inc. (AVMSCI)">Allah Valley
                                    Medical Specialists' Center, Inc. (AVMSCI)</option>
                                <option value="Socomedics Medical Center">Socomedics Medical Center</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="hospital_other" id="add-hospital-other-input"
                                placeholder="Specify Hospital" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit"
                            style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                    </form>
                </div>
            </div>
            <!-- Current Medication Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;">Current Medications</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Medication</th>
                                <th style="padding:0.5em;">Dosage</th>
                                <th style="padding:0.5em;">Frequency</th>
                                <th style="padding:0.5em;">Prescribed By</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['current_medications'])): ?>
                                <?php foreach ($medical_history['current_medications'] as $idx => $med): ?>
                                    <tr>
                                        <td><?= h($med['medication']) ?></td>
                                        <td><?= h($med['dosage']) ?></td>
                                        <td><?= h($med['frequency']) ?></td>
                                        <td><?= h($med['prescribed_by']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditMedModal('editMedModal<?= $idx ?>', <?= htmlspecialchars(json_encode($med), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('current_medications', <?= h($med['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup" id="custom-delete-popup-current_medications-<?= h($med['id']) ?>" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete this medication record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button" style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="proceedDelete('current_medications', <?= h($med['id']) ?>, this)">Delete</button>
                                                        <button type="button" style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="closeCustomDeletePopup('custom-delete-popup-current_medications-<?= h($med['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Medication Modal -->
                                    <div id="editMedModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Medication</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="current_medications">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($med['id']) ? h($med['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Medication*
                                                    <select name="medication_dropdown" id="edit-medication-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-medication-other-input-<?= $idx ?>')">
                                                        <option value="">Select Medication</option>
                                                        <option value="Metformin">Metformin</option>
                                                        <option value="Lisinopril">Lisinopril</option>
                                                        <option value="Amlodipine">Amlodipine</option>
                                                        <option value="Atorvastatin">Atorvastatin</option>
                                                        <option value="Paracetamol">Paracetamol</option>
                                                        <option value="Ibuprofen">Ibuprofen</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="medication_other"
                                                        id="edit-medication-other-input-<?= $idx ?>"
                                                        placeholder="Specify Medication"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Dosage*
                                                    <input type="text" name="dosage" value="<?= h($med['dosage']) ?>" required
                                                        style="width:100%;">
                                                </label><br>
                                                <label>Frequency*
                                                    <select name="frequency_dropdown" id="edit-frequency-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-frequency-other-input-<?= $idx ?>')">
                                                        <option value="">Select Frequency</option>
                                                        <option value="Once Daily">Once Daily</option>
                                                        <option value="Twice Daily">Twice Daily</option>
                                                        <option value="Three Times Daily">Three Times Daily</option>
                                                        <option value="Every Other Day">Every Other Day</option>
                                                        <option value="As Needed">As Needed</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="frequency_other"
                                                        id="edit-frequency-other-input-<?= $idx ?>"
                                                        placeholder="Specify Frequency"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Prescribed By
                                                    <input type="text" name="prescribed_by"
                                                        value="<?= h($med['prescribed_by']) ?>" style="width:100%;">
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editMedModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function openEditMedModal(modalId, med) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editMedModal', '');
                                            // Medication
                                            var medSel = document.getElementById('edit-medication-select-' + idx);
                                            var medOther = document.getElementById('edit-medication-other-input-' + idx);
                                            medSel.value = '';
                                            medOther.style.display = 'none';
                                            medOther.value = '';
                                            var foundMed = false;
                                            for (var i = 0; i < medSel.options.length; i++) {
                                                if (medSel.options[i].value === med.medication) {
                                                    medSel.selectedIndex = i;
                                                    foundMed = true;
                                                    break;
                                                }
                                            }
                                            if (!foundMed && med.medication) {
                                                medSel.value = 'Others';
                                                medOther.style.display = 'block';
                                                medOther.value = med.medication;
                                            }
                                            // Frequency
                                            var freqSel = document.getElementById('edit-frequency-select-' + idx);
                                            var freqOther = document.getElementById('edit-frequency-other-input-' + idx);
                                            freqSel.value = '';
                                            freqOther.style.display = 'none';
                                            freqOther.value = '';
                                            var foundFreq = false;
                                            for (var j = 0; j < freqSel.options.length; j++) {
                                                if (freqSel.options[j].value === med.frequency) {
                                                    freqSel.selectedIndex = j;
                                                    foundFreq = true;
                                                    break;
                                                }
                                            }
                                            if (!foundFreq && med.frequency) {
                                                freqSel.value = 'Others';
                                                freqOther.style.display = 'block';
                                                freqOther.value = med.frequency;
                                            }
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Current Medication Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="current_medications">
                        <h4>Add New Medication</h4>
                        <label>Medication*
                            <select name="medication_dropdown" id="add-medication-select" required
                                onchange="editToggleOtherInput(this, 'add-medication-other-input')">
                                <option value="">Select Medication</option>
                                <option value="Metformin">Metformin</option>
                                <option value="Lisinopril">Lisinopril</option>
                                <option value="Amlodipine">Amlodipine</option>
                                <option value="Atorvastatin">Atorvastatin</option>
                                <option value="Paracetamol">Paracetamol</option>
                                <option value="Ibuprofen">Ibuprofen</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="medication_other" id="add-medication-other-input"
                                placeholder="Specify Medication" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Dosage*
                            <input type="text" name="dosage" required style="width:100%;">
                        </label><br>
                        <label>Frequency*
                            <select name="frequency_dropdown" id="add-frequency-select" required
                                onchange="editToggleOtherInput(this, 'add-frequency-other-input')">
                                <option value="">Select Frequency</option>
                                <option value="Once Daily">Once Daily</option>
                                <option value="Twice Daily">Twice Daily</option>
                                <option value="Three Times Daily">Three Times Daily</option>
                                <option value="Every Other Day">Every Other Day</option>
                                <option value="As Needed">As Needed</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="frequency_other" id="add-frequency-other-input"
                                placeholder="Specify Frequency" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Prescribed By
                            <input type="text" name="prescribed_by" style="width:100%;">
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit"
                            style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                    </form>
                </div>
            </div>
            <!-- Immunizations Table -->
            <div class="profile-photo-col">
                <div class="profile-card">
                    <h3 style="margin-top:0;color:#333;">Immunizations</h3>
                    <table class="medical-history-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:0.5em;">Vaccine</th>
                                <th style="padding:0.5em;">Year Received</th>
                                <th style="padding:0.5em;">Doses Completed</th>
                                <th style="padding:0.5em;">Status</th>
                                <th style="padding:0.5em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medical_history['immunizations'])): ?>
                                <?php foreach ($medical_history['immunizations'] as $idx => $imm): ?>
                                    <tr>
                                        <td><?= h($imm['vaccine']) ?></td>
                                        <td><?= h($imm['year_received']) ?></td>
                                        <td><?= h($imm['doses_completed']) ?></td>
                                        <td><?= h($imm['status']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <div class="action-btn-group" style="display:flex;gap:0.5em;justify-content:center;align-items:center;flex-wrap:wrap;">
                                                <button type="button" class="action-btn edit" title="Edit" style="background:#f1c40f;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openEditImmunModal('editImmunModal<?= $idx ?>', <?= htmlspecialchars(json_encode($imm), ENT_QUOTES, 'UTF-8') ?>)"><i class='fas fa-edit icon'></i> Edit</button>
                                                <button type="button" class="action-btn delete" title="Delete" style="background:#e74c3c;color:#fff;border:none;padding:0.3em 0.8em;border-radius:5px;cursor:pointer;font-weight:600;font-size:0.95em;min-width:70px;display:flex;align-items:center;gap:0.3em;" onclick="openCustomDeletePopup('immunizations', <?= h($imm['id']) ?>, this)"><i class="fas fa-trash icon"></i> Delete</button>
                                            </div>
                                            <!-- Custom Delete Popup -->
                                            <div class="custom-delete-popup" id="custom-delete-popup-immunizations-<?= h($imm['id']) ?>" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);justify-content:center;align-items:center;">
                                                <div style="background:#fff;padding:2em 1.5em;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.15);max-width:90vw;width:350px;text-align:center;">
                                                    <div style="font-size:1.2em;font-weight:600;color:#e74c3c;margin-bottom:1em;">Confirm Deletion</div>
                                                    <div style="margin-bottom:1.5em;color:#444;">Are you sure you want to delete this immunization record?</div>
                                                    <div style="display:flex;gap:1em;justify-content:center;">
                                                        <button type="button" style="background:#e74c3c;color:#fff;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="proceedDelete('immunizations', <?= h($imm['id']) ?>, this)">Delete</button>
                                                        <button type="button" style="background:#bbb;color:#333;border:none;padding:0.5em 1.5em;border-radius:5px;font-weight:600;cursor:pointer;" onclick="closeCustomDeletePopup('custom-delete-popup-immunizations-<?= h($imm['id']) ?>')">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Immunization Modal -->
                                    <div id="editImmunModal<?= $idx ?>" class="custom-modal" style="display:none;">
                                        <div class="custom-modal-content" style="max-width:400px;">
                                            <h3>Edit Immunization</h3>
                                            <form method="post" action="update_medical_history.php">
                                                <input type="hidden" name="table" value="immunizations">
                                                <input type="hidden" name="id"
                                                    value="<?= isset($imm['id']) ? h($imm['id']) : '' ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                                <label>Vaccine*
                                                    <select name="vaccine_dropdown" id="edit-vaccine-select-<?= $idx ?>"
                                                        required
                                                        onchange="editToggleOtherInput(this, 'edit-vaccine-other-input-<?= $idx ?>')">
                                                        <option value="">Select Vaccine</option>
                                                        <option value="COVID-19">COVID-19</option>
                                                        <option value="Influenza">Influenza</option>
                                                        <option value="Hepatitis B">Hepatitis B</option>
                                                        <option value="Tetanus">Tetanus</option>
                                                        <option value="Measles/MMR">Measles/MMR</option>
                                                        <option value="Varicella">Varicella</option>
                                                        <option value="Polio">Polio</option>
                                                        <option value="Pneumococcal">Pneumococcal</option>
                                                        <option value="HPV">HPV</option>
                                                        <option value="Rabies">Rabies</option>
                                                        <option value="Typhoid">Typhoid</option>
                                                        <option value="Others">Others (specify)</option>
                                                    </select>
                                                    <input type="text" name="vaccine_other"
                                                        id="edit-vaccine-other-input-<?= $idx ?>" placeholder="Specify Vaccine"
                                                        style="display:none;margin-top:0.3em;width:100%;" />
                                                </label><br>
                                                <label>Year Received*
                                                    <input type="number" name="year_received" min="1900" max="<?= date('Y') ?>"
                                                        value="<?= h($imm['year_received']) ?>" required style="width:100%;">
                                                </label><br>
                                                <label>Doses Completed*
                                                    <input type="number" name="doses_completed" min="0"
                                                        value="<?= h($imm['doses_completed']) ?>" required style="width:100%;">
                                                </label><br>
                                                <label>Status*
                                                    <select name="status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="Complete" <?= h($imm['status']) == 'Complete' ? 'selected' : '' ?>>Complete</option>
                                                        <option value="Incomplete" <?= h($imm['status']) == 'Incomplete' ? 'selected' : '' ?>>Incomplete
                                                        </option>
                                                        <option value="Pending" <?= h($imm['status']) == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Unknown" <?= h($imm['status']) == 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                                                    </select>
                                                </label><br>
                                                <button type="submit"
                                                    style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Save</button>
                                                <button type="button" onclick="closeModal('editImmunModal<?= $idx ?>')"
                                                    style="margin-left:1em;">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function openEditImmunModal(modalId, imm) {
                                            document.getElementById(modalId).style.display = 'flex';
                                            var idx = modalId.replace('editImmunModal', '');
                                            var vacSel = document.getElementById('edit-vaccine-select-' + idx);
                                            var vacOther = document.getElementById('edit-vaccine-other-input-' + idx);
                                            vacSel.value = '';
                                            vacOther.style.display = 'none';
                                            vacOther.value = '';
                                            var foundVac = false;
                                            for (var i = 0; i < vacSel.options.length; i++) {
                                                if (vacSel.options[i].value === imm.vaccine) {
                                                    vacSel.selectedIndex = i;
                                                    foundVac = true;
                                                    break;
                                                }
                                            }
                                            if (!foundVac && imm.vaccine) {
                                                vacSel.value = 'Others';
                                                vacOther.style.display = 'block';
                                                vacOther.value = imm.vaccine;
                                            }
                                        }
                                    </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#888;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Add Immunization Form -->
                    <form method="post" action="add_medical_history.php" style="margin-top:1em;">
                        <input type="hidden" name="table" value="immunizations">
                        <h4>Add New Immunization</h4>
                        <label>Vaccine*
                            <select name="vaccine_dropdown" id="add-vaccine-select" required
                                onchange="editToggleOtherInput(this, 'add-vaccine-other-input')">
                                <option value="">Select Vaccine</option>
                                <option value="COVID-19">COVID-19</option>
                                <option value="Influenza">Influenza</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Tetanus">Tetanus</option>
                                <option value="Measles/MMR">Measles/MMR</option>
                                <option value="Varicella">Varicella</option>
                                <option value="Polio">Polio</option>
                                <option value="Pneumococcal">Pneumococcal</option>
                                <option value="HPV">HPV</option>
                                <option value="Rabies">Rabies</option>
                                <option value="Typhoid">Typhoid</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="vaccine_other" id="add-vaccine-other-input"
                                placeholder="Specify Vaccine" style="display:none;margin-top:0.3em;width:100%;" />
                        </label><br>
                        <label>Year Received*
                            <input type="number" name="year_received" min="1900" max="<?= date('Y') ?>" required
                                style="width:100%;">
                        </label><br>
                        <label>Doses Completed*
                            <input type="number" name="doses_completed" min="0" required style="width:100%;">
                        </label><br>
                        <label>Status*
                            <select name="status" required>
                                <option value="">Select Status</option>
                                <option value="Complete">Complete</option>
                                <option value="Incomplete">Incomplete</option>
                                <option value="Pending">Pending</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </label><br>
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <button type="submit"
                            style="background:#27ae60;color:#fff;border:none;padding:0.5em 1.2em;border-radius:5px;cursor:pointer;font-weight:600;">Add</button>
                    </form>
                </div>
            </div>

        </div>
    </section>


    <style>
        .custom-modal {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18);
            padding: 2em 2.5em;
            max-width: 350px;
            text-align: center;
            animation: modalFadeIn 0.2s;
        }

        @keyframes modalFadeIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
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

        .btn-danger:hover {
            background: #a93226;
        }

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

        .btn-secondary:hover {
            background: #d5d5d5;
        }

        /* Responsive logo switch for mobile */
        .responsive-logo {
            height: 48px;
            transition: content 0.2s;
        }

        @media (max-width: 600px) {
            .responsive-logo {
                content: url('https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128');
                height: 48px;
            }
        }
    </style>
    <script>
        // Custom Back/Cancel modal logic
        const backBtn = document.getElementById('backCancelBtn');
        const modal = document.getElementById('backCancelModal');
        const modalCancel = document.getElementById('modalCancelBtn');
        const modalStay = document.getElementById('modalStayBtn');
        backBtn.addEventListener('click', function () {
            modal.style.display = 'flex';
        });
        modalCancel.addEventListener('click', function () {
            window.location.href = 'patientProfile.php';
        });
        modalStay.addEventListener('click', function () {
            modal.style.display = 'none';
        });
        // Close modal on outside click
        modal.addEventListener('click', function (e) {
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
    <script>
        function confirmDelete(table, id, btn) {
            if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                btn.disabled = true;
                fetch('delete_medical_history.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `table=${encodeURIComponent(table)}&id=${encodeURIComponent(id)}`
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Remove row from table
                            const row = btn.closest('tr');
                            if (row) row.remove();
                        } else {
                            alert('Delete failed: ' + (data.error || 'Unknown error'));
                            btn.disabled = false;
                        }
                    })
                    .catch(() => {
                        alert('Delete failed due to network error.');
                        btn.disabled = false;
                    });
            }
        }
    </script>
</body>

</html>