<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

// Fetch FAQs
$stmt = $conn->prepare("SELECT id, question, is_active FROM faqs");
$stmt->execute();
$result = $stmt->get_result();
$faqs = [];
while($row = $result->fetch_assoc()) {
  $faqs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage FAQs</title>
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
      <h1>Manage FAQs</h1>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Question</th>
            <th>Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($faqs as $faq): ?>
          <tr>
            <td><?= htmlspecialchars($faq['id']); ?></td>
            <td><?= htmlspecialchars($faq['question']); ?></td>
            <td><?= $faq['is_active'] ? 'Yes' : 'No'; ?></td>
            <td>
              <a href="edit_faq.php?id=<?= htmlspecialchars($faq['id']) ?>">Edit</a>
              <a href="delete_faq.php?id=<?= htmlspecialchars($faq['id']) ?>" onclick="return confirm('Delete this FAQ?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="add_faq.php" class="button">Add New FAQ</a>
    </main>
  </div>
</body>
</html>
