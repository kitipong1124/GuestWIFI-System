<?php
session_start();
require_once __DIR__ . '/../config.php'; // ✅ เรียกไฟล์ config

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$msg_type = '';

// เชื่อมต่อฐานข้อมูล
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    $username = $_SESSION['admin_name'];

    // ตรวจสอบไฟล์
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        
        // สร้างโฟลเดอร์ uploads ถ้ายังไม่มี
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // ตั้งชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ (เช่น admin_654321.jpg)
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = $username . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;

        // อนุญาตเฉพาะไฟล์รูปภาพ
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($ext), $allowedExts)) {
            
            // 1. อัปโหลดไฟล์
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                
                // 2. ✅ สำคัญมาก: อัปเดตชื่อไฟล์ลง Database
                $stmt = $pdo->prepare("UPDATE admin_users SET profile_pic = ? WHERE username = ?");
                if ($stmt->execute([$newFileName, $username])) {
                    
                    // 3. อัปเดต Session ให้แสดงผลทันทีโดยไม่ต้องล็อกอินใหม่
                    $_SESSION['admin_profile'] = $newFileName;
                    
                    $message = "อัปเดตรูปโปรไฟล์สำเร็จ!";
                    $msg_type = "success";
                } else {
                    $message = "เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล";
                    $msg_type = "error";
                }

            } else {
                $message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
                $msg_type = "error";
            }
        } else {
            $message = "อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF) เท่านั้น";
            $msg_type = "error";
        }
    } else {
        $message = "กรุณาเลือกไฟล์รูปภาพ";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Change Profile Picture</title>
    <style>
        :root { --main-green: #38761D; --light-bg: #f5f7fa; --text-dark: #333; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: var(--light-bg); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); width: 350px; text-align: center; border-top: 5px solid var(--main-green); }
        h2 { color: var(--text-dark); margin: 10px 0 20px; font-size: 24px; font-weight: 500; }
        
        /* Preview Image */
        .profile-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 4px solid #eee; }
        
        input[type="file"] { margin-bottom: 20px; }
        
        button { width: 100%; padding: 12px; border: none; border-radius: 6px; background: var(--main-green); color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #6AA84F; }
        
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; }
        .alert-error { color: #e74c3c; background: #fbecec; border: 1px solid #f5c6cb; }
        
        .back-link { display: block; margin-top: 20px; color: #777; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: var(--main-green); }
    </style>
</head>
<body>
    <div class="card">
        <h2>Change Profile Picture</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg_type == 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php 
            $currentProfile = isset($_SESSION['admin_profile']) && !empty($_SESSION['admin_profile']) 
                              ? 'uploads/' . $_SESSION['admin_profile'] 
                              : 'logo2.png';
            
            // เช็คว่าไฟล์มีอยู่จริงไหม ถ้าไม่มีให้ใช้ logo2.png
            if (!file_exists($currentProfile) && $currentProfile !== 'logo2.png') {
                $currentProfile = 'logo2.png';
            }
        ?>
        <img src="<?= htmlspecialchars($currentProfile) ?>" alt="Current Profile" class="profile-preview">

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_pic" accept="image/*" required>
            <button type="submit">UPLOAD NEW PICTURE</button>
        </form>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>