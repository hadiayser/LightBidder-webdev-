<?php
require_once('admin_auth.php');  // Ensures only admins can access
require_once('../php/conn.php');

// Fetch all forum threads along with user info
$query = "
SELECT threads.*, users.firstname, users.lastname 
FROM threads 
JOIN users ON threads.user_id = users.user_id 
ORDER BY threads.created_at DESC
";
$result = $conn->query($query);
$threads = [];
while($row = $result->fetch_assoc()) {
    $threads[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Forum Threads</title>
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
      <h1>Manage Forum Threads</h1>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($threads as $thread): ?>
          <tr>
            <td><?= htmlspecialchars($thread['id']); ?></td>
            <td><?= htmlspecialchars($thread['title']); ?></td>
            <td><?= htmlspecialchars($thread['firstname'] . ' ' . $thread['lastname']); ?></td>
            <td><?= htmlspecialchars($thread['created_at']); ?></td>
            <td>
              <a href="edit_forum_thread.php?id=<?= htmlspecialchars($thread['id']); ?>">Edit</a>
              <a href="delete_forum_thread.php?id=<?= htmlspecialchars($thread['id']); ?>" 
                 onclick="return confirm('Delete this thread?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="add_forum_thread.php" class="button">Add New Thread</a>
    </main>
  </div>
</body>
</html>
