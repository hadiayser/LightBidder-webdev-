<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Check if bid amount and artwork ID are set
if (isset($_POST['bid_amount']) && isset($_POST['artwork_id'])) {
    $bid_amount = $_POST['bid_amount'];
    $artwork_id = $_POST['artwork_id'];

    // Simulate placing a bid (you can store it in session or handle it as needed)
    $_SESSION['last_bid'] = [
        'artwork_id' => $artwork_id,
        'bid_amount' => $bid_amount,
        'user_id' => $_SESSION['user_id']
    ];

    // Redirect back to the bidding page or handle accordingly
    header("Location: bid.php?artwork_id=" . $artwork_id);
    exit();
} else {
    // Handle invalid bid submission
    header("Location: collections.php");
    exit();
}
?> 