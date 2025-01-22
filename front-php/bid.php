<?php
session_start();
require_once('../php/conn.php');

// **Added: Helper function to determine the correct image path**
function getImagePath($image_url, $placeholder = '../img/placeholder.jpg') {
    if (!empty($image_url)) {
        // Check if the image URL is an external link
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            return $image_url; // External URL
        }
        // Check if the image URL starts with 'uploads/' indicating an internal path
        elseif (strpos($image_url, 'uploads/') === 0) {
            return '../' . $image_url; // Internal path
        }
        else {
            return $image_url; // Other internal path or relative path
        }
    }
    else {
        return $placeholder; // Return placeholder if image_url is empty
    }
}

// Initialize $user as an empty array
$user = [];

// Ensure user is logged in and fetch user data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT firstname, profile_picture FROM users WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
        
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        // Optionally, set an error message for the user
    }
}
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: web.html");
    exit();
}

// Get the auction ID from the URL
if (isset($_GET['auction_id'])) {
    $auction_id = $_GET['auction_id'];

    // Fetch auction details along with bids
    $auction_query = "
        SELECT a.*, w.title, w.image_url, MAX(b.bid_amount) AS highest_bid, b.bid_amount AS last_bid, b.bid_time
        FROM auctions a
        JOIN artworks w ON a.artwork_id = w.artwork_id
        LEFT JOIN bids b ON a.auction_id = b.auction_id
        WHERE a.auction_id = ?";
    $stmt = $conn->prepare($auction_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $auction_result = $stmt->get_result();

    if ($auction_result->num_rows === 0) {
        // Auction not found
        header("Location: auctions.php");
        exit();
    }

    $auction = $auction_result->fetch_assoc();
    $highest_bid = $auction['highest_bid'] ?? 0; // Default to 0 if no bids
    $last_bid = $auction['last_bid'] ?? null; // Get the last bid amount
    $last_bid_time = $auction['bid_time'] ?? null; // Get the last bid time
} else {
    // No auction ID provided
    header("Location: auctions.php");
    exit();
}

function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $time_ago = time() - $timestamp;
    $seconds = $time_ago;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "an hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4) {
        return ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "one month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "one year ago" : "$years years ago";
    }
}

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid_amount'])) {
    $bid_amount = $_POST['bid_amount'];

    // Validate bid amount
    if ($bid_amount <= $highest_bid) {
        $error_message = "Your bid must be higher than the current highest bid of $" . number_format($highest_bid, 2) . ".";
    } else {
        // Insert the bid into the database
        $insert_bid_query = "INSERT INTO bids (auction_id, user_id, bid_amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_bid_query);
        $stmt->bind_param("iid", $auction_id, $_SESSION['user_id'], $bid_amount);

        if ($stmt->execute()) {
            $success_message = "Your bid has been placed successfully!";
            // Optionally, you can redirect to the auction page or show a confirmation
        } else {
            $error_message = "Error placing your bid.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bid on Artwork</title>
    <link rel="stylesheet" href="../css/css.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?<?php echo time(); ?>">
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
                            // **Updated: Use getImagePath for profile picture**
                            $avatarPath = '../img/default-avatar.png'; // Ensure this path is correct
                            if (!empty($user['profile_picture'])) {
                                $avatarPath = getImagePath($user['profile_picture'], '../img/default-avatar.png');
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

<div class="auctions-container">
    <div class="bid-content">
        <div class="artwork-display">
            <h1><?php echo htmlspecialchars($auction['title']); ?></h1>
            <p class="price"><strong>Starting Price:</strong> $<?php echo number_format($auction['reserve_price'], 2); ?></p>
            <p class="price"><strong>Highest Bid:</strong> $<?php echo number_format($highest_bid, 2); ?></p>
            <!-- **Updated: Use getImagePath for artwork image** -->
            <img src="<?php echo htmlspecialchars(getImagePath($auction['image_url'], '../img/placeholder.jpg')); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>">
            <!-- <p><strong>Starting Price:</strong> $<?php echo number_format($auction['reserve_price'], 2); ?></p> -->
            <!-- <p><strong>Auction Ends On:</strong> <?php echo date('Y-m-d H:i', strtotime($auction['end_date'])); ?></p> -->
            <!-- <?php if ($last_bid): ?>
                <p><strong>Last Bid:</strong> $<?php echo number_format($last_bid, 2); ?> (Placed <?php echo time_ago($last_bid_time); ?> ago)</p>
            <?php endif; ?> -->
        </div>
        <div class="bid-form">
            <div class="bidding-log">
                <h4>Bidding Log</h4>
                <ul>
                    <?php
                    // Fetch and display the bidding history
                    $log_query = "SELECT bid_amount, bid_time FROM bids WHERE auction_id = ? ORDER BY bid_time DESC";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("i", $auction_id);
                    $log_stmt->execute();
                    $log_result = $log_stmt->get_result();

                    while ($log_entry = $log_result->fetch_assoc()) {
                        echo '<li>$' . number_format($log_entry['bid_amount'], 2) . ' - ' . time_ago($log_entry['bid_time']) . '</li>';
                    }
                    ?>
                </ul>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="auction_id" value="<?php echo $auction['auction_id']; ?>">
                <label for="bid_amount">Your Bid Amount:</label>
                <input type="number" id="bid_amount" name="bid_amount" step="0.01" required>
                <button type="submit">Place Bid</button>
            </form>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bidding Log Section -->
    
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
