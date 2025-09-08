<?php

require_once '../../config/db.php';

// Accept patient_id from GET
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
if (!$patient_id || !is_numeric($patient_id)) {
    header('Content-Type: image/png');
    readfile('https://i.ibb.co/Y0m9XGk/user-icon.png');
    exit;
}

$stmt = $pdo->prepare('SELECT profile_photo FROM personal_information WHERE patient_id = ?');
$stmt->execute([$patient_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && !empty($row['profile_photo'])) {
    $img = $row['profile_photo'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($img);
    if (!$mime) $mime = 'image/jpeg';
    header('Content-Type: ' . $mime);
    echo $img;
} else {
    header('Content-Type: image/png');
    readfile('https://i.ibb.co/Y0m9XGk/user-icon.png');
}
