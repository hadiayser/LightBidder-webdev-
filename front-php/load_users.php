<?php
session_start();
include '../php/conn.php'; // Database connection file

// Set response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = array();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Logged-in user ID

    // Fetch all users except the logged-in user
    $sql = "SELECT user_id, username, role, profile_picture FROM users WHERE user_id != ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $response['status'] = 'error';
        $response['message'] = 'Error preparing statement: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = array(
                'user_id' => $row['user_id'],
                'username' => htmlspecialchars($row['username']),
                'role' => htmlspecialchars($row['role']), // Include role
                'avatar_url' => !empty($row['profile_picture']) ? '../' . $row['profile_picture'] : '../uploads/profile_pictures/default-avatar.png'
            );
        }
        $response['status'] = 'success';
        $response['users'] = $users;
    } else {
        $response['status'] = 'success';
        $response['users'] = array(); // No other users found
    }

    $stmt->close();
    $conn->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'You must be logged in to view users.';
}

echo json_encode($response);
?>
