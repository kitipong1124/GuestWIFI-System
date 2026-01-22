<?php
require_once 'config.php'; // เรียกไฟล์ config

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset(DB_CHARSET);
$user_id = intval($_GET['user_id']);
$result = $mysqli->query("SELECT approved, enabled FROM guest_users WHERE id=$user_id");
$user = $result->fetch_assoc();
$mysqli->close();

$response = [
    'approved' => $user['approved'] ?? 0,  // 0=รอ,1=approved,-1=reject
    'enabled'  => $user['enabled'] ?? 0
];
header('Content-Type: application/json');
echo json_encode($response);
