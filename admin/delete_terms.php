<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("No document ID provided.");
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM terms_conditions WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: manage_terms.php");
    exit();
} else {
    die("Error deleting document.");
}
$stmt->close();
?>
