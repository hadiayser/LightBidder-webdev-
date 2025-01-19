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

    // Check if 'user_id' is provided via GET to identify the conversation partner
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $other_user_id = intval($_GET['user_id']); // The selected user ID

        // Verify that the other_user_id exists
        $verify_sql = "SELECT user_id, username, profile_picture FROM users WHERE user_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        if ($verify_stmt === false) {
            $response['status'] = 'error';
            $response['message'] = 'Error preparing verification statement: ' . $conn->error;
            echo json_encode($response);
            exit;
        }
        $verify_stmt->bind_param("i", $other_user_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();

        if ($result->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = 'Conversation partner does not exist.';
            $verify_stmt->close();
            echo json_encode($response);
            exit;
        }

        $partner = $result->fetch_assoc();
        $partner_username = htmlspecialchars($partner['username']);
        $partner_avatar_url = 'uploads/profile_pictures/default-avatar.png'; // Default avatar

        if (!empty($partner['profile_picture']) && file_exists('../' . $partner['profile_picture'])) {
            $partner_avatar_url = htmlspecialchars($partner['profile_picture']);
        }

        $verify_stmt->close();

        // Fetch the logged-in user's profile_picture
        $user_sql = "SELECT profile_picture FROM users WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_sql);
        if ($user_stmt === false) {
            $response['status'] = 'error';
            $response['message'] = 'Error preparing user profile statement: ' . $conn->error;
            echo json_encode($response);
            exit;
        }
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();

        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $user_avatar_url = 'uploads/profile_pictures/default-avatar.png'; // Default avatar

            if (!empty($user_data['profile_picture']) && file_exists('../' . $user_data['profile_picture'])) {
                $user_avatar_url = htmlspecialchars($user_data['profile_picture']);
            }
        } else {
            $user_avatar_url = 'uploads/profile_pictures/default-avatar.png'; // Fallback
        }

        $user_stmt->close();

        // Correct SQL query with swapped parameters
        $sql = "SELECT m.message_content, m.timestamp, m.sender_id, u.username AS sender, u.profile_picture 
                FROM internal_messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE (m.receiver_id = ? AND m.sender_id = ?) 
                   OR (m.receiver_id = ? AND m.sender_id = ?)
                ORDER BY m.timestamp ASC";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $response['status'] = 'error';
            $response['message'] = 'Error preparing message retrieval statement: ' . $conn->error;
            echo json_encode($response);
            exit;
        }

        // Corrected binding: user_id, other_user_id, other_user_id, user_id
        $stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = array(); // Array to hold messages

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Determine if the message was sent or received
                if ($row['sender_id'] == $user_id) {
                    $message_class = 'sent';
                    $sender_name = 'You';
                    $sender_avatar = $user_avatar_url; // Use logged-in user's avatar
                } else {
                    $message_class = 'received';
                    $sender_name = htmlspecialchars($row['sender']);
                    $sender_avatar = 'uploads/profile_pictures/default-avatar.png'; // Default avatar

                    if (!empty($row['profile_picture']) && file_exists('../' . $row['profile_picture'])) {
                        $sender_avatar = htmlspecialchars($row['profile_picture']);
                    }
                }

                // Sanitize message content
                $message_content = htmlspecialchars($row['message_content']);

                // Format timestamp
                $timestamp = date("F j, Y, g:i a", strtotime($row['timestamp']));

                $messages[] = array(
                    'message_class' => $message_class,
                    'sender_name' => $sender_name,
                    'message_content' => $message_content,
                    'timestamp' => $timestamp,
                    'sender_avatar' => $sender_avatar
                );
            }

            $response['status'] = 'success';
            $response['messages'] = $messages;
            $response['chat_with_name'] = $partner_username;
            $response['avatar_url'] = $partner_avatar_url;
        } else {
            $response['status'] = 'success';
            $response['messages'] = array(); // No messages found
            $response['chat_with_name'] = $partner_username;
            $response['avatar_url'] = $partner_avatar_url;
        }

        $stmt->close();
        $conn->close();
    } else {
        $response['status'] = 'error';
        $response['message'] = 'No conversation partner specified.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'You must be logged in to view messages.';
}

echo json_encode($response);
?>
