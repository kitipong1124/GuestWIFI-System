<?php
// export_csv.php (ปรับปรุงให้รองรับฟิลด์ใหม่ IP และ MAC พร้อมภาษาไทยเต็มรูปแบบ)

$conn = new mysqli("localhost", "root", "", "guestwifi_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ตั้งค่า header สำหรับดาวน์โหลดไฟล์ CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="guestwifi_users.csv"');

// เขียน BOM เพื่อให้ Excel รองรับ UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// เขียนหัวตาราง (เพิ่ม IP Address และ MAC Address)
fputcsv($output, ['ชื่อจริง', 'นามสกุล', 'อีเมล', 'บริษัท', 'ประเภทอุปกรณ์', 'เวลาเริ่ม', 'หมดอายุ', 'Username', 'Password', 'สถานะ', 'IP Address', 'MAC Address']);

// ดึงข้อมูลผู้ใช้ (เพิ่ม ip_address และ mac_address)
$sql = "SELECT first_name, last_name, email, company, device_type, start_time, expire_time, username, password, approved, ip_address, mac_address 
        FROM guest_users 
        ORDER BY start_time DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $status = 'รออนุมัติ';
    if ($row['approved'] == 1) {
        $status = 'อนุมัติ';
    } elseif ($row['approved'] == 2) {
        $status = 'ไม่อนุมัติ';
    }

    fputcsv($output, [
        $row['first_name'],
        $row['last_name'],
        $row['email'],
        $row['company'],
        $row['device_type'],
        $row['start_time'],
        $row['expire_time'],
        $row['username'],
        $row['password'],
        $status,
        $row['ip_address'],
        $row['mac_address']
    ]);
}

fclose($output);
exit;
