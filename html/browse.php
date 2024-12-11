<?php
require_once('../php/conn.php');

// Add debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the collection ID and add debugging
$collection_id = isset($_GET['collection_id']) ? (int)$_GET['collection_id'] : 0;
echo "<!-- Debug: Received collection_id = " . $collection_id . " -->";

// Validate collection_id more thoroughly
if (!isset($_GET['collection_id']) || empty($_GET['collection_id'])) {
    die("No collection ID provided. Please go back and try again.");
}

if ($collection_id <= 0) {
    die("Invalid collection ID format. Please go back and try again.");
}

// Prepare the query with error checking
$query = "SELECT * FROM collections WHERE collection_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $collection_id);

// Execute with error checking
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
$collection = $result->fetch_assoc();

// Check if collection exists
if (!$collection) {
    die("Collection with ID $collection_id not found. Please go back and try again.");
}

$collection_name = $collection['name'];
$collection_description = $collection['description'];

// Fetch artworks from the selected collection
$query = "
    SELECT artworks.* 
    FROM artworks 
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
    <link rel="stylesheet" href="../css/css.css?v=1">
    <link rel="stylesheet" href="../css/collections.css?v=1">
    <title><?php echo htmlspecialchars($collection_name); ?> - Collections</title>
</head>
<body>
    <header>
        <div>
            <div class="nav-logo">
                <a href="#" class="logo"><img src="../img/logo-no-background.png" alt="Logo"></a>
            </div>
            <ul id="homepageNav">
                <li><a href="index.html">Home</a></li>
                <li><a href="artworks.html">Artwork</a></li>
                <li><a href="collections.php">Collections</a></li>
                <li><a href="exhibitions.html">Exhibitions</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><a href="login.html">Login/Signup</a></li>
            </ul>
        </div>
    </header>

    <div class="browse-container">
        <h2 class="browse-title"><?php echo htmlspecialchars($collection_name); ?></h2>
        <p class="browse-description"><?php echo nl2br(htmlspecialchars($collection_description)); ?></p>
        <div class="browse-grid">
            <?php
            // Fetch and display the artworks
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="browse-item">';
                    echo '<img src="' . htmlspecialchars($row['image_url']) . '" alt="' . htmlspecialchars($row['title']) . '">';
                    echo '<h4>' . htmlspecialchars($row['title']) . '</h4>';
                    echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>No artworks found in this collection.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>
