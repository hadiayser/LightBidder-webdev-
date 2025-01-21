<?php
session_start();
require_once('../php/conn.php');

// Fetch all active FAQs from the database
$query = "SELECT * FROM faqs WHERE is_active = TRUE ORDER BY id ASC";
$result = $conn->query($query);
$faqs = [];
while($row = $result->fetch_assoc()) {
    $faqs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ - Bidder Art Auctions</title>
  <link rel="stylesheet" href="../css/faq.css" />
  <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>" />
</head>
<body>
  <header>
    <div>
      <div class="nav-logo">
        <a href="index.php" class="logo">
          <img src="../img/bidder-high-resolution-logo-black-transparent.png" alt="Bidder Logo">
        </a>
      </div>
      <button class="hamburger" aria-label="Toggle navigation">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </button>
      <ul id="homepageNav">
        <li><a href="index.php">Home</a></li>
        <li><a href="collections.php">Collections</a></li>
        <li><a href="artists.php">Artists</a></li>
        <li><a href="auctions.php">Auctions</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="forum.php">Forum</a></li>
        <li><a href="faq.php">FAQ</a></li>
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
        <?php else: ?>
          <li><a href="web.html">Login/Signup</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </header>
  
  <div class="faq-container">
    <h2>Frequently Asked Questions</h2>
    <?php foreach($faqs as $faq): ?>
      <div class="faq">
        <button class="faq-question">
          <?php echo htmlspecialchars($faq['question']); ?> <span class="arrow">▼</span>
        </button>
        <div class="faq-answer">
          <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  
  <script src="../JS/faq.js"></script>
</body>
</html>
