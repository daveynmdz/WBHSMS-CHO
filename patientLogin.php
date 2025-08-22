<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_number = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = '';

    if (empty($patient_number) || empty($password)) {
        $error = 'Please enter both Patient Number and Password.';
    } else {
    $stmt = $pdo->prepare('SELECT id, password FROM patients WHERE username = ?');
    $stmt->execute([$patient_number]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['patient_id'] = $row['id'];
                header('Location: patientHomepage.php');
                exit();
            } else {
                $error = 'Invalid Patient Number or Password.';
            }
        } else {
            $error = 'Invalid Patient Number or Password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Patient Login</title>
    <link rel="stylesheet" href="css/patientLogin.css" />
</head>
<body>
    <main class="homepage" id="main-content">
        <section class="login-box" aria-labelledby="login-title">
            <h1 id="login-title" class="visually-hidden">Patient Login</h1>
            <form class="form active" action="patientLogin.php" method="POST" novalidate>
                <div class="form-header">
                    <h2>Patient Login</h2>
                </div>
                <label for="username">Patient Number</label>
                <input type="text" id="username" name="username" class="input-field" placeholder="Enter Patient Number (e.g., P000001)" inputmode="text" autocomplete="username" pattern="^P\d{6}$" required />
                <div class="password-wrapper">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="input-field" placeholder="Enter Password" autocomplete="current-password" required />
                </div>
                <div class="form-footer">
                    <a href="forgotPassword.html" class="forgot">Forgot Password?</a>
                </div>
                <button type="submit" class="btn">Login</button>
                <p class="alt-action">
                    Don’t have an account?
                    <a class="register-link" href="patientRegistration.html">Register</a>
                </p>
                <?php if (!empty($error)): ?>
                    <div class="error-message" style="color:red; margin-top:10px;"> <?php echo htmlspecialchars($error); ?> </div>
                <?php endif; ?>
            </form>
        </section>
    </main>
</body>
</html>
