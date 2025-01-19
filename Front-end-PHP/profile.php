<?php
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
    header("Location: web.html");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Update Personal & Account Info
    if (isset($_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['username'])) {
        $firstname = $_POST['firstname'];
        $lastname  = $_POST['lastname'];
        $email     = $_POST['email'];
        $username  = $_POST['username'];

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
        } else {
            $error_message = "Error updating profile.";
        }
    }

    // 2. Handle role change
    if (isset($_POST['role'])) {
        $newRole = $_POST['role'];
        $updateRoleStmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $updateRoleStmt->bind_param("si", $newRole, $user_id);
        $updateRoleStmt->execute();
    }

    // 3. Change Password
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        // Hash the new password
        $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        $updatePasswordStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $updatePasswordStmt->bind_param("si", $hashedPassword, $user_id);
        $updatePasswordStmt->execute();
    }

    // 4. Profile Picture Upload
    if (isset($_POST['upload_picture'])) {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['profile_picture']);
            if ($uploadResult !== false) {
                // Optionally remove old photo if not default
                if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'uploads/profile_pictures/default.png') {
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
                    $refreshStmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                    $refreshStmt->bind_param("i", $user_id);
                    $refreshStmt->execute();
                    $newResult = $refreshStmt->get_result();
                    $user      = $newResult->fetch_assoc();
                } else {
                    $error_message = "Database update failed. Please try again.";
                }
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
    <!-- Link your main CSS -->
    <link rel="stylesheet" href="../css/css.css?v7">
    <link rel="stylesheet" href="../css/profile.css?v1">
    <link rel="stylesheet" href="../css/auctions.css?v5">
    <style>
    /* Additional styling when Save Changes is clicked */
    .success-message {
        color: green;
        font-weight: bold;
        margin: 1rem 0;
        /* Subtle border or highlight */
        border: 1px solid #c2e0c6;
        background-color: #d7ffe0;
        padding: 10px;
        border-radius: 5px;
    }
    .error-message {
        color: red;
        margin: 1rem 0;
        border: 1px solid #f9c2c2;
        background-color: #ffe0e0;
        padding: 10px;
        border-radius: 5px;
    }
    </style>
</head>
<body>
    <header>
        <div>
            <div class="nav-logo">
                <!-- Example brand logo -->
                <a href="#" class="logo">
                    <img src="../img/bidder-high-resolution-logo-black-transparent.png" alt="Brand Logo">
                </a>
            </div>
            <ul id="homepageNav">
                <li><a href="index.php">Home</a></li>
                <li><a href="collections.php">Collections</a></li>
                <li><a href="artists.php">Artists</a></li>
                <li><a href="auctions.php">Auctions</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="forum.php">Forum</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <button class="dropbtn">
                            <div class="user-profile">
                                <?php
                                // For the top-right corner small avatar
                                $avatarPath = '../img/—Pngtree—user avatar placeholder black_6796227.png';
                                if (!empty($user['profile_picture'])) {
                                    $avatarPath = '../' . $user['profile_picture'];
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                     alt="Profile" 
                                     class="profile-img">
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
                <?php else: ?>
                    <li><a href="web.html">Login/Signup</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-picture-container">
                <?php
                $profilePicturePath = '../img/—Pngtree—user avatar placeholder black_6796227.png';
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
                    style="display: none;"
                >
                    <input type="file" name="profile_picture" accept="image/*" required>
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
        <form method="POST" class="edit-form" id="editForm" style="display: none;">
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
                <label for="role">User Role:</label>
                <select name="role" id="role">
                    <option value="Artist"    <?php echo ($user['role'] === 'Artist')    ? 'selected' : ''; ?>>Artist</option>
                    <option value="Bidder"    <?php echo ($user['role'] === 'Bidder')    ? 'selected' : ''; ?>>Bidder</option>
                    <option value="Collector" <?php echo ($user['role'] === 'Collector') ? 'selected' : ''; ?>>Collector</option>
                    <option value="Admin"     <?php echo ($user['role'] === 'Admin')     ? 'selected' : ''; ?>>Admin</option>
                </select>
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
                    >
                </div>
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
            <button type="button" class="cancel-btn" onclick="toggleEditMode()">
                Cancel
            </button>
        </form>
    </div>

    <script src="../JS/dropdown.js">
    </script>
      <script src="../JS/profile.js">
    </script>
</body>
</html>
