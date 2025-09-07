<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require '../../../config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../../vendor/autoload.php';

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
    // Only show HTML if not an AJAX POST request
    ?>
    
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Forgot Password</title>
        <link rel="stylesheet" href="css/forgotPassword.css" />
    </head>

    <body>
        <header>
            <div class="logo-container">
                <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128"
                    alt="CHO Koronadal Logo" />
            </div>
        </header>
        <section class="homepage">
            <div class="registration-box">
                <h2>Forgot Password</h2>
                <form id="forgotForm" class="form" autocomplete="off" novalidate>
                    <label for="identifier">Enter your Patient ID or Email Address</label>
                    <input type="text" id="identifier" name="identifier" class="input-field" required
                        placeholder="Patient ID or Email" />
                    <div id="error" class="error" role="alert" aria-live="polite" style="display:none"></div>
                    <div class="form-footer">
                        <button id="submitBtn" type="submit" class="btn">Send OTP <i
                                class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </section>
        <script>
            const form = document.getElementById('forgotForm');
            const error = document.getElementById('error');
            const submitBtn = document.getElementById('submitBtn');
            function showError(msg) {
                error.textContent = msg;
                error.style.display = 'block';
                setTimeout(() => { error.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 50);
            }
            function clearError() {
                error.textContent = '';
                error.style.display = 'none';
            }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                clearError();
                const identifier = document.getElementById('identifier').value.trim();
                if (!identifier) {
                    showError('Please enter your Patient ID or Email Address.');
                    return;
                }
                submitBtn.disabled = true;
                fetch('forgotPassword.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'identifier=' + encodeURIComponent(identifier)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'verifyOTPPassword.html?reset=1';
                        } else {
                            showError(data.message || 'No matching user found.');
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(() => {
                        showError('Server error. Please try again.');
                        submitBtn.disabled = false;
                    });
            });
        </script>
    </body>

    </html>
    <?php
}