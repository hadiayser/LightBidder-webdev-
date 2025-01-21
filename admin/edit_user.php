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
    $firstname = trim($_POST['firstname']) ?? $user['firstname'];
    $lastname  = trim($_POST['lastname']) ?? $user['lastname'];
    $email     = trim($_POST['email']) ?? $user['email'];
    $username  = trim($_POST['username']) ?? $user['username'];
    $role      = trim($_POST['role']) ?? $user['role'];
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
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit();
    } else {
        $error_message = "Error updating user: " . htmlspecialchars($conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
    <header>
        <h1>Edit User</h1>
    </header>
    <div class="wrapper">
        <nav class="sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_faq.php"><i class="fas fa-question-circle"></i> Manage FAQs</a></li>
                <li><a href="manage_terms.php"><i class="fas fa-file-alt"></i> Manage Terms &amp; Conditions</a></li>
                <li><a href="manage_legal.php"><i class="fas fa-gavel"></i> Manage Legal Notices</a></li>
                <li><a href="../front-php/index.php" class="return-site"><i class="fas fa-home"></i> Return to Site</a></li>
                <!-- Add more navigation links as needed -->
            </ul>
        </nav>
        <main class="content">
            <div class="container">
                <h2>Edit User</h2>
                <?php if (isset($error_message)) { echo "<div class='error-message'>$error_message</div>"; } ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="firstname">First Name:</label>
                        <input type="text" name="firstname" id="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name:</label>
                        <input type="text" name="lastname" id="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="Artist" <?= $user['role'] === 'Artist' ? 'selected' : '' ?>>Artist</option>
                            <option value="Bidder" <?= $user['role'] === 'Bidder' ? 'selected' : '' ?>>Bidder</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">New Password (leave blank to keep current):</label>
                        <input type="password" name="password" id="password" placeholder="Enter new password">
                    </div>
                    <button type="submit" class="save-btn">Save Changes</button>
                    <a href="manage_users.php" class="button cancel-btn">Cancel</a>
                </form>
            </div>
        </main>
    </div>
    <!-- Font Awesome JS (Optional for Icons) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
