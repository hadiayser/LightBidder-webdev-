<?php
session_start();
require_once('conn.php'); // Adjust the path if necessary

if (isset($_POST['auction_id']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $auction_id = (int)$_POST['auction_id'];

    // Prepare the SQL statement to remove the favorite
    $query = "DELETE FROM favorites WHERE user_id = ? AND auction_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $auction_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
} else {
    echo json_encode(['status' => 'error']);
}
?> 