<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$userId = intval($_GET['id']);

// Fetch existing user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input
    $firstname = $_POST['firstname'] ?? $user['firstname'];
    $lastname  = $_POST['lastname'] ?? $user['lastname'];
    $email     = $_POST['email'] ?? $user['email'];
    $username  = $_POST['username'] ?? $user['username'];
    $role      = $_POST['role'] ?? $user['role'];
    $password  = $_POST['password'] ?? '';

    // Begin building update query
    $updateFields = "firstname = ?, lastname = ?, email = ?, username = ?, role = ?";
    $params = [$firstname, $lastname, $email, $username, $role];
    $types = "sssss";

    // If password is provided, update it as well
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateFields .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $params[] = $userId;
    $types .= "i";

    // Prepare update query
    $sql = "UPDATE users SET $updateFields WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit();
    } else {
        $error_message = "Error updating user: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit User</title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
<div class="container">
  <h1>Edit User</h1>
  <?php if (isset($error_message)) { echo "<p class='error-message'>$error_message</p>"; } ?>
  <form method="POST">
    <div class="form-group">
      <label>First Name:</label>
      <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required>
    </div>
    <div class="form-group">
      <label>Last Name:</label>
      <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required>
    </div>
    <div class="form-group">
      <label>Email:</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="form-group">
      <label>Username:</label>
      <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>
    <div class="form-group">
      <label>Role:</label>
      <select name="role">
        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
        <option value="Artist" <?= $user['role'] === 'Artist' ? 'selected' : '' ?>>Artist</option>
        <option value="Bidder" <?= $user['role'] === 'Bidder' ? 'selected' : '' ?>>Bidder</option>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </div>
    <div class="form-group">
      <label>New Password (leave blank to keep current):</label>
      <input type="password" name="password">
    </div>
    <button type="submit" class="save-btn">Save Changes</button>
    <a href="manage_users.php" class="button">Cancel</a>
  </form>
</div>
</body>
</html>
