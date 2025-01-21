<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("Thread ID not provided.");
}
$thread_id = (int)$_GET['id'];

// Fetch thread details
$stmt = $conn->prepare("SELECT * FROM threads WHERE id = ?");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$result = $stmt->get_result();
$thread = $result->fetch_assoc();
$stmt->close();

if (!$thread) {
    die("Thread not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    $updateStmt = $conn->prepare("UPDATE threads SET title = ?, content = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $title, $content, $thread_id);
    if ($updateStmt->execute()) {
        header("Location: manage_forum_threads.php");
        exit();
    } else {
        $error_message = "Error updating thread.";
    }
    $updateStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Forum Thread</title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <header>
    <!-- Header content -->
  </header>
  <div class="wrapper">
    <nav class="sidebar">
      <!-- Sidebar navigation -->
    </nav>
    <main class="content">
      <h1>Edit Forum Thread</h1>
      <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
      <form method="POST">
        <div class="form-group">
          <label for="title">Thread Title:</label>
          <input type="text" id="title" name="title" value="<?= htmlspecialchars($thread['title']); ?>" required />
        </div>
        <div class="form-group">
          <label for="content">Content:</label>
          <textarea id="content" name="content" required><?= htmlspecialchars($thread['content']); ?></textarea>
        </div>
        <button type="submit" class="button">Save Changes</button>
      </form>
    </main>
  </div>
</body>
</html>
