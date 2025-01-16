<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

// Fetch all users for listing
$stmt = $conn->prepare("SELECT user_id, firstname, lastname, email, username, role FROM users");
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while($row = $result->fetch_assoc()) {
  $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Users</title>
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
        <!-- Additional links as needed -->
      </ul>
    </nav>
    <main class="content">
      <h1>Manage Users</h1>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['user_id']); ?></td>
            <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
            <td><?= htmlspecialchars($user['email']); ?></td>
            <td><?= htmlspecialchars($user['username']); ?></td>
            <td><?= htmlspecialchars($user['role']); ?></td>
            <td>
              <a href="edit_user.php?id=<?= htmlspecialchars($user['user_id']) ?>">Edit</a>
              <a href="delete_user.php?id=<?= htmlspecialchars($user['user_id']) ?>" onclick="return confirm('Delete this user?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="add_user.php" class="button">Add New User</a>
    </main>
  </div>
</body>
</html>
