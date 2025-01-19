<?php
session_start(); 
require_once('../php/conn.php');

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

  <!-- (Optional) Font Awesome for the magnifying glass icon -->
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" /> -->
</head>
<body>
<header>
  <div>
    <div class="nav-logo">
      <a href="#" class="logo">
        <img src="../img/bidder-high-resolution-logo-black-transparent.png" alt="Logo">
      </a>
    </div>
    <ul id="homepageNav">
      <li><a href="index.php">Home</a></li>
      <li><a href="collections.php">Collections</a></li>
      <li><a href="artists.php">Artists</a></li>
      <li><a href="auctions.php">Auctions</a></li>
      <li><a href="contact.php">Contact</a></li>
      <li><a href="forum.php">Forum</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item dropdown">
          <button class="dropbtn">
            <div class="user-profile">
                <img src="../img/—Pngtree—user avatar placeholder black_6796227.png" 
                     alt="Profile" class="profile-img">
                <span><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
            </div>
            <i class="arrow down"></i>
          </button>
          <div class="dropdown-content">
            <a href="profile.php">My Profile</a>
            <a href="my-collections.php">My Collections</a>
            <a href="my_favorites.php">My Favorites</a>
            <a href="../php/logout.php" style="background-color: #cb5050; !important;">
              Logout
            </a>
          </div>
        </li>
      <?php else: ?>
        <li><a href="web.html">Login/Signup</a></li>
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
        ?>
        
        <article 
          class="artist-card" 
          data-artist-name="<?php echo strtolower($artistName); ?>"
          onclick="showArtistPopup(
            '<?php echo addslashes($artistName); ?>', 
            '<?php echo addslashes($bio); ?>', 
            '<?php echo addslashes($imgUrl); ?>', 
            '<?php echo addslashes($portfolio); ?>'
          )"
        >
          <figure>
            <img src="<?php echo htmlspecialchars($imgUrl); ?>" 
                 alt="<?php echo htmlspecialchars($artistName); ?>">
          </figure>
          <div class="article-body">
            <h2><?php echo htmlspecialchars($artistName); ?></h2>
            <!-- If you want a short snippet or 'Read more' link, add it here -->
          </div>
        </article>
      <?php endforeach; ?>
    </div>
</div>

<!-- Artist Popup Modal -->
<div id="artistPopup" class="popup">
  <div class="popup-content">
      <button class="close-button" onclick="closeArtistPopup()">×</button>
      <div class="popup-image">
          <img id="popupArtistImage" src="" alt="Artist Image">
      </div>
      <div class="popup-details">
          <h3 id="popupArtistName">Artist Name</h3>
          <p id="popupArtistBiography" class="popup-biography">Biography</p>
          <div class="portfolio-link" id="popupArtistPortfolio"></div>
      </div>
  </div>
</div>

<script src="../JS/dropdown.js"></script>
<script>
function showArtistPopup(name, biography, imageUrl, portfolio) {
  const popupImg       = document.getElementById('popupArtistImage');
  const popupName      = document.getElementById('popupArtistName');
  const popupBiography = document.getElementById('popupArtistBiography');
  const popupPortfolio = document.getElementById('popupArtistPortfolio');
  const artistPopup    = document.getElementById('artistPopup');

  // Assign or fallback
  popupImg.src            = imageUrl || 'placeholder.jpg';
  popupName.textContent   = name || 'Unknown Artist';
  popupBiography.textContent = biography || 'No biography available.';

  if (portfolio) {
    popupPortfolio.innerHTML = `<a href="${portfolio}" target="_blank">Visit Portfolio</a>`;
  } else {
    popupPortfolio.textContent = 'No portfolio available.';
  }

  // Show the popup
  artistPopup.classList.add('show');
}

function closeArtistPopup() {
  // Hide the popup
  document.getElementById('artistPopup').classList.remove('show');
}

/* Filter function for search (case-insensitive) */
function filterArtists() {
  const input = document.getElementById('artistSearchInput');
  const filter = input.value.toLowerCase();
  const grid = document.getElementById('artistsGrid');
  const cards = grid.getElementsByTagName('article'); // or 'artist-card' class

  for (let i = 0; i < cards.length; i++) {
    const artistName = cards[i].getAttribute('data-artist-name') || '';
    if (artistName.includes(filter)) {
      cards[i].style.display = '';
    } else {
      cards[i].style.display = 'none';
    }
  }
}
</script>
</body>
</html>
