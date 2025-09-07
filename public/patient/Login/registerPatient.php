<?php
// registerPatient.php
require_once '../../../config/db.php'; // include your DB connection file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../../vendor/autoload.php';

function generateOTP($length = 6) {
    return str_pad(random_int(0, 999999), $length, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $suffix = trim($_POST['suffix']);
    $barangay = trim($_POST['barangay']);
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $contact_num = trim($_POST['contact_num']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate required fields (server-side)
    if (!$last_name || !$first_name || !$barangay || !$dob || !$sex || !$contact_num || !$email || !$password) {
        die('All required fields must be filled.');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die('Email already exists.');
    }

    // Password hash (store only after OTP verification)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Generate OTP
    $otp = generateOTP();

    // Store all registration data and OTP in session
    session_start();
    $_SESSION['registration'] = [
        'last_name' => $last_name,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'suffix' => $suffix,
        'barangay' => $barangay,
        'dob' => $dob,
        'sex' => $sex,
        'contact_num' => $contact_num,
        'email' => $email,
        'password' => $passwordHash
    ];
    $_SESSION['otp'] = $otp;

    // Send OTP via email
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cityhealthofficeofkoronadal@gmail.com'; // SMTP username
        $mail->Password   = 'iclhoflunfkzmlie'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('cityhealthofficeofkoronadal@gmail.com', 'City Health Office of Koronadal');
        $mail->addAddress($email, $first_name . ' ' . $last_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Registration';
        $mail->Body    = "<p>Your One-Time Password (OTP) is: <strong>$otp</strong></p>";

        $mail->send();
    } catch (Exception $e) {
        file_put_contents('mail_error.log', $mail->ErrorInfo . PHP_EOL, FILE_APPEND);
        die('OTP could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }

    // Redirect to OTP verification page
    header('Location: verifyOTP.html');
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
