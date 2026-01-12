<?php
require('routeros_api.class.php');

$API = new RouterosAPI();

$ip = '172.16.123.254'; // IP ของ Mikrotik
$user = 'admin';      // ชื่อผู้ใช้
$pass = '1234'; // รหัสผ่าน

if ($API->connect($ip, $user, $pass)) {
    $API->write('/ip/hotspot/active/print');
    $READ = $API->read(false);
    $ARRAY = $API->parseResponse($READ);

    if (count($ARRAY) > 0) {
        foreach ($ARRAY as $user) {
            echo "<tr>";
            echo "<td>{$user['user']}</td>";
            echo "<td>{$user['address']}</td>";
            echo "<td>{$user['mac-address']}</td>";
            echo "<td>{$user['uptime']}</td>";
            echo "<td>" . formatBytes($user['bytes-in']) . "</td>";
            echo "<td>" . formatBytes($user['bytes-out']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>ไม่มีผู้ใช้งานขณะนี้</td></tr>";
    }

    $API->disconnect();
} else {
    echo "<tr><td colspan='6'>เชื่อมต่อ Mikrotik ไม่ได้</td></tr>";
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1 << (10 * $pow)), $precision) . ' ' . $units[$pow];
}
?>
