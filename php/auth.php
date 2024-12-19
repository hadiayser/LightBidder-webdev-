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

            // Redirect to index page
            error_log("Redirecting to index.php");
            header("Location: ../html/index.php");
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

        // Insert the new user with the generated username
        $sql = "INSERT INTO users (firstname, lastname, email, username, password, role, date_registered) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $firstname, $lastname, $email, $username, $hashed_password, $role);
        $stmt->execute();

        // Debug: Log registration success
        error_log("New user registered: $username");

        // Redirect to login page after successful registration
        header("Location: ../html/web.html");
        exit();
    }
}
?>