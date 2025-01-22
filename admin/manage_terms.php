<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

// Fetch all Terms & Conditions documents
$stmt = $conn->prepare("SELECT id, version, effective_date, is_active FROM terms_conditions ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$termsDocs = [];
while($row = $result->fetch_assoc()) {
    $termsDocs[] = $row;
}
$stmt->close();
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
      <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_faq.php">Manage FAQs</a></li>
                <li><a href="manage_terms.php">Manage Terms &amp; Conditions</a></li>
                <li><a href="manage_legal.php">Manage Legal Notices</a></li>
                <li><a href="manage_forum_threads.php">Manage Forum</a></li>
                <li><a href="../front-php/index.php" class="return-site">Return to Site</a></li>
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
            <th>Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($termsDocs as $doc): ?>
          <tr>
            <td><?= htmlspecialchars($doc['id']); ?></td>
            <td><?= htmlspecialchars($doc['version']); ?></td>
            <td><?= htmlspecialchars($doc['effective_date']); ?></td>
            <td><?= $doc['is_active'] ? 'Yes' : 'No'; ?></td>
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
