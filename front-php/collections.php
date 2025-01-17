<?php
session_start();
include('../php/conn.php'); // Include database connection

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
<header>
    <div>
        <div class="nav-logo">
            <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
        </div>
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
                        <a href="../php/logout.php" style="background-color: #cb5050;">Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="web.html">Login/Signup</a></li>
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
