<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/css.css" />
    <title>Homepage</title>
  </head>
  <body>
    <header>
      <div>
        <div class="nav-logo">
          <a href="#" class="logo"><img src="../img/logo-no-background.png" alt=""></a>
      </div>
        <ul id="homepageNav">
          <li><a href="index.html">Home</a></li>
          <li><a href="artworks.html">Artwork</a></li>
          <li><a href="collections.php">Collections</a></li>
          <li><a href="index.html">Exhibitions</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="web.html">Login/Signup</a></li>

        </ul>
      </div>
    </header>
    <div id="ads">
      <h1>
        Discover unique <br />
        art from <br />
        emerging artists
      </h1>
      <a href="#" id="Discover">Discover now</a href="">
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
      <a href="#" id="shopNow">Shop now</a>
   
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
  </body>
</html>
