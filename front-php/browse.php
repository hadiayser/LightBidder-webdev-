<?php
require_once('../php/conn.php');

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
// Debugging setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the collection ID
$collection_id = isset($_GET['collection_id']) ? (int)$_GET['collection_id'] : 0;

// Validate collection_id
if ($collection_id <= 0) {
    die("Invalid or missing collection ID. Please go back and try again.");
}

// Fetch collection details
$query = "SELECT * FROM collections WHERE collection_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$result = $stmt->get_result();
$collection = $result->fetch_assoc();

if (!$collection) {
    die("Collection not found.");
}

$collection_name = $collection['name'];
$collection_description = $collection['description'];

// Fetch artworks and associated artist details
$query = "
    SELECT artworks.*, artists.artist_name, artists.biography AS artist_description, artworks.description AS artwork_description
    FROM artworks
    LEFT JOIN artists ON artworks.artist_id = artists.artist_id
    WHERE artworks.collection_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/css.css?v=7">
    <link rel="stylesheet" href="../css/collections.css?v=7">
    <link rel="stylesheet" href="../css/auctions.css">
    <title><?php echo htmlspecialchars($collection_name); ?> - Collections</title>
    <style>
        /* Popup styling */
        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .popup:not(.hidden) {
            visibility: visible;
            opacity: 1;
        }

        .popup-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            cursor: pointer;
        }

        .popup-content {
            position: relative;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            max-width: 800px;
            width: 90%;
            display: flex;
            gap: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.4s ease;
        }

        .popup-image img {
            max-width: 250px;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
            transition: transform 0.3s ease;
            cursor: zoom-in;
        }
        .popup-image img.zoomed {
            transform: scale(2);
            cursor: zoom-out;
        }

        .popup-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .popup-details h3 {
            margin: 0 0 10px;
            font-size: 26px;
            color: #333;
        }

        .popup-details p {
            margin: 5px 0;
            color: #555;
            font-size: 16px;
        }

        .popup-artist {
            font-weight: bold;
            color: #222;
        }

        .popup-description,
        .popup-biography {
            line-height: 1.6;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 28px;
            color: #333;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-button:hover {
            color: #e74c3c;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
<div class="content"> 
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
<div class="browse-container">
    <h2 class="browse-title"><?php echo htmlspecialchars($collection_name); ?></h2>
    <p class="browse-description"><?php echo nl2br(htmlspecialchars($collection_description)); ?></p>
        
    <div class="browse-grid">
        <?php
        // Display artworks
        if ($result->num_rows > 0):
            while ($artwork = $result->fetch_assoc()): ?>
                <div class="artwork" 
                     onclick="showPopup(
                         '<?php echo htmlspecialchars($artwork['image_url'] ?? ''); ?>',
                         '<?php echo htmlspecialchars($artwork['title'] ?? 'Untitled'); ?>',
                         '<?php echo htmlspecialchars($artwork['artist_name'] ?? 'Unknown Artist'); ?>',
                         '<?php echo htmlspecialchars($artwork['artist_description'] ?? 'No description available.'); ?>',
                         '<?php echo htmlspecialchars($artwork['artwork_description'] ?? 'No description available.'); ?>'
                     )">
                    <img src="<?php echo htmlspecialchars($artwork['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($artwork['title'] ?? ''); ?>">
                    <h4><?php echo htmlspecialchars($artwork['title'] ?? ''); ?></h4>
                </div>
            <?php endwhile;
        else:
            echo '<p>No artworks found in this collection.</p>';
        endif;
        ?>
    </div>
</div>

<!-- Popup Modal -->
<div id="artworkPopup" class="popup hidden">
    <div class="popup-overlay" onclick="closePopup()"></div>
    <div class="popup-content">
        <button class="close-button" onclick="closePopup()">×</button>
        <div class="popup-image">
            <img id="popupImage" src="" alt="Artwork Image">
        </div>
        <div class="popup-details">
            <h3 id="popupTitle">Artwork Title</h3>
            <p class="popup-artist"><strong>Artist:</strong> <span id="popupArtist">Artist Name</span></p>
            <p id="popupArtworkDescription" class="popup-description">Artwork Description</p>
            <p id="popupBiography" class="popup-biography">Biography or Details</p>
        </div>
    </div>
</div>

</div>

<script>
    // Function to show the popup with artwork details and enable zoom
    function showPopup(imageUrl, title, artistName, description, artworkDescription) {
        console.log('Popup Data:', { imageUrl, title, artistName, description, artworkDescription });

        // Update the popup content
        const popupImage = document.getElementById('popupImage');
        popupImage.src = imageUrl || 'placeholder.jpg';
        document.getElementById('popupTitle').textContent = title || 'Untitled';
        document.getElementById('popupArtist').textContent = artistName || 'Unknown Artist';
        document.getElementById('popupBiography').textContent = description || 'No description available.';
        document.getElementById('popupArtworkDescription').textContent = artworkDescription || 'No description available.';

        // Remove zoom class if previously applied
        popupImage.classList.remove('zoomed');

        // Add click handler to toggle zoom
        popupImage.onclick = function() {
            this.classList.toggle('zoomed');
        };

        // Show the popup
        const popup = document.getElementById('artworkPopup');
        popup.classList.remove('hidden');
    }

    // Function to close the popup
    function closePopup() {
        const popup = document.getElementById('artworkPopup');
        popup.classList.add('hidden');
    }
</script>
</body>
</html>
