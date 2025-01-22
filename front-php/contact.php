<?php
include '../php/conn.php';
session_start();
// Initialize $user as an empty array
$user = [];

// Ensure user is logged in and fetch user data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT firstname, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    
    $stmt->close();
}
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname       = $conn->real_escape_string($_POST['firstname']);
    $lastname        = $conn->real_escape_string($_POST['lastname']);
    $email           = $conn->real_escape_string($_POST['email']);
    $question_field  = $conn->real_escape_string($_POST['question_field']);
    $subject         = $conn->real_escape_string($_POST['subject']);

    $stmt = $conn->prepare("INSERT INTO contact (firstname, lastname, email, writehere, subject) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $question_field, $subject);

    if ($stmt->execute()) {
        // On success, show an alert or you could display an on-page message
        echo "<script>alert('Thank you for contacting us. We will get back to you soon.');</script>";
    } else {
        // On error, alert the user (or show an error div on the page)
        echo "<script>alert('Error: {$stmt->error}');</script>";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us</title>
  <!-- Main CSS for overall site -->
  <link 
  rel="stylesheet" 
  href="../css/css.css?v=<?php echo time(); ?>" 
/>
<link 
  rel="stylesheet" 
  href="../css/css.css?v=<?php echo time(); ?>" 
/>
</head>
<style>
  #body_contact {
    /* Remove or comment the old background-image line */
    /* background-image: url("..."); */
    
    background: linear-gradient(
                  rgba(0, 0, 0, 0.4),
                  rgba(0, 0, 0, 0.4)
                ),
                url("../img/saint_john_the_evangelist__right_panel__1939.1.261.c.jpg") center/cover no-repeat;
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    min-height: 100vh; /* Ensures we have a full view height background */
    color: #fff;       /* White text if you want text directly on BG */
    position: relative;
}
</style>
<body id="body_contact">
<header id="messagesHeader">
        <div>
            <div class="nav-logo">
                <!-- Example brand logo -->
                <a href="#" class="logo">
                    <img src="./img/bidder-high-resolution-logo-black-transparent.png" alt="Brand Logo">
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
  <!-- Minimalistic Contact Section -->
  <section class="contact-section">
    <div class="contact-container">
      <h1>Contact Us</h1>
      <p class="contact-intro">We’d love to hear from you! Please share your concerns, questions, or feedback.</p>

      <!-- IMPORTANT: method="POST" so data goes to the PHP above -->
      <form method="POST" action="" class="contact-form">
        <div class="form-group">
          <label for="fname">First Name</label>
          <input 
            type="text" 
            id="fname" 
            name="firstname" 
            placeholder="Your first name" 
            required
          />
        </div>

        <div class="form-group">
          <label for="lname">Last Name</label>
          <input 
            type="text" 
            id="lname" 
            name="lastname" 
            placeholder="Your last name" 
            required
          />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="Your email address" 
            required
          />
        </div>

        <div class="form-group">
          <label for="question_field">Topic</label>
          <select id="question_field" name="question_field" required>
            <option value="questions">General Questions</option>
            <option value="art">Art</option>
            <option value="refund">Refund</option>
          </select>
        </div>

        <div class="form-group">
          <label for="subject">Message</label>
          <textarea
            id="subject"
            name="subject"
            placeholder="How can we help you?"
            rows="6"
            required
          ></textarea>
        </div>

        <button type="submit" class="contact-submit">Submit</button>
      </form>
    </div>
  </section>
  <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>About Us</h4>
                <p>Bidder is your go-to marketplace for discovering, bidding on, and collecting unique artworks from around the world.</p>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="collections.php">Collections</a></li>
                    <li><a href="artists.php">Artists</a></li>
                    <li><a href="auctions.php">Auctions</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="./HTML/terms.html">Terms & Conditions</a></li>
                    <li><a href="./HTML/legal.html">Legal</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>Email: <a href="mailto:support@bidder.com">support@bidder.com</a></p>
                <p>Phone: +1 (111) 111-111</p>
                <p>Location: Paris, France</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date("M, Y"); ?> Bidder. All Rights Reserved.</p>
        </div>
      </footer>
</body>
</html>
