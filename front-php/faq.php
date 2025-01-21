<?php
// Start the session at the very beginning of the file
session_start();
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
            <img src="./img/bidder-high-resolution-logo-black-transparent.png" alt="">
          </a>
        </div>

        <!-- Hamburger Menu Button for Mobile -->
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
                <a href="messages.php">Messages</a> <!-- Added Messages Link in Dropdown -->
                <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
              </div>
            </li>
          <?php else: ?>
            <li><a href="HTML/web.html">Login/Signup</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </header>
    <div class="faq-container">
      <h2>Frequently Asked Questions</h2>

      <div class="faq">
        <button class="faq-question">
          What is Bidder? <span class="arrow">▼</span>
        </button>
        <div class="faq-answer">
          <p>
            Bidder is an online art marketplace where you can discover, bid on,
            and buy unique artworks from artists around the world.
          </p>
        </div>
      </div>

      <div class="faq">
        <button class="faq-question">
          How do I place a bid? <span class="arrow">▼</span>
        </button>
        <div class="faq-answer">
          <p>
            Simply go to an artwork’s page, enter your bid amount, and click
            "Place Bid". You’ll be notified if someone outbids you.
          </p>
        </div>
      </div>

      <div class="faq">
        <button class="faq-question">
          What payment methods do you accept? <span class="arrow">▼</span>
        </button>
        <div class="faq-answer">
          <p>We accept credit/debit cards, PayPal, and bank transfers.</p>
        </div>
      </div>

      <div class="faq">
        <button class="faq-question">
          How long does shipping take? <span class="arrow">▼</span>
        </button>
        <div class="faq-answer">
          <p>
            Shipping times vary but usually take 5-14 business days, depending
            on the location.
          </p>
        </div>
      </div>

      <div class="faq">
        <button class="faq-question">
          How do I contact customer support? <span class="arrow">▼</span>
        </button>
        <div class="faq-answer">
          <p>
            Reach out via our Contact Page or email us at support@bidder.com.
          </p>
        </div>
      </div>
    </div>
  
    <script src="../JS/faq.js"></script>
  </body>
</html>
