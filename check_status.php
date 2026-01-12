<?php
$id = intval($_GET['id'] ?? 0);
$mysqli = new mysqli("localhost", "root", "", "guestwifi_db");
$mysqli->set_charset("utf8mb4");

$stmt = $mysqli->prepare("SELECT approved, remark, username, password, link_login_only FROM guest_users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$mysqli->close();

if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(["approved"=>0]);
}
