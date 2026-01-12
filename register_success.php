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
$stmt = $mysqli->prepare("SELECT username, password, approved, remark FROM guest_users WHERE id=?");
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
<style>
    /* 🎨 Theme Variables */
    :root {
        --main-green: #38761D;
        --light-bg: #f5f7fa;
        --text-dark: #333;
        --shadow-subtle: rgba(0, 0, 0, 0.1);
        --status-pending: #f39c12;
        --status-rejected: #e74c3c;
    }

    /* Reset & Base Styles */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
        font-family: 'Kanit', sans-serif;
        background-color: var(--light-bg);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        color: var(--text-dark);
    }

    /* Card Design */
    .card {
        background: #ffffff;
        width: 100%;
        max-width: 450px;
        padding: 50px 30px;
        border-radius: 12px;
        box-shadow: 0 6px 20px var(--shadow-subtle);
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: slideUp 0.5s ease-out;
        border-top: 5px solid var(--main-green);
    }

    /* Logo */
    .logo {
        max-width: 100px;
        margin-bottom: 20px;
        height: auto;
        opacity: 0.9;
    }

    /* Icon Styles */
    .status-icon {
        font-size: 70px;
        margin-bottom: 20px;
        display: inline-block; /* สำคัญ! ต้องมีบรรทัดนี้ถึงจะหมุนได้ */
    }

    /* ✅ เพิ่ม Animation หมุนนาฬิกาทรายเฉพาะสถานะ Pending */
    .status-pending .status-icon {
        animation: hourglassFlip 3s infinite ease-in-out;
    }

    /* Keyframes: หยุดรอ -> พลิก -> หยุดรอ -> พลิกกลับ */
    @keyframes hourglassFlip {
        0% { transform: rotate(0deg); }
        40% { transform: rotate(0deg); }   /* ช่วงหยุดนิ่ง 40% ของเวลา */
        50% { transform: rotate(180deg); } /* ช่วงพลิกตัวเร็วๆ */
        90% { transform: rotate(180deg); } /* หยุดนิ่งในท่ากลับหัว */
        100% { transform: rotate(360deg); } /* พลิกกลับมาท่าเดิม */
    }

    h2 { 
        font-size: 24px; 
        font-weight: 500; 
        margin-bottom: 10px; 
        color: var(--text-dark); 
    }
    
    p { 
        font-size: 15px; 
        color: #666; 
        line-height: 1.6; 
        margin-bottom: 25px; 
    }

    /* Status Colors */
    .status-pending h2 { color: var(--status-pending); }
    .status-approved h2 { color: var(--main-green); }
    .status-rejected h2 { color: var(--status-rejected); }

    .reason-box {
        background-color: #fff5f5;
        border-left: 4px solid var(--status-rejected);
        color: #c0392b;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 25px;
        font-size: 14px;
        text-align: left;
    }
    .reason-box strong {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #a93226;
    }

    /* Spinner & Progress */
    .spinner {
        margin: 20px auto;
        width: 40px;
        height: 40px;
        border: 4px solid rgba(56, 118, 29, 0.1);
        border-top-color: var(--main-green);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    .progress-container {
        width: 100%;
        background-color: #eee;
        border-radius: 20px;
        margin-top: 20px;
        height: 6px;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        background: var(--main-green);
        width: 0;
        animation: fillProgress 3s ease-in-out forwards;
        border-radius: 20px;
    }
    .btn-retry {
        display: inline-block;
        text-decoration: none;
        color: var(--status-rejected);
        border: 1px solid var(--status-rejected);
        padding: 10px 20px;
        border-radius: 6px;
        transition: all 0.3s;
        font-size: 14px;
    }
    .btn-retry:hover {
        background-color: var(--status-rejected);
        color: white;
    }

    /* Animations */
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes fillProgress { from { width: 0%; } to { width: 100%; } }
    
    .pulse-text {
        animation: pulse 1.5s infinite;
        font-weight: 500;
        color: var(--main-green);
        font-size: 14px;
    }
    @keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }

</style>
</head>
<body>

<div class="card">
    <img src="images/logo1.png" alt="Logo" class="logo">

    <?php if ($user['approved'] == 0): ?>
        <div class="status-pending">
            <div class="status-icon">⏳</div>
            
            <h2>Registration Successful</h2>
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
            <p>Welcome, <strong><?=htmlspecialchars($user['username'])?></strong></p>
            
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
            const steps = ["Verifying credentials...", "Connecting to WiFi Network...", "Success! Redirecting..."];
            let idx = 0;
            const textElement = document.getElementById('loadingText');
            setInterval(() => { if (idx < steps.length) { textElement.innerText = steps[idx]; idx++; } }, 800);
            setTimeout(() => { document.getElementById('autoLoginForm').submit(); }, 3000);
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