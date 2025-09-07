<?php
// Start session and include database connection at the very top, before any output!
session_start();
require_once 'db.php';

// Initialize variables
$error = '';
$employee_number = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$employee_number = trim($_POST['employee_number'] ?? '');
	$plain_password = $_POST['password'] ?? '';

	if ($employee_number && $plain_password) {
		// Use the $conn object from db.php (MySQLi connection)
		if (!$conn) {
			die('Database connection failed: ' . mysqli_connect_error());
		}
		$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_number = ? LIMIT 1");
		if ($stmt) {
			$stmt->bind_param("s", $employee_number);
			$stmt->execute();
			$result = $stmt->get_result();
			if ($row = $result->fetch_assoc()) {
				// If passwords are hashed, use password_verify($plain_password, $row['password'])
				if ($plain_password === $row['password']) {
					// Set both employee_id and employee_number in session for homepage reference
					$_SESSION['employee_id'] = $row['employee_id'];
					$_SESSION['employee_number'] = $row['employee_number'];
					$_SESSION['employee_last_name'] = $row['last_name'];
					$_SESSION['employee_first_name'] = $row['first_name'];
					$_SESSION['employee_middle_name'] = $row['middle_name'];
					$_SESSION['role'] = $row['role'];
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
			$error = "Database error: " . $conn->error;
		}
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
			<img class="logo" src="https://ik.imagekit.io/wbhsmslogo/Nav_LogoClosed.png?updatedAt=1751197276128"
				alt="City Health Office Koronadal logo" width="100" height="100" decoding="async" />
		</div>
	</header>
	<main class="homepage" id="main-content">
		<section class="login-box" aria-labelledby="login-title">
			<h1 id="login-title" class="visually-hidden">Employee Login</h1>
			<form class="form active" action="employeeLogin.php" method="POST">
				<div class="form-header">
					<h2>Employee Login</h2>
				</div>

				<?php if (!empty($error)): ?>
					<div style="color: red; text-align:center; margin-bottom: 10px;"> <?= htmlspecialchars($error) ?> </div>
				<?php endif; ?>

				<!-- Employee Number -->
				<label for="employee_number">Employee Number</label>
				<input type="text" id="employee_number" name="employee_number" class="input-field"
					placeholder="Enter Employee Number (e.g., EMP00001)" inputmode="text" autocomplete="username"
					pattern="^EMP\d{5}$" aria-describedby="employee-number-help" required
					value="<?= htmlspecialchars($employee_number) ?>" />
				<!-- Password -->
				<div class="password-wrapper">
					<label for="password">Password</label>
					<input type="password" id="password" name="password" class="input-field"
						placeholder="Enter Password" autocomplete="current-password" required />
					<button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false"
						title="Show/Hide Password" tabindex="0">
						<i class="fa-solid fa-eye" aria-hidden="true"></i>
					</button>
				</div>

				<div class="form-footer">
					<a href="employeeForgotPassword.php" class="forgot">Forgot Password?</a>
				</div>

				<button type="submit" class="btn">Login</button>

				<!-- Live region for client-side validation or server messages -->
				<div class="sr-only" role="status" aria-live="polite" id="form-status"></div>
			</form>
		</section>
	</main>
	<script>
		// Password toggle (accessible, no validation logic)
		document.addEventListener('DOMContentLoaded', function () {
			const toggleBtn = document.querySelector(".toggle-password");
			const pwd = document.getElementById("password");
			const icon = toggleBtn.querySelector("i");
			toggleBtn.addEventListener("click", function () {
				const isHidden = pwd.type === "password";
				pwd.type = isHidden ? "text" : "password";
				toggleBtn.setAttribute("aria-pressed", String(isHidden));
				toggleBtn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
				icon.classList.toggle("fa-eye");
				icon.classList.toggle("fa-eye-slash");
			});
		});
	</script>
</body>

</html>