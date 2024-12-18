<?php
// Start the session at the very beginning of the file
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/css.css?v1" />
    <script src="../JS/forum.js"></script>
    <title>Homepage</title>
  </head>
  <body>
    <header>
      <div>
        <div class="nav-logo">
          <a href="index.php" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
        </div>
        <ul id="homepageNav">
          <li><a href="index.php">Home</a></li>
          <!-- <li><a href="artworks.html">Artwork</a></li> -->
          <li><a href="collections.php">Collections</a></li>
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
                    <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
                </div>
            </li>
          <?php else: ?>
            <li><a href="web.html">Login/Signup</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </header>
    <section id="create-thread">
      <h2>Lets talk about art!</h2>
      <input
        type="text"
        id="thread-title"
        placeholder="Write something...or dont"
      />
      <textarea
        id="thread-content"
        placeholder="What is on your mind"
      ></textarea>
      <button id="post-thread">Post Thread</button>
    </section>

    <!-- Forum Threads Section -->
    <section id="forum-threads">
      <h2>Forum Threads</h2>
      <div id="threads-container">
        <!-- Threads will appear here -->
      </div>
    </section>
  </body>
</html>
