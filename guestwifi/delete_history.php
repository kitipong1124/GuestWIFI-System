<?php
session_start();
header('Content-Type: application/json'); // ✅ บังคับให้ส่งค่ากลับเป็น JSON
require_once __DIR__ . '/../config.php';  // ✅ เรียก config ถอยหลัง 1 ชั้น

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: กรุณาเข้าสู่ระบบ']);
    exit;
}

// 2. ตรวจสอบว่าเป็น Method POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;

    // คำนวณวันที่ตัดยอด (Cutoff Date) ด้วย PHP เพื่อความชัวร์
    // เช่น ถ้า $days = 30 จะได้วันที่ย้อนหลังไป 30 วันจากตอนนี้
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Database Connection Failed: " . $conn->connect_error);
        }
        $conn->set_charset(DB_CHARSET);

        // 3. คำสั่ง SQL ลบข้อมูลที่เก่ากว่าวันที่กำหนด
        $stmt = $conn->prepare("DELETE FROM guest_users WHERE expire_time < ?");
        $stmt->bind_param("s", $cutoff_date);
        
        if ($stmt->execute()) {
            $deleted_count = $stmt->affected_rows;
            echo json_encode([
                'status' => 'success', 
                'message' => "✅ ลบข้อมูลสำเร็จ: $deleted_count รายการ (เก่ากว่า $cutoff_date)"
            ]);
        } else {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>