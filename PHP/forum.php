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
    $title = $_POST['title'];
    $content = $_POST['content'];

    $sql = "INSERT INTO threads (title, content) VALUES ('$title', '$content')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
} elseif ($action === 'addComment') {
    $threadId = $_POST['thread_id'];
    $text = $_POST['text'];

    $sql = "INSERT INTO thread_comments (thread_id, text) VALUES ('$threadId', '$text')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
} elseif ($action === 'getThreads') {
    $threads = [];
    $result = $conn->query("SELECT * FROM threads ORDER BY created_at DESC");

    while ($thread = $result->fetch_assoc()) {
        $threadId = $thread['id'];
        $commentsResult = $conn->query("SELECT * FROM thread_comments WHERE thread_id = $threadId ORDER BY created_at ASC");
        $thread['comments'] = $commentsResult->fetch_all(MYSQLI_ASSOC);
        $threads[] = $thread;
    }

    echo json_encode($threads);
}

$conn->close();
?>
