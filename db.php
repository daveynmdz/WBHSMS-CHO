<?php
// db.php
// Database credentials
$servername = "localhost";
$username = "root";
$password = "@Dav200110";
$dbname = "wbhsms_database";

// MySQLi connection (legacy, for compatibility)
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// PDO connection (recommended)
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>
