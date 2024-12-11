<?php
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
    <link rel="stylesheet" href="../css/css.css?v=1" />
    <link rel="stylesheet" href="../css/collections.css?v=1" />
    <title>Collections</title>
</head>
<body>
  <header>
    <div>
      <div class="nav-logo">
        <a href="#" class="logo"><img src="../img/logo-no-background.png" alt="Logo"></a>
      </div>
      <ul id="homepageNav">
        <li><a href="index.php">Home</a></li>
        <li><a href="artworks.php">Artwork</a></li>
        <li><a href="collections.php">Collections</a></li>
        <li><a href="exhibitions.php">Exhibitions</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="login.php">Login/Signup</a></li>
      </ul>
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

</body>
</html>
