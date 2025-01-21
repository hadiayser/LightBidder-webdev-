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

if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Fetch artists from the database
$query = "SELECT artist_id, artist_name, biography, image_url, portfolio_url FROM artists";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$artists = [];
while ($row = $result->fetch_assoc()) {
    $artists[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Artists</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Main CSS for the Glassy Search Bar & New Card Layout -->
  <link rel="stylesheet" href="../css/artists.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>" />


  <!-- (Optional) Font Awesome for the magnifying glass icon -->
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" /> -->
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

<div class="artists-page-container">
    <h1>Our Artists</h1>

    <!-- Glassy Search Bar -->
    <div class="artist-search-bar">
      <div class="search-bar-container">
        <div class="search-icon">
          <!-- Font Awesome icon example -->
          <i class="fa fa-search"></i>
        </div>
        <input 
          type="text"
          class="search-input"
          id="artistSearchInput"
          placeholder="Search artists..."
          onkeyup="filterArtists()"
        />
      </div>
    </div>

    <!-- Artists Grid -->
    <div class="articles" id="artistsGrid"><!-- 'articles' for the new card layout -->
      <?php foreach ($artists as $artist): ?>
        <?php
          // Placeholders for missing data
          $artistName = !empty($artist['artist_name']) ? $artist['artist_name'] : 'Unknown Artist';
          $imgUrl     = !empty($artist['image_url']) ? $artist['image_url'] : 'placeholder.jpg';
          $bio        = !empty($artist['biography']) ? $artist['biography'] : 'No biography available.';
          $portfolio  = !empty($artist['portfolio_url']) ? $artist['portfolio_url'] : '';
          $artistId   = $artist['artist_id'];
        ?>
        
        <article 
          class="artist-card"
        >
          <a href="artist-detail.php?id=<?php echo $artistId; ?>">
            <figure>
              <img src="<?php echo htmlspecialchars($imgUrl); ?>" 
                   alt="<?php echo htmlspecialchars($artistName); ?>">
            </figure>
            <div class="article-body">
              <h2><?php echo htmlspecialchars($artistName); ?></h2>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
</div>

<script src="../JS/dropdown.js"></script>
<script>
/* Filter function for search (case-insensitive) */
function filterArtists() {
  const input = document.getElementById('artistSearchInput');
  const filter = input.value.toLowerCase();
  const grid = document.getElementById('artistsGrid');
  const cards = grid.getElementsByTagName('article'); // or 'artist-card' class

  for (let i = 0; i < cards.length; i++) {
    const artistName = cards[i].getElementsByTagName('h2')[0].textContent || '';
    if (artistName.toLowerCase().includes(filter)) {
      cards[i].style.display = '';
    } else {
      cards[i].style.display = 'none';
    }
  }
}
</script>
</body>
</html>
