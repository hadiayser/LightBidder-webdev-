<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM legal_documents WHERE id = ? AND doc_type = 'legal'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: manage_legal.php");
        exit();
    } else {
        die("Error deleting document.");
    }
} else {
    die("No document ID provided.");
}
?>
