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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="card">
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
            <button type="submit" class="btn">LOG IN</button>
        </form>
        <p class="hint-text">regal jewelry manufacture co. ltd</p>
    </div>
</body>
</html>