<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/routeros_api.class.php';

// 🔧 Mikrotik API Config
$router_ip   = ROUTER_IP;
$router_user = ROUTER_USER;
$router_pass = ROUTER_PASS;
$router_port = ROUTER_PORT;

// 🔧 Database connection
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$pdo = new PDO($dsn, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// รับค่าจาก Mikrotik (query string)
$mac           = $_REQUEST['mac'] ?? '';
$ip            = $_REQUEST['ip'] ?? '';
$linkLoginOnly = $_REQUEST['link-login-only'] ?? 'http://192.168.55.1/login';
$dst           = $_REQUEST['dst'] ?? 'https://www.regal-jewelry.com/';

$error = ""; // ประกาศตัวแปร Error ไว้ก่อน

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname      = trim($_POST["fullname"]);
    $company_name  = trim($_POST["company_name"]);
    $email         = trim($_POST["email"]);
    $device_type   = trim($_POST["device_type"]);

    // --- Validation Zone ---

    // 1. ตรวจสอบอีเมล (Gmail Only)
    if (!preg_match("/^[a-zA-Z0-9._]+@gmail\.com$/", $email)) {
        $error = "❌ อีเมลต้องเป็นภาษาอังกฤษและลงท้ายด้วย @gmail.com เท่านั้น";
    }
    
    // 2. ตรวจสอบชื่อ (ห้ามมีอักขระพิเศษ)
    if (!preg_match("/^[a-zA-Z\x{0E00}-\x{0E7F}\s.-]+$/u", $fullname)) {
        $error = "❌ ชื่อ-นามสกุลไม่ถูกต้อง ห้ามใส่อักษรพิเศษ";
    }

    // --- Process Zone ---
    
    // ✅ เช็คว่าไม่มี Error ถึงจะทำงานต่อ
    if (empty($error)) {
        
        // 🔹 สร้าง username/password สำหรับ hotspot
        $username = "u" . substr(uniqid(), -6);
        $password = "p" . rand(100000, 999999);

        $expireTime = date('Y-m-d 23:59:59', strtotime('+1 day'));
        $token = bin2hex(random_bytes(16)); 
        $token_expire = date('Y-m-d H:i:s', strtotime('+1 day'));

        // 🔹 แยกชื่อจริง/นามสกุล
        $nameParts = explode(" ", $fullname, 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        // 🔹 บันทึกข้อมูลลง Database
        $stmt = $pdo->prepare("
        INSERT INTO guest_users (
            first_name, last_name, email, company,
            username, password, device_type, mac_address, ip_address,
            approved, start_time, expire_time, approve_token, approve_expire
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, ?, ?)
        ");
        $stmt->execute([
            $firstName, $lastName, $email, $company_name,
            $username, $password, $device_type, $mac, $ip,
            $expireTime, $token, $token_expire
        ]);

        $user_id = $pdo->lastInsertId();

        require_once __DIR__ . "/send_mail.php";
        sendUserMail($user_id, $pdo);

        // 🔹 redirect ไปหน้า success
        header("Location: register_success.php?user_id={$user_id}" .
            "&link-login-only=" . urlencode($linkLoginOnly) .
            "&dst=" . urlencode($dst)
        );
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WiFi Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
<style>
    /* 🎨 Theme Variables */
    :root {
        --main-green: #38761D;
        --light-bg: #f5f7fa;
        --text-dark: #333;
        --shadow-subtle: rgba(0, 0, 0, 0.1);
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

    /* Card Styling */
    .card {
        background: #ffffff;
        width: 100%;
        max-width: 420px;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 6px 20px var(--shadow-subtle);
        text-align: center;
        transition: transform 0.3s ease;
        border-top: 5px solid var(--main-green);
    }

    .card:hover { transform: translateY(-5px); }

    /* Logo */
    .logo {
        max-width: 120px;
        margin-bottom: 25px;
        height: auto;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* Headings */
    h2 {
        font-weight: 500;
        margin-bottom: 30px;
        color: var(--text-dark);
        font-size: 24px;
        letter-spacing: 0.5px;
    }

    /* Form Elements */
    .form-group { margin-bottom: 20px; text-align: left; }
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #555;
        font-size: 14px;
    }
    input[type="text"], input[type="email"], select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: inherit;
        font-size: 15px;
        transition: all 0.3s ease;
        background-color: #fff;
        outline: none;
        color: #333;
    }
    
    input:focus, select:focus {
        border-color: var(--main-green);
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(56, 118, 29, 0.2);
    }
    
    ::placeholder { color: #aaa; font-weight: 300; }

    /* Button Styling */
    .btn {
        width: 100%;
        padding: 14px;
        background: var(--main-green);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s;
        margin-top: 15px;
    }
    .btn:hover {
        background: #2e6318;
        transform: translateY(-2px);
    }

    /* Error Styling */
    .input-error {
        border-color: #e74c3c !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2) !important;
    }
    .php-error-box {
        background-color: #fbecec;
        color: #e74c3c;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
        font-size: 14px;
    }
</style>
</head>

<body>
<div class="card">
    <img src="images/logo1.png" alt="Logo" class="logo">
    <h2>WiFi Guest Registration</h2>
    
    <?php if (!empty($error)): ?>
        <div class="php-error-box">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <input type="hidden" name="mac" value="<?=htmlspecialchars($mac)?>">
        <input type="hidden" name="ip" value="<?=htmlspecialchars($ip)?>">
        <input type="hidden" name="link-login-only" value="<?=htmlspecialchars($linkLoginOnly)?>">

        <div class="form-group">
            <label for="fullname">Full Name (ชื่อ-นามสกุล)</label>
            <input type="text" name="fullname" id="fullname" required
            placeholder="Somchai Yaito"
            value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>"
            oninput="this.value = this.value.replace(/[^a-zA-Z\u0E00-\u0E7F\s.-]/g, '');">
        </div>

        <div class="form-group">
            <label for="company_name">Company (บริษัท)</label>
            <input type="text" name="company_name" id="company_name" required 
            value="<?= isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : '' ?>"
            placeholder="Your company name">
        </div>

        <div class="form-group">
            <label for="email">Email (Gmail Only)</label>
            <input type="email" name="email" id="email" required 
            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
            placeholder="name@gmail.com" autocomplete="off">
            <small id="email-error" style="color: #e74c3c; display: none; margin-top: 5px; font-size: 14px;"></small>
        </div>

        <div class="form-group">
            <label for="device_type">Device Type</label>
            <select name="device_type" id="device_type" required>
                <option value="" disabled selected>-- Select Device --</option>
                <option value="Laptop">💻 Laptop</option>
                <option value="Mobile">📱 Mobile</option>
                <option value="Tablet">📟 Tablet</option>
                <option value="Other">🔌 Other</option>
            </select>
        </div>

        <button type="submit" class="btn">Connect WiFi</button>
    </form>
</div>

<script>
    const emailInput = document.getElementById('email');
    const errorMsg = document.getElementById('email-error');
    const form = document.querySelector('form'); 

    function validateEmail() {
        const val = emailInput.value;
        if (val === "") return true; 

        // เช็ค Format Email
        const isValid = /^[a-zA-Z0-9._]+@gmail\.com$/.test(val);

        if (!isValid) {
            showError("❌ ต้องใช้ Gmail ภาษาอังกฤษ (เช่น name@gmail.com)");
            return false;
        } else {
            clearError();
            return true;
        }
    }

    function showError(message) {
        emailInput.classList.add('input-error'); 
        errorMsg.textContent = message;
        errorMsg.style.display = 'block';
    }

    function clearError() {
        emailInput.classList.remove('input-error'); 
        errorMsg.style.display = 'none';
    }

    emailInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9._@]/g, '');
        clearError(); 
    });

    emailInput.addEventListener('blur', validateEmail);

    form.addEventListener('submit', function(e) {
        if (!validateEmail()) {
            e.preventDefault(); // หยุดการส่งฟอร์มทันทีถ้าผิด
            emailInput.focus();
        }
    });
</script>

</body>
</html>