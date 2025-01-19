<?php
session_start();
include('conn.php'); // Include database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug: Log when the form is accessed
error_log('Attempt to login or register');

// Handle Login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Debug: Log login attempt
    error_log("Login attempt with email: $email");

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];

            // Debug: Ensure the session variables are set
            error_log("Login successful for " . $_SESSION['username']);

            // Redirect based on user role
            if ($user['role'] === 'admin') {
                error_log("Redirecting to admin dashboard");
                header("Location: ../admin/dashboard.php");
            } else {
                error_log("Redirecting to index.php");
                header("Location: ../html/index.php");
            }
            exit();

        } else {
            $error_message = "Incorrect password!";
            error_log($error_message); // Debug log
        }
    } else {
        $error_message = "No user found with that email!";
        error_log($error_message); // Debug log
    }
}

// Handle Registration
if (isset($_POST['register'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Debug: Log the role
    error_log("Role received: $role");

    // Create the username by combining firstname and lastname
    $username = strtolower($firstname . $lastname);

    // Check if the email already exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = "Email already exists! Please use a different email.";
        error_log($error_message); // Debug log
    } else {
        // Hash password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user
        $sql = "INSERT INTO users (firstname, lastname, email, username, password, role, date_registered) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $firstname, $lastname, $email, $username, $hashed_password, $role);

        if ($stmt->execute()) {
            // Get the newly inserted user_id
            $user_id = $stmt->insert_id;
            error_log("New user_id: $user_id");

            // If the user is an artist, insert into the artists table
            if ($role === 'Artist') {
                $artist_name = "$firstname $lastname"; // Default artist name
                $biography = $_POST['biography'] ?? "Biography not provided.";
                $portfolio_url = $_POST['portfolio_url'] ?? null;

                error_log("Inserting artist details for user_id: $user_id");

                $sql = "INSERT INTO artists (user_id, artist_name, biography, portfolio_url) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    error_log("Error preparing artist insert query: " . $conn->error);
                } else {
                    $stmt->bind_param("isss", $user_id, $artist_name, $biography, $portfolio_url);

                    if ($stmt->execute()) {
                        error_log("Artist successfully added for user_id: $user_id");
                    } else {
                        error_log("Error inserting into artists: " . $stmt->error);
                    }
                }
            }

            // Redirect to login page after successful registration
            header("Location: ../html/web.html");
            exit();
        } else {
            error_log("Error inserting into users: " . $stmt->error);
        }
    }
}
?>
