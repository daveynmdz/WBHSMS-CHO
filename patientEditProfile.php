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

// Handle form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name', 'middle_name', 'last_name', 'suffix', 'dob', 'sex', 'blood_type', 'civil_status', 'religion',
        'occupation', 'contact_num', 'email', 'philhealth_id', 'address', 'barangay',
        'emergency_name', 'emergency_relationship', 'emergency_contact',
        'smoking', 'alcohol', 'activity', 'diet'
    ];
    $updates = [];
    $params = [];
    foreach ($fields as $field) {
        $updates[] = "$field = ?";
        $params[] = trim($_POST[$field] ?? '');
    }
    // Handle profile photo upload
    $photo_sql = '';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $patient_id . '_' . time() . '.' . $ext;
        $target = 'images/' . $filename;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
            $photo_sql = ', profile_photo = ?';
            $params[] = $filename;
        } else {
            $error = 'Failed to upload profile photo.';
        }
    }
    $params[] = $patient_id;
    if (!$error) {
        $sql = "UPDATE patients SET " . implode(', ', $updates) . $photo_sql . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $success = true;
            // Refresh patient data
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$patient_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update profile.';
        }
    }
}

function h($v) { return htmlspecialchars($v ?? ''); }
$profile_photo_url = !empty($patient['profile_photo']) ? 'images/' . $patient['profile_photo'] : 'https://i.ibb.co/Y0m9XGk/user-icon.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/patientUI.css">
    <link rel="stylesheet" href="css/patientProfile.css">
    <style>
        .edit-profile-form { max-width: 700px; margin: 2rem auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #0001; padding: 2rem; }
        .edit-profile-form h2 { margin-bottom: 1.5rem; }
        .edit-profile-form .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .edit-profile-form label { flex: 1; font-weight: 500; }
        .edit-profile-form input, .edit-profile-form select { width: 100%; padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc; }
        .edit-profile-form .profile-photo-preview { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .edit-profile-form .profile-photo-preview img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; }
        .edit-profile-form .form-actions { text-align: right; }
        .edit-profile-form .btn { background: var(--brand, #007bff); color: #fff; border: none; padding: 0.7rem 1.5rem; border-radius: 5px; cursor: pointer; font-size: 1rem; }
        .edit-profile-form .btn:disabled { background: #ccc; }
        .edit-profile-form .error { color: #c00; margin-bottom: 1rem; }
        .edit-profile-form .success { color: #28a745; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="mobile-topbar">
        <a href="patientHomepage.php">
            <img id="topbarLogo" class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_Logo.png?updatedAt=1750422462527" alt="City Health Logo" />
        </a>
    </div>
    <section class="homepage">
        <form class="edit-profile-form" method="post" enctype="multipart/form-data">
            <h2>Edit Profile</h2>
            <?php if ($success): ?><div class="success">Profile updated successfully!</div><?php endif; ?>
            <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <div class="profile-photo-preview">
                <img src="<?= h($profile_photo_url) ?>" alt="Profile Photo" />
                <div>
                    <label>Change Photo:
                        <input type="file" name="profile_photo" accept="image/*">
                    </label>
                </div>
            </div>
            <div class="form-row">
                <label>First Name
                    <input type="text" name="first_name" value="<?= h($patient['first_name']) ?>" required>
                </label>
                <label>Middle Name
                    <input type="text" name="middle_name" value="<?= h($patient['middle_name']) ?>">
                </label>
                <label>Last Name
                    <input type="text" name="last_name" value="<?= h($patient['last_name']) ?>" required>
                </label>
                <label>Suffix
                    <input type="text" name="suffix" value="<?= h($patient['suffix']) ?>">
                </label>
            </div>
            <div class="form-row">
                <label>Date of Birth
                    <input type="date" name="dob" value="<?= h($patient['dob']) ?>" required>
                </label>
                <label>Sex
                    <select name="sex" required>
                        <option value="">Select</option>
                        <option value="Male" <?= $patient['sex']==='Male'?'selected':'' ?>>Male</option>
                        <option value="Female" <?= $patient['sex']==='Female'?'selected':'' ?>>Female</option>
                    </select>
                </label>
                <label>Blood Type
                    <input type="text" name="blood_type" value="<?= h($patient['blood_type']) ?>">
                </label>
                <label>Civil Status
                    <input type="text" name="civil_status" value="<?= h($patient['civil_status']) ?>">
                </label>
            </div>
            <div class="form-row">
                <label>Religion
                    <input type="text" name="religion" value="<?= h($patient['religion']) ?>">
                </label>
                <label>Occupation
                    <input type="text" name="occupation" value="<?= h($patient['occupation']) ?>">
                </label>
                <label>Contact No.
                    <input type="text" name="contact_num" value="<?= h($patient['contact_num']) ?>" required>
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= h($patient['email']) ?>" required>
                </label>
            </div>
            <div class="form-row">
                <label>PhilHealth ID
                    <input type="text" name="philhealth_id" value="<?= h($patient['philhealth_id']) ?>">
                </label>
                <label>House No. & Street
                    <input type="text" name="address" value="<?= h($patient['address']) ?>">
                </label>
                <label>Barangay
                    <input type="text" name="barangay" value="<?= h($patient['barangay']) ?>" required>
                </label>
            </div>
            <div class="form-row">
                <label>Emergency Name
                    <input type="text" name="emergency_name" value="<?= h($patient['emergency_name']) ?>">
                </label>
                <label>Emergency Relationship
                    <input type="text" name="emergency_relationship" value="<?= h($patient['emergency_relationship']) ?>">
                </label>
                <label>Emergency Contact
                    <input type="text" name="emergency_contact" value="<?= h($patient['emergency_contact']) ?>">
                </label>
            </div>
            <div class="form-row">
                <label>Smoking Status
                    <input type="text" name="smoking" value="<?= h($patient['smoking']) ?>">
                </label>
                <label>Alcohol Intake
                    <input type="text" name="alcohol" value="<?= h($patient['alcohol']) ?>">
                </label>
                <label>Physical Activity
                    <input type="text" name="activity" value="<?= h($patient['activity']) ?>">
                </label>
                <label>Dietary Habit
                    <input type="text" name="diet" value="<?= h($patient['diet']) ?>">
                </label>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">Save Changes</button>
                <a href="patientProfile.php" class="btn" style="background:#888;">Cancel</a>
            </div>
        </form>
    </section>
</body>
</html>
