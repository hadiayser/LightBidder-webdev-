<?php
session_start();
include('../php/conn.php'); // Include database connection

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
// Fetch search keyword
$searchKeyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Adjust SQL query based on search input
if ($searchKeyword) {
    $query = "SELECT * FROM artworks 
              WHERE title LIKE '%$searchKeyword%' OR description LIKE '%$searchKeyword%' 
              ORDER BY collection_id";
} else {
    $query = "SELECT * FROM artworks ORDER BY collection_id";
}

$result = mysqli_query($conn, $query);

$collections = []; // Store grouped artworks
while ($row = mysqli_fetch_assoc($result)) {
    $collections[$row['collection_id']][] = $row;
}

// Fetch collection names
$collectionsQuery = "SELECT * FROM collections";
$collectionsResult = mysqli_query($conn, $collectionsQuery);

$collectionsNames = [];
while ($collection = mysqli_fetch_assoc($collectionsResult)) {
    $collectionsNames[$collection['collection_id']] = $collection['name'];
}

// Debugging
echo "<!-- Debug: Search keyword = $searchKeyword -->";
echo "<!-- Debug: Query executed = $query -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>" />
<link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>" />
<script src="../js/hamburger.js"></script>


    <link rel="stylesheet" href="../css/auctions.css" />

    <title>Collections</title>
</head>
<body>
<div class="content"> <!-- Content wrapper -->
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
<div id="collections">
    <h2>Explore Our Collections</h2>

    <!-- Search Form -->
    <form method="GET" action="collections.php" class="search-form">
    <input type="text" name="search" placeholder="Search artworks..." value="<?php echo htmlspecialchars($searchKeyword); ?>" />
    <button type="submit">Search</button>
</form>


    <?php if (empty($collections)): ?>
        <p>No artworks found for "<?php echo htmlspecialchars($searchKeyword); ?>".</p>
    <?php else: ?>
        <?php foreach ($collections as $collection_id => $artworks): ?>
            <div class="category-row">
                <h3><?php echo $collectionsNames[$collection_id] ?? 'Unknown Collection'; ?></h3>
                <div class="artwork-row">
                    <?php foreach ($artworks as $artwork): ?>
                        <div class="artwork">
                            <img src="<?php echo $artwork['image_url']; ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                            <h4><?php echo htmlspecialchars($artwork['title']); ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="browse.php?collection_id=<?php echo (int)$collection_id; ?>" class="browse-button">
                    Browse <?php echo $collectionsNames[$collection_id] ?? 'Collection'; ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
