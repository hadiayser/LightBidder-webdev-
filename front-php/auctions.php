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
// Fetch all auctions grouped by artist ID
$query = "
    SELECT a.*, w.title, w.image_url, MAX(b.bid_amount) AS highest_bid, ar.artist_name
    FROM auctions a
    JOIN artworks w ON a.artwork_id = w.artwork_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    JOIN artists ar ON w.artist_id = ar.artist_id
    GROUP BY ar.artist_name, a.auction_id
    ORDER BY a.start_date ASC";

$result = mysqli_query($conn, $query);

// Initialize an array to hold auctions grouped by artist ID
$auctionsByArtist = [];
while ($auction = mysqli_fetch_assoc($result)) {
    $auctionsByArtist[$auction['artist_name']][] = $auction;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Auctions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Main CSS Files -->
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/dropdown.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>">
    
    
</head>
<body>
    <div class="background-overlay"></div> <!-- Dimmed overlay -->
    <div class="content"> <!-- Content wrapper -->
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
                                // Determine the user's profile image path
                                if (!empty($user['profile_picture'])) {
                                    if (strpos($user['profile_picture'], 'uploads/') === 0) {
                                        $avatarPath = '../' . $user['profile_picture'];
                                    } else {
                                        $avatarPath = $user['profile_picture']; // External URL
                                    }
                                } else {
                                    $avatarPath = '../img/default-avatar.png'; // Default avatar
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

    <div class="auctions-container">
        <h2>All Auctions</h2>

        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Artist'): ?>
            <div class="create-auction-button">
                <a href="create_auction.php" class="button">Create Auction</a>
            </div>
        <?php endif; ?>

        <?php foreach ($auctionsByArtist as $artistName => $auctions): ?>
            <div class="artist-auctions">
                <h3>Artist: <?php echo htmlspecialchars($artistName); ?></h3>
                <div class="auctions-grid">
                    <?php foreach ($auctions as $auction): ?>
                        <?php
                            // Determine the auction's artwork image path
                            if (!empty($auction['image_url'])) {
                                if (strpos($auction['image_url'], 'uploads/') === 0) {
                                    // Internal path
                                    $auctionImagePath = '../' . $auction['image_url'];
                                } else {
                                    // External URL
                                    $auctionImagePath = $auction['image_url'];
                                }
                            } else {
                                // Default placeholder image
                                $auctionImagePath = '../img/placeholder.jpg';
                            }
                        ?>
                        <div class="auction-card">
                            <img src="<?php echo htmlspecialchars($auctionImagePath); ?>" alt="Artwork Image">
                            <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                            <p>Current Highest Bid: $<?php echo number_format($auction['highest_bid'], 2); ?></p>
                            <p>Auction Starts On: <?php echo date('Y-m-d H:i', strtotime($auction['start_date'])); ?></p>
                            <p>Auction Ends On: <?php echo date('Y-m-d H:i', strtotime($auction['end_date'])); ?></p>
                            <div class="button-container">
                                <button class="fav-button" data-auction-id="<?php echo $auction['auction_id']; ?>">
                                    Favorite
                                </button>
                                <a href="bid.php?auction_id=<?php echo $auction['auction_id']; ?>" class="bid-button">Bid</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Footer Content -->
    <footer class="footer">
        <!-- Footer Content -->
    </footer>
    <!-- Notification Element -->
    <div id="notification" class="notification hidden">
        <div class="notification-content">
            <span id="notification-message"></span>
            <button id="close-notification">Close</button>
        </div>
    </div>
    <!-- Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const favoriteButtons = document.querySelectorAll('.fav-button');
        const notification = document.getElementById('notification');
        const notificationMessage = document.getElementById('notification-message');
        const closeNotificationButton = document.getElementById('close-notification');

        favoriteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const auctionId = this.getAttribute('data-auction-id');

                fetch('../php/add_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'auction_id=' + auctionId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        notificationMessage.textContent = 'Added to favorites!';
                        notification.classList.add('show');
                        setTimeout(() => {
                            notification.classList.remove('show');
                        }, 3000); // Hide after 3 seconds
                    } else if (data.status === 'already_favorited') {
                        notificationMessage.textContent = 'This item is already in your favorites.';
                        notification.classList.add('show');
                        setTimeout(() => {
                            notification.classList.remove('show');
                        }, 3000); // Hide after 3 seconds
                    } else {
                        notificationMessage.textContent = 'Error adding to favorites.';
                        notification.classList.add('show');
                        setTimeout(() => {
                            notification.classList.remove('show');
                        }, 3000); // Hide after 3 seconds
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        closeNotificationButton.addEventListener('click', function() {
            notification.classList.remove('show');
        });
    });
    </script>
    <script src="../JS/dropdown.js"></script>
</body>
</html>
