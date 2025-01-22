<?php
// profile.php

session_start();
require_once('../php/conn.php');

// Define the upload directory
define('UPLOAD_DIR', '../uploads/profile_pictures/');

/**
 * Handles image uploads for the user’s profile picture.
 *
 * @param array $file The uploaded file from $_FILES.
 * @return string|false A relative path (e.g., 'uploads/profile_pictures/filename.jpg') or false on failure.
 */
function handleImageUpload($file) {
    $maxSize = 20 * 1024 * 1024; // 20MB
    if ($file['size'] > $maxSize) {
        $_SESSION['page2_errors']['profile_picture_err'] = 'File size is too large';
        return false;
    }

    // Allowed file extensions and MIME types
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $allowedMimeTypes  = ['image/jpeg', 'image/png', 'image/gif'];

    $tempLocation = $file['tmp_name'];
    $fileName     = $file['name'];
    $fileExtension= strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate file extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        $_SESSION['page2_errors']['profile_picture_err'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
        return false;
    }

    // Validate MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tempLocation);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        $_SESSION['page2_errors']['profile_picture_err'] = 'Invalid file MIME type.';
        return false;
    }

    // Generate a unique filename
    $uniqueName = uniqid('profile_') . '.' . $fileExtension;

    // Ensure upload directory exists
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Check if upload directory is writable
    if (!is_writable(UPLOAD_DIR)) {
        $_SESSION['page2_errors']['profile_picture_err'] = "The directory is not writable!";
        return false;
    }

    // Move the file
    $fullPath = UPLOAD_DIR . $uniqueName;
    if (move_uploaded_file($tempLocation, $fullPath)) {
        // Return the relative path (for storing in DB)
        return 'uploads/profile_pictures/' . $uniqueName;
    } else {
        $_SESSION['page2_errors']['profile_picture_err'] = 'Failed to upload file';
        return false;
    }
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html"); // Redirect to login page
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Update Personal & Account Info
    if (isset($_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['username'])) {
        $firstname = trim($_POST['firstname']);
        $lastname  = trim($_POST['lastname']);
        $email     = trim($_POST['email']);
        $username  = trim($_POST['username']);

        // Basic validation (you can enhance this)
        if (empty($firstname) || empty($lastname) || empty($email) || empty($username)) {
            $error_message = "All fields are required.";
        } else {
            // Update the database
            $update_stmt = $conn->prepare("UPDATE users 
                SET firstname = ?, lastname = ?, email = ?, username = ? 
                WHERE user_id = ?");
            $update_stmt->bind_param("ssssi", 
                $firstname, 
                $lastname, 
                $email, 
                $username, 
                $user_id
            );

            if ($update_stmt->execute()) {
                // Also update session info
                $_SESSION['firstname'] = $firstname;
                $_SESSION['lastname']  = $lastname;
                $success_message       = "Profile updated successfully!";
                // Refresh user data
                $user['firstname'] = $firstname;
                $user['lastname']  = $lastname;
                $user['email']     = $email;
                $user['username']  = $username;
            } else {
                $error_message = "Error updating profile.";
            }

            $update_stmt->close();
        }
    }

    // 2. Handle role change
    if (isset($_POST['role'])) {
        $newRole = trim($_POST['role']);
        $allowedRoles = ['Artist', 'Bidder', 'Collector', 'Admin']; // Define allowed roles

        if (in_array($newRole, $allowedRoles)) {
            $updateRoleStmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $updateRoleStmt->bind_param("si", $newRole, $user_id);
            if ($updateRoleStmt->execute()) {
                $success_message = "User role updated successfully!";
                $user['role'] = $newRole;
            } else {
                $error_message = "Error updating role.";
            }
            $updateRoleStmt->close();
        } else {
            $error_message = "Invalid role selected.";
        }
    }

    // 3. Change Password
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        // Hash the new password
        $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        $updatePasswordStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $updatePasswordStmt->bind_param("si", $hashedPassword, $user_id);
        if ($updatePasswordStmt->execute()) {
            $success_message = "Password updated successfully!";
        } else {
            $error_message = "Error updating password.";
        }
        $updatePasswordStmt->close();
    }

    // 4. Profile Picture Upload
    if (isset($_POST['upload_picture'])) {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['profile_picture']);
            if ($uploadResult !== false) {
                // Optionally remove old photo if not default
                if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'uploads/profile_pictures/default-avatar.png') {
                    $oldPath = '../' . $user['profile_picture'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Update DB with new path
                $picStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $picStmt->bind_param("si", $uploadResult, $user_id);

                if ($picStmt->execute()) {
                    $success_message = "Profile picture updated successfully!";
                    // Refresh user data
                    $user['profile_picture'] = $uploadResult;
                } else {
                    $error_message = "Database update failed. Please try again.";
                }
                $picStmt->close();
            }
        } else {
            $_SESSION['page2_errors']['profile_picture_err'] = 
                "No file uploaded or there was an upload error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <!-- Link to CSS files -->
    <link rel="stylesheet" href="../css/css.css?v7"> <!-- Main CSS (if any) -->
    <link rel="stylesheet" href="../css/profile.css"> <!-- Profile CSS -->
    <link rel="stylesheet" href="../css/auctions.css?v5"> <!-- Other CSS (if any) -->
    <style>
    /* Additional inline styles if needed */
    </style>
</head>
<body>
<header id="messagesHeader">
        <div>
            <div class="nav-logo">
                <!-- Example brand logo -->
                <a href="#" class="logo">
                <img src="../css/img/bidder-high-resolution-logo-black-transparent.png" alt="Brand Logo">
                </a>
            </div>
            <ul id="homepageNav">
                <li><a href="index.php">Home</a></li>
                <li><a href="collections.php">Collections</a></li>
                <li><a href="artists.php">Artists</a></li>
                <li><a href="auctions.php">Auctions</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="forum.php">Forum</a></li>
                <li><a href="faq.php">FAQ</a></li>

                <?php if (!empty($user)): ?>
                    <li class="nav-item dropdown">
                        <button class="dropbtn">
                            <div class="user-profile">
                                <?php
                                // For the top-right corner small avatar
                                $avatarPath = '../img/default-avatar.png'; // Ensure this path is correct
                                if (!empty($user['profile_picture'])) {
                                    $avatarPath = '../' . $user['profile_picture'];
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                     alt="Profile" 
                                     class="profile-img">
                                <span><?php echo htmlspecialchars($user['firstname']); ?></span>
                            </div>
                            <i class="arrow down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="profile.php">My Profile</a>
                            <a href="my-collections.php">My Collections</a>
                            <a href="my_favorites.php">My Favorites</a>
                            <a href="messages.php">Messages</a>
                            <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="./HTML/web.html">Login/Signup</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-picture-container">
                <?php
                $profilePicturePath = '../img/default-avatar.png'; // Default avatar path
                if (!empty($user['profile_picture'])) {
                    $profilePicturePath = '../' . $user['profile_picture'];
                }
                ?>
                <img 
                    src="<?php echo htmlspecialchars($profilePicturePath); ?>" 
                    alt="Profile" 
                    class="profile-picture"
                >
                <div class="edit-overlay" onclick="toggleUploadForm()">
                    <span>Change Photo</span>
                </div>
                <!-- Profile Picture Upload Form -->
                <form 
                    action="profile.php" 
                    method="post" 
                    enctype="multipart/form-data" 
                    class="profile-pic-form" 
                    id="profilePicForm"
                    aria-labelledby="changePhotoModal"
                    role="dialog"
                >
                    <label for="profile_picture">Select a new profile picture:</label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required onchange="previewImage(event)">
                    <img id="preview" src="#" alt="Image Preview">
                    <button type="submit" name="upload_picture">Upload</button>
                </form>
            </div>
            <div class="profile-info">
                <h1>
                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                </h1>
                <p class="username">
                    @<?php echo htmlspecialchars($user['username']); ?>
                </p>
                <p class="email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>
            </div>
        </div>

        <!-- Display success or error messages -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['page2_errors']['profile_picture_err'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['page2_errors']['profile_picture_err'];
                    unset($_SESSION['page2_errors']['profile_picture_err']);
                ?>
            </div>
        <?php endif; ?>

        <div class="profile-content">
            <!-- Personal Info Section -->
            <div class="profile-section">
                <h2>Personal Information</h2>
                <div class="info-display">
                    <div class="info-group">
                        <span class="info-label">First Name</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($user['firstname']); ?>
                        </span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Last Name</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($user['lastname']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Account Info Section -->
            <div class="profile-section">
                <h2>Account Information</h2>
                <div class="info-display">
                    <div class="info-group">
                        <span class="info-label">Email Address</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Username</span>
                        <span class="info-value">
                            @<?php echo htmlspecialchars($user['username']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Password Display (Masked) -->
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
                        <span class="info-value">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Button -->
            <button class="edit-profile-btn" onclick="toggleEditMode()">
                Edit Profile
            </button>
        </div>

        <!-- Edit Form -->
        <form method="POST" class="edit-form" id="editForm">
            <!-- Edit Personal Information -->
            <div class="profile-section">
                <h2>Edit Personal Information</h2>
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input 
                        type="text" 
                        id="firstname" 
                        name="firstname" 
                        value="<?php echo htmlspecialchars($user['firstname']); ?>" 
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input 
                        type="text" 
                        id="lastname" 
                        name="lastname" 
                        value="<?php echo htmlspecialchars($user['lastname']); ?>" 
                        required
                    >
                </div>
            </div>

            <!-- Edit Account Information -->
            <div class="profile-section">
                <h2>Edit Account Information</h2>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($user['email']); ?>" 
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($user['username']); ?>" 
                        required
                    >
                </div>
            </div>

            <!-- Role Change -->
            <div class="profile-section role-form">
                <h2>Role</h2>
                <div class="form-group">
                    <label for="role">User Role:</label>
                    <select name="role" id="role">
                        <option value="Artist"    <?php echo ($user['role'] === 'Artist')    ? 'selected' : ''; ?>>Artist</option>
                        <option value="Bidder"    <?php echo ($user['role'] === 'Bidder')    ? 'selected' : ''; ?>>Bidder</option>
                        <option value="Collector" <?php echo ($user['role'] === 'Collector') ? 'selected' : ''; ?>>Collector</option>
                        <option value="Admin"     <?php echo ($user['role'] === 'Admin')     ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>

            <!-- Change Password -->
            <div class="profile-section password-form">
                <h2>Password</h2>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input 
                        type="password" 
                        name="new_password" 
                        id="new_password"
                        placeholder="Enter new password"
                    >
                </div>
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
            <button type="button" class="cancel-btn" onclick="toggleEditMode()">
                Cancel
            </button>
        </form>
    </div>

    <!-- Include JavaScript files -->
    <script src="../JS/profile.js"></script>
</body>
</html>
