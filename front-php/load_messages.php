<?php
session_start();
include '../php/conn.php';

// Ensure the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Logged-in user ID

    // Fetch messages for the logged-in user (both sent and received)
    $sql = "SELECT m.message_content, m.timestamp, u.username AS sender 
            FROM internal_messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE m.receiver_id = ? 
            ORDER BY m.timestamp DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            echo "<p><strong>From: " . htmlspecialchars($row['sender']) . "</strong></p>";
            echo "<p>" . nl2br(htmlspecialchars($row['message_content'])) . "</p>";
            echo "<p><small>Sent on: " . $row['timestamp'] . "</small></p><hr>";
        }

        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
} else {
    echo "You must be logged in to view messages.";
}
?>
