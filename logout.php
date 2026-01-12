<?php
session_start();
require_once 'routeros_api.class.php'; // ใช้ Mikrotik API

// รับ username จาก query string
$username = $_GET['username'] ?? '';

if(!empty($username)){
    // --- Mikrotik API config ---
    $router_ip   = "172.16.123.254";
    $router_user = "admin";
    $router_pass = "1234";
    $router_port = 8728;

    $API = new RouterosAPI();

    if ($API->connect($router_ip, $router_user, $router_pass, $router_port)) {
        // หาผู้ใช้งานที่ active
        $API->write('/ip/hotspot/active/print', false);
        $API->write('?user=' . $username);
        $activeUsers = $API->read();

        // ลบผู้ใช้งานที่ active
        if(!empty($activeUsers)){
            foreach($activeUsers as $u){
                $API->write('/ip/hotspot/active/remove', false);
                $API->write('=.id=' . $u['.id']);
                $API->read();
            }
        }

        $API->disconnect();
    }
}

// ล้าง session ฝั่ง PHP
session_destroy();

// Redirect ไปหน้า register.php ให้ guest ลงทะเบียนใหม่
header("Location: register.php");
exit;
?>
