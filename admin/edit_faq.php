<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("FAQ ID not provided.");
}
$id = intval($_GET['id']);

// Fetch existing FAQ data
$stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$faq = $result->fetch_assoc();

if (!$faq) {
    die("FAQ not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $_POST['question'] ?? $faq['question'];
    $answer = $_POST['answer'] ?? $faq['answer'];
    $category = $_POST['category'] ?? $faq['category'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $update = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, category = ?, is_active = ? WHERE id = ?");
    $update->bind_param("sssii", $question, $answer, $category, $is_active, $id);
    if ($update->execute()) {
        header("Location: manage_faq.php");
        exit();
    } else {
        $error_message = "Error updating FAQ.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit FAQ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <header>...</header>
  <div class="wrapper">
    <nav class="sidebar">...</nav>
    <main class="content">
      <h1>Edit FAQ</h1>
      <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
      <form method="POST">
        <div class="form-group">
          <label>Question:</label>
          <input type="text" name="question" value="<?= htmlspecialchars($faq['question']); ?>" required>
        </div>
        <div class="form-group">
          <label>Answer:</label>
          <textarea name="answer" required><?= htmlspecialchars($faq['answer']); ?></textarea>
        </div>
        <div class="form-group">
          <label>Category:</label>
          <input type="text" name="category" value="<?= htmlspecialchars($faq['category']); ?>">
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="is_active" <?= $faq['is_active'] ? 'checked' : ''; ?>> Active
          </label>
        </div>
        <button type="submit" class="button">Save Changes</button>
      </form>
    </main>
  </div>
</body>
</html>
