<?php
require 'routeros_api.class.php';

$ip = $_GET['ip'] ?? '';
$data = ['rx'=>0,'tx'=>0];

if($ip){
    $API = new RouterosAPI();
    if($API->connect('172.16.123.254','admin','1234')){
        $active = $API->comm("/ip/hotspot/active/print", ["?address" => $ip]);
        if(isset($active[0])){
            $data['rx'] = $active[0]['bytes-in'];
            $data['tx'] = $active[0]['bytes-out'];
        }
        $API->disconnect();
    }
}

header('Content-Type: application/json');
echo json_encode($data);
