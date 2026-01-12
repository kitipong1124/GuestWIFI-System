<?php
// update_mikrotik_status.php
require_once __DIR__ . '/routeros_api.class.php'; // ไฟล์ Mikrotik API

$mysqli = new mysqli("localhost", "root", "", "guestwifi_db");
if ($mysqli->connect_error) exit("DB connection failed");

// Mikrotik API Config
$router_ip   = "172.16.123.254";
$router_user = "admin";
$router_pass = "1234";
$router_port = 8728;

$API = new RouterosAPI();
$API->debug = false;
if (!$API->connect($router_ip, $router_user, $router_pass, $router_port)) {
    exit("Cannot connect Mikrotik");
}

// ดึงผู้ใช้ hotspot ทั้งหมดที่ Mikrotik กำลัง active
$activeUsers = [];
$users = $API->comm("/ip/hotspot/active/print");
foreach($users as $u){
    $activeUsers[$u['user']] = [
        'address' => $u['address'],
        'mac-address' => $u['mac-address']
    ];
}

// อัปเดต DB
$result = $mysqli->query("SELECT username FROM guest_users");
while($row = $result->fetch_assoc()){
    $username = $row['username'];
    if(isset($activeUsers[$username])){
        // Active
        $stmt = $mysqli->prepare("UPDATE guest_users SET disconnected=0, ip_address=?, mac_address=? WHERE username=?");
        $stmt->bind_param("sss", $activeUsers[$username]['address'], $activeUsers[$username]['mac-address'], $username);
        $stmt->execute();
        $stmt->close();
    } else {
        // Inactive / disconnected
        $stmt = $mysqli->prepare("UPDATE guest_users SET disconnected=1, ip_address=NULL, mac_address=NULL WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
    }
}

$API->disconnect();
$mysqli->close();
echo "Update complete at ".date('Y-m-d H:i:s')."\n";
