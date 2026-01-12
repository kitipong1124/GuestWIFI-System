<?php
session_start();
require_once __DIR__ . '/../config.php'; // ✅ เรียกไฟล์ config

// ตรวจสอบสิทธิ์ (ถ้ายังไม่ล็อกอิน ให้ดีดกลับไปหน้า Login)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$msg_type = ''; // error หรือ success

// เชื่อมต่อฐานข้อมูล
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $username     = $_SESSION['admin_name'];

    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $message = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        $msg_type = "error";
    } elseif ($new_pass !== $confirm_pass) {
        $message = "รหัสผ่านใหม่ไม่ตรงกัน";
        $msg_type = "error";
    } else {
        // ดึงรหัสผ่านเก่าจาก DB มาตรวจ
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_pass, $user['password'])) {
            // ✅ รหัสเก่าถูกต้อง -> อัปเดตรหัสใหม่
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            
            if ($update->execute([$new_hash, $username])) {
                $message = "เปลี่ยนรหัสผ่านสำเร็จ!";
                $msg_type = "success";
            } else {
                $message = "เกิดข้อผิดพลาด กรุณาลองใหม่";
                $msg_type = "error";
            }
        } else {
            $message = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <style>
        /* 🎨 Ultra Minimalist Green Theme (เหมือนหน้า Login) */
        :root {
            --main-green: #38761D;   
            --light-bg: #f5f7fa;     
            --text-dark: #333;
            --shadow-subtle: rgba(0, 0, 0, 0.1);
        }

        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background-color: var(--light-bg); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }

        .login-card { 
            background: #fff; 
            padding: 40px 40px; 
            border-radius: 12px; 
            box-shadow: 0 6px 20px var(--shadow-subtle); 
            width: 350px; 
            max-width: 90%;
            text-align: center; 
            border-top: 5px solid var(--main-green); 
        }

        h2 { 
            color: var(--text-dark); 
            margin: 10px 0 25px; 
            font-size: 24px;
            font-weight: 500;
        }

        .form-group { margin-bottom: 15px; text-align: left; }
        
        label { 
            display: block; 
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 600; 
            color: #555; 
        }
        
        input[type="password"] { 
            width: 100%; 
            padding: 12px;
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 16px; 
            background: white;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus {
            border-color: var(--main-green);
            box-shadow: 0 0 0 3px rgba(56, 118, 29, 0.2);
            outline: none;
        }

        button { 
            width: 100%; 
            padding: 12px;
            border: none; 
            border-radius: 6px; 
            background: var(--main-green); 
            color: white; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background 0.3s; 
            margin-top: 15px;
        }

        button:hover { 
            background: #6AA84F; 
        }

        .alert { 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            font-weight: 500; 
            font-size: 14px;
        }
        .alert-error { color: #e74c3c; background: #fbecec; border: 1px solid #f5c6cb; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; }
        
        .logo { 
            width: 100px;
            margin-bottom: 5px; 
            filter: drop-shadow(0 0 2px rgba(0,0,0,0.1));
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link:hover { color: var(--main-green); }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="logo1.png" alt="Logo RJM" class="logo">
        <h2>Change Password</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type == 'success' ? 'success' : 'error' ?>">
                <?= $msg_type == 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>CURRENT PASSWORD</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>NEW PASSWORD</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>CONFIRM NEW PASSWORD</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit">UPDATE PASSWORD</button>
        </form>
        
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>