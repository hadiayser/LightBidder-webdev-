<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

// Fetch Terms and Conditions document(s)
$stmt = $conn->prepare("SELECT id, version, effective_date FROM legal_documents WHERE doc_type = 'terms'");
$stmt->execute();
$result = $stmt->get_result();
$termsDocs = [];
while($row = $result->fetch_assoc()) {
  $termsDocs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Terms &amp; Conditions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <header>
    <h1>Admin Dashboard</h1>
  </header>
  <div class="wrapper">
    <nav class="sidebar">
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_faq.php">Manage FAQs</a></li>
        <li><a href="manage_terms.php">Manage Terms &amp; Conditions</a></li>
        <li><a href="manage_legal.php">Manage Legal Notices</a></li>
      </ul>
    </nav>
    <main class="content">
      <h1>Manage Terms &amp; Conditions</h1>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Version</th>
            <th>Effective Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($termsDocs as $doc): ?>
          <tr>
            <td><?= htmlspecialchars($doc['id']); ?></td>
            <td><?= htmlspecialchars($doc['version']); ?></td>
            <td><?= htmlspecialchars($doc['effective_date']); ?></td>
            <td>
              <a href="edit_terms.php?id=<?= htmlspecialchars($doc['id']) ?>">Edit</a>
              <a href="delete_terms.php?id=<?= htmlspecialchars($doc['id']) ?>" onclick="return confirm('Delete this document?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="add_terms.php" class="button">Add New Terms Document</a>
    </main>
  </div>
</body>
</html>
