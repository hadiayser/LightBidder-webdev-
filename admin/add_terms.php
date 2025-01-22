<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $version = $_POST['version'] ?? '';
    $content = $_POST['content'] ?? '';
    $effective_date = $_POST['effective_date'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO terms_conditions (version, content, effective_date, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $version, $content, $effective_date, $is_active);
    
    if ($stmt->execute()) {
        header("Location: manage_terms.php");
        exit();
    } else {
        $error_message = "Error adding document: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Terms Document</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <header>
    <!-- Include fixed header content -->
  </header>
  <div class="wrapper">
    <nav class="sidebar">
      <!-- Include sidebar navigation -->
    </nav>
    <main class="content">
      <h1>Add New Terms Document</h1>
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
        <button type="submit" class="button">Add Document</button>
      </form>
    </main>
  </div>
</body>
</html>
