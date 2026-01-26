<?php
require_once __DIR__ . '/config.php';

// เชื่อมต่อฐานข้อมูล
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset(DB_CHARSET);

$user_id = intval($_GET['user_id'] ?? 0);
$link_login_only = $_GET['link-login-only'] ?? "http://192.168.55.1/login";
$dst = $_GET['dst'] ?? "https://www.regal-jewelry.com/";

// ดึงข้อมูล User
$stmt = $mysqli->prepare("SELECT first_name, username, password, approved, remark FROM guest_users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$mysqli->close();

if (!$user) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h2 style='color:#e74c3c;'>❌ No user account found</h2>
            <p>Please register again.</p>
            <a href='register.php'>Go to Registration</a>
         </div>");
} 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

<div class="card">
    <img src="images/logo1.png" alt="Logo" class="logo">

    <?php if ($user['approved'] == 0): ?>
        <div class="status-pending">
            <div class="status-icon">⏳</div>
            <h2>Registration Pending</h2>
            <p>Your account is awaiting approval from IT Admin.<br>
            Please wait momentarily.</p>
            
            <div style="background: #f9f9f9; padding: 10px; border-radius: 6px; display: inline-block;">
                <p style="margin:0; font-size: 13px; color: #777;">Auto-refreshing in 5 seconds...</p>
            </div>
        </div>
        <meta http-equiv="refresh" content="5">

    <?php elseif ($user['approved'] == 1): ?>
        <div class="status-approved">
            <div class="status-icon">✅</div>
            <h2>Access Granted!</h2>
            <p>Welcome, <strong><?=htmlspecialchars($user['first_name'])?></strong></p>            
            <p id="loadingText" class="pulse-text">Initiating login process...</p>
            
            <div class="spinner"></div>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>

        <form id="autoLoginForm" method="post" action="<?=htmlspecialchars($link_login_only)?>">
            <input type="hidden" name="username" value="<?=htmlspecialchars($user['username'])?>">
            <input type="hidden" name="password" value="<?=htmlspecialchars($user['password'])?>">
            <input type="hidden" name="dst" value="<?=htmlspecialchars($dst)?>">
            <input type="hidden" name="popup" value="true">
        </form>

        <script>
            // Script เดิมของคุณสำหรับการเปลี่ยนข้อความและ Submit form
            const steps = ["Verifying credentials...", "Connecting to WiFi Network...", "Success! Redirecting..."];
            let idx = 0;
            const textElement = document.getElementById('loadingText');
            
            // เปลี่ยนข้อความทุก 0.8 วิ
            setInterval(() => { 
                if (idx < steps.length) { 
                    textElement.innerText = steps[idx]; 
                    idx++; 
                } 
            }, 800);

            // ส่งข้อมูล Login หลังจาก 3 วิ
            setTimeout(() => { 
                document.getElementById('autoLoginForm').submit(); 
            }, 3000);
        </script>

    <?php else: ?>
        <div class="status-rejected">
            <div class="status-icon">❌</div>
            <h2>Request Denied</h2>
            <p>Your registration request has been declined.</p>

            <?php if (!empty($user['remark'])): ?>
                <div class="reason-box">
                    <strong>Reason from Admin:</strong>
                    <?= htmlspecialchars($user['remark']) ?>
                </div>
            <?php endif; ?>

            <p style="font-size: 13px; margin-top: 20px; color: #999;">If you believe this is a mistake, please contact IT.</p>
            <a href="register.php" class="btn-retry">Try Registering Again</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>