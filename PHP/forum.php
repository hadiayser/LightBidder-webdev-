<?php
$servername = "localhost";
$username = "root"; // Update with your MySQL username
$password = ""; // Update with your MySQL password
$dbname = "lightbidder2";

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the action from the request
$action = $_GET['action'];

if ($action === 'addThread') {
    session_start();
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID from the session

    // Insert the new thread into the database, along with the user ID
    $sql = "INSERT INTO threads (title, content, user_id) VALUES ('$title', '$content', '$user_id')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]); // Return success response
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]); // Return error response
    }
} elseif ($action === 'addComment') {
    session_start();
    $threadId = $_POST['thread_id'];
    $text = $_POST['text'];
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID

    // Insert the new comment into the database, including the thread ID and user ID
    $sql = "INSERT INTO thread_comments (thread_id, text, user_id) VALUES ('$threadId', '$text', '$user_id')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]); // Return success response
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]); // Return error response
    }
} elseif ($action === 'getThreads') {
    $threads = [];
    // Fetch all threads, including the first and last names of the users who posted them
    $result = $conn->query("
        SELECT threads.*, users.firstname, users.lastname 
        FROM threads 
        JOIN users ON threads.user_id = users.user_id 
        ORDER BY threads.created_at DESC
    ");

    while ($thread = $result->fetch_assoc()) {
        $threadId = $thread['id'];
        // Fetch comments for each thread, including the first and last names of the users who commented
        $commentsResult = $conn->query("
            SELECT thread_comments.*, users.firstname, users.lastname 
            FROM thread_comments 
            JOIN users ON thread_comments.user_id = users.user_id 
            WHERE thread_id = $threadId 
            ORDER BY thread_comments.created_at ASC
        ");
        $thread['comments'] = $commentsResult->fetch_all(MYSQLI_ASSOC); // Add comments to the thread
        $threads[] = $thread;
    }

    echo json_encode($threads); // Return the threads with their comments
}

$conn->close();
?>

