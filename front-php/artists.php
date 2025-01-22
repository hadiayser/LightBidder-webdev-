<?php
session_start(); 
require_once('../php/conn.php');

// Initialize $user as an empty array
$user = [];

// Ensure user is logged in and fetch user data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT firstname, profile_picture FROM users WHERE user_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['error'] = "Internal server error. Please try again later.";
        header("Location: web.php");
        exit();
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    
    $stmt->close();
}

// Redirect to login/signup page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.php"); // Ensure it's web.php
    exit();
}

// Fetch artists from the database with their profile picture (from users table)
$query = "
    SELECT 
        artists.artist_id, 
        artists.artist_name, 
        artists.biography, 
        artists.portfolio_url, 
        artists.image_url,
        users.profile_picture AS artist_image
    FROM artists
    JOIN users ON artists.user_id = users.user_id
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Failed to prepare statement: " . $conn->error);
    $_SESSION['error'] = "Internal server error. Please try again later.";
    header("Location: web.php");
    exit();
}
$stmt->execute();
$result = $stmt->get_result();

$artists = [];
while ($row = $result->fetch_assoc()) {
    $artists[] = $row;
}
$stmt->close();
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
  <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>" />
  <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>" />
  
  <!-- Include Font Awesome for search icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  
  <!-- Optional: Add some basic styling for error messages or images -->
 
</head>
<body>
<header id="messagesHeader">
    <div>
        <div class="nav-logo">
            <!-- Example brand logo -->
            <a href="#" class="logo">
                <img src="../css/img/bidder-high-resolution-logo-black-transparent.png" alt="Brand Logo">
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
                            $avatarPath = '../img/default-avatar.png'; // Default avatar path
                            if (!empty($user['profile_picture'])) {
                                $avatarPath = '../' . $user['profile_picture']; // Profile picture path
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                 alt="Profile" 
                                 class="profile-img" style="width:40px; height:40px; border-radius:50%;">
                            <span><?php echo htmlspecialchars($user['firstname']); ?></span>
                        </div>
                        <i class="arrow down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <a href="my-collections.php">My Collections</a>
                        <a href="my_favorites.php">My Favorites</a>
                        <a href="messages.php">Messages</a>
                        <a href="../php/logout.php" style="background-color: #cb5050;">Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="./HTML/web.php">Login/Signup</a></li> <!-- Changed to web.php -->
            <?php endif; ?>
        </ul>
    </div>
</header>

<div id="collections">
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

    <!-- Display Error Message if Any -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Artists Grid -->
    <div class="articles" id="artistsGrid"><!-- 'articles' for the new card layout -->
        <?php foreach ($artists as $artist): ?>
            <?php
                // Placeholders for missing data
                $artistName = !empty($artist['artist_name']) ? $artist['artist_name'] : 'Unknown Artist';
                // Check if profile_picture is available in the database, use it if exists
                $artistImage = !empty($artist['artist_image']) ? $artist['artist_image'] : ''; 
                // If profile_picture is not available, use the external image_url or default image
                if (empty($artistImage) && !empty($artist['image_url'])) {
                    $artistImage = $artist['image_url'];
                }
                // If both profile_picture and image_url are missing, use a default placeholder image
                if (empty($artistImage)) {
                    $artistImage = '../img/placeholder.jpg'; // Default placeholder image with correct path
                }
                $bio        = !empty($artist['biography']) ? $artist['biography'] : 'No biography available.';
                $artistId   = $artist['artist_id'];

                // The path to the artist's profile image (if from uploads/profile_pictures/)
                if (strpos($artistImage, 'uploads/profile_pictures/') === 0) {
                    $artistImagePath = '../' . $artistImage; // Prepend '../' to match directory structure
                } else {
                    $artistImagePath = $artistImage; // External URL or already correct path
                }
            ?>

            <article class="artist-card">
                <a href="artist-detail.php?id=<?php echo $artistId; ?>">
                    <figure>
                        <img src="<?php echo htmlspecialchars($artistImagePath); ?>" 
                             alt="<?php echo htmlspecialchars($artistName); ?>"
                             class="artist-profile-img">
                    </figure>
                    <div class="article-body">
                        <h1 class="artist-name"><?php echo htmlspecialchars($artistName); ?></h1>
                        <!-- Optional: Display biography or other details -->
                        <!-- <p><?php echo htmlspecialchars($bio); ?></p> -->
                        <!-- Optional: Link to portfolio -->
                        <?php if (!empty($portfolio)): ?>
                            <p><a href="<?php echo htmlspecialchars($portfolio); ?>" target="_blank">View Portfolio</a></p>
                        <?php endif; ?>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<!-- Include any additional scripts -->
<script src="../JS/dropdown.js"></script>
<script>
/* Filter function for search (case-insensitive) */
function filterArtists() {
    const input = document.getElementById('artistSearchInput');
    const filter = input.value.toLowerCase();
    const grid = document.getElementById('artistsGrid');
    const cards = grid.getElementsByTagName('article'); // or 'artist-card' class

    for (let i = 0; i < cards.length; i++) {
        const artistNameElement = cards[i].getElementsByTagName('h1')[0];
        const artistName = artistNameElement ? artistNameElement.textContent || '' : '';
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
