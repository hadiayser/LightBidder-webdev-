<?php
session_start(); // Start the session

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/css.css?v=7"> <!-- Main CSS -->
    <link rel="stylesheet" href="../css/collections.css?v=7"> <!-- Collections CSS -->
    <link rel="stylesheet" href="../css/auctions.css"> <!-- Auctions CSS -->
    <title>Artists</title>
    <style>
        /* Add any additional styles here if needed */
      /* Container for the artists */
/* General styling for the page */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

/* Container for the artists */
.artists-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 20px;
    justify-content: center;
    margin-top: 100px;
}

/* Each artist card styling */
.artist-card {
    cursor: pointer;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 15px;
    width: 250px;
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.3s ease;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

/* Hover effect on artist card */
.artist-card:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

/* Image inside artist card */
.artist-card img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 10px;
}

/* Artist name styling */
.artist-card h4 {
    font-size: 18px;
    font-weight: bold;
    margin: 10px 0;
    color: #333;
}

/* Popup modal styling */
/* Popup modal styling */
.popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: none; /* Hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* Popup content styling */
.popup-content {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    max-width: 800px;  /* Set a maximum width */
    width: 80%;        /* Set a percentage width */
    max-height: 80vh;  /* Set a maximum height */
    display: grid;
    grid-template-columns: 1fr 2fr; /* Two columns: 1 for image, 2 for details */
    gap: 20px; /* Space between image and details */
    overflow-y: auto;  /* Allow scrolling if content overflows */
    position: relative;
}

/* Close button for the popup */
.close-button {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: transparent;
    border: none;
    font-size: 20px;
    color: #333;
    cursor: pointer;
}

/* Popup image styling */
.popup-image img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: cover; /* Ensures the image maintains aspect ratio */
    max-height: 300px; /* Set a max height for the image */
}

/* Popup artist details */
.popup-details h3 {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.popup-biography {
    font-size: 16px;
    color: #555;
    line-height: 1.5;
    overflow-y: auto;  /* Allow scrolling if biography is too long */
}


    </style>
</head>
<body>
<header>
      <div>
        <div class="nav-logo">
          <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
        </div>
        <ul id="homepageNav">
        <li><a href="index.php">Home</a></li>
          <!-- <li><a href="artworks.html">Artwork</a></li> -->
          <li><a href="collections.php">Collections</a></li>
          <li><a href="artists.php">Artists</a></li>
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

    <div class="artists-container">
        <?php while ($artist = $result->fetch_assoc()): ?>
            <div class="artist-card" onclick="showArtistPopup('<?php echo htmlspecialchars($artist['artist_name']); ?>', '<?php echo htmlspecialchars($artist['biography']); ?>', '<?php echo htmlspecialchars($artist['image_url']); ?>', '<?php echo htmlspecialchars($artist['portfolio_url']); ?>')">
            <img src="<?php echo htmlspecialchars($artist['image_url'] ?? 'placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($artist['artist_name']); ?>">
                <h4><?php echo htmlspecialchars($artist['artist_name']); ?></h4>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Artist Popup Modal -->
 <!-- Artist Popup Modal -->
<div id="artistPopup" class="popup hidden">
    <div class="popup-overlay" onclick="closeArtistPopup()"></div>
    <div class="popup-content">
        <button class="close-button" onclick="closeArtistPopup()">×</button>
        <div class="popup-image">
            <img id="popupArtistImage" src="" alt="Artist Image">
        </div>
        <div class="popup-details">
            <h3 id="popupArtistName">Artist Name</h3>
            <p id="popupArtistBiography" class="popup-biography">Biography</p>
            <p id="popupArtistPortfolio" class="popup-portfolio">Portfolio</p>
        </div>
    </div>
</div>


    <script>
      function showArtistPopup(name, biography, imageUrl, portfolio) {
    document.getElementById('popupArtistImage').src = imageUrl || 'placeholder.jpg';
    document.getElementById('popupArtistName').textContent = name || 'Unknown Artist';
    document.getElementById('popupArtistBiography').textContent = biography || 'No biography available.';
    
    // Set the portfolio URL or hide it if not available
    const portfolioElement = document.getElementById('popupArtistPortfolio');
    if (portfolio) {
        portfolioElement.innerHTML = `<a href="${portfolio}" target="_blank">View Portfolio</a>`;
    } else {
        portfolioElement.textContent = 'No portfolio available.';
    }

    const popup = document.getElementById('artistPopup');
    popup.style.display = 'flex'; // Show the popup
}
function closeArtistPopup() {
    const popup = document.getElementById('artistPopup');
    popup.style.display = 'none'; // Hide the popup
}


    </script>
    <script src="../JS/dropdown.js"></script>
</body>
</html> 