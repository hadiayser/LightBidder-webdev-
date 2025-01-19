<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: manage_faq.php");
        exit();
    } else {
        die("Error deleting FAQ.");
    }
} else {
    die("No FAQ ID provided.");
}
?>
