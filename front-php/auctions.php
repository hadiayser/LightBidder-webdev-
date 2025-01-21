<?php
session_start();
require_once('../php/conn.php');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/css.css?v=v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/dropdown.css?v=3">
    <link rel="stylesheet" href="../css/collections.css?v=3">
    <link rel="stylesheet" href="../css/auctions.css?v=4">
    <script src="../js/hamburger.js"></script>

    <title>All Auctions</title>
    <style>
        /* Background styling */
        body {
            background: 
                url('../img/madonna_and_child_on_a_curved_throne_1937.1.1.jpg') no-repeat center center fixed,
                url('../img/saint_john_the_evangelist__right_panel__1939.1.261.c.jpg') no-repeat center center fixed,
                url('../img/background3.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: white; /* Change text color for better visibility */
        }

        /* Dimmed overlay */
        .background-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Adjust opacity for dimming effect */
            z-index: 1; /* Ensure overlay is above the background */
        }

        /* Ensure content is above the overlay */
        .content {
            position: relative;
            z-index: 2; /* Ensure content is above the overlay */
        }
        /* Notification Styles */
/* Notification Styles */
.notification {
    position: fixed;
    top: 150px; /* Adjust this value to position it lower */
    right: 20px; /* Keep this value to position it to the right */
    background-color: #3f7dc0;
    color: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    transition: opacity 0.3s ease, transform 0.3s ease;
    opacity: 0;
    transform: translateY(-20px);
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.hidden {
    display: none;
}

.notification.show {
    display: flex;
    opacity: 1;
    transform: translateY(0);
}
    </style>
</head>
<body>
    <div class="background-overlay"></div> <!-- Dimmed overlay -->
    <div class="content"> <!-- Content wrapper -->
    <header>
      <div>
        <div class="nav-logo">
          <a href="index.php" class="logo">
            <img src="./img/bidder-high-resolution-logo-black-transparent.png" alt="">
          </a>
        </div>

        <!-- Hamburger Menu Button for Mobile -->
        <button class="hamburger" aria-label="Toggle navigation">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </button>

        <ul id="homepageNav">
          <li><a href="index.php">Home</a></li>
          <li><a href="collections.php">Collections</a></li>
          <li><a href="artists.php">Artists</a></li>
          <li><a href="auctions.php">Auctions</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="forum.php">Forum</a></li>
          <li><a href="faq.php">FAQ</a></li>
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
                <a href="messages.php">Messages</a> <!-- Added Messages Link in Dropdown -->
                <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
              </div>
            </li>
          <?php else: ?>
            <li><a href="HTML/web.html">Login/Signup</a></li>
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
                        <div class="auction-card">
                            <img src="<?php echo htmlspecialchars($auction['image_url']); ?>" alt="Artwork Image">
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
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>About Us</h4>
                <p>Bidder is your go-to marketplace for discovering, bidding on, and collecting unique artworks from around the world.</p>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="collections.php">Collections</a></li>
                    <li><a href="artists.php">Artists</a></li>
                    <li><a href="auctions.php">Auctions</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="terms.html">Terms & Conditions</a></li>
                    <li><a href="legal.html">Legal</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>Email: <a href="mailto:support@bidder.com">support@bidder.com</a></p>
                <p>Phone: +1 (111) 111-111</p>
                <p>Location: Paris, France</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date("M, Y"); ?> Bidder. All Rights Reserved.</p>
        </div>
      </footer>
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
<div id="notification" class="notification hidden">
    <div class="notification-content">
        <span id="notification-message"></span>
        <button id="close-notification">Close</button>
    </div>
</div>
</body>
</html> 