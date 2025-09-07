<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_number = trim($_POST['employee_number'] ?? '');
    if ($employee_number) {
        $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_number = ? LIMIT 1");
        $stmt->bind_param("s", $employee_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Here you would send an email or SMS with a reset link or code
            // For demo, just show a message
            $msg = "If this employee number exists, a reset link has been sent.";
        } else {
            $msg = "If this employee number exists, a reset link has been sent.";
        }
        $stmt->close();
    } else {
        $msg = "Please enter your employee number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Forgot Password</title>
    <link rel="stylesheet" href="css/employeeForgotPassword.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128" alt="City Health Office Koronadal logo" width="100" height="100" decoding="async" />
        </div>
    </header>
    <main class="homepage">
        <section class="forgot-box">
            <h2>Forgot Password</h2>
            <?php if (!empty($msg)): ?>
                <div class="success"> <?= htmlspecialchars($msg) ?> </div>
            <?php endif; ?>
            <form action="employeeForgotPassword.php" method="POST">
                <label for="employee_number">Employee Number</label>
                <input type="text" id="employee_number" name="employee_number" required>
                <button type="submit">Send Reset Link</button>
            </form>
            <div class="forgot-footer">
                <a href="employeeLogin.php">Back to Login</a>
            </div>
        </section>
    </main>
</body>
</html>
