<?php
$mysqli = new mysqli("localhost","root","","guestwifi_db");
$mysqli->set_charset("utf8mb4");
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
