<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("Legal Notice ID not provided.");
}
$id = (int)$_GET['id'];

// Fetch the legal notice to edit
$stmt = $conn->prepare("SELECT * FROM legal_documents WHERE id = ? AND doc_type = 'legal'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();
$stmt->close();

if (!$document) {
    die("Legal Notice not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $version = $_POST['version'] ?? '';
    $content = $_POST['content'] ?? '';
    $effective_date = $_POST['effective_date'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $updateStmt = $conn->prepare("
      UPDATE legal_documents 
      SET version = ?, content = ?, effective_date = ?, is_active = ? 
      WHERE id = ? AND doc_type = 'legal'
    ");
    $updateStmt->bind_param("sssii", $version, $content, $effective_date, $is_active, $id);
    
    if ($updateStmt->execute()) {
        header("Location: manage_legal.php");
        exit();
    } else {
        $error_message = "Error updating legal notice.";
    }
    $updateStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Legal Notice</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <header><!-- Header content here --></header>
  <div class="wrapper">
    <nav class="sidebar">
      <!-- Sidebar navigation here -->
    </nav>
    <main class="content">
      <h1>Edit Legal Notice</h1>
      <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
      <form method="POST">
        <div class="form-group">
          <label for="version">Version:</label>
          <input type="text" id="version" name="version" value="<?= htmlspecialchars($document['version']); ?>" required />
        </div>
        <div class="form-group">
          <label for="effective_date">Effective Date:</label>
          <input type="date" id="effective_date" name="effective_date" value="<?= htmlspecialchars($document['effective_date']); ?>" />
        </div>
        <div class="form-group">
          <label for="content">Content:</label>
          <textarea id="content" name="content" required><?= htmlspecialchars($document['content']); ?></textarea>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="is_active" <?= $document['is_active'] ? 'checked' : ''; ?> /> Active
          </label>
        </div>
        <button type="submit" class="button">Save Changes</button>
      </form>
    </main>
  </div>
</body>
</html>
