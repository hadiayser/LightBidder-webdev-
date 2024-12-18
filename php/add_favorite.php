<?php
session_start();
require_once('../php/conn.php');

if (isset($_POST['auction_id']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $auction_id = (int)$_POST['auction_id'];

    // Check if the favorite already exists
    $query = "SELECT * FROM favorites WHERE user_id = ? AND auction_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert new favorite
        $insertQuery = "INSERT INTO favorites (user_id, auction_id) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ii", $user_id, $auction_id);
        $insertStmt->execute();
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'already_favorited']);
    }
} else {
    echo json_encode(['status' => 'error']);
}
?> 