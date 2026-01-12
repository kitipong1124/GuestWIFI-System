<?php
require_once __DIR__ . '/routeros_api.class.php';

$mikrotik_ip   = "172.16.123.254";
$mikrotik_user = "admin";
$mikrotik_pass = "1234";
$mikrotik_port = 8728;

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) die("Invalid user ID");

$pdo = new PDO("mysql:host=localhost;dbname=guestwifi_db;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT first_name,last_name,email,company,username FROM guest_users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$user) die("User not found");

// เชื่อม Mikrotik API
$API = new RouterosAPI();
$API->debug = false;

if($API->connect($mikrotik_ip,$mikrotik_user,$mikrotik_pass,$mikrotik_port)){
    $activeUsers = $API->comm("/ip/hotspot/active/print");
    foreach($activeUsers as $active){
        if($active['user'] === $user['username']){
            $API->comm("/ip/hotspot/active/remove", ["numbers"=>$active['.id']]);
            break;
        }
    }
    $API->disconnect();

    // บันทึก log
    // หลังจากตัดการเชื่อมต่อ Mikrotik
    $stmt = $pdo->prepare("UPDATE guest_users SET disconnected=1 WHERE id=?");
    $stmt->execute([$user_id]);

// บันทึก log
    $stmt = $pdo->prepare("INSERT INTO guest_disconnect_log (user_id, disconnect_time, first_name, last_name, email, company)
                       VALUES (?, NOW(), ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user['first_name'], $user['last_name'], $user['email'], $user['company']]);


    echo "✅ ตัดการเชื่อมต่อ {$user['username']} เรียบร้อย";
} else {
    echo "❌ ไม่สามารถเชื่อม Mikrotik API ได้";
}
