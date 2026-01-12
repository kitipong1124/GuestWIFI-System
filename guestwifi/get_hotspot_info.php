<?php
require('routeros_api.class.php');

header('Content-Type: application/json');

$API = new RouterosAPI();
$ip = '172.16.123.254';  // เปลี่ยนตาม IP จริง
$user = 'admin';       // เปลี่ยนตาม user จริง
$pass = '1234';        // เปลี่ยนตาม password จริง

$response = [
    'board' => 'N/A',
    'model' => 'N/A',
    'cpu' => 0,
    'hotspot' => []
];

// รับพารามิเตอร์กรองเวลา (format: 'YYYY-MM-DD HH:MM:SS')
$startTime = $_GET['start'] ?? null;
$endTime = $_GET['end'] ?? null;

if ($API->connect($ip, $user, $pass)) {
    // ข้อมูลระบบ
    $systemResource = $API->comm("/system/resource/print");
    if (isset($systemResource[0])) {
        $response['board'] = $systemResource[0]['board-name'] ?? 'N/A';
        $response['model'] = $systemResource[0]['platform'] ?? 'N/A';
        $response['cpu'] = $systemResource[0]['cpu-load'] ?? 0;
    }

    // ดึงรายการ Hotspot Users ทั้งหมด
    $hotspotUsers = $API->comm("/ip/hotspot/active/print");

    foreach ($hotspotUsers as $user) {
        // เวลา uptime เป็น string เช่น "1h23m45s"
        // ถ้าอยากกรองเวลาที่เชื่อมต่อ กรณีนี้จะต้องคำนวณเวลา connection จริงจาก uptime + current time
        // RouterOS ไม่มีฟิลด์ start_time โดยตรงจาก /ip/hotspot/active/print
        // วิธีที่ง่าย: สมมติ uptime คือระยะเวลาที่ออนไลน์ล่าสุด ถอดเป็นวินาที แล้วลบจากเวลาปัจจุบัน = เวลาเริ่มเชื่อมต่อ

        $uptime = $user['uptime'] ?? '';
        $uptimeSec = parseUptimeToSeconds($uptime);
        $connectedAt = time() - $uptimeSec; // timestamp เวลาเริ่มเชื่อมต่อ

        // ถ้ามีการส่งพารามิเตอร์กรองเวลา
        if ($startTime && $endTime) {
            $startTimestamp = strtotime($startTime);
            $endTimestamp = strtotime($endTime);
            if ($connectedAt < $startTimestamp || $connectedAt > $endTimestamp) {
                continue; // ข้าม user ที่ไม่อยู่ในช่วงเวลาที่กำหนด
            }
        }

        $response['hotspot'][] = [
            'user' => $user['user'] ?? '',
            'address' => $user['address'] ?? '',
            'mac' => $user['mac-address'] ?? '',
            'bytes_in' => (int)($user['bytes-in'] ?? 0),
            'bytes_out' => (int)($user['bytes-out'] ?? 0),
            'uptime' => $uptime,
            'signal' => $user['signal-strength'] ?? null,  // สัญญาณ WiFi (ถ้ามี)
        ];
    }

    $API->disconnect();
}

echo json_encode($response);


// ฟังก์ชันแปลง uptime string เป็นวินาที
function parseUptimeToSeconds($uptime) {
    $seconds = 0;
    if (preg_match('/(\d+)d/', $uptime, $m)) $seconds += $m[1] * 86400;
    if (preg_match('/(\d+)h/', $uptime, $m)) $seconds += $m[1] * 3600;
    if (preg_match('/(\d+)m/', $uptime, $m)) $seconds += $m[1] * 60;
    if (preg_match('/(\d+)s/', $uptime, $m)) $seconds += $m[1];
    return $seconds;
}
