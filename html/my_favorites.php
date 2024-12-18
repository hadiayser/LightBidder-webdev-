<?php
session_start();
require_once('../php/conn.php');

// Fetch user's favorites with image URL and title
$query = "
    SELECT a.auction_id, a.start_date, a.end_date, w.title, w.image_url, MAX(b.bid_amount) AS highest_bid
    FROM favorites f
    JOIN auctions a ON f.auction_id = a.auction_id
    JOIN artworks w ON a.artwork_id = w.artwork_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE f.user_id = ?
    GROUP BY a.auction_id"; // Ensure you group by auction_id to get the correct results

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/css.css?v=3">
    <link rel="stylesheet" href="../css/collections.css?v=6">
    <link rel="stylesheet" href="../css/my_favorites.css?v=1">
    <link rel="stylesheet" href="../css/auctions.css?v=6">
    <title>My Favorites</title>
</head>
<body>
<header>
      <div>
        <div class="nav-logo">
          <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
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
                <div class="auction-card">
                    <img src="<?php echo htmlspecialchars($favorite['image_url']); ?>" alt="Artwork Image">
                    <h3><?php echo htmlspecialchars($favorite['title']); ?></h3>
                    <p>Current Highest Bid: $<?php echo number_format($favorite['highest_bid'], 2); ?></p>
                    <p>Auction Starts On: <?php echo date('Y-m-d H:i', strtotime($favorite['start_date'])); ?></p>
                    <p>Auction Ends On: <?php echo date('Y-m-d H:i', strtotime($favorite['end_date'])); ?></p>
                    <button class="remove-favorite-button" data-auction-id="<?php echo $favorite['auction_id']; ?>">Remove from Favorites</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const removeButtons = document.querySelectorAll('.remove-favorite-button');
    const removeNotification = document.getElementById('remove-notification');
    const removeNotificationMessage = document.getElementById('remove-notification-message');
    const closeRemoveNotificationButton = document.getElementById('close-remove-notification');

    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const auctionId = this.getAttribute('data-auction-id');

            fetch('../php/remove_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'auction_id=' + auctionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    removeNotificationMessage.textContent = 'Removed from favorites!';
                    removeNotification.classList.add('show');
                    setTimeout(() => {
                        removeNotification.classList.remove('show');
                    }, 5000); // Hide after 3 seconds
                    location.reload(); // Reload the page to update the list
                } else {
                    removeNotificationMessage.textContent = 'Error removing from favorites.';
                    removeNotification.classList.add('show');
                    setTimeout(() => {
                        removeNotification.classList.remove('show');
                    }, 3000); // Hide after 3 seconds
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    closeRemoveNotificationButton.addEventListener('click', function() {
        removeNotification.classList.remove('show');
    });
});
</script>
<script src="../JS/dropdown.js"></script>
<div id="remove-notification" class="notification hidden">
    <div class="notification-content">
        <span id="remove-notification-message"></span>
        <button id="close-remove-notification">Close</button>
    </div>
</div>
</body>
</html> 