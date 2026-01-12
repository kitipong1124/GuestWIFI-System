<?php
// GuestWIFI/status_api.php
// แก้ไข: เพิ่มระบบ Caching ลดภาระ Router

require_once __DIR__ . '/routeros_api.class.php';

$username = $_GET['username'] ?? '';

if (empty($username)) {
    echo json_encode(["error" => "No username"]);
    exit;
}

// 1. ตั้งค่า Cache
$cacheParams = [
    'file' => __DIR__ . "/log/status_{$username}.json", // เก็บไฟล์แยกราย User
    'time' => 60 // อายุ Cache (วินาที) - ดึงข้อมูลใหม่จาก Router ทุก 60 วิ
];

// ตรวจสอบโฟลเดอร์ log
if (!is_dir(__DIR__ . '/log')) {
    mkdir(__DIR__ . '/log', 0755, true);
}

// 2. ตรวจสอบ Cache ก่อน
if (file_exists($cacheParams['file'])) {
    $cacheData = json_decode(file_get_contents($cacheParams['file']), true);
    // ถ้าไฟล์ยังไม่หมดอายุ และไม่มี error ในไฟล์เดิม
    if ((time() - $cacheData['timestamp'] < $cacheParams['time']) && !isset($cacheData['data']['error'])) {
        echo json_encode($cacheData['data']);
        exit;
    }
}

// 3. ถ้าไม่มี Cache หรือหมดอายุ -> เชื่อมต่อ Mikrotik
$router_ip   = "172.16.123.254";
$router_user = "admin";
$router_pass = "1234";
$router_port = 8728;

$API = new RouterosAPI();
//$API->debug = true; // เปิดเฉพาะตอนเทส

if ($API->connect($router_ip, $router_user, $router_pass, $router_port)) {
    $active = $API->comm("/ip/hotspot/active/print", [
        "?user" => $username
    ]);
    $API->disconnect();

    if (count($active) > 0) {
        $session = $active[0];
        $output = [
            "uptime"   => $session['uptime'] ?? "0",
            "bytes_in" => $session['bytes-in'] ?? "0",
            "bytes_out"=> $session['bytes-out'] ?? "0"
        ];
    } else {
        // User อาจจะหลุดไปแล้ว หรือหาไม่เจอ
        $output = ["error" => "User not found or offline"];
    }
} else {
    $output = ["error" => "Cannot connect to Mikrotik"];
}

// 4. บันทึก Cache ใหม่
file_put_contents($cacheParams['file'], json_encode([
    'timestamp' => time(),
    'data'      => $output
]));

// ส่งค่ากลับ
echo json_encode($output);
?>