
<?php
session_start();
require_once 'db.php';

$error = '';
$employee_number = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_number = trim($_POST['employee_number'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($employee_number && $password) {
        // Use MySQLi object-oriented style for compatibility with get_result
        $mysqli = new mysqli($servername, $username, $password, $dbname);
        if ($mysqli->connect_error) {
            die('Database connection failed: ' . $mysqli->connect_error);
        }
        $stmt = $mysqli->prepare("SELECT * FROM employees WHERE employee_number = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $employee_number);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['employee_id'] = $row['id'];
                    $_SESSION['employee_number'] = $row['employee_number'];
                    header('Location: employeeHomepage.php');
                    exit();
                } else {
                    $error = "Invalid employee number or password.";
                }
            } else {
                $error = "Invalid employee number or password.";
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $mysqli->error;
        }
        $mysqli->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CHO – Employee Login</title>
    <!-- Icons & Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="css/employeeLogin.css" />
</head>

<body>
    <header class="site-header">
        <div class="logo-container" role="banner">
            <img
                class="logo"
                src="https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128"
                alt="City Health Office Koronadal logo"
                width="100"
                height="100"
                decoding="async"
            />
        </div>
    </header>

    <main class="homepage" id="main-content">
        <section class="login-box" aria-labelledby="login-title">
            <h1 id="login-title" class="visually-hidden">Employee Login</h1>

            <form class="form active" action="employeeLogin.php" method="POST" novalidate>
                <div class="form-header">
                    <h2>Employee Login</h2>
                </div>

                <?php if (!empty($error)): ?>
                <div style="color: red; text-align:center; margin-bottom: 10px;"> <?= htmlspecialchars($error) ?> </div>
                <?php endif; ?>

                <!-- Employee Number -->
                <label for="employee_number">Employee Number</label>
                <input
                    type="text"
                    id="employee_number"
                    name="employee_number"
                    class="input-field"
                    placeholder="Enter Employee Number (e.g., E000001)"
                    inputmode="text"
                    autocomplete="username"
                    pattern="^E\d{6}$"
                    aria-describedby="employee-number-help"
                    required
                    value="<?= htmlspecialchars($employee_number) ?>"
                />
                <!-- Password -->
                <div class="password-wrapper">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input-field"
                        placeholder="Enter Password"
                        autocomplete="current-password"
                        required
                    />
                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Show password"
                        aria-pressed="false"
                        title="Show/Hide Password"
                    >
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="form-footer">
                    <a href="employeeForgotPassword.html" class="forgot">Forgot Password?</a>
                </div>

                <button type="submit" class="btn">Login</button>

                <!-- Live region for client-side validation or server messages -->
                <div class="sr-only" role="status" aria-live="polite" id="form-status"></div>
            </form>
        </section>
    </main>

    <script>
        // Password toggle (accessible)
        (function () {
            const toggleBtn = document.querySelector(".toggle-password");
            const pwd = document.getElementById("password");
            const icon = toggleBtn.querySelector("i");

            function toggle() {
                const isHidden = pwd.type === "password";
                pwd.type = isHidden ? "text" : "password";
                toggleBtn.setAttribute("aria-pressed", String(isHidden));
                toggleBtn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
                icon.classList.toggle("fa-eye");
                icon.classList.toggle("fa-eye-slash");
            }

            toggleBtn.addEventListener("click", toggle);
        })();

        // Optional: Light client validation message surface
        (function () {
            const form = document.querySelector("form");
            const status = document.getElementById("form-status");
            form.addEventListener("submit", function (e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    status.textContent = "Please fix the highlighted fields.";
                }
            });
        })();
    </script>
</body>
</html>

