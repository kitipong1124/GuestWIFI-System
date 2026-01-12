<?php
require 'routeros_api.class.php'; // ถ้าใช้ Mikrotik API

$mac = $_GET['mac'] ?? '';
$response = ['active' => false];

if($mac){
    $API = new RouterosAPI();
    if($API->connect('192.168.99.1', 'admin', '1234')){
        $activeUsers = $API->comm("/ip/hotspot/active/print", ["?mac-address" => $mac]);
        if(count($activeUsers) > 0) $response['active'] = true;
        $API->disconnect();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
