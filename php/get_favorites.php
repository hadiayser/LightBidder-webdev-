<?php
session_start();
require_once('../php/conn.php');

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $query = "SELECT a.* FROM favorites f JOIN auctions a ON f.auction_id = a.auction_id WHERE f.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }

    echo json_encode($favorites);
} else {
    echo json_encode([]);
}
?> 