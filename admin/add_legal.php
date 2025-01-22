<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $version = $_POST['version'] ?? '';
    $content = $_POST['content'] ?? '';
    $effective_date = $_POST['effective_date'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    // Insert new legal notice with doc_type 'legal'
    $stmt = $conn->prepare("
      INSERT INTO legal_documents (doc_type, version, content, effective_date, is_active) 
      VALUES ('legal', ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssi", $version, $content, $effective_date, $is_active);
    
    if ($stmt->execute()) {
        header("Location: manage_legal.php");
        exit();
    } else {
        $error_message = "Error adding legal notice: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Legal Notice</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <header></header>
  <div class="wrapper">
    <nav class="sidebar">
    </nav>
    <main class="content">
      <h1>Add New Legal Notice</h1>
      <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
      <form method="POST">
        <div class="form-group">
          <label for="version">Version:</label>
          <input type="text" id="version" name="version" required />
        </div>
        <div class="form-group">
          <label for="effective_date">Effective Date:</label>
          <input type="date" id="effective_date" name="effective_date" />
        </div>
        <div class="form-group">
          <label for="content">Content:</label>
          <textarea id="content" name="content" required></textarea>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="is_active" checked /> Active
          </label>
        </div>
        <button type="submit" class="button">Add Legal Notice</button>
      </form>
    </main>
  </div>
</body>
</html>
