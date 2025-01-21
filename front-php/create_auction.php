<?php
session_start();
require_once('../php/conn.php');

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Get the artist ID
$user_id = $_SESSION['user_id'];
$artist_query = "SELECT artist_id FROM artists WHERE user_id = ?";
$stmt = $conn->prepare($artist_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$artist_result = $stmt->get_result();

if ($artist_result->num_rows === 0) {
    die("You must be an artist to create an auction.");
}

$artist = $artist_result->fetch_assoc();
$artist_id = $artist['artist_id'];

// Handle auction creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auction_name = $_POST['auction_name']; // Get the auction name
    $artwork_ids = $_POST['artwork_ids']; // This should be an array of artwork IDs
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reserve_price = $_POST['reserve_price'];

    // Debugging output
    echo "<!-- Debug: Start Date: $start_date, End Date: $end_date -->";

    // Check if artwork_ids is set and is an array
    if (isset($artwork_ids) && is_array($artwork_ids)) {
        // Loop through each artwork ID and insert an auction for each
        foreach ($artwork_ids as $artwork_id) {
            $insert_auction_query = "INSERT INTO auctions (artwork_id, auction_name, start_date, end_date, reserve_price, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')";
            $stmt = $conn->prepare($insert_auction_query);
            $stmt->bind_param("issds", $artwork_id, $auction_name, $start_date, $end_date, $reserve_price);

            if ($stmt->execute()) {
                $success_message = "Auction '$auction_name' created successfully for artwork ID: $artwork_id!";
            } else {
                $error_message = "Error creating auction for artwork ID: $artwork_id. " . $stmt->error;
            }
        }
    } else {
        $error_message = "No artworks selected.";
    }
}

// Fetch artworks for the dropdown
$artworks_query = "SELECT artwork_id, title, image_url FROM artworks WHERE artist_id = ?";
$stmt = $conn->prepare($artworks_query);
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$artworks_result = $stmt->get_result();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/css.css?v=7">
    <link rel="stylesheet" href="../css/collections.css?v=5">
    <link rel="stylesheet" href="../css/auctions.css?v=6">
    <title>Create Auction</title>
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

    <div class="create-auction-container">
        <h2>Create New Auction</h2>
        <form method="POST">
            <label for="auction_name">Auction Name:</label>
            <input type="text" name="auction_name" required>

            <label for="artwork_id">Select Artwork(s):</label>
            <div class="artwork-selection">
                <?php while ($artwork = $artworks_result->fetch_assoc()): ?>
                    <div class="artwork-item" onclick="selectArtwork(<?php echo $artwork['artwork_id']; ?>)">
                        <img src="<?php echo htmlspecialchars($artwork['image_url']); ?>">
                        <h4><?php echo htmlspecialchars($artwork['title']); ?></h4>
                        <input type="checkbox" name="artwork_ids[]" value="<?php echo $artwork['artwork_id']; ?>" style="display: none;">
                    </div>
                <?php endwhile; ?>
            </div>

            <label for="start_date">Start Date:</label>
            <input type="datetime-local" name="start_date" required>

            <label for="end_date">End Date:</label>
            <input type="datetime-local" name="end_date" required>

            <label for="reserve_price">Reserve Price:</label>
            <input type="number" name="reserve_price" step="0.01" required>

            <button type="submit">Create Auction</button>
        </form>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>

    <script>
        function selectArtwork(artworkId) {
            const checkbox = document.querySelector(`input[value='${artworkId}']`);
            checkbox.checked = !checkbox.checked; // Toggle the checkbox state
            const artworkItem = checkbox.closest('.artwork-item');
            artworkItem.classList.toggle('selected'); // Add a class to highlight the selected artwork
        }
    </script>
</body>
</html> 