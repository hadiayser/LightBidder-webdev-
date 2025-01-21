<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("No thread ID provided.");
}
$thread_id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM threads WHERE id = ?");
$stmt->bind_param("i", $thread_id);
if ($stmt->execute()) {
    header("Location: manage_forum_threads.php");
    exit();
} else {
    die("Error deleting thread.");
}
$stmt->close();
?>
