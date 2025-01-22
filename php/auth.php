<?php
ob_start(); // Start output buffering
session_start();
include('conn.php'); // Ensure conn.php doesn't output anything

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug: Log when this file is accessed
error_log('Attempt to login or register');

// Handle Login
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    error_log("Login attempt with email: $email");

    $sql  = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['error'] = "Internal server error. Please try again later.";
        header("Location: /front-php/HTML/web.html");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Compare plaintext input ($password) to the hashed DB password
        if (password_verify($password, $user['password'])) {
            // Password OK: set session variables
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname']  = $user['lastname'];

            error_log("Login successful for " . $_SESSION['username']);

            // Redirect based on user role
            if (strtolower($user['role']) === 'admin') {
                error_log("Redirecting to admin dashboard");
                header("Location: http://localhost/LightBidder-webdev-/admin/dashboard.php");
            } else {
                error_log("Redirecting to front-php/index.php");
                header("Location: http://localhost/LightBidder-webdev-/front-php/index.php");
            }
            exit();
        } else {
            // Incorrect password
            $error_message = "Incorrect password!";
            error_log($error_message);
            $_SESSION['error'] = $error_message;
            header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html");
            exit();
        }
    } else {
        // No user row found for that email
        $error_message = "No user found with that email!";
        error_log($error_message);
        $_SESSION['error'] = $error_message;
        header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html");
        exit();
    }
}

// Handle Registration
if (isset($_POST['register'])) {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'Bidder'; // Direct assignment

    error_log("Registration attempt with email: $email, role: $role");

    // Create a username by combining firstname + lastname
    $username = strtolower($firstname . $lastname);

    // Check if email already exists
    $checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
    $checkStmt= $conn->prepare($checkSql);
    if (!$checkStmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['error'] = "Internal server error. Please try again later.";
        header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html");
        exit();
    }
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        $error_message = "Email already exists! Please use a different email.";
        error_log($error_message);
        $_SESSION['error'] = $error_message;
        header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html");
        exit();
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $sql = "INSERT INTO users (firstname, lastname, email, username, password, role, date_registered) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log("Error preparing user insert query: " . $conn->error);
            $_SESSION['error'] = "Internal server error. Please try again later.";
            header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html");
            exit();
        }

        $stmt->bind_param("ssssss", $firstname, $lastname, $email, $username, $hashed_password, $role);

        if ($stmt->execute()) {
            // Grab new user_id
            $user_id = $stmt->insert_id;
            error_log("New user_id: $user_id");

            // If role is Artist, insert into 'artists' table
            if (strtolower($role) === 'artist') {
                $artist_name   = "$firstname $lastname";
                $biography     = $_POST['biography'] ?? "Biography not provided.";
                $portfolio_url = $_POST['portfolio_url'] ?? null;

                $artistSql = "INSERT INTO artists (user_id, artist_name, biography, portfolio_url)
                              VALUES (?, ?, ?, ?)";
                $artistStmt= $conn->prepare($artistSql);
                if ($artistStmt) {
                    $artistStmt->bind_param("isss", $user_id, $artist_name, $biography, $portfolio_url);
                    if ($artistStmt->execute()) {
                        error_log("Artist added for user_id: $user_id");
                    } else {
                        error_log("Error inserting into artists: " . $artistStmt->error);
                    }
                } else {
                    error_log("Error preparing artist insert query: " . $conn->error);
                }
            }

            // Redirect to login page after successful registration
            error_log("Redirecting to web.php");
            header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html"); // Changed to web.php
            exit();
        } else {
            error_log("Error inserting into users: " . $stmt->error);
            $_SESSION['error'] = "Registration failed. Please try again.";
            header("Location: http://localhost/LightBidder-webdev-/front-php/HTML/web.html");
            exit();
        }
    }   
}

ob_end_flush(); // Flush the output buffer
?>
