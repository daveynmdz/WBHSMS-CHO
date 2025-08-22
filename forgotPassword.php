<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function generateOTP($length = 6) {
    return str_pad(random_int(0, 999999), $length, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    if (!$identifier) {
        echo json_encode(['success' => false, 'message' => 'Identifier required.']);
        exit;
    }
    // Find user by patient ID or email
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM patients WHERE email = ? OR username = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No matching user found.']);
        exit;
    }
    $otp = generateOTP();
    session_start();
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_user_id'] = $user['id'];
    $_SESSION['reset_email'] = $user['email'];
    $_SESSION['reset_name'] = $user['first_name'] . ' ' . $user['last_name'];
    // Send OTP via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cityhealthofficeofkoronadal@gmail.com';
        $mail->Password   = 'iclhoflunfkzmlie';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('cityhealthofficeofkoronadal@gmail.com', 'City Health Office of Koronadal');
        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Password Reset';
        $mail->Body    = "<p>Your One-Time Password (OTP) for password reset is: <strong>$otp</strong></p>";
        $mail->send();
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'OTP could not be sent. Mailer Error: ' . $mail->ErrorInfo]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
