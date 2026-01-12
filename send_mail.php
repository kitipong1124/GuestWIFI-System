<?php
require_once __DIR__ . '/config.php';
require 'vendor/autoload.php';

// ✅ แก้ไข 1: ใช้ค่า Database จาก config.php
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendUserMail($user_id, $pdo) {
    // ดึงข้อมูล user
    $stmt = $pdo->prepare("SELECT * FROM guest_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        
        // ✅ แก้ไข 2: ใช้ค่า Email Server จาก config.php
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // ✅ bypass certificate check (คงเดิมไว้ เพราะจำเป็นสำหรับบาง Server)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        // ✅ แก้ไข 3: ใช้ค่าผู้ส่ง/ผู้รับ จาก config.php
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME); 
        $mail->addAddress(MAIL_ADMIN_ADDRESS, 'Admin'); 

        // ✅ แก้ไข 4: สร้าง Link Approve/Reject โดยใช้ BASE_URL จาก config.php
        // (จะได้ไม่ต้องมาแก้ IP ตรงนี้เวลาเปลี่ยน Server)
        $approveLink = BASE_URL . "approve_mail.php?action=approve&user_id={$user_id}&token={$user['approve_token']}";
        $rejectLink  = BASE_URL . "approve_mail.php?action=reject&user_id={$user_id}&token={$user['approve_token']}";

        $mail->isHTML(true);
        $mail->Subject = "(!!) New Guest WiFi Registration: {$user['first_name']} {$user['last_name']}";
        $mail->Body = "
            <h4>New Guest WiFi Registration info.</h4>
            <ul>
                <li><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</li>
                <li><strong>Company:</strong> {$user['company']}</li>
                <li><strong>Email:</strong> {$user['email']}</li>
                <li><strong>Username:</strong> {$user['username']}</li>
                <li><strong>IP:</strong> {$user['ip_address']}</li>
                <li><strong>MAC:</strong> {$user['mac_address']}</li>
                <li><strong>Device:</strong> {$user['device_type']}</li>
                <li><strong>Expire:</strong> {$user['expire_time']}</li>
            </ul>
            <p>
            <a href='{$approveLink}'
            style='background:#28a745;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;margin-right:10px;'>✅ Approve</a>
            
            <a href='{$rejectLink}'
            style='background:#dc3545;color:#fff;padding:10px 15px;text-decoration:none;border-radius:5px;'>❌ Reject</a>
            </p>
            <hr>
            <small>Guest WiFi System By PHP + Mikrotik</small>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>