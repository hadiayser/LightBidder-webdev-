<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content        = $_POST['content'] ?? '';
    $version        = $_POST['version'] ?? '';
    $effective_date = $_POST['effective_date'] ?? null;

    $doc_type = 'terms'; // Set doc_type for Terms & Conditions

    $sql = "INSERT INTO legal_documents (doc_type, content, version, effective_date, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $doc_type, $content, $version, $effective_date);

    if ($stmt->execute()) {
        header("Location: manage_terms.php"); 
        exit();
    } else {
        $error_message = "Error adding Terms Document: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Terms Document</title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
<div class="container">
  <h1>Add New Terms Document</h1>
  <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
  <form method="POST">
    <div class="form-group">
      <label>Version:</label>
      <input type="text" name="version" required>
    </div>
    <div class="form-group">
      <label>Effective Date:</label>
      <input type="date" name="effective_date">
    </div>
    <div class="form-group">
      <label>Content:</label>
      <textarea name="content" required></textarea>
    </div>
    <button type="submit" class="save-btn">Add Terms Document</button>
    <a href="manage_terms.php" class="button">Cancel</a>
  </form>
</div>
</body>
</html>
