<?php
session_start();
require_once __DIR__ . '/../config.php'; // ✅ เรียกไฟล์ config ถอยหลัง 1 ชั้น

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

try {
    // ✅ ใช้ค่า Constant จาก config.php
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ตรวจสอบว่าตาราง admin_users มีหรือยัง
$pdo->exec("
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL
)
");

// ตรวจสอบว่ามี admin user หรือไม่
$stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
$count = $stmt->fetchColumn();

if ($count == 0) {
    // ถ้าไม่มี สร้าง admin default (username: admin, password: 123456)
    $default_pass = password_hash('123456', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO admin_users (username, password) VALUES ('admin', '$default_pass')");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $user['username'];
        $_SESSION['admin_profile'] = $user['profile_pic'] ?? 'logo2.png';
        header('Location: admin.php');
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        /* 🎨 Ultra Minimalist Green Theme */
        :root {
            --main-green: #38761D;   /* Deep Forest Green (Primary Button/Text) */
            --light-bg: #f5f7fa;     /* Very Light Gray/Blue Background */
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
            padding: 50px 40px; /* เพิ่ม Padding ให้ดูโปร่ง */
            border-radius: 12px; 
            box-shadow: 0 6px 20px var(--shadow-subtle); /* เงาอ่อนๆ */
            width: 350px; 
            max-width: 90%;
            text-align: center; 
            border-top: 5px solid var(--main-green); /* เพิ่มเส้นสีเขียวด้านบนเพื่อ Branding */
        }

        h2 { 
            color: var(--text-dark); 
            margin: 15px 0 30px; 
            font-size: 26px;
            font-weight: 500;
        }

        .form-group { margin-bottom: 20px; text-align: left; }
        
        label { 
            display: block; 
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 600; 
            color: #555; 
        }
        
        input[type="text"], 
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
        
        input[type="text"]:focus, 
        input[type="password"]:focus {
            border-color: var(--main-green);
            box-shadow: 0 0 0 3px rgba(56, 118, 29, 0.2); /* Ring Focus สีเขียวอ่อนๆ */
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
            background: #6AA84F; /* สีเขียวอ่อนลงเมื่อ Hover */
        }

        .error { 
            color: #e74c3c; 
            background: #fbecec; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            font-weight: 500; 
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }
        
        .logo { 
            width: 120px;
            margin-bottom: 10px; 
            filter: drop-shadow(0 0 2px rgba(0,0,0,0.1));
        }
        
        .hint-text {
            margin-top: 25px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="logo1.png" alt="Logo RJM" class="logo">
        <h2>Admin Login</h2>
        
        <?php if ($error): ?>
            <p class="error">❌ <?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">USERNAME</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">LOG IN</button>
        </form>
        <p class="hint-text">regal jewelry manufacture co. ltd</p>
    </div>
</body>
</html>