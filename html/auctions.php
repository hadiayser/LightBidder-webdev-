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
    <link rel="stylesheet" href="../css/css.css?v=6">
    <link rel="stylesheet" href="../css/collections.css?v=3">
    <link rel="stylesheet" href="../css/auctions.css?v=4">
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
    </style>
</head>
<body>
    <div class="background-overlay"></div> <!-- Dimmed overlay -->
    <div class="content"> <!-- Content wrapper -->
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
                            <a href="bid.php?auction_id=<?php echo $auction['auction_id']; ?>" class="bid-button">Go to Auction</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
   document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.querySelector('.nav-item.dropdown');
    const dropbtn = document.querySelector('.dropbtn');

    if (dropdown && dropbtn) {
        dropbtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Prevent dropdown from closing when clicking inside
        dropdown.querySelector('.dropdown-content').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
    </script>
</body>
</html> 