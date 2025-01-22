<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO threads (title, content, user_id) VALUES (?, ?, ?)");
    $admin_user_id = $_SESSION['user_id']; 
    $stmt->bind_param("ssi", $title, $content, $admin_user_id);
    
    if ($stmt->execute()) {
        header("Location: manage_forum_threads.php");
        exit();
    } else {
        $error_message = "Error adding thread: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Forum Thread</title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <main class="content">
    <h1>Add New Forum Thread</h1>
    <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
    <form method="POST">
      <div class="form-group">
        <label for="title">Thread Title:</label>
        <input type="text" id="title" name="title" required />
      </div>
      <div class="form-group">
        <label for="content">Content:</label>
        <textarea id="content" name="content" required></textarea>
      </div>
      <button type="submit" class="button">Add Thread</button>
    </form>
  </main>
</body>
</html>
