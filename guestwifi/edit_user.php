<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = intval($_POST['id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $company    = trim($_POST['company'] ?? '');

    if(!$id || !$first_name || !$last_name || !$email || !$company){
        die("Missing data");
    }

    $conn = new mysqli("localhost", "root", "", "guestwifi_db");
    if($conn->connect_error) die("Database connection failed");
    $conn->set_charset("utf8mb4");

    $stmt = $conn->prepare("UPDATE guest_users SET first_name=?, last_name=?, email=?, company=? WHERE id=?");
    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $company, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "User info updated successfully ✅";
}
?>
