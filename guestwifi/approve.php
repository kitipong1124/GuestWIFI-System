<?php
require 'vendor/autoload.php';
require_once "routeros_api.class.php";
require_once __DIR__ . '/../config.php'; // ✅ เรียกไฟล์ config

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// 🔧 Mikrotik Config (ใช้ค่าจาก config)
$API = new RouterosAPI();
$API->debug = false;
$mt_connected = $API->connect(ROUTER_IP, ROUTER_USER, ROUTER_PASS, ROUTER_PORT);

// รับค่า
$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

// ✅ ใช้ค่า Constant จาก config.php
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed.");
}
$conn->set_charset(DB_CHARSET);

// ดึงข้อมูลผู้ใช้ (เพิ่ม password)
$stmt = $conn->prepare("SELECT first_name, last_name, email, username, password FROM guest_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['toast'] = '❌ ไม่พบผู้ใช้งานที่ระบุ';
    header("Location: dashboard.php");
    exit;
}

// ถ้าเป็น reject → แสดงฟอร์มใส่เหตุผลก่อน
if ($action === 'reject' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <title>Reject User</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h4>Reject User: ' . htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']) . '</h4>
            </div>
            <form method="post" action="approve.php?action=reject&id=' . $id . '">
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>Rejection Reason</label>
                        <textarea name="remark" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-danger">Confirm</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    </body>
    </html>';
    exit;
}

// เตรียมสถานะ
$statusText = '';
$remark = $_POST['remark'] ?? '';

try {
    switch ($action) {
        case 'approve':
            if ($mt_connected) {
                // ถ้าเชื่อมต่อได้ ให้เพิ่ม User ใน MikroTik ก่อน
                $API->comm("/ip/hotspot/user/add", [
                    "name"     => $user['username'],
                    "password" => $user['password'],
                    "profile"  => "guest",
                    "comment"  => $user['first_name'] . " " . $user['last_name'] . " | " . $user['email']
                ]);
                
                // เพิ่มสำเร็จ ค่อยมาอัปเดต Database
                $statusText = 'Approved';
                // ✅ แก้ไข: อัปเดต start_time เป็นปัจจุบัน และ expire_time เป็นอีก 1 วัน (24 ชม.) ข้างหน้า
                $stmt = $conn->prepare("UPDATE guest_users SET approved = 1, start_time = NOW(), expire_time = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            } else {
                // ⚠️ ถ้าเชื่อมต่อไม่ได้ ให้แจ้ง Error และ 'ไม่' อัปเดต Database
                $_SESSION['toast'] = '❌ Error: ไม่สามารถเชื่อมต่อ MikroTik ได้ (User ยังไม่ถูก Approve)';
                header("Location: dashboard.php");
                exit;
            }
            break;

        case 'reject':
            $statusText = 'Rejected';
            $stmt = $conn->prepare("UPDATE guest_users SET approved = 2, remark = ? WHERE id = ?");
            $stmt->bind_param("si", $remark, $id);
            $stmt->execute();

            if ($mt_connected) {
                // ลบ user ออกจาก Mikrotik
                $API->comm("/ip/hotspot/user/remove", [
                    "numbers" => $user['username']
                ]);
            }
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM guest_users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($mt_connected) {
                $API->comm("/ip/hotspot/user/remove", [
                    "numbers" => $user['username']
                ]);
            }

            $_SESSION['toast'] = '🗑️ ลบผู้ใช้งานเรียบร้อยแล้ว';
            header("Location: dashboard.php");
            exit;

        case 'extend':
            $stmt = $conn->prepare("UPDATE guest_users SET expire_time = DATE_ADD(expire_time, INTERVAL 5 HOUR) WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($mt_connected) {
                $API->comm("/ip/hotspot/user/set", [
                    "numbers" => $user['username'],
                    "limit-uptime" => "8h"
                ]);
            }

            $_SESSION['toast'] = '⏳ ขยายเวลาผู้ใช้งานแล้ว';
            header("Location: dashboard.php");
            exit;

        default:
            $_SESSION['toast'] = '⚠️ ไม่สามารถดำเนินการได้';
            header("Location: dashboard.php");
            exit;
    }

    $stmt->close();
    $conn->close();

    if ($mt_connected) $API->disconnect();

    // ส่งอีเมลแจ้งเตือน
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    
    // ✅ ใช้ค่าจาก config.php
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
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
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

    $mail->send();

    $_SESSION['toast'] = "✅ ดำเนินการ <strong>$statusText</strong> ผู้ใช้งานแล้ว";

} catch (Exception $e) {
    error_log("Email error: " . $mail->ErrorInfo);
    $_SESSION['toast'] = "⚠️ ดำเนินการ <strong>$statusText</strong> แล้ว แต่ส่งอีเมลไม่สำเร็จ";
}

header("Location: dashboard.php");
exit;
?>