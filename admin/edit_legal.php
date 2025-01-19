<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("Document ID not provided.");
}
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM legal_documents WHERE id = ? AND doc_type = 'legal'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    die("Document not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? $doc['content'];
    $version = $_POST['version'] ?? $doc['version'];
    $effective_date = $_POST['effective_date'] ?? $doc['effective_date'];

    $update = $conn->prepare("UPDATE legal_documents SET content = ?, version = ?, effective_date = ? WHERE id = ?");
    $update->bind_param("sssi", $content, $version, $effective_date, $id);
    if ($update->execute()) {
        header("Location: manage_legal.php");
        exit();
    } else {
        $error_message = "Error updating document.";
    }
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
  <!-- Include header and sidebar -->
  <header>...</header>
  <div class="wrapper">
    <nav class="sidebar">...</nav>
    <main class="content">
      <h1>Edit Legal Notice</h1>
      <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
      <form method="POST">
        <div class="form-group">
          <label>Version:</label>
          <input type="text" name="version" value="<?= htmlspecialchars($doc['version']); ?>" required>
        </div>
        <div class="form-group">
          <label>Effective Date:</label>
          <input type="date" name="effective_date" value="<?= htmlspecialchars($doc['effective_date']); ?>">
        </div>
        <div class="form-group">
          <label>Content:</label>
          <textarea name="content" required><?= htmlspecialchars($doc['content']); ?></textarea>
        </div>
        <button type="submit" class="button">Save Changes</button>
      </form>
    </main>
  </div>
</body>
</html>
