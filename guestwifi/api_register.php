<?php
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

// รับค่าจาก HTML
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// เชื่อมฐานข้อมูล MySQL
$pdo = new PDO("mysql:host=localhost;dbname=guestwifi;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ตัวอย่าง: บันทึกลงฐานข้อมูล
$stmt = $pdo->prepare("INSERT INTO guest_users (username, password, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$username, $password]);

// เรียก Mikrotik API เพื่อเพิ่ม user (ต้องใช้ RouterOS API PHP Class)
require('routeros_api.class.php');
$API = new RouterosAPI();
if ($API->connect('192.168.99.1', 'admin', '1234')) {
    $API->comm("/ip/hotspot/user/add", [
        "name" => $username,
        "password" => $password,
        "profile" => "guest"
    ]);
    $API->disconnect();
    echo json_encode(["allow" => true]);
} else {
    echo json_encode(["allow" => false]);
}
