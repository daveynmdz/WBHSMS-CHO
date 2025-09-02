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
// Fetch personal_information for this patient
$stmt = $pdo->prepare("SELECT * FROM personal_information WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$personal_information = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch lifestyle_info for this patient
$stmt = $pdo->prepare("SELECT * FROM lifestyle_info WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$lifestyle_info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch emergency_contact for this patient
$stmt = $pdo->prepare("SELECT * FROM emergency_contact WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$emergency_contact = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Handle form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';
    if ($form_type === 'personal_info') {
        // Only update personal info fields
        $fields = [
            'blood_type', 'civil_status', 'religion', 'occupation', 'philhealth_id'
        ];
        $updates = [];
        $params = [];
        foreach ($fields as $field) {
            $updates[] = "$field = ?";
            $params[] = trim($_POST[$field] ?? '');
        }
        $params[] = $patient_id;
        $stmt = $pdo->prepare("SELECT id FROM personal_information WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        if ($stmt->fetch()) {
            $sql = "UPDATE personal_information SET " . implode(', ', $updates) . " WHERE patient_id = ?";
        } else {
            $fields_str = implode(', ', $fields) . ', patient_id';
            $qmarks = rtrim(str_repeat('?, ', count($fields)), ', ') . ', ?';
            $sql = "INSERT INTO personal_information ($fields_str) VALUES ($qmarks)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } elseif ($form_type === 'home_address') {
        // Only update street
        $street = trim($_POST['street'] ?? '');
        $stmt = $pdo->prepare("SELECT id FROM personal_information WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        if ($stmt->fetch()) {
            $sql = "UPDATE personal_information SET street = ? WHERE patient_id = ?";
        } else {
            $sql = "INSERT INTO personal_information (street, patient_id) VALUES (?, ?)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$street, $patient_id]);
    } elseif ($form_type === 'emergency_contact') {
        // Update emergency_contact table with correct column names
        $fields = ['last_name', 'first_name', 'middle_name', 'relation', 'contact_num'];
        $form_fields = ['ec_last_name', 'ec_first_name', 'ec_middle_name', 'ec_relation', 'ec_contact_num'];
        $updates = [];
        $params = [];
        foreach ($fields as $i => $col) {
            $updates[] = "$col = ?";
            $params[] = trim($_POST[$form_fields[$i]] ?? '');
        }
        $params[] = $patient_id;
        $stmt = $pdo->prepare("SELECT id FROM emergency_contact WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        if ($stmt->fetch()) {
            $sql = "UPDATE emergency_contact SET " . implode(', ', $updates) . " WHERE patient_id = ?";
        } else {
            $fields_str = implode(', ', $fields) . ', patient_id';
            $qmarks = rtrim(str_repeat('?, ', count($fields)), ', ') . ', ?';
            $sql = "INSERT INTO emergency_contact ($fields_str) VALUES ($qmarks)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } elseif ($form_type === 'lifestyle_info') {
        // Update lifestyle_info table instead of personal_information
        $fields = ['smoking_status', 'alcohol_intake', 'physical_act', 'diet_habit'];
        $updates = [];
        $params = [];
        foreach ($fields as $field) {
            $updates[] = "$field = ?";
            $params[] = trim($_POST[$field] ?? '');
        }
        $params[] = $patient_id;
        $stmt = $pdo->prepare("SELECT id FROM lifestyle_info WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        if ($stmt->fetch()) {
            $sql = "UPDATE lifestyle_info SET " . implode(', ', $updates) . " WHERE patient_id = ?";
        } else {
            $fields_str = implode(', ', $fields) . ', patient_id';
            $qmarks = rtrim(str_repeat('?, ', count($fields)), ', ') . ', ?';
            $sql = "INSERT INTO lifestyle_info ($fields_str) VALUES ($qmarks)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    // Redirect to self to prevent resubmission and reload updated data (PRG pattern)
    header('Location: patientEditProfile.php');
    exit();
}

function h($v)
{
    return htmlspecialchars($v ?? '');
}
$profile_photo_url = !empty($patient['profile_photo']) ? 'images/' . $patient['profile_photo'] : 'https://i.ibb.co/Y0m9XGk/user-icon.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Edit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/patientUI.css">
    <link rel="stylesheet" href="css/patientProfile.css">
    <link rel="stylesheet" href="css/editProfileLayout.css">
    <link rel="stylesheet" href="css/cropper-modal.css">
    <!-- Cropper.js CSS from CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
</head>

<body>
    <header class="edit-profile-topbar">
        <a href="patientHomepage.php" class="topbar-logo">
            <img src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
        <div class="topbar-title" style="color: #ffffff;">Edit Profile</div>
        <div class="topbar-userinfo">
            <div class="topbar-usertext">
                <strong
                    style="color: #ffffff;"><?= h($patient['first_name'] . ' ' . $patient['last_name']) ?></strong><br>
                <small style="color: #ffffff;">Patient</small>
            </div>
            <img src="patient_profile_photo.php" alt="User Profile" class="topbar-userphoto" onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';" />

        </div>
    </header>
    <section>
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
        <div class="edit-profile-flex profile-photo-info-grid">
            <div class="profile-photo-col">
                <form class="profile-card profile-photo-form" id="profilePhotoForm" method="post"
                    enctype="multipart/form-data" action="profile_photo_upload.php">
                    <h3>Profile Photo</h3>
                    <div class="profile-photo-container">
                        <img src="patient_profile_photo.php" alt="Profile Photo" id="profilePhotoPreview"
                            style="width:100%;max-width:200px;aspect-ratio:1/1;object-fit:cover;border-radius:8px;display:block;margin:auto;"
                            onerror="this.onerror=null;this.src='https://ik.imagekit.io/wbhsmslogo/user.png?updatedAt=1750423429172';" />
                        <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*"
                            style="margin-top: 1em;" />
                    </div>
                    <div>
                        <strong>Picture Requirements:</strong>
                        <ul style="margin:0.5em 0 0 1.2em; padding:0; list-style:disc;">
                            <li>2x2-sized photo.</li>
                            <li>Under 10 MB only.</li>
                        </ul>
                    </div>
                    <div class="form-actions"><button class="btn" type="submit" id="savePhotoBtn" disabled>Save
                            Photo</button></div>
                </form>
            </div>
            <div class="profile-info-col">
                <!-- Personal Information Form -->
                <form class="profile-card" id="personalInfoForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="form_type" value="personal_info">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <label>Last Name <input type="text" name="last_name" value="<?= h($patient['last_name']) ?>" required readonly class="uneditable-field"></label>
                        <label>First Name <input type="text" name="first_name" value="<?= h($patient['first_name']) ?>" required readonly class="uneditable-field"></label>
                        <label>Middle Name <input type="text" name="middle_name" value="<?= h($patient['middle_name']) ?>" readonly class="uneditable-field"></label>
                    </div>
                    <div class="form-row">
                        <label>Suffix <input type="text" name="suffix" value="<?= h($patient['suffix']) ?>"></label>
                        <label>Date of Birth <input type="date" name="dob" id="dobField" value="<?= h($patient['dob']) ?>" required readonly class="uneditable-field"></label>
                        <label>Age <input type="text" id="ageField" value="<?= h($patient['dob']) ? (date_diff(date_create($patient['dob']), date_create('now'))->y) : '' ?>" readonly class="uneditable-field"></label>
                        <label>Sex <select name="sex" required disabled class="uneditable-field">
                                <option value="">Select</option>
                                <option value="Male" <?= $patient['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $patient['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select></label>
                    </div>
                    <div class="form-row">
                        <label>Blood Type
                            <select name="blood_type">
                                <option value="">Select</option>
                                <option value="A+" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'A+') ? 'selected' : '' ?>>A+</option>
                                <option value="A-" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'A-') ? 'selected' : '' ?>>A-</option>
                                <option value="B+" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'B+') ? 'selected' : '' ?>>B+</option>
                                <option value="B-" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'B-') ? 'selected' : '' ?>>B-</option>
                                <option value="AB+" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'AB+') ? 'selected' : '' ?>>AB+</option>
                                <option value="AB-" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'AB-') ? 'selected' : '' ?>>AB-</option>
                                <option value="O+" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'O+') ? 'selected' : '' ?>>O+</option>
                                <option value="O-" <?= (isset($personal_information['blood_type']) && $personal_information['blood_type'] === 'O-') ? 'selected' : '' ?>>O-</option>
                            </select>
                        </label>
                        <label>Civil Status
                            <select name="civil_status" required>
                                <option value="" <?= empty($personal_information['civil_status']) ? 'selected' : '' ?>>
                                    Select</option>
                                <option value="Single" <?= (isset($personal_information['civil_status']) && $personal_information['civil_status'] === 'Single') ? 'selected' : '' ?>>Single
                                </option>
                                <option value="Married" <?= (isset($personal_information['civil_status']) && $personal_information['civil_status'] === 'Married') ? 'selected' : '' ?>>Married
                                </option>
                                <option value="Widowed" <?= (isset($personal_information['civil_status']) && $personal_information['civil_status'] === 'Widowed') ? 'selected' : '' ?>>Widowed
                                </option>
                                <option value="Legally Separated" <?= (isset($personal_information['civil_status']) && $personal_information['civil_status'] === 'Legally Separated') ? 'selected' : '' ?>>
                                    Legally Separated</option>
                            </select>
                        </label>
                        <label>Religion
                            <select name="religion" required>
                                <option value="" <?= empty($personal_information['religion']) ? 'selected' : '' ?>>Select
                                </option>
                                <option value="Roman Catholic" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Roman Catholic') ? 'selected' : '' ?>>Roman
                                    Catholic</option>
                                <option value="Islam" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Islam') ? 'selected' : '' ?>>Islam</option>
                                <option value="Iglesia ni Cristo" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Iglesia ni Cristo') ? 'selected' : '' ?>>
                                    Iglesia ni Cristo</option>
                                <option value="Seventh Day Adventist" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Seventh Day Adventist') ? 'selected' : '' ?>>
                                    Seventh Day Adventist</option>
                                <option value="Aglipay" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Aglipay') ? 'selected' : '' ?>>Aglipay</option>
                                <option value="Iglesia Filipina Independiente"
                                    <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Iglesia Filipina Independiente') ? 'selected' : '' ?>>Iglesia Filipina Independiente</option>
                                <option value="Bible Baptist Church" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Bible Baptist Church') ? 'selected' : '' ?>>
                                    Bible Baptist Church</option>
                                <option value="United Church of Christ in the Philippines"
                                    <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'United Church of Christ in the Philippines') ? 'selected' : '' ?>>United Church of Christ in the Philippines</option>
                                <option value="Jehovah’s Witness" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Jehovah’s Witness') ? 'selected' : '' ?>>
                                    Jehovah’s Witness</option>
                                <option value="Church of Christ" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Church of Christ') ? 'selected' : '' ?>>Church
                                    of Christ</option>
                                <option value="Others" <?= (isset($personal_information['religion']) && $personal_information['religion'] === 'Others') ? 'selected' : '' ?>>Others</option>
                            </select>
                        </label>
                        <label>Occupation <input type="text" name="occupation" maxlength="50"
                                value="<?= h($personal_information['occupation'] ?? '') ?>"></label>
                    </div>
                    <div class="form-row">
                        <label>Contact No. <input type="text" name="contact_num" id="contactField" value="<?= h(preg_replace('/^(\+63|0)/', '+63', $patient['contact_num'])) ?>" required readonly class="uneditable-field"></label>
                        <label>Email <input type="email" name="email" value="<?= h($patient['email']) ?>" required readonly class="uneditable-field"></label>
                        <label>PhilHealth ID <input type="text" name="philhealth_id" value="<?= h($personal_information['philhealth_id'] ?? '') ?>" pattern="\d{2}-\d{9}-\d{1}" maxlength="14" placeholder="XX-XXXXXXXXX-X"></label>
        <!-- Custom popup for uneditable fields -->
        <div id="uneditablePopup" class="custom-modal" style="display:none;">
            <div class="custom-modal-content">
                <h3>Notice</h3>
                <p>To edit your name, date of birth, age, sex, contact number, or email, please go to User Settings.</p>
                <div class="custom-modal-actions">
                    <button type="button" class="btn btn-secondary" id="closeUneditablePopup">OK</button>
                </div>
            </div>
        </div>
                    </div>
                    <div class="form-actions"><button class="btn" type="submit">Save Personal Info</button></div>
                </form>
            </div>
        </div>

        <div class="edit-profile-flex">
            <!-- Home Address Form -->
            <form class="profile-card" id="homeAddressForm" method="post">
                <input type="hidden" name="form_type" value="home_address">
                <h3>Home Address</h3>
                <div class="form-row">
                    <label>Street <input type="text" name="street" maxlength="100"
                            value="<?= h($personal_information['street'] ?? '') ?>"></label>
                    <label>Barangay
                        <select name="barangay" required disabled class="uneditable-field">
                            <?php
                            $barangays = [
                                'Brgy. Assumption',
                                'Brgy. Avanceña',
                                'Brgy. Cacub',
                                'Brgy. Caloocan',
                                'Brgy. Carpenter Hill',
                                'Brgy. Concepcion',
                                'Brgy. Esperanza',
                                'Brgy. General Paulino Santos',
                                'Brgy. Mabini',
                                'Brgy. Magsaysay',
                                'Brgy. Mambucal',
                                'Brgy. Morales',
                                'Brgy. Namnama',
                                'Brgy. New Pangasinan',
                                'Brgy. Paraiso',
                                'Brgy. Rotonda',
                                'Brgy. San Isidro',
                                'Brgy. San Roque',
                                'Brgy. San Jose',
                                'Brgy. Sta. Cruz',
                                'Brgy. Sto. Niño',
                                'Brgy. Saravia',
                                'Brgy. Topland',
                                'Brgy. Zone 1',
                                'Brgy. Zone 2',
                                'Brgy. Zone 3',
                                'Brgy. Zone 4'
                            ];
                            $selected_barangay = $patient['barangay'] ?? '';
                            foreach ($barangays as $b) {
                                $sel = ($b === $selected_barangay) ? 'selected' : '';
                                echo "<option value=\"$b\" $sel>$b</option>";
                            }
                            ?>
                        </select>
                </div>
                <div class="form-row">
                    <label>City <input type="text" value="Koronadal" readonly></label>
                    <label>Province <input type="text" value="South Cotabato" readonly></label>
                    <label>ZIP <input type="text" value="9506" readonly></label>
                </div>
                <div class="form-actions"><button class="btn" type="submit">Save Address</button></div>
            </form>

            <!-- Emergency Contact Form -->
            <form class="profile-card" id="emergencyContactForm" method="post">
                <input type="hidden" name="form_type" value="emergency_contact">
                <h3>Emergency Contact</h3>
                <div class="form-row">
                    <label>Last Name <input type="text" name="ec_last_name"
                            value="<?= h($emergency_contact['last_name'] ?? '') ?>" required></label>
                    <label>First Name <input type="text" name="ec_first_name"
                            value="<?= h($emergency_contact['first_name'] ?? '') ?>" required></label>
                    <label>Middle Name <input type="text" name="ec_middle_name"
                            value="<?= h($emergency_contact['middle_name'] ?? '') ?>"></label>
                </div>
                <div class="form-row">
                    <label>Relation
                        <select name="ec_relation" required>
                            <option value="" <?= empty($emergency_contact['relation']) ? 'selected' : '' ?>>Select
                            </option>
                            <option value="Father" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Father') ? 'selected' : '' ?>>Father</option>
                            <option value="Mother" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Mother') ? 'selected' : '' ?>>Mother</option>
                            <option value="Spouse" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Spouse') ? 'selected' : '' ?>>Spouse</option>
                            <option value="Son" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Son') ? 'selected' : '' ?>>Son</option>
                            <option value="Daughter" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Daughter') ? 'selected' : '' ?>>Daughter</option>
                            <option value="Brother" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Brother') ? 'selected' : '' ?>>Brother</option>
                            <option value="Sister" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Sister') ? 'selected' : '' ?>>Sister</option>
                            <option value="Grandfather" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Grandfather') ? 'selected' : '' ?>>Grandfather
                            </option>
                            <option value="Grandmother" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Grandmother') ? 'selected' : '' ?>>Grandmother
                            </option>
                            <option value="Uncle" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Uncle') ? 'selected' : '' ?>>Uncle</option>
                            <option value="Aunt" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Aunt') ? 'selected' : '' ?>>Aunt</option>
                            <option value="Cousin" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Cousin') ? 'selected' : '' ?>>Cousin</option>
                            <option value="Nephew" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Nephew') ? 'selected' : '' ?>>Nephew</option>
                            <option value="Niece" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Niece') ? 'selected' : '' ?>>Niece</option>
                            <option value="Friend" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Friend') ? 'selected' : '' ?>>Friend</option>
                            <option value="Other" <?= (isset($emergency_contact['relation']) && $emergency_contact['relation'] === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </label>
                    <label>Contact No. <input type="text" name="ec_contact_num"
                            value="<?= h($emergency_contact['contact_num'] ?? '') ?>" required></label>
                </div>
                <div class="form-actions"><button class="btn" type="submit">Save Emergency Contact</button></div>
            </form>
        </div>

        <div class="edit-profile-flex">
            <!-- Lifestyle Info Form -->
            <form class="profile-card" id="lifestyleInfoForm" method="post">
                <input type="hidden" name="form_type" value="lifestyle_info">
                <h3>Lifestyle Information</h3>
                <!-- Smoking Status Section -->
                <div class="lifestyle-info-section">
                    <h4>Smoking Status</h4>
                    <div class="lifestyle-matrix">
                        <div class="lifestyle-matrix-header">
                            <div class="lifestyle-matrix-rowlabel"></div>
                            <?php
                            $smoking_opts = [
                                'Never smoked',
                                'Former smoker',
                                'Current occasional smoker',
                                'Current daily smoker',
                            ];
                            foreach ($smoking_opts as $opt):
                                $display = preg_replace('/(\(.*?\))/', '<span class="matrix-desc">$1</span>', $opt);
                                ?>
                                <div class="lifestyle-matrix-col"><?= $display ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="lifestyle-matrix-row">
                            <div class="lifestyle-matrix-rowlabel">How often do you smoke?</div>
                            <?php $smoking_val = $lifestyle_info['smoking_status'] ?? '';
                            foreach ($smoking_opts as $opt) {
                                $keyword = explode(' (', $opt)[0];
                                ?>
                                <label class="lifestyle-matrix-cell">
                                    <input type="radio" name="smoking_status" value="<?= $keyword ?>"
                                        <?= ($smoking_val === $keyword) ? 'checked' : '' ?> />
                                    <span class="custom-matrix-radio"></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>

                </div>

                <!-- Alcohol Intake Section -->
                <div class="lifestyle-info-section">
                    <h4>Alcohol Intake</h4>
                    <div class="lifestyle-matrix">
                        <div class="lifestyle-matrix-header">
                            <div class="lifestyle-matrix-rowlabel"></div>
                            <?php
                            $alcohol_opts = [
                                'None',
                                'Occasional (social drinking)',
                                'Moderate (1–2 drinks per day)',
                                'Heavy (3 or more drinks per day)',
                            ];
                            foreach ($alcohol_opts as $opt):
                                $display = preg_replace('/(\(.*?\))/', '<span class="matrix-desc">$1</span>', $opt);
                                ?>
                                <div class="lifestyle-matrix-col"><?= $display ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="lifestyle-matrix-row">
                            <div class="lifestyle-matrix-rowlabel">How often do you drink alcohol?</div>
                            <?php $alcohol_val = $lifestyle_info['alcohol_intake'] ?? '';
                            foreach ($alcohol_opts as $opt) {
                                $keyword = explode(' (', $opt)[0];
                                ?>
                                <label class="lifestyle-matrix-cell">
                                    <input type="radio" name="alcohol_intake" value="<?= $keyword ?>"
                                        <?= ($alcohol_val === $keyword) ? 'checked' : '' ?> />
                                    <span class="custom-matrix-radio"></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>

                </div>

                <!-- Physical Activity Section -->
                <div class="lifestyle-info-section">
                    <h4>Physical Activity</h4>
                    <div class="lifestyle-matrix">
                        <div class="lifestyle-matrix-header">
                            <div class="lifestyle-matrix-rowlabel"></div>
                            <?php
                            $activity_opts = [
                                'Sedentary (little to no exercise)',
                                'Light (1–2 days per week)',
                                'Moderate (3–4 days per week)',
                                'Active (5 or more days per week)',
                            ];
                            foreach ($activity_opts as $opt):
                                $display = preg_replace('/(\(.*?\))/', '<span class="matrix-desc">$1</span>', $opt);
                                ?>
                                <div class="lifestyle-matrix-col"><?= $display ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="lifestyle-matrix-row">
                            <div class="lifestyle-matrix-rowlabel">How often do you exercise or do physical activity?
                            </div>
                            <?php $activity_val = $lifestyle_info['physical_act'] ?? '';
                            foreach ($activity_opts as $opt) {
                                $keyword = explode(' (', $opt)[0];
                                ?>
                                <label class="lifestyle-matrix-cell">
                                    <input type="radio" name="physical_act" value="<?= $keyword ?>"
                                        <?= ($activity_val === $keyword) ? 'checked' : '' ?> />
                                    <span class="custom-matrix-radio"></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>

                </div>

                <!-- Dietary Habit Section -->
                <div class="lifestyle-info-section">
                    <h4>Dietary Habit</h4>
                    <div class="lifestyle-matrix">
                        <div class="lifestyle-matrix-header">
                            <div class="lifestyle-matrix-rowlabel"></div>
                            <?php
                            $diet_opts = [
                                'Unhealthy (frequent fast food, sugary drinks, processed foods)',
                                'Fair (mixed diet, occasional fruits / vegetables)',
                                'Healthy (balanced diet, regular fruits / vegetables, limited processed foods)',
                                'Very Healthy (strictly balanced, nutrient-rich, whole foods)',
                            ];
                            foreach ($diet_opts as $opt):
                                $display = preg_replace('/(\(.*?\))/', '<span class="matrix-desc">$1</span>', $opt);
                                ?>
                                <div class="lifestyle-matrix-col"><?= $display ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="lifestyle-matrix-row">
                            <div class="lifestyle-matrix-rowlabel">How would you describe your diet?</div>
                            <?php $diet_val = $lifestyle_info['diet_habit'] ?? '';
                            foreach ($diet_opts as $opt) {
                                $keyword = explode(' (', $opt)[0];
                                ?>
                                <label class="lifestyle-matrix-cell">
                                    <input type="radio" name="diet_habit" value="<?= $keyword ?>" <?= ($diet_val === $keyword) ? 'checked' : '' ?> />
                                    <span class="custom-matrix-radio"></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="form-actions"><button class="btn" type="submit">Save Lifestyle Info</button></div>
            </form>
        </div>
        <!-- Cropper.js from CDN -->
        <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
        <script src="js/profile-photo-cropper.js"></script>
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

            // Uneditable fields popup logic
            const uneditablePopup = document.getElementById('uneditablePopup');
            const closeUneditablePopup = document.getElementById('closeUneditablePopup');
            // All fields that should trigger the popup
            document.querySelectorAll('.uneditable-field').forEach(function(field) {
                // For readonly/disabled fields, listen to focus and click
                field.addEventListener('focus', showUneditablePopup);
                field.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    showUneditablePopup();
                });
                // For selects, also listen to change
                if (field.tagName === 'SELECT') {
                    field.addEventListener('change', showUneditablePopup);
                }
            });
            function showUneditablePopup() {
                uneditablePopup.style.display = 'flex';
            }
            closeUneditablePopup.addEventListener('click', function() {
                uneditablePopup.style.display = 'none';
            });
            // Close popup on outside click
            uneditablePopup.addEventListener('click', function(e) {
                if (e.target === uneditablePopup) uneditablePopup.style.display = 'none';
            });

            // Enable Save Photo button only when a valid file is set
            const fileInput = document.getElementById('profilePhotoInput');
            const saveBtn = document.getElementById('savePhotoBtn');
            if (fileInput && saveBtn) {
                fileInput.addEventListener('change', function () {
                    saveBtn.disabled = !fileInput.files || !fileInput.files[0];
                });
            }
            // Prevent form submit if no file
            const photoForm = document.getElementById('profilePhotoForm');
            if (photoForm && saveBtn) {
                photoForm.addEventListener('submit', function (e) {
                    if (!fileInput.files || !fileInput.files[0]) {
                        e.preventDefault();
                        alert('Please select and crop a photo before saving.');
                    }
                });
            }
        </script>
        <script>
            function updateAge() {
                const dob = document.getElementById('dobField').value;
                if (!dob) { document.getElementById('ageField').value = ''; return; }
                const birthDate = new Date(dob);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('ageField').value = age;
            }
            function formatContact(input) {
                let digits = input.value.replace(/[^0-9]/g, '').replace(/^0/, '');
                input.value = '+63' + digits.slice(0, 10);
            }
            window.addEventListener('DOMContentLoaded', function () {
                updateAge();
            });
        </script>
    </section>
</body>

</html>