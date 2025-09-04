
<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$allergen = trim($_POST['allergen'] ?? '');
	$reaction = trim($_POST['reaction'] ?? '');
	$severity = trim($_POST['severity'] ?? '');
	$patient_id = intval($_POST['patient_id'] ?? 0);

	if ($id && $allergen && $reaction && $severity && $patient_id) {
		try {
			$stmt = $pdo->prepare("UPDATE allergies SET allergen = ?, reaction = ?, severity = ? WHERE id = ? AND patient_id = ?");
			$stmt->execute([$allergen, $reaction, $severity, $id, $patient_id]);
			$_SESSION['allergy_update_success'] = true;
		} catch (Exception $e) {
			$_SESSION['error'] = 'Failed to update allergy.';
		}
	} else {
		$_SESSION['error'] = 'Invalid input.';
	}
	header('Location: patientProfile.php');
	exit;
} else {
	header('Location: patientProfile.php');
	exit;
}
