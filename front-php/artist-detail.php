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

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.php"); // Ensure it's web.php
    exit();
}

// Check if artist ID is provided
if (!isset($_GET['id'])) {
    header("Location: artists.php");
    exit();
}

$artistId = intval($_GET['id']);

// Fetch artist details from the database, including profile_picture from users table
$query = "
    SELECT 
        artists.artist_name, 
        artists.biography, 
        artists.image_url, 
        artists.portfolio_url, 
        users.profile_picture 
    FROM artists
    JOIN users ON artists.user_id = users.user_id
    WHERE artists.artist_id = ?
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $_SESSION['error'] = "Internal server error. Please try again later.";
    header("Location: web.php");
    exit();
}
$stmt->bind_param("i", $artistId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Redirect if no artist is found with the given ID
    header("Location: artists.php");
    exit();
}

$artist = $result->fetch_assoc();
$stmt->close();

// Fetch artworks for the artist
$artworks_query = "
    SELECT artwork_id, title, image_url, description, year_created, starting_price 
    FROM artworks 
    WHERE artist_id = ? 
    ORDER BY artwork_id DESC
";
$stmt = $conn->prepare($artworks_query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $_SESSION['error'] = "Internal server error. Please try again later.";
    header("Location: web.php");
    exit();
}
$stmt->bind_param("i", $artistId);
$stmt->execute();
$artworks_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($artist['artist_name']); ?> - Artist Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Main CSS for the Glassy Search Bar & New Card Layout -->
    <link rel="stylesheet" href="../css/artist-details.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>">
    
    <!-- Google Fonts for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnH1zJ0D4D5CjGxLEKHzPiVmwlvJ5lJvQjXU4kV1ZKQkgnqjvhvvy1aztYuwlTvZ9KQkXZqNw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Optional: Add some basic styling for error messages or images -->
    <style>
        .artist-profile-img {
            width: 100%;
            max-width: 400px;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .artwork-image {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
        }
        .artist-card, .artwork-card {
            /* Add your desired styles for the artist and artwork cards */
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 16px;
            margin: 16px;
            text-align: center;
            transition: box-shadow 0.3s;
        }
        .artist-card:hover, .artwork-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .artist-name, .artwork-details h3 {
            margin-top: 12px;
            font-size: 1.2em;
            color: #333;
        }
        .biography, .description {
            color: #555;
            font-size: 1em;
            line-height: 1.5em;
        }
        .portfolio-link {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #007BFF;
            font-weight: bold;
        }
        .portfolio-link:hover {
            text-decoration: underline;
        }
        .artwork-meta {
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
        }
        .error-message {
            color: red;
            background-color: #fdd;
            padding: 10px;
            margin: 15px;
            border: 1px solid red;
            border-radius: 5px;
            text-align: center;
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 8px;
        }
        .artwork-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .artwork-image-container:hover .overlay {
            opacity: 1;
        }
        .overlay-text {
            position: absolute;
            bottom: 0;
            left: 0;
            color: #fff;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
        }
        .no-artworks {
            text-align: center;
            color: #777;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <!-- Header is included here -->
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
                            <a href="../php/logout.php" style="background-color: #cb5050 !important;">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="./HTML/web.php">Login/Signup</a></li> <!-- Changed to web.php -->
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <main>
        <!-- Display Error Message if Any -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Artist Profile Section -->
        <section class="artist-profile">
            <div class="profile-image">
                <?php
                // Determine the artist's profile image path
                if (!empty($artist['profile_picture'])) {
                    // Internal path
                    $artistProfilePicPath = '../' . $artist['profile_picture'];
                } elseif (!empty($artist['image_url'])) {
                    // External URL or internal path, decide based on the string
                    if (strpos($artist['image_url'], 'uploads/') === 0) {
                        $artistProfilePicPath = '../' . $artist['image_url'];
                    } else {
                        $artistProfilePicPath = $artist['image_url'];
                    }
                } else {
                    // Default placeholder image
                    $artistProfilePicPath = '../img/placeholder.jpg';
                }
                ?>
                <img src="<?php echo htmlspecialchars($artistProfilePicPath); ?>" alt="<?php echo htmlspecialchars($artist['artist_name']); ?>" class="artist-profile-img">
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
                        <?php
                            // Determine the artwork image path
                            if (!empty($artwork['image_url'])) {
                                if (strpos($artwork['image_url'], 'uploads/') === 0) {
                                    // Internal path
                                    $artworkImagePath = '../' . $artwork['image_url'];
                                } else {
                                    // External URL
                                    $artworkImagePath = $artwork['image_url'];
                                }
                            } else {
                                // Default placeholder image
                                $artworkImagePath = '../img/placeholder.jpg';
                            }
                        ?>
                        <div class="artwork-card">
                            <div class="artwork-image-container">
                                <img src="<?php echo htmlspecialchars($artworkImagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                                     class="artwork-image">
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
