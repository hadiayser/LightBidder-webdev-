<?php
session_start();
require_once('../php/conn.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ./front-php/HTML/web.html");
    exit();
}

// Fetch user data to verify role
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    // Redirect non-admins away
    header("Location: ./front-php/index.php");
    exit();
}
?>
