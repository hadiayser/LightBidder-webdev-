<?php
session_start();
require_once('../php/conn.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile updates (personal/account information)
    if (isset($_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['username'])) {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        $username = $_POST['username'];

        // Update user information
        $update_stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, username = ? WHERE user_id = ?");
        $update_stmt->bind_param("ssssi", $firstname, $lastname, $email, $username, $user_id);

        if ($update_stmt->execute()) {
            // Update session variables
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile.";
        }
    }

    // Handle role change
    if (isset($_POST['role'])) {
        $newRole = $_POST['role'];
        $updateRoleQuery = "UPDATE users SET role = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateRoleQuery);
        $updateStmt->bind_param("si", $newRole, $user_id);
        $updateStmt->execute();
    }

    // Handle password change
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $updatePasswordQuery = "UPDATE users SET password = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updatePasswordQuery);
        $updateStmt->bind_param("si", $newPassword, $user_id);
        $updateStmt->execute();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../css/css.css?v7">
    <link rel="stylesheet" href="../css/profile.css?v1">
    <link rel="stylesheet" href="../css/auctions.css?v5">
</head>
<body>
    <header>
        <div>
            <div class="nav-logo">
                <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
            </div>
            <ul id="homepageNav">
            <li><a href="index.php">Home</a></li>
          <!-- <li><a href="artworks.html">Artwork</a></li> -->
          <li><a href="collections.php">Collections</a></li>
          <li><a href="artists.php">Artists</a></li>
          <li><a href="auctions.php">Auctions</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="forum.php">Forum</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <button class="dropbtn">
                            <div class="user-profile">
                                <img src="../img/—Pngtree—user avatar placeholder black_6796227.png" alt="Profile" class="profile-img">
                                <span><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
                            </div>
                            <i class="arrow down"></i>
                        </button>
                        <div class="dropdown-content">
                    <a href="profile.php">My Profile</a>
                    <a href="my-collections.php">My Collections</a>
                    <a href="my_favorites.php">My Favorites</a>
                    <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
                </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-picture-container">
                <img src="../img/—Pngtree—user avatar placeholder black_6796227.png" alt="Profile" class="profile-picture">
                <div class="edit-overlay">
                    <span>Change Photo</span>
                </div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h1>
                <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-content">
            <div class="profile-section">
                <h2>Personal Information</h2>
                <div class="info-display">
                    <div class="info-group">
                        <span class="info-label">First Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['firstname']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Last Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['lastname']); ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-section">
                <h2>Account Information</h2>
                <div class="info-display">
                    <div class="info-group">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Username</span>
                        <span class="info-value">@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Password Display -->
            <div class="profile-section password-display">
                <h2>Password</h2>
                <div class="info-display">
                    <div class="info-group">
                        <span class="info-label">Password</span>
                        <span class="info-value">********</span>
                    </div>
                </div>
            </div>

            <!-- Role Display -->
            <div class="profile-section role-display">
                <h2>Role</h2>
                <div class="info-display">
                    <div class="info-group">
                        <span class="info-label">User Role</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Button -->
            <button class="edit-profile-btn" onclick="toggleEditMode()">Edit Profile</button>
        </div>

        <!-- Edit Form -->
        <form method="POST" class="edit-form" id="editForm" style="display: none;">
            <div class="profile-section">
                <h2>Edit Personal Information</h2>
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                </div>
            </div>

            <div class="profile-section">
                <h2>Edit Account Information</h2>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            </div>

            <div class="profile-section role-form">
                <h2>Role</h2>
                <label for="role">User Role:</label>
                <select name="role" id="role">
                    <option value="Artist" <?php echo $user['role'] === 'Artist' ? 'selected' : ''; ?>>Artist</option>
                    <option value="Bidder" <?php echo $user['role'] === 'Bidder' ? 'selected' : ''; ?>>Bidder</option>
                </select>
            </div>

            <div class="profile-section password-form">
                <h2>Password</h2>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password">
                </div>
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
            <button type="button" class="cancel-btn" onclick="toggleEditMode()">Cancel</button>
        </form>
    </div>

    <script>
        function toggleEditMode() {
            const editForm = document.getElementById('editForm');
            const profileContent = document.querySelector('.profile-content');
            
            if (editForm.style.display === 'none') {
                editForm.style.display = 'grid';
                profileContent.style.display = 'none';
            } else {
                editForm.style.display = 'none';
                profileContent.style.display = 'grid';
            }
        }

        // Include your dropdown JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.querySelector('.nav-item.dropdown');
            const dropbtn = document.querySelector('.dropbtn');

            if (dropdown && dropbtn) {
                dropbtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                });

                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });

                dropdown.querySelector('.dropdown-content').addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>
</body>
</html>
