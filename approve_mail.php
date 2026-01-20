<?php
require_once __DIR__ . '/config.php'; // ✅ เรียกไฟล์ config
require_once __DIR__ . '/routeros_api.class.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🔧 Mikrotik API Config (ใช้ค่าจาก config.php)
$router_ip   = ROUTER_IP;
$router_user = ROUTER_USER;
$router_pass = ROUTER_PASS;
$router_port = ROUTER_PORT;

// 🔧 Database connection (ใช้ค่าจาก config.php)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<h2 style='color:red'>❌ Database Connection Failed</h2>");
}

// รับค่า
$user_id = $_GET['user_id'] ?? '';
$token   = $_GET['token'] ?? '';
$action  = $_GET['action'] ?? 'approve';

if (!$user_id || !$token) {
    die("<h2 style='color:red'>❌ Invalid request</h2>");
}

$stmt = $pdo->prepare("SELECT * FROM guest_users WHERE id = ? AND approve_token = ?");
$stmt->execute([$user_id, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("<h2 style='color:red'>❌ Invalid token or user</h2>");
}

// 🔧 เชื่อม Mikrotik
$API = new RouterosAPI();
$API->debug = false; // ปิด debug เพื่อความสะอาดของหน้าจอ
$mt_connected = $API->connect($router_ip, $router_user, $router_pass, $router_port);

$msg = '';
$color = '';
$statusText = '';
$remark = '';

switch ($action) {
    case 'approve':
        $statusText = 'Approved';
        $pdo->prepare("UPDATE guest_users SET approved = 1, approve_token = NULL WHERE id = ?")
            ->execute([$user_id]);
        
        if ($mt_connected) {
            $API->comm("/ip/hotspot/user/add", [
                "name"     => $user['username'],
                "password" => $user['password'],
                "profile"  => "guest",
                "comment"  => $user['first_name'] . " " . $user['last_name'] . " | " . $user['email']
            ]);
            $API->disconnect();
        }
        $msg = "User <strong>{$user['first_name']} {$user['last_name']}</strong> has been Approved ✅";
        $color = '#28a745';
        break;

    case 'reject':
        $statusText = 'Rejected';
        $remark = 'Rejected via email';
        $pdo->prepare("UPDATE guest_users SET approved = 2, approve_token = NULL, remark = ? WHERE id = ?")
            ->execute([$remark, $user_id]);
        
        if ($mt_connected) {
            $API->comm("/ip/hotspot/user/remove", ["numbers" => $user['username']]);
            $API->disconnect();
        }
        $msg = "User <strong>{$user['first_name']} {$user['last_name']}</strong> has been Rejected ❌";
        $color = '#dc3545';
        break;

    default:
        $msg = "⚠️ Invalid action";
        $color = '#ff9800';
        $statusText = 'Invalid';
}

// 🔧 ส่งเมลแจ้ง Admin กลับไปยืนยันผล
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    
    // ✅ ใช้ค่า Email Server จาก config.php
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    
    // ✅ bypass certificate check
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ];

    // ✅ ใช้ค่าผู้ส่ง/ผู้รับ จาก config.php
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_ADMIN_ADDRESS, 'Admin');

    $mail->isHTML(true);
    $mail->Subject = "(!!) Guest User $statusText: {$user['first_name']} {$user['last_name']}";
    $mail->Body = "
        <h4>Alert $statusText Guest WiFi User</h4>
        <ul>
          <li><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</li>
          <li><strong>Email:</strong> {$user['email']}</li>
          <li><strong>Username:</strong> {$user['username']}</li>
          <li><strong>Status:</strong> <span style='color:" . ($statusText === 'Approved' ? 'green' : 'red') . "'>$statusText</span></li>" .
          ($remark ? "<li><strong>Rejection Reason:</strong> $remark</li>" : '') .
        "</ul><hr><small>Guest WiFi System by PHP + Mikrotik</small>";
    
    $mail->AltBody = "Alert $statusText Guest WiFi User\nName: {$user['first_name']} {$user['last_name']}\nEmail: {$user['email']}\nUsername: {$user['username']}\nStatus: $statusText" . ($remark ? "\nRejection Reason: $remark" : '');

    $mail->send();
} catch (Exception $e) {
    error_log("Email error: " . $mail->ErrorInfo);
}

// สร้างลิงก์กลับหน้า Dashboard โดยใช้ BASE_URL
$dashboardLink = BASE_URL . "guestwifi/dashboard.php";

?>
<!DOCTYPE html>
<html lang='th'>
<head>
<meta charset='UTF-8'>
<title>Email Approval</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
body {
    font-family: 'Inter', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
    margin: 0;
}
.card {
    background: #fff;
    padding: 30px 20px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    text-align: center;
    max-width: 450px;
    width: 95%;
    margin: 20px auto;
}
h2 {
    color: <?php echo $color; ?>;
    font-size: 1.8rem;
    margin-bottom: 20px;
    text-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.btn {
    display: inline-block;
    text-decoration: none;
    padding: 12px 25px;
    border-radius: 8px;
    background: #007bff;
    color: #fff;
    font-weight: 600;    
    margin-top: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    background: #0056b3;
}
</style>
</head>
<body>
<div class="card">
    <h2><?php echo $msg; ?></h2>
    <a class="btn" href='<?php echo $dashboardLink; ?>'>🔙 Back to Dashboard</a>
</div>
</body>
</html>