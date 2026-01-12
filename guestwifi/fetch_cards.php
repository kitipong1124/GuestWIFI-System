<?php
// fetch_cards.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error'=>'Not logged in']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=guestwifi_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cards = [
        'total'   => $pdo->query("SELECT COUNT(*) FROM guest_users")->fetchColumn(),
        'active'  => $pdo->query("SELECT COUNT(*) FROM guest_users WHERE NOW() BETWEEN start_time AND expire_time AND approved = 1")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM guest_users WHERE approved = 0")->fetchColumn(),
        'today'   => $pdo->query("SELECT COUNT(*) FROM guest_users WHERE DATE(start_time) = CURDATE()")->fetchColumn()
    ];

    header('Content-Type: application/json');
    echo json_encode($cards);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
