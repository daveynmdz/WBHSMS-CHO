<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only allow if OTP was verified for reset
    if (!isset($_SESSION['otp_verified_for_reset'], $_SESSION['reset_user_id']) || !$_SESSION['otp_verified_for_reset']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or session expired.']);
        exit;
    }
    $userId = $_SESSION['reset_user_id'];
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (!$password) {
        echo json_encode(['success' => false, 'message' => 'Password is required.']);
        exit;
    }
    // Validate password strength (same as frontend)
    $ok = strlen($password) >= 8 &&
          preg_match('/[A-Z]/', $password) &&
          preg_match('/[a-z]/', $password) &&
          preg_match('/[0-9]/', $password);
    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Password does not meet requirements.']);
        exit;
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare('UPDATE patients SET password = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $userId]);
        // Clean up session
        unset($_SESSION['otp_verified_for_reset']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_name']);
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
