<?php
date_default_timezone_set('Asia/Bangkok');

// ---------------- Database Config ----------------
$host = 'localhost';
$db   = 'guestwifi_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $folder = 'C:/xampp/htdocs/TEST2/log';
    $timestampFile = $folder . '/last_export_timestamp.txt';
    $lastDeleteFile = $folder . '/last_delete_timestamp.txt';

    if (!file_exists($timestampFile)) {
        echo "⚠️ No export timestamp found.\n";
        exit;
    }

    $lastExport = (int)file_get_contents($timestampFile);
    $lastDelete = file_exists($lastDeleteFile) ? (int)file_get_contents($lastDeleteFile) : 0;

    // ถ้าผ่านไป >= 86400 วินาที (1 วัน)
    if (time() - $lastExport >= 86400 && time() - $lastDelete >= 86400) {
        $pdo->exec("DELETE FROM guest_users");
        file_put_contents($lastDeleteFile, time());
        echo "🗑️ Data cleared successfully after 1 day.\n";
    } else {
        echo "ℹ️ No action — less than 1 day since last export.\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
