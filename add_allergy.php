<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $allergen = isset($_POST['allergen']) ? trim($_POST['allergen']) : '';
    $reaction = isset($_POST['reaction']) ? trim($_POST['reaction']) : '';
    $severity = isset($_POST['severity']) ? trim($_POST['severity']) : '';

    // Basic validation
    if ($patient_id && $allergen && $reaction && $severity) {
        $stmt = $pdo->prepare("INSERT INTO allergies (patient_id, allergen, reaction, severity) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$patient_id, $allergen, $reaction, $severity]);
        if ($success) {
            // Redirect back to profile with success
            header('Location: patientProfile.php?id=' . $patient_id . '&allergy_added=1');
            exit();
        } else {
            $error = 'Failed to add allergy. Please try again.';
        }
    } else {
        $error = 'All fields are required.';
    }
} else {
    $error = 'Invalid request.';
}
// If not redirected, show error
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Allergy</title>
    <meta http-equiv="refresh" content="2;url=patientProfile.php?id=<?= isset($patient_id) ? $patient_id : '' ?>">
    <style>body{font-family:sans-serif;text-align:center;margin-top:3em;} .error{color:#c0392b;} .success{color:#27ae60;}</style>
</head>
<body>
    <?php if (isset($error)): ?>
        <div class="error">Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <p>Redirecting to profile...</p>
</body>
</html>
