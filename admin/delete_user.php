<?php
require_once('admin_auth.php');  // Ensures user is admin
require_once('../php/conn.php'); // Database connection

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);

    // Prevent deletion of currently logged-in admin, or protect special accounts
    if ($userId === $_SESSION['user_id']) {
        die("You cannot delete your own account.");
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Redirect back to the user management page after deletion
        header("Location: manage_users.php");
        exit();
    } else {
        echo "Error deleting user: " . $conn->error;
    }
} else {
    echo "No user ID provided.";
}
?>
