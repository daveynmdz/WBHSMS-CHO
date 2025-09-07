<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $inputOTP = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    if (!$inputOTP) {
        echo json_encode(['success' => false, 'message' => 'OTP is required.']);
        exit;
    }
    // Check OTP from session
    if (!isset($_SESSION['otp'], $_SESSION['registration'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please register again.']);
        exit;
    }
    $sessionOTP = $_SESSION['otp'];
    $reg = $_SESSION['registration'];
    if ($inputOTP === $sessionOTP) {
        // Insert into DB
        try {
            $stmt = $pdo->prepare("INSERT INTO patients (last_name, first_name, middle_name, suffix, barangay, dob, sex, contact_num, email, password, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");
            $stmt->execute([
                $reg['last_name'],
                $reg['first_name'],
                $reg['middle_name'],
                $reg['suffix'],
                $reg['barangay'],
                $reg['dob'],
                $reg['sex'],
                $reg['contact_num'],
                $reg['email'],
                $reg['password']
            ]);
            $patient_id = $pdo->lastInsertId();
            $username = 'P' . str_pad($patient_id, 6, '0', STR_PAD_LEFT);
            $updateStmt = $pdo->prepare("UPDATE patients SET username = ? WHERE id = ?");
            $updateStmt->execute([$username, $patient_id]);
            // Clean up session
            unset($_SESSION['otp']);
            unset($_SESSION['registration']);
            echo json_encode(['success' => true, 'username' => $username]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        exit;
    }
}
?>