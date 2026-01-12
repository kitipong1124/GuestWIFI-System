<?php
header("Content-Type: application/json");
$conn = new mysqli("localhost", "root", "", "guestwifi_db");
if ($conn->connect_error) {
    echo json_encode(["count" => 0]);
    exit;
}
$q = $conn->query("SELECT COUNT(*) AS c FROM guest_users WHERE approved = 0");
$row = $q->fetch_assoc();
echo json_encode(["count" => (int)$row['c']]);
$conn->close();
