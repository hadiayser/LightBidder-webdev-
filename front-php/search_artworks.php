<?php
// search_artworks.php
session_start();
include('../php/conn.php'); // Include database connection

// Set response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = array();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['status'] = 'error';
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit();
}

// Get the search query
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($searchQuery)) {
    $response['status'] = 'error';
    $response['message'] = 'Empty search query';
    echo json_encode($response);
    exit();
}

// Prepare SQL statement with LIKE clauses for title and description
$sql = "
    SELECT a.*, c.name as collection_name
    FROM artworks a
    INNER JOIN collections c ON a.collection_id = c.collection_id
    WHERE a.title LIKE CONCAT('%', ?, '%') OR a.description LIKE CONCAT('%', ?, '%')
    ORDER BY a.title ASC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $response['status'] = 'error';
    $response['message'] = 'Error preparing statement: ' . $conn->error;
    echo json_encode($response);
    exit();
}

$stmt->bind_param("ss", $searchQuery, $searchQuery);
$stmt->execute();
$result = $stmt->get_result();

$artworks = array();
while ($row = $result->fetch_assoc()) {
    $artworks[] = array(
        'collection_id' => $row['collection_id'],
        'title' => htmlspecialchars($row['title']),
        'image_url' => !empty($row['image_url']) ? '../' . $row['image_url'] : '../img/placeholder.jpg',
        'collection_name' => htmlspecialchars($row['collection_name'])
    );
}

$response['status'] = 'success';
$response['data'] = $artworks;

$stmt->close();
$conn->close();

echo json_encode($response);
?>
