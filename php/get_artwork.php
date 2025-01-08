<?php
session_start();
require_once('conn.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$artwork_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get the artist_id
$artist_query = "SELECT artist_id FROM artists WHERE user_id = ?";
$stmt = $conn->prepare($artist_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$artist_result = $stmt->get_result();
$artist = $artist_result->fetch_assoc();

if (!$artist) {
    http_response_code(403);
    exit('Not authorized');
}

// Get the artwork details
$query = "SELECT * FROM artworks WHERE artwork_id = ? AND artist_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $artwork_id, $artist['artist_id']);
$stmt->execute();
$result = $stmt->get_result();
$artwork = $result->fetch_assoc();

if (!$artwork) {
    http_response_code(404);
    exit('Artwork not found');
}

header('Content-Type: application/json');
echo json_encode($artwork); 