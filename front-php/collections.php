<?php
// collections.php
session_start(); 
require_once('../php/conn.php');

// Helper function to determine the correct image path
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

// Redirect to login/signup page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: web.php"); // Ensure it's web.php
    exit();
}

// Fetch search keyword securely using prepared statements
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Adjust SQL query based on search input
if ($searchKeyword) {
    // Using prepared statements to prevent SQL injection
    $query = "
        SELECT a.*, c.name as collection_name
        FROM artworks a
        INNER JOIN collections c ON a.collection_id = c.collection_id
        WHERE a.title LIKE CONCAT('%', ?, '%') OR a.description LIKE CONCAT('%', ?, '%')
        ORDER BY c.name ASC, a.title ASC
    ";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ss", $searchKeyword, $searchKeyword);
    } else {
        die(json_encode(["status" => "error", "message" => "Database query error"]));
    }
} else {
    // No search keyword, fetch all artworks ordered by collection_id
    $query = "SELECT a.*, c.name as collection_name FROM artworks a INNER JOIN collections c ON a.collection_id = c.collection_id ORDER BY c.name ASC, a.title ASC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die(json_encode(["status" => "error", "message" => "Database query error"]));
    }
}

// Execute the prepared statement
$stmt->execute();
$result = $stmt->get_result();

// Group artworks by collection_id
$collections = [];
while ($row = $result->fetch_assoc()) {
    $collection_id = $row['collection_id'];
    if (!isset($collections[$collection_id])) {
        $collections[$collection_id] = [
            'collection_id' => $collection_id,
            'collection_name' => $row['collection_name'],
            'artworks' => []
        ];
    }

    // Limit to 3 artworks per collection for initial display
    if (count($collections[$collection_id]['artworks']) < 3) {
        $collections[$collection_id]['artworks'][] = [
            'title' => htmlspecialchars($row['title']),
            'image_url' => getImagePath($row['image_url'], '../img/placeholder.jpg')
        ];
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Collections</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <!-- External CSS -->
    <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="../css/collections.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="../css/auctions.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="../css/artists.css?v=<?php echo time(); ?>" />
    
    <!-- Include Font Awesome for search icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    
    <!-- Optional: Add some basic styling for error messages or images -->
</head>
<style>
    /*******************************************************
   collections.css
   Enhanced Styling for Collections Page
*******************************************************/

/* Reset some basic elements for consistency */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #fafafa;
    color: #333;
}

/* Collections Section */
#collections {
    padding: 120px 20px 60px; /* Top padding accounts for fixed header */
    max-width: 1200px;
    margin: 0 auto;
}

#collections h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 2em;
    color: #2c3e50;
}

/* Search Form */
.search-form {
    position: relative;
    max-width: 600px;
    margin: 0 auto 40px;
    display: flex;
    align-items: center;
}

.search-form input[type="text"] {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid #ccc;
    border-radius: 25px 0 0 25px;
    font-size: 1em;
    outline: none;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.search-form input[type="text"]:focus {
    border-color: #4A90E2;
    box-shadow: 0 0 5px rgba(74, 144, 226, 0.5);
}

.search-form button.search-button {
    padding: 12px 20px;
    border: 2px solid #4A90E2;
    background-color: #4A90E2;
    color: #fff;
    border-radius: 0 25px 25px 0;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
}

.search-form button.search-button:hover {
    background-color: #357ABD;
    transform: translateY(-2px);
}

.search-form button.search-button:active {
    background-color: #285A9E;
    transform: translateY(0);
}

/* Search Results Container */
#search-results {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none; /* Hidden by default */
}

#search-results.visible {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    padding: 15px;
}

/* Search Artwork Card */
.search-artwork {
    position: relative;
    text-align: center;
    background: #f9f9f9; /* Slightly different background for distinction */
    border-radius: 15px;
    padding: 10px;
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}

.search-artwork img {
    width: 100%;
    height: 120px; /* Adjusted height for search results */
    object-fit: cover;
    border-radius: 12px;
    transition: transform 0.5s;
}

.search-artwork:hover img {
    transform: scale(1.05);
}

.search-artwork:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.search-artwork h4 {
    margin-top: 10px; /* Slightly less margin for compactness */
    font-size: 1em; /* Slightly smaller font size */
    color: #2c3e50;
    font-weight: 600;
    transition: color 0.3s ease;
}

.search-artwork:hover h4 {
    color: #3f7dc0;
}

/* Category Rows */
.category-row {
    margin-bottom: 50px;
}

.category-row h3 {
    margin-bottom: 20px;
    font-size: 1.8em;
    color: #2c3e50;
    border-bottom: 2px solid #4A90E2;
    display: inline-block;
    padding-bottom: 5px;
}



.artwork img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 12px;
    transition: transform 0.5s;
}

.artwork:hover img {
    transform: scale(1.05);
}

.artwork:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.artwork h4 {
    margin-top: 20px;
    font-size: 1.4em;
    color: #2c3e50;
    font-weight: 600;
    transition: color 0.3s ease;
}

.artwork:hover h4 {
    color: #3f7dc0;
}

.browse-button {
    display: inline-block;
    margin-top: 15px;
    padding: 10px 20px;
    background-color: #4A90E2;
    color: #fff;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.2s;
}

.browse-button:hover {
    background-color: #357ABD;
    transform: translateY(-2px);
}

.browse-button:active {
    background-color: #285A9E;
    transform: translateY(0);
}

/* No Results Styling */
.no-results {
    text-align: center;
    margin-top: 20px;
    font-size: 1.2em;
    color: #777;
}

/* Footer Styling */
.footer {
    background-color: #333;
    color: #fff;
    padding: 40px 20px;
    text-align: center;
}

.footer-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    max-width: 1200px;
    margin: 0 auto;
}

.footer-section {
    flex: 1 1 250px;
    margin: 10px;
}

.footer-section h4 {
    margin-bottom: 15px;
    font-size: 1.2em;
    color: #fff;
}

.footer-section p, .footer-section ul {
    font-size: 1em;
    color: #ddd;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 10px;
}

.footer-section ul li a {
    color: #bbb;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-section ul li a:hover {
    color: #fff;
}

.footer-bottom {
    margin-top: 20px;
    border-top: 1px solid #444;
    padding-top: 20px;
}

.footer-bottom p {
    font-size: 0.9em;
    color: #bbb;
}

/* Responsive Footer */
@media (max-width: 768px) {
    .footer-container {
        flex-direction: column;
        align-items: center;
    }

    .footer-section {
        margin: 20px 0;
    }
}

@media (max-width: 480px) {
    .footer-section h4 {
        font-size: 1em;
    }

    .footer-section p, .footer-section ul {
        font-size: 0.9em;
    }

    .footer-bottom p {
        font-size: 0.8em;
    }
}

/* Responsive Adjustments for Collections */
@media (max-width: 992px) {
    /* Medium devices (tablets, 768px and up) */
    #collections h2 {
        font-size: 1.8em;
    }

    .search-form {
        max-width: 500px;
    }

    .category-row h3 {
        font-size: 1.6em;
    }

    .artwork-row {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .artwork img {
        height: 220px;
    }
}

@media (max-width: 768px) {
    /* Small devices (phones, less than 768px) */
    #collections {
        padding: 130px 10px 40px;
    }

    .search-form {
        max-width: 100%;
        flex-direction: column;
    }

    .search-form input[type="text"], .search-form button.search-button {
        width: 100%;
        border-radius: 25px;
    }

    .search-form button.search-button {
        margin: 10px 0 0 0;
    }

    .category-row h3 {
        font-size: 1.4em;
    }

    .artwork-row {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }

    .artwork img {
        height: 200px;
    }

    /* Adjust search results grid */
    #search-results.visible {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }

    .search-artwork img {
        height: 100px;
    }

    .search-artwork h4 {
        font-size: 0.9em;
    }
}

@media (max-width: 480px) {
    /* Extra small devices (phones, less than 480px) */
    #collections {
        padding: 140px 5px 30px;
    }

    .category-row h3 {
        font-size: 1.2em;
    }

    .artwork-row {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }

    .artwork img {
        height: 180px;
    }

    /* Adjust search results grid */
    #search-results.visible {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }

    .search-artwork img {
        height: 80px;
    }

    .search-artwork h4 {
        font-size: 0.85em;
    }

    .footer-section h4 {
        font-size: 1em;
    }

    .footer-section p, .footer-section ul {
        font-size: 0.9em;
    }

    .footer-bottom p {
        font-size: 0.8em;
    }
}

/* User Profile Image Styling */
.profile-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

/* Dropdown Menu Styling */
.nav-item.dropdown {
    position: relative;
}

.nav-item.dropdown .dropbtn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    font: inherit;
    color: inherit;
}

.nav-item.dropdown .dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    border-radius: 5px;
    z-index: 1;
}

.nav-item.dropdown:hover .dropdown-content {
    display: block;
}

.dropdown-content a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    border-radius: 5px;
}

.dropdown-content a:hover {
    background-color: #e0e0e0;
}

</style>
<body>
    <!-- Header -->
    <header id="messagesHeader">
        <div>
            <div class="nav-logo">
                <!-- Corrected brand logo path -->
                <a href="index.php" class="logo">
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
                        <button class="dropbtn" aria-haspopup="true" aria-expanded="false">
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
                                     class="profile-img">
                                <span><?php echo htmlspecialchars($user['firstname']); ?></span>
                            </div>
                            <i class="arrow down"></i>
                        </button>
                        <div class="dropdown-content" role="menu">
                            <a href="profile.php">My Profile</a>
                            <a href="my-collections.php">My Collections</a>
                            <a href="my_favorites.php">My Favorites</a>
                            <a href="messages.php">Messages</a>
                            <a href="../php/logout.php" style="background-color: #cb5050;">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="./HTML/web.php">Login/Signup</a></li> <!-- Changed to web.php -->
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    <div id="collections">
        <h2>Explore Our Collections</h2>

        <!-- Search Form -->
        <form method="GET" action="collections.php" class="search-form" autocomplete="off">
            <input type="text" id="search-input" name="search" placeholder="Search artworks..." value="<?php echo htmlspecialchars($searchKeyword); ?>" />
            <button type="submit" class="search-button" aria-label="Search">
                <i class="fa fa-search"></i>
            </button>
        </form>

        <!-- Container for Live Search Results -->
        <div id="search-results" class="search-results" aria-live="polite"></div>

        <?php if (empty($collections)): ?>
            <p class="no-results">No artworks found for "<?php echo htmlspecialchars($searchKeyword); ?>".</p>
        <?php else: ?>
            <?php foreach ($collections as $collection_id => $collection): ?>
                <div class="category-row">
                    <h3><?php echo htmlspecialchars($collection['collection_name'] ?? 'Unknown Collection'); ?></h3>
                    <div class="artwork-row">
                        <?php foreach ($collection['artworks'] as $artwork): ?>
                            <div class="artwork">
                                <img src="<?php echo htmlspecialchars($artwork['image_url']); ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                                <h4><?php echo htmlspecialchars($artwork['title']); ?></h4>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="browse.php?collection_id=<?php echo (int)$collection_id; ?>" class="browse-button">
                        Browse <?php echo htmlspecialchars($collection['collection_name'] ?? 'Collection'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
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

    <!-- External JavaScript -->
    <script src="../JS/dropdown.js"></script>

    <!-- JavaScript for Live Search -->
    <script>
        // Debounce function to limit the rate of AJAX requests
        function debounce(func, delay) {
            let debounceTimer;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => func.apply(context, args), delay);
            }
        }

        // Function to fetch search results
        function fetchSearchResults(query) {
            const resultsContainer = document.getElementById('search-results');

            if (query.trim() === '') {
                // Hide search results if query is empty
                resultsContainer.classList.remove('visible');
                resultsContainer.innerHTML = ''; // Clear previous results
                return;
            }

            fetch(`search_artworks.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displaySearchResults(data.data);
                    } else {
                        console.error(data.message);
                        clearSearchResults();
                    }
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                    clearSearchResults();
                });
        }

        // Function to display search results
        function displaySearchResults(artworks) {
            const resultsContainer = document.getElementById('search-results');
            resultsContainer.innerHTML = ''; // Clear previous results

            if (artworks.length === 0) {
                const noResult = document.createElement('div');
                noResult.classList.add('search-result-item');
                noResult.textContent = 'No results found.';
                resultsContainer.appendChild(noResult);
            } else {
                artworks.forEach(artwork => {
                    const artworkDiv = document.createElement('div');
                    artworkDiv.classList.add('search-artwork'); // Use the new class for search results

                    // Create the image element
                    const img = document.createElement('img');
                    img.src = artwork.image_url;
                    img.alt = artwork.title;
                    artworkDiv.appendChild(img);

                    // Create the title element
                    const title = document.createElement('h4');
                    title.textContent = artwork.title;
                    artworkDiv.appendChild(title);

                    // Make the entire artwork clickable
                    artworkDiv.addEventListener('click', () => {
                        window.location.href = `browse.php?collection_id=${artwork.collection_id}`;
                    });

                    resultsContainer.appendChild(artworkDiv);
                });
            }

            resultsContainer.classList.add('visible');
        }

        // Function to clear search results
        function clearSearchResults() {
            const resultsContainer = document.getElementById('search-results');
            resultsContainer.innerHTML = '';
            resultsContainer.classList.remove('visible');
        }

        // Add event listener to the search input with debounce
        document.getElementById('search-input').addEventListener('input', debounce(function(e) {
            const query = e.target.value;
            fetchSearchResults(query);
        }, 300));

        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchForm = document.querySelector('.search-form');
            const resultsContainer = document.getElementById('search-results');
            if (!searchForm.contains(event.target)) {
                resultsContainer.classList.remove('visible');
            }
        });
    </script>
</body>
</html>
