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
    $sender_id = $_SESSION['user_id']; // Logged-in user ID
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $message_content = isset($_POST['message_content']) ? trim($_POST['message_content']) : '';

    $response['sender_id'] = $sender_id;
    $response['receiver_id'] = $receiver_id;
    $response['message_content'] = $message_content;

    // Validate receiver_id and message_content
    if ($receiver_id <= 0) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid receiver selected.';
        echo json_encode($response);
        exit;
    }

    if (empty($message_content)) {
        $response['status'] = 'error';
        $response['message'] = 'Message content cannot be empty.';
        echo json_encode($response);
        exit;
    }

    // Verify that receiver_id exists in the users table
    $verify_sql = "SELECT user_id FROM users WHERE user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    if ($verify_stmt === false) {
        $response['status'] = 'error';
        $response['message'] = 'Error preparing verification statement: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $verify_stmt->bind_param("i", $receiver_id);
    $verify_stmt->execute();
    $verify_stmt->store_result();

    if ($verify_stmt->num_rows == 0) {
        $response['status'] = 'error';
        $response['message'] = 'Receiver does not exist.';
        $verify_stmt->close();
        echo json_encode($response);
        exit;
    }
    $verify_stmt->close();

    // Prepare SQL query to insert the message into the database
    $sql = "INSERT INTO internal_messages (sender_id, receiver_id, message_content) 
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $response['status'] = 'error';
        $response['message'] = 'Error preparing insert statement: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message_content);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Message sent successfully!';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error inserting message: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'You must be logged in to send a message.';
}

echo json_encode($response);
?>
