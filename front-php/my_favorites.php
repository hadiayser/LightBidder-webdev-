<?php
session_start();
require_once('../php/conn.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Query user's favorites with the necessary joins
$query = "
    SELECT a.auction_id, a.start_date, a.end_date, w.title, w.image_url, MAX(b.bid_amount) AS highest_bid
    FROM favorites f
    JOIN auctions a ON f.auction_id = a.auction_id
    JOIN artworks w ON a.artwork_id = w.artwork_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE f.user_id = ?
    GROUP BY a.auction_id
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Favorites</title>
    <!-- Dynamically load CSS with cache-busting using 'time()' or 'filemtime()' -->
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/my_favorites.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>">

    <style>
    </style>
</head>
<body>
<header>
    <div>
        <div class="nav-logo">
            <a href="#" class="logo">
                <img src="../img/bidder-high-resolution-logo-black-transparent.png" alt="">
            </a>
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

<div id="favorites">
    <h2>My Favorite Auctions</h2>
    <?php if (empty($favorites)): ?>
        <p>You have no favorite auctions.</p>
    <?php else: ?>
        <div class="favorites-grid">
            <?php foreach ($favorites as $favorite): ?>
                <div class="auction-card" data-auction-id="<?php echo $favorite['auction_id']; ?>">
                    <img src="<?php echo htmlspecialchars($favorite['image_url']); ?>" alt="Artwork Image">
                    <h3><?php echo htmlspecialchars($favorite['title']); ?></h3>
                    <p>Current Highest Bid: $<?php echo number_format($favorite['highest_bid'], 2); ?></p>
                    <p>Auction Starts On: <?php echo date('Y-m-d H:i', strtotime($favorite['start_date'])); ?></p>
                    <p>Auction Ends On: <?php echo date('Y-m-d H:i', strtotime($favorite['end_date'])); ?></p>
                    <button class="remove-favorite-button" data-auction-id="<?php echo $favorite['auction_id']; ?>">
                        Remove from Favorites
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- JS for dropdown (already existing) -->
<script src="../JS/dropdown.js"></script>
<!-- JS to handle favorites logic (AJAX remove, show notification, etc.) -->
<script src="../JS/favorites.js"></script>

<!-- Notification popup -->
<div id="remove-notification" class="notification">
    <div class="notification-content">
        <span id="remove-notification-message"></span>
        <button id="close-remove-notification">×</button>
    </div>
</div>
</body>
</html>