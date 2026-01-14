<?php
// ==========================================
//  DISCONNECT USER SCRIPT (Full Version)
// ==========================================

session_start();
header('Content-Type: text/plain; charset=utf-8'); 

// 1. เรียกไฟล์ Config และ API
// ตรวจสอบว่าไฟล์ config และ api อยู่ถูกที่หรือไม่
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/routeros_api.class.php'; 

// ==========================================
// ⚙️ ตั้งค่า MikroTik Router (แก้ไขตรงนี้)
// ==========================================
// ใส่ IP, User, Password ของ Router คุณที่นี่
$mikrotik_ip   = "172.16.123.254";   // <--- แก้เป็น IP Router ของคุณ
$mikrotik_user = "admin";          // <--- แก้เป็น User ของ Router
$mikrotik_pass = "1234";           // <--- แก้เป็น Password ของ Router
$mikrotik_port = 8728;             // <--- Port API (ปกติ 8728)
// ==========================================

// ตรวจสอบ ID ที่ส่งมา
$user_id = intval($_GET['id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(400);
    die("❌ Error: Invalid User ID");
}

try {
    // 2. เชื่อมต่อฐานข้อมูล MySQL
    // ใช้ค่า DB_HOST, DB_USER... จากไฟล์ config.php
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. ดึงข้อมูล User จาก Database
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, company, username FROM guest_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("❌ Error: ไม่พบ User นี้ในระบบฐานข้อมูล");
    }

    // 4. เริ่มเชื่อมต่อ MikroTik API
    $API = new RouterosAPI();
    // $API->debug = true; // เปิดบรรทัดนี้ถ้าอยากเห็น Log การเชื่อมต่อละเอียดๆ

    if ($API->connect($mikrotik_ip, $mikrotik_user, $mikrotik_pass, $mikrotik_port)) {
        
        $username = $user['username'];
        $mac_address = ""; 

        // --- 4.1 ลบออกจาก Active (ตัดเน็ต) ---
        // ใช้ filter ?user เพื่อหาเฉพาะคนนี้ (เร็วกว่าดึงมาทั้งหมด)
        $activeUsers = $API->comm("/ip/hotspot/active/print", ["?user" => $username]);
        
        if (count($activeUsers) > 0) {
            foreach ($activeUsers as $active) {
                // เก็บ MAC Address ไว้ใช้ลบ Host ต่อ
                if(isset($active['mac-address'])) {
                    $mac_address = $active['mac-address'];
                }
                // สั่งเตะ (Kick)
                $API->comm("/ip/hotspot/active/remove", [".id" => $active['.id']]);
            }
        }

        // --- 4.2 ลบ Cookie (กัน Login กลับมาเอง) ---
        $cookies = $API->comm("/ip/hotspot/cookie/print", ["?user" => $username]);
        if (count($cookies) > 0) {
            foreach ($cookies as $cookie) {
                $API->comm("/ip/hotspot/cookie/remove", [".id" => $cookie['.id']]);
            }
        }

        // --- 4.3 ลบ Host (เคลียร์สถานะเครื่อง) ---
        if (!empty($mac_address)) {
            $hosts = $API->comm("/ip/hotspot/host/print", ["?mac-address" => $mac_address]);
            if (count($hosts) > 0) {
                foreach ($hosts as $host) {
                    $API->comm("/ip/hotspot/host/remove", [".id" => $host['.id']]);
                }
            }
        }

        // ปิดการเชื่อมต่อ MikroTik
        $API->disconnect(); 

        // 5. อัปเดตสถานะใน Database เป็น Disconnected
        $updateStmt = $pdo->prepare("UPDATE guest_users SET disconnected = 1 WHERE id = ?");
        $updateStmt->execute([$user_id]);

        // 6. บันทึก Log การตัดเน็ต
        $logStmt = $pdo->prepare("INSERT INTO guest_disconnect_log 
            (user_id, disconnect_time, first_name, last_name, email, company, performed_by) 
            VALUES (?, NOW(), ?, ?, ?, ?, ?)");
        
        $admin_name = $_SESSION['admin_name'] ?? 'Admin'; // ถ้าไม่มี session admin ให้ใช้ชื่อ 'Admin'

        $logStmt->execute([
            $user_id, 
            $user['first_name'], 
            $user['last_name'], 
            $user['email'], 
            $user['company'],
            $admin_name
        ]);

        echo "✅ ตัดการเชื่อมต่อคุณ {$user['first_name']} เรียบร้อยแล้ว (Active/Cookie/Host Cleaned)";

    } else {
        // กรณี Connect MikroTik ไม่ได้
        http_response_code(500);
        echo "❌ Error: ไม่สามารถเชื่อมต่อ MikroTik API ได้\n";
        echo "ตรวจสอบ IP: $mikrotik_ip หรือรหัสผ่านให้ถูกต้อง";
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "❌ Database Error: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "❌ System Error: " . $e->getMessage();
}
?>