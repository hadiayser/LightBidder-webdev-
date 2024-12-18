<?php
// Start the session at the very beginning of the file
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/css.css?v5" />
    <title>Homepage</title>
  </head>
  <body>
    <header>
      <div>
        <div class="nav-logo">
          <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
        </div>
        <ul id="homepageNav">
          <li><a href="index.php">Home</a></li>
          <li><a href="artworks.html">Artwork</a></li>
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
    <div id="ads">
      <h1>
        Discover unique <br />
        art from <br />
        emerging artists
      </h1>
      <a href="collections.php" id="Discover">Discover now</a href="">
      <?php
        include '../php/conn.php';

        // Fetch artworks with specific IDs from the database
        $sql = "SELECT title, description, image_url FROM artworks WHERE artwork_id IN (1, 2, 3)";
        $result = $conn->query($sql);

        // Check if there are any results
        if ($result->num_rows > 0) {
            echo '<div id="imagesArtwork">';
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
          <h3>Discover Unique Artworks</h3>
      <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
        </div>
        <div class="pair">
          <h3>Bid or Buy Instantly</h3>
      <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
  </div>

  <div>
      <h3>Support artists</h3>
      <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
    </div>
  </div>
    </div>
    <div class="FeaturedArtworks">
      <div id="text2">
      <h2>Featured <br> Artworks</h2>
      <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. "</p>
      <a href="auctions.php" id="shopNow">Shop now</a>
   
    </div>
    <div id="imagesArtwork">
      <div class="artwork">
        <img
          src="https://i.ebayimg.com/images/g/pi0AAOSwZBhhjSMC/s-l1200.jpg"
          alt=""
        />
      </div>

      <div class="artwork">
        <img
          src="https://m.media-amazon.com/images/I/91ZTNUjqClL._AC_UF894,1000_QL80_.jpg"
          alt=""
        />
      </div>

      <div class="artwork">
        <img
          src="https://paintings.pinotspalette.com/van-goghs-starry-night---halloween-ii-tv.jpeg?v=10027528"
          alt=""
        />
      </div>
    </div>
  </div>
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
