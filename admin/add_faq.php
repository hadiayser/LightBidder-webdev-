<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question  = $_POST['question'] ?? '';
    $answer    = $_POST['answer'] ?? '';
    $category  = $_POST['category'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "INSERT INTO faqs (question, answer, category, is_active, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $question, $answer, $category, $is_active);

    if ($stmt->execute()) {
        header("Location: manage_faq.php"); 
        exit();
    } else {
        $error_message = "Error adding FAQ: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New FAQ</title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
<div class="container">
  <h1>Add New FAQ</h1>
  <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
  <form method="POST">
    <div class="form-group">
      <label>Question:</label>
      <input type="text" name="question" required>
    </div>
    <div class="form-group">
      <label>Answer:</label>
      <textarea name="answer" required></textarea>
    </div>
    <div class="form-group">
      <label>Category:</label>
      <input type="text" name="category">
    </div>
    <div class="form-group">
      <label>
        <input type="checkbox" name="is_active" checked> Active
      </label>
    </div>
    <button type="submit" class="save-btn">Add FAQ</button>
    <a href="manage_faq.php" class="button">Cancel</a>
  </form>
</div>
</body>
</html>
