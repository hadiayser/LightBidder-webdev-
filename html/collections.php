<?php
session_start();
// Include database connection file
include('../php/conn.php');

// Fetch all artworks and group by collection_id
$query = "SELECT * FROM artworks ORDER BY collection_id";
$result = mysqli_query($conn, $query);

$collections = []; // Initialize an array to hold grouped artworks
while ($row = mysqli_fetch_assoc($result)) {
    $collections[$row['collection_id']][] = $row;
}

// Fetch collection names (if collections are stored in a separate table)
$collectionsQuery = "SELECT * FROM collections"; 
$collectionsResult = mysqli_query($conn, $collectionsQuery);

$collectionsNames = [];
while ($collection = mysqli_fetch_assoc($collectionsResult)) {
    $collectionsNames[$collection['collection_id']] = $collection['name'];
}

// Add debugging
echo "<!-- Debug: Query executed = $query -->";
echo "<!-- Debug: Number of collections found = " . count($collections) . " -->";
foreach ($collections as $id => $arts) {
    echo "<!-- Debug: Collection $id has " . count($arts) . " artworks -->";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/css.css?v=3" />
    <link rel="stylesheet" href="../css/collections.css?v=2" />
    <title>Collections</title>
</head>
<body>
  <header>
    <div>
      <div class="nav-logo">
        <a href="index.php" class="logo">
            <img src="../img/bidder-high-resolution-logo-black-transparent.png" alt="Logo">
        </a>
      </div>
      <nav>
        <ul id="homepageNav">
          <li><a href="index.php">Home</a></li>
          <li><a href="artworks.html">Artwork</a></li>
          <li><a href="collections.php">Collections</a></li>
          <li><a href="exhibitions.html">Exhibitions</a></li>
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
      </nav>
    </div>
  </header>

  <div id="collections">
    <h2>Explore Our Collections</h2>

    <?php foreach ($collections as $collection_id => $artworks): ?>
        <div class="category-row">
            <h3><?php echo $collectionsNames[$collection_id] ?? 'Unknown Collection'; ?></h3>
            <div class="artwork-row">
                <?php foreach ($artworks as $artwork): ?>
                    <div class="artwork">
                        <img src="<?php echo $artwork['image_url']; ?>" alt="<?php echo $artwork['title']; ?>">
                        <h4><?php echo $artwork['title']; ?></h4>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php echo "<!-- Debug: collection_id = $collection_id -->"; ?>
            <a href="browse.php?collection_id=<?php echo (int)$collection_id; ?>" class="browse-button">
                Browse <?php echo $collectionsNames[$collection_id] ?? 'Collection'; ?>
            </a>
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
