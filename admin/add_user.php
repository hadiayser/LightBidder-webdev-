<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input
    $firstname = $_POST['firstname'] ?? '';
    $lastname  = $_POST['lastname'] ?? '';
    $email     = $_POST['email'] ?? '';
    $username  = $_POST['username'] ?? '';
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'user';  // Default role as user if not specified

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare INSERT query
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, username, password, role, date_registered) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $firstname, $lastname, $email, $username, $hashed_password, $role);

    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit();
    } else {
        $error_message = "Error adding user: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New User</title>
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
<div class="container">
  <h1>Add New User</h1>
  <?php if (isset($error_message)) { echo "<p class='error-message'>$error_message</p>"; } ?>
  <form method="POST">
    <div class="form-group">
      <label>First Name:</label>
      <input type="text" name="firstname" required>
    </div>
    <div class="form-group">
      <label>Last Name:</label>
      <input type="text" name="lastname" required>
    </div>
    <div class="form-group">
      <label>Email:</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-group">
      <label>Username:</label>
      <input type="text" name="username" required>
    </div>
    <div class="form-group">
      <label>Password:</label>
      <input type="password" name="password" required>
    </div>
    <div class="form-group">
      <label>Role:</label>
      <select name="role">
        <option value="user">User</option>
        <option value="Artist">Artist</option>
        <option value="Bidder">Bidder</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <button type="submit" class="save-btn">Add User</button>
    <a href="manage_users.php" class="button">Cancel</a>
  </form>
</div>
</body>
</html>
