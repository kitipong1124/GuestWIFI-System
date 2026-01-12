<?php
header("Content-Type: application/json");
try {
    $pdo = new PDO("mysql:host=localhost;dbname=guestwifi_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT COUNT(*) FROM guest_users WHERE approved = 0");
    $count = (int)$stmt->fetchColumn();
    echo json_encode(["count" => $count]);
} catch (Exception $e) {
    echo json_encode(["count" => 0]);
}
