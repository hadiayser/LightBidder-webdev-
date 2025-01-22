<?php
session_start();
require_once('../php/conn.php');

// Helper function to determine the correct image path
function getImagePath($image_url, $placeholder = '../img/placeholder.jpg') {
    if (!empty($image_url)) {
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            return $image_url; // External URL
        } elseif (strpos($image_url, 'uploads/') === 0) {
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

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the collection ID
$collection_id = isset($_GET['collection_id']) ? (int)$_GET['collection_id'] : 0;

// Validate collection_id
if ($collection_id <= 0) {
    die("Invalid or missing collection ID. Please go back and try again.");
}

// Fetch collection details using prepared statements
$query = "SELECT * FROM collections WHERE collection_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . htmlspecialchars($conn->error));
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
$stmt->close();

// Fetch all artworks and associated artist details
$query = "
    SELECT artworks.*, artists.artist_name, artists.biography AS artist_description, artworks.description AS artwork_description
    FROM artworks
    LEFT JOIN artists ON artworks.artist_id = artists.artist_id
    WHERE artworks.collection_id = ?
    ORDER BY artworks.year_created DESC";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch artworks into an array
$artworks = [];
while ($row = $result->fetch_assoc()) {
    $artworks[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Artworks in <?php echo htmlspecialchars($collection_name); ?> - Collections</title>
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>">
    <style>
        /* Reuse the same popup styling as in browse.php */
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

        

        @media (max-width: 1024px) {
            .artwork {
                flex: 1 1 calc(33.333% - 20px);
            }
        }

        @media (max-width: 768px) {
            .artwork {
                flex: 1 1 calc(50% - 20px);
            }

            .popup-content {
                flex-direction: column;
                align-items: center;
            }

            .popup-image img {
                max-width: 100%;
            }

            .popup-details {
                align-items: center;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .artwork {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
<div class="content"> 
    <header id="messagesHeader">
        <div>
            <div class="nav-logo">
                <!-- Corrected brand logo path -->
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
                                    $avatarPath = getImagePath($user['profile_picture'], '../img/default-avatar.png');
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
                    <li><a href="./HTML/web.php">Login/Signup</a></li> <!-- Changed to web.php for consistency -->
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="browse-container">
        <h2 class="browse-title"><?php echo htmlspecialchars($collection_name); ?></h2>
        <p class="browse-description"><?php echo nl2br(htmlspecialchars($collection_description)); ?></p>
            
        <div class="browse-grid">
            <?php
            // Display artworks, limited to 3
            if (!empty($artworks)):
                foreach ($artworks as $artwork): ?>
                    <div class="artwork" 
                         onclick="showPopup(
                             '<?php echo htmlspecialchars(getImagePath($artwork['image_url'], '../img/placeholder.jpg')); ?>',
                             '<?php echo htmlspecialchars($artwork['title'] ?? 'Untitled'); ?>',
                             '<?php echo htmlspecialchars($artwork['artist_name'] ?? 'Unknown Artist'); ?>',
                             '<?php echo htmlspecialchars($artwork['artist_description'] ?? 'No biography available.'); ?>',
                             '<?php echo htmlspecialchars($artwork['artwork_description'] ?? 'No description available.'); ?>'
                         )">
                        <img src="<?php echo htmlspecialchars(getImagePath($artwork['image_url'], '../img/placeholder.jpg')); ?>" alt="<?php echo htmlspecialchars($artwork['title'] ?? ''); ?>">
                        <h4><?php echo htmlspecialchars($artwork['title'] ?? ''); ?></h4>
                    </div>
                <?php endforeach;
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
                <li><a href="./HTML/terms.html">Terms & Conditions</a></li>
                <li><a href="./HTML/legal.html">Legal</a></li>
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
    // Function to show the popup with artwork details and enable zoom
    function showPopup(imageUrl, title, artistName, description, artworkDescription) {
        console.log('Popup Data:', { imageUrl, title, artistName, description, artworkDescription });

        // Update the popup content
        const popupImage = document.getElementById('popupImage');
        popupImage.src = imageUrl || '../img/placeholder.jpg';
        document.getElementById('popupTitle').textContent = title || 'Untitled';
        document.getElementById('popupArtist').textContent = artistName || 'Unknown Artist';
        document.getElementById('popupBiography').textContent = description || 'No biography available.';
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

    // Dropdown menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const dropdown = document.querySelector('.nav-item.dropdown');
        const dropbtn = document.querySelector('.dropbtn');

        if (dropdown && dropbtn) {
            dropbtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropdown.classList.toggle('active');
            });

            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            dropdown.querySelector('.dropdown-content').addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>
</body>
</html>
