<?php 
session_start();
require_once('../php/conn.php');

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <!-- Link to CSS files -->
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>" /> <!-- Main CSS (if any) -->
    <style>
    /* Additional inline styles if needed */
    </style>
</head>
</head>
<body>
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



    <main>
      <div id="ads">
        <h1>
          Discover unique <br />
          art from <br />
          emerging artists
        </h1>
        <a href="collections.php" id="Discover">Discover now</a>
        <?php
          include '../php/conn.php';

          // Fetch artworks with specific IDs from the database
          $sql = "SELECT title, description, image_url FROM artworks WHERE artwork_id IN (1, 2, 3)";
          $result = $conn->query($sql);

          // Check if there are any results
          if ($result->num_rows > 0) {
              echo '<div class="imagesArtwork">';
              while ($row = $result->fetch_assoc()) {
                  echo '<div class="artwork">';
                  echo '<img src="' . $row['image_url'] . '" alt="' . htmlspecialchars($row['title']) . '">';
                  echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                  echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                  echo '</div>';
              }
              echo '</div>';
          } else {
              echo '<p>No artworks found.</p>';
          }
        ?>

        <div id="text">
          <div class="pair">
            <i class="fa-solid fa-palette fa-2x"></i>
            <h3>Discover One-of-a-Kind Masterpieces</h3>
            <p>From timeless classics to avant-garde creations—find art that speaks to you, only on Bidder.</p>
          </div>
          <div class="pair">
            <i class="fa-solid fa-eye fa-2x"></i>
            <h3>Bid, Buy & Own a Piece of History</h3>
            <p>Engage in thrilling auctions or secure your favorite artwork instantly. The world of art is yours to explore.</p>
          </div>
          <div>
            <i class="fa-solid fa-brush fa-2x"></i>
            <h3>Empower Artists, Elevate Creativity</h3>
            <p>Support legendary artists and rising stars alike. Every bid fuels artistic passion and innovation.</p>
          </div>
        </div>

        <div class="FeaturedArtworks">
          <div id="text2">
            <h2>Featured <br> Artworks</h2>
            <p>Look at our featured artwork by pressing the button!</p>
            <a href="auctions.php" id="shopNow">Shop now</a>
          </div>
          <div class="imagesArtwork">
            <?php
              include '../php/conn.php';

              // Fetch artworks with specific IDs from the database
              $sql = "SELECT title, description, image_url FROM artworks WHERE artwork_id IN (1, 2, 3)";
              $result = $conn->query($sql);

              // Check if there are any results
              if ($result->num_rows > 0) {
                  echo '<div class="imagesArtwork">';
                  while ($row = $result->fetch_assoc()) {
                      echo '<div class="artwork">';
                      echo '<img src="' . $row['image_url'] . '" alt="' . htmlspecialchars($row['title']) . '">';
                      echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                      echo '</div>';
                  }
                  echo '</div>';
              } else {
                  echo '<p>No artworks found.</p>';
              }
            ?>
          </div>
        </div>
      </main>

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
                    <li><a href="terms.html">Terms & Conditions</a></li>
                    <li><a href="legal.html">Legal</a></li>
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

      <!-- Floating Messages Button -->
      <a href="messages.php" class="floating-message-btn" aria-label="Messages">
        <i class="fa-solid fa-envelope"></i>
      </a>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.querySelector('.nav-item.dropdown');
            const dropbtn = document.querySelector('.dropbtn');

            if (dropdown && dropbtn) {
                dropbtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });

                // Prevent dropdown from closing when clicking inside
                dropdown.querySelector('.dropdown-content').addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
      </script>
  </body>
</html>
