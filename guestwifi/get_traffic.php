<?php
require('routeros_api.class.php');

// ตั้งค่า Content-Type สำหรับ JSON
header('Content-Type: application/json');

// รับ IP จากพารามิเตอร์
$ip = $_GET['ip'] ?? '';
if (!$ip) {
    echo json_encode(['rx' => 0, 'tx' => 0, 'error' => 'IP not provided']);
    exit;
}

// ตั้งค่าการเชื่อมต่อ MikroTik
$API = new RouterosAPI();
$host = '172.16.123.254'; // ปรับตามจริง
$username = 'admin';     // ปรับตามจริง
$password = '1234';      // ปรับตามจริง

try {
    if ($API->connect($host, $username, $password)) {
        $activeUsers = $API->comm('/ip/hotspot/active/print');

        foreach ($activeUsers as $user) {
            if ($user['address'] === $ip) {
                $rx = (int) $user['bytes-in'];
                $tx = (int) $user['bytes-out'];
                $API->disconnect();
                echo json_encode(['rx' => $rx, 'tx' => $tx]);
                exit;
            }
        }

        $API->disconnect();
        echo json_encode(['rx' => 0, 'tx' => 0, 'error' => 'User not found']);
    } else {
        echo json_encode(['rx' => 0, 'tx' => 0, 'error' => 'Unable to connect']);
    }
} catch (Exception $e) {
    echo json_encode(['rx' => 0, 'tx' => 0, 'error' => $e->getMessage()]);
}
