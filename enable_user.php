<?php
require_once __DIR__ . '/config.php'; // ✅ เรียกไฟล์ config
require_once __DIR__ . '/routeros_api.class.php';

// 🔧 Database Connection (เปลี่ยนมาใช้ค่าจาก config)
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    exit('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset(DB_CHARSET);

$user_id = intval($_GET['user_id']);
$result = $mysqli->query("SELECT username, password FROM guest_users WHERE id=$user_id");
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    exit('User not found');
}

$API = new RouterosAPI();
$API->debug = false;
$success = false;

// 🔧 Mikrotik Connection (เปลี่ยนมาใช้ค่าจาก config)
if ($API->connect(ROUTER_IP, ROUTER_USER, ROUTER_PASS, ROUTER_PORT)) {
    
    // ตรวจสอบว่ามี User นี้อยู่ใน Hotspot หรือยัง
    $existing = $API->comm("/ip/hotspot/user/print", ["?name" => $user['username']]);
    
    if (!$existing) {
        // ถ้าไม่มี ให้สร้างใหม่
        $API->comm("/ip/hotspot/user/add", [
            "name"     => $user['username'],
            "password" => $user['password'],
            "profile"  => "guest",
            "disabled" => "no",
            "comment"  => "Auto-enable after admin approval"
        ]);
        $success = true;
    } else {
        // ถ้ามีอยู่แล้ว ให้ Enable
        $API->comm("/ip/hotspot/user/enable", ["numbers" => $existing[0]['.id']]);
        $success = true;
    }
    $API->disconnect();
}

if ($success) {
    // ⚠️ หมายเหตุ: ตรวจสอบชื่อ column ใน DB ของคุณด้วยนะครับ 
    // ใน SQL ที่ให้มาไม่มี column 'enabled' (มีแต่ 'approved' หรือ 'disconnected')
    // ถ้าโค้ดเดิมใช้ 'enabled' แล้ว error ให้ลองแก้เป็น 'approved=1' หรือ 'disconnected=0' ครับ
    $stmt = $mysqli->prepare("UPDATE guest_users SET enabled=1 WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

$mysqli->close();
echo json_encode(['success' => $success]);
?>