<?php
session_start();
require_once('../php/conn.php');

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Artist') {
    header("Location: web.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch artist ID
$artist_query = "SELECT artist_id FROM artists WHERE user_id = ?";
$stmt = $conn->prepare($artist_query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Internal server error.");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$artist_result = $stmt->get_result();

if ($artist_result->num_rows === 0) {
    die("You must be an artist to access this page.");
}

$artist = $artist_result->fetch_assoc();
$artist_id = $artist['artist_id'];
$stmt->close();

// Handle Auction Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_auction_id'])) {
    // CSRF Protection (Optional but Recommended)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token.";
    } else {
        $delete_auction_id = intval($_POST['delete_auction_id']);

        // Verify that the auction belongs to the artist
        $verify_query = "SELECT a.auction_id FROM auctions a JOIN artworks w ON a.artwork_id = w.artwork_id WHERE a.auction_id = ? AND w.artist_id = ?";
        $stmt = $conn->prepare($verify_query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $error_message = "Internal server error.";
        } else {
            $stmt->bind_param("ii", $delete_auction_id, $artist_id);
            $stmt->execute();
            $verify_result = $stmt->get_result();

            if ($verify_result->num_rows > 0) {
                // Proceed to delete
                $delete_query = "DELETE FROM auctions WHERE auction_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                if (!$delete_stmt) {
                    error_log("Prepare failed: " . $conn->error);
                    $error_message = "Internal server error.";
                } else {
                    $delete_stmt->bind_param("i", $delete_auction_id);
                    if ($delete_stmt->execute()) {
                        $success_message = "Auction deleted successfully.";
                    } else {
                        $error_message = "Failed to delete auction.";
                    }
                    $delete_stmt->close();
                }
            } else {
                $error_message = "Auction not found or you don't have permission to delete it.";
            }
            $stmt->close();
        }
    }
}

// Fetch artist's auctions
$auctions_query = "
    SELECT a.*, w.title, w.image_url, MAX(b.bid_amount) AS highest_bid
    FROM auctions a
    JOIN artworks w ON a.artwork_id = w.artwork_id
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE w.artist_id = ?
    GROUP BY a.auction_id
    ORDER BY a.start_date ASC";

$stmt = $conn->prepare($auctions_query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Internal server error.");
}
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$auctions_result = $stmt->get_result();
$stmt->close();

// Fetch user data for header
$user = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT firstname, profile_picture FROM users WHERE user_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Internal server error.");
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }

    $stmt->close();
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle AJAX request for fetching auction data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_auction' && isset($_GET['auction_id'])) {
    $auction_id = intval($_GET['auction_id']);

    // Fetch auction details
    $fetch_query = "
        SELECT a.*, w.title, w.image_url
        FROM auctions a
        JOIN artworks w ON a.artwork_id = w.artwork_id
        WHERE a.auction_id = ? AND w.artist_id = ?";
    $stmt = $conn->prepare($fetch_query);
    if ($stmt) {
        $stmt->bind_param("ii", $auction_id, $artist_id);
        $stmt->execute();
        $fetch_result = $stmt->get_result();

        if ($fetch_result->num_rows > 0) {
            $auction_data = $fetch_result->fetch_assoc();
            echo json_encode(['status' => 'success', 'auction' => $auction_data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Auction not found or unauthorized.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Auctions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Main CSS Files -->
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/dropdown.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>">

    <!-- Additional CSS for Modals and Enhanced Styling -->
    <style>
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.6); /* Black w/ opacity */
            transition: all 0.3s ease;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered */
            padding: 30px;
            border: 1px solid #888;
            width: 90%; /* Could be more or less, depending on screen size */
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation-name: modalopen;
            animation-duration: 0.5s;
        }

        @keyframes modalopen {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Button Styles */
        .edit-button, .delete-button {
            padding: 10px 16px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }

        .edit-button {
            background-color: #007BFF; /* Blue */
        }

        .edit-button:hover {
            background-color: #0056b3;
        }

        .delete-button {
            background-color: #DC3545; /* Red */
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        /* Form Styles */
        .manage-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .submit-btn {
            background-color: #28A745; /* Green */
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #218838;
        }

        .cancel-btn {
            background-color: #6C757D; /* Gray */
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #17A2B8; /* Teal */
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .notification.show {
            opacity: 1;
        }

        /* Success and Error Messages */
        .success-message, .error-message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1em;
        }

        .success-message {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .error-message {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        /* Auctions Grid */
        .auctions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
            height:100vh;
        }

        .auction-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-height: 25rem;
            max-width: 20rem;
        }

        .auction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .auction-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .auction-card h3 {
            margin: 15px;
            font-size: 1.2em;
            color: #333;
        }

        .auction-card p {
            margin: 0 15px 10px 15px;
            color: #555;
            font-size: 0.95em;
        }

        /* Manage Buttons Container */
        .manage-buttons {
            display: flex;
            justify-content: flex-end;
            padding: 10px 15px 15px 15px;
            gap: 10px;
        }

        /* Responsive Modal */
        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<header id="messagesHeader">
    <div>
        <div class="nav-logo">
            <!-- Brand Logo -->
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
                        <a href="../php/logout.php" style="background-color: #cb5050 !important;">Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="./HTML/web.php">Login/Signup</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<div class="auctions-container">
    <h2>My Auctions</h2>

    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($auctions_result->num_rows > 0): ?>
        <div class="auctions-grid">
            <?php while ($auction = $auctions_result->fetch_assoc()): ?>
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
                    <div class="manage-buttons">
                        <button class="edit-button" onclick="editAuction(<?php echo $auction['auction_id']; ?>)">Edit</button>
                        <button class="delete-button" onclick="confirmDelete(<?php echo $auction['auction_id']; ?>)">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>You have not created any auctions yet. <a href="create_auction.php">Create one now!</a></p>
    <?php endif; ?>
</div>

<!-- Edit Auction Modal -->
<div id="editAuctionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Auction</h2>
        <form method="POST" id="editAuctionForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_auction">
            <input type="hidden" name="auction_id" id="edit_auction_id">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Auction Details -->
            <div class="form-group">
                <label for="edit_title">Auction Title</label>
                <input type="text" id="edit_title" name="title" required>
            </div>

            <div class="form-group">
                <label for="edit_artwork_id">Artwork ID</label>
                <input type="number" id="edit_artwork_id" name="artwork_id" readonly>
            </div>

            <div class="form-group">
                <label for="edit_start_date">Start Date</label>
                <input type="datetime-local" id="edit_start_date" name="start_date" required>
            </div>

            <div class="form-group">
                <label for="edit_end_date">End Date</label>
                <input type="datetime-local" id="edit_end_date" name="end_date" required>
            </div>

            <div class="form-group">
                <label for="edit_reserve_price">Starting Price ($)</label>
                <input type="number" id="edit_reserve_price" name="reserve_price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="edit_status">Status</label>
                <select id="edit_status" name="status" required>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_image">Change Artwork Image (Optional)</label>
                <input type="file" id="edit_image" name="image_url" accept="image/*">
            </div>

            <div class="manage-buttons">
                <button type="submit" class="submit-btn">Save Changes</button>
                <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete this auction?</p>
        <form method="POST" id="deleteAuctionForm">
            <input type="hidden" name="delete_auction_id" id="delete_auction_id_confirm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="manage-buttons">
                <button type="submit" class="delete-button">Yes, Delete</button>
                <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Footer Content -->
<footer class="footer">
    <!-- Add your footer content here -->
</footer>

<!-- Notification Element -->
<div id="notification" class="notification">
    <span id="notification-message"></span>
</div>

<!-- Scripts -->
<script>
    // Function to open the Edit Auction Modal and populate with auction data
    function editAuction(auctionId) {
        // Fetch auction data via AJAX
        fetch(`my_auctions.php?action=get_auction&auction_id=${auctionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const auction = data.auction;
                    document.getElementById('edit_auction_id').value = auction.auction_id;
                    document.getElementById('edit_title').value = auction.title;
                    document.getElementById('edit_artwork_id').value = auction.artwork_id;
                    document.getElementById('edit_start_date').value = auction.start_date.slice(0,16);
                    document.getElementById('edit_end_date').value = auction.end_date.slice(0,16);
                    document.getElementById('edit_reserve_price').value = auction.reserve_price;
                    document.getElementById('edit_status').value = auction.status;

                    // Open the modal
                    document.getElementById('editAuctionModal').style.display = 'block';
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error fetching auction data:', error);
                showNotification('An error occurred while fetching auction data.', 'error');
            });
    }

    // Function to close the Edit Auction Modal
    function closeEditModal() {
        document.getElementById('editAuctionModal').style.display = 'none';
    }

    // Function to open the Delete Confirmation Modal
    function confirmDelete(auctionId) {
        document.getElementById('delete_auction_id_confirm').value = auctionId;
        document.getElementById('deleteConfirmModal').style.display = 'block';
    }

    // Function to close the Delete Confirmation Modal
    function closeDeleteModal() {
        document.getElementById('deleteConfirmModal').style.display = 'none';
    }

    // Function to show notifications
    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        const messageSpan = document.getElementById('notification-message');
        messageSpan.textContent = message;

        // Set background color based on type
        if (type === 'success') {
            notification.style.backgroundColor = '#28A745'; // Green
        } else if (type === 'error') {
            notification.style.backgroundColor = '#DC3545'; // Red
        } else {
            notification.style.backgroundColor = '#17A2B8'; // Teal
        }

        notification.classList.add('show');

        // Hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Close modals when clicking outside of them
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });
    }

    // Close buttons functionality
    document.querySelectorAll('.close').forEach(function(closeBtn) {
        closeBtn.onclick = function() {
            this.closest('.modal').style.display = 'none';
        }
    });
</script>
<script src="../JS/dropdown.js"></script>
</body>
</html>
