<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("No Legal Notice ID provided.");
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM legal_documents WHERE id = ? AND doc_type = 'legal'");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: manage_legal.php");
    exit();
} else {
    die("Error deleting legal notice.");
}
$stmt->close();
?>
