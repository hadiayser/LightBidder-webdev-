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
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Check if artist ID is provided
if (!isset($_GET['id'])) {
    header("Location: artists.php");
    exit();
}

$artistId = intval($_GET['id']);

// Fetch artist details from the database
$query = "SELECT artist_name, biography, image_url, portfolio_url FROM artists WHERE artist_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $artistId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Redirect if no artist is found with the given ID
    header("Location: artists.php");
    exit();
}

$artist = $result->fetch_assoc();

// Fetch artworks for the artist
$artworks_query = "
    SELECT artwork_id, title, image_url, description, year_created, starting_price 
    FROM artworks 
    WHERE artist_id = ? 
    ORDER BY artwork_id DESC
";
$stmt = $conn->prepare($artworks_query);
$stmt->bind_param("i", $artistId);
$stmt->execute();
$artworks_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artist['artist_name']); ?> - Artist Profile</title>
    <link rel="stylesheet" href="../css/artist-details.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>">
    <!-- Google Fonts for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnH1zJ0D4D5CjGxLEKHzPiVmwlvJ5lJvQjXU4kV1ZKQkgnqjvhvvy1aztYuwlTvZ9KQkXZqNw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <!-- Header is assumed to be included here -->
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
        <!-- Artist Profile Section -->
        <section class="artist-profile">
            <div class="profile-image">
                <img src="<?php echo htmlspecialchars($artist['image_url']); ?>" alt="<?php echo htmlspecialchars($artist['artist_name']); ?>">
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($artist['artist_name']); ?></h1>
                <p class="biography"><?php echo nl2br(htmlspecialchars($artist['biography'])); ?></p>
                <?php if (!empty($artist['portfolio_url'])): ?>
                    <a href="<?php echo htmlspecialchars($artist['portfolio_url']); ?>" target="_blank" class="portfolio-link">
                        <i class="fas fa-external-link-alt"></i> Visit Portfolio
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Artworks Section -->
        <section class="artworks-section">
            <h2>Artworks</h2>
            <div class="artworks-grid">
                <?php if ($artworks_result && $artworks_result->num_rows > 0): ?>
                    <?php while ($artwork = $artworks_result->fetch_assoc()): ?>
                        <div class="artwork-card">
                            <div class="artwork-image-container">
                                <img src="<?php echo htmlspecialchars($artwork['image_url']); ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>" class="artwork-image">
                                <div class="overlay">
                                    <div class="overlay-text">
                                        <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($artwork['year_created']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="artwork-details">
                                <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                <p class="description"><?php echo nl2br(htmlspecialchars($artwork['description'])); ?></p>
                                <div class="artwork-meta">
                                    <span><strong>Year:</strong> <?php echo htmlspecialchars($artwork['year_created']); ?></span>
                                    <span><strong>Starting Price:</strong> $<?php echo number_format($artwork['starting_price'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-artworks">No artworks found for this artist.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer is assumed to be included here -->
    <script src="../JS/dropdown.js"></script>
</body>
</html>
