<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputOTP = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    if (!$inputOTP) {
        echo json_encode(['success' => false, 'message' => 'OTP is required.']);
        exit;
    }
    if (!isset($_SESSION['reset_otp'], $_SESSION['reset_user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please try again.']);
        exit;
    }
    $sessionOTP = $_SESSION['reset_otp'];
    if ($inputOTP === $sessionOTP) {
        // OTP verified, allow password reset
        unset($_SESSION['reset_otp']); // Optionally, keep user_id for next step
        $_SESSION['otp_verified_for_reset'] = true;
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
