<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php'; // ✅ เรียก config

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
$mysqli->set_charset("utf8mb4");

// รับค่าจาก Dashboard
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? ''; // ✅ รับค่าวันที่เริ่ม
$end_date   = $_GET['end_date'] ?? '';   // ✅ รับค่าวันที่สิ้นสุด
$mode   = $_GET['mode'] ?? '';

// ... (ส่วน Mikrotik Active Cache คงเดิมไว้ได้เลยครับ) ...
require_once __DIR__ . '/routeros_api.class.php';
define('CACHE_FILE', __DIR__ . '/mikrotik_active_cache.json');
function getAllMikrotikActiveDynamic() { 
    // 1. อ่าน Cache เดิม
    $cache = @json_decode(@file_get_contents(CACHE_FILE), true);
    $now = time();

    // 2. ปรับเวลา Cache ให้ยืดหยุ่นขึ้น (อย่างน้อย 15 วินาที เพื่อไม่ให้ชนกับ Loop 5 วิหน้าเว็บ)
    // ไม่ว่า user จะน้อยหรือมาก ให้ cache ไว้อย่างน้อย 15 วิ
    if($cache && isset($cache['time']) && isset($cache['data'])){
        // ถ้า User เยอะ (>200) ให้ Cache นานขึ้นเป็น 30 วิ เพื่อลดโหลด
        $expire = (count($cache['data']) > 200) ? 30 : 15; 
        
        if($now - $cache['time'] < $expire) {
            return $cache['data']; // ส่งค่าจาก Cache ทันที ไม่ยิง Router
        }
    }

    // 3. ถ้า Cache หมดอายุ ถึงจะยิง Router
    $API = new RouterosAPI();
    $activeMap = [];

    // เพิ่ม Timeout 2 วินาที เผื่อ Router ค้าง จะได้ไม่ทำให้เว็บค้างตาม
    if ($API->connect(ROUTER_IP, ROUTER_USER, ROUTER_PASS, ROUTER_PORT)) {
        $users = $API->comm('/ip/hotspot/active/print');
        foreach ($users as $u) { 
            if(isset($u['user'])) $activeMap[$u['user']] = 1; 
        }
        $API->disconnect();
    }

    // 4. บันทึก Cache พร้อม Lock file (ป้องกัน Admin 2 คนเขียนไฟล์ชนกัน)
    file_put_contents(CACHE_FILE, json_encode(['time'=>$now, 'data'=>$activeMap]), LOCK_EX);
    
    return $activeMap;
}

// =================== SQL Query ===================
$sql = "SELECT id, first_name, last_name, email, company, start_time, expire_time, username, password, approved, disconnected, device_type, ip_address, mac_address
        FROM guest_users WHERE 1=1";

$params = [];
$types = "";

// 1. ค้นหาด้วย Search Text
if(!empty($search)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ? OR username LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "sssss";
}

// 2. กรองสถานะ
if($status !== '' && in_array($status,['0','1','2'])) {
    $sql .= " AND approved = ?";
    $params[] = $status;
    $types .= "i";
}

// 3. ✅ กรองวันที่ (Date Filter)
if(!empty($start_date)) {
    $sql .= " AND DATE(start_time) >= ?";
    $params[] = $start_date;
    $types .= "s";
}
if(!empty($end_date)) {
    $sql .= " AND DATE(start_time) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$sql .= " ORDER BY start_time DESC";

// Execute SQL
$stmt = $mysqli->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
$activeMap = ($mode === 'admin') ? getAllMikrotikActiveDynamic() : [];

while ($row = $result->fetch_assoc()) {
    $row['disconnected'] = (int)($row['disconnected'] ?? 0);
    if($mode === 'admin'){
        $row['active_mikrotik'] = $activeMap[$row['username']] ?? 0;
    }
    $users[] = $row;
}

echo json_encode($users);
$stmt->close();
$mysqli->close();
?>