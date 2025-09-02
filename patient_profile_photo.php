<?php
session_start();
require_once 'db.php';

// Only allow logged-in patients to view their own photo
if (!isset($_SESSION['patient_id'])) {
    http_response_code(403);
    exit('Forbidden');
}
$patient_id = $_SESSION['patient_id'];

$stmt = $pdo->prepare('SELECT profile_photo FROM personal_information WHERE patient_id = ?');
$stmt->execute([$patient_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && !empty($row['profile_photo'])) {
    $img = $row['profile_photo'];
    // Try to detect mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($img);
    if (!$mime) $mime = 'image/jpeg';
    header('Content-Type: ' . $mime);
    echo $img;
} else {
    // Output a default image if not set
    header('Content-Type: image/png');
    readfile('https://i.ibb.co/Y0m9XGk/user-icon.png');
}
