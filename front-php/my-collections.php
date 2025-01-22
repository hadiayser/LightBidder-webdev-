<?php
session_start();
require_once('../php/conn.php');

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id'])) {
    header("Location: web.html");
    exit();
}

// Get the artist_id for the logged-in user
$user_id = $_SESSION['user_id'];
$artist_query = "SELECT artist_id FROM artists WHERE user_id = ?";
$stmt = $conn->prepare($artist_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$artist_result = $stmt->get_result();

if ($artist_result->num_rows === 0) {
    // User is not an artist
    header("Location: index.php");
    exit();
}

$artist = $artist_result->fetch_assoc();
$artist_id = $artist['artist_id'];

/**
 * Handles artwork image uploads.
 *
 * @param array $file The uploaded file from $_FILES.
 * @return string|false A relative path (e.g. 'uploads/artworks/filename.jpg') or false on failure.
 */
function handleImageUpload($file) {
    $maxSize = 20 * 1024 * 1024; // 20MB
    if ($file['size'] > $maxSize) {
        return false; 
    }

    // Allowed file extensions and MIME types
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $allowedMimeTypes  = ['image/jpeg', 'image/png', 'image/gif'];

    $tempPath      = $file['tmp_name'];
    $originalName  = $file['name'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Validate extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }

    // Validate MIME type using finfo
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tempPath);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        return false;
    }

    // Generate a unique filename
    $newFilename = uniqid('artwork_') . '.' . $fileExtension;

    // Define upload directory (ensure it's writable)
    $uploadDir = '../uploads/artworks/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Final path on the server
    $destination = $uploadDir . $newFilename;

    // Move the uploaded file
    if (move_uploaded_file($tempPath, $destination)) {
        // Return the relative path to store in the 'imagepath' column
        return 'uploads/artworks/' . $newFilename;
    }
    return false;
}

// Now fetch artworks (including 'imagepath')
$artworks_query = "
    SELECT 
        a.artwork_id,
        a.artist_id,
        a.title,
        a.medium,
        a.style,
        a.description,
        a.dimensions,
        a.year_created,
        a.starting_price,
        a.image_url,
        a.imagepath,  /* new column for local path */
        c.name as collection_name,
        c.description as collection_description
    FROM artworks a
    LEFT JOIN collections c ON a.collection_id = c.collection_id
    WHERE a.artist_id = ?
    ORDER BY a.artwork_id DESC
";

try {
    $stmt = $conn->prepare($artworks_query);
    $stmt->bind_param("i", $artist_id);
    $stmt->execute();
    $artworks = $stmt->get_result();
    
    if (!$artworks) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $artworks = false;
}

// Fetch collections for the dropdown
$collections_query = "SELECT collection_id, name FROM collections";
$collections = $conn->query($collections_query);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Creating a new artwork
    if (isset($_POST['action']) && $_POST['action'] === 'create_artwork') {
        $title          = $_POST['title'];
        $medium         = $_POST['medium'];
        $style          = $_POST['style'];
        $description    = $_POST['description'];
        $dimensions     = $_POST['dimensions'];
        $year_created   = $_POST['year_created'];
        $starting_price = $_POST['starting_price'];
        $collection_id  = $_POST['collection_id'];

        // Handle file upload if provided
        $uploadedPath = '';
        if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = handleImageUpload($_FILES['artwork_image']);
            if (!$uploadedPath) {
                $error_message = "Error uploading image (invalid file type or too large).";
            }
        }

        // If no file uploaded or error, optionally set a placeholder or keep blank
        if (empty($uploadedPath)) {
            $uploadedPath = '../img/placeholder.jpg'; 
        }

        if (!isset($error_message)) {
            // Insert into the new 'imagepath' column 
            $insert_query = "INSERT INTO artworks (
                artist_id, title, medium, style, description, dimensions, 
                year_created, starting_price, image_url, imagepath, collection_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param(
                "issssssdssi",
                $artist_id, 
                $title, 
                $medium, 
                $style, 
                $description, 
                $dimensions, 
                $year_created, 
                $starting_price,
                $uploadedPath,  /* reusing this for 'image_url' if you want, or '' if not needed */
                $uploadedPath,  /* storing local path in 'imagepath' */
                $collection_id
            );

            if ($stmt->execute()) {
                $success_message = "Artwork added successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error adding artwork.";
            }
        }
    }

    // Editing an existing artwork
    if (isset($_POST['action']) && $_POST['action'] === 'edit_artwork') {
        $artwork_id     = $_POST['artwork_id'];
        $title          = $_POST['title'];
        $medium         = $_POST['medium'];
        $style          = $_POST['style'];
        $description    = $_POST['description'];
        $dimensions     = $_POST['dimensions'];
        $year_created   = $_POST['year_created'];
        $starting_price = $_POST['starting_price'];
        $collection_id  = $_POST['collection_id'];

        // Check if user uploaded a new file
        $newPath = false;
        if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
            $newPath = handleImageUpload($_FILES['artwork_image']);
            if (!$newPath) {
                $error_message = "Error uploading new image (invalid file type or too large).";
            }
        }

        // Build the update query, optionally setting image_url or imagepath only if there's a new file
        $update_query = "
            UPDATE artworks SET
                title = ?, 
                medium = ?, 
                style = ?, 
                description = ?, 
                dimensions = ?, 
                year_created = ?, 
                starting_price = ?, 
        ";

        if ($newPath) {
            // If a new file is uploaded, update both 'image_url' & 'imagepath'
            $update_query .= " image_url = ?, imagepath = ?, ";
        }

        $update_query .= "
                collection_id = ?
            WHERE artwork_id = ? AND artist_id = ?
        ";

        $stmt = $conn->prepare($update_query);

        if ($newPath) {
            $stmt->bind_param(
                "ssssssdsssii",
                $title, 
                $medium, 
                $style, 
                $description, 
                $dimensions, 
                $year_created, 
                $starting_price,
                $newPath,       // setting 'image_url'
                $newPath,       // setting 'imagepath'
                $collection_id, 
                $artwork_id, 
                $artist_id
            );
        } else {
            // If no new image, skip 'image_url' and 'imagepath' updates
            $stmt->bind_param(
                "ssssssdssii",
                $title, 
                $medium, 
                $style, 
                $description, 
                $dimensions, 
                $year_created, 
                $starting_price,
                $collection_id, 
                $artwork_id, 
                $artist_id
            );
        }

        if (!isset($error_message)) {
            if ($stmt->execute()) {
                $success_message = "Artwork updated successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error updating artwork.";
            }
        }
    }

    // Deleting an artwork
    if (isset($_POST['action']) && $_POST['action'] === 'delete_artwork') {
        $artwork_id = $_POST['artwork_id'];

        // Delete from database
        $delete_query = "DELETE FROM artworks WHERE artwork_id = ? AND artist_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $artwork_id, $artist_id);

        if ($stmt->execute()) {
            $success_message = "Artwork deleted successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Error deleting artwork.";
        }
    }
}
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collections</title>
    <!-- Use a dynamic query param for cache-busting or a version constant in production -->
    <link rel="stylesheet" href="../css/css.css?v=<?php echo filemtime(__DIR__ . '/../css/css.css'); ?>">
    <link rel="stylesheet" href="../css/collections.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/auctions.css?<?php echo time(); ?>">
</head>
<body>
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
        <div class="collections-header">
            <h1>My Artworks</h1>
            <button class="new-artwork-btn" onclick="showNewArtworkForm()">
                <span>+</span> New Artwork
            </button>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="artworks-grid">
            <?php if ($artworks && $artworks->num_rows > 0): ?>
                <?php while ($artwork = $artworks->fetch_assoc()): ?>
                    <?php
                        // If 'imagepath' is not empty, prepend '../' to find the file,
                        // otherwise fallback to old 'image_url'.
                        $displayImage = $artwork['imagepath'];
                        
                        if (!empty($displayImage)) {
                            // The DB might store 'uploads/artworks/filename.jpg',
                            // so we go up one directory: '../uploads/artworks/filename.jpg'
                            $displayImage = '../' . $displayImage;
                        } else {
                            // fallback to image_url if imagepath is empty
                            $displayImage = $artwork['image_url'];
                            
                            // If also empty, final fallback is placeholder
                            if (empty($displayImage)) {
                                $displayImage = '../img/placeholder.jpg';
                            }
                        }
                    ?>
                    <div class="artwork-card">
                        <div class="artwork-preview">
                            <img src="<?php echo htmlspecialchars($displayImage); ?>" 
                                 alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                            <?php if (!empty($artwork['collection_name'])): ?>
                                <div class="artwork-collection">
                                    <?php echo htmlspecialchars($artwork['collection_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="artwork-info">
                            <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                            <p class="artwork-details">
                                <?php echo htmlspecialchars($artwork['medium']); ?> • 
                                <?php echo htmlspecialchars($artwork['style']); ?> • 
                                <?php echo htmlspecialchars($artwork['year_created']); ?>
                            </p>
                            <p class="artwork-dimensions">
                                <?php echo htmlspecialchars($artwork['dimensions']); ?>
                            </p>
                            <p class="artwork-price">
                                Starting at $<?php echo number_format($artwork['starting_price'], 2); ?>
                            </p>
                            <div class="artwork-actions">
                                <button onclick="editArtwork(<?php echo $artwork['artwork_id']; ?>)">Edit</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_artwork">
                                    <input type="hidden" name="artwork_id" value="<?php echo $artwork['artwork_id']; ?>">
                                    <button type="submit" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this artwork?');">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-artworks">
                    <p>No artworks found. Add your first artwork!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- New Artwork Modal -->
    <div id="newArtworkModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Artwork</h2>
            <form method="POST" class="artwork-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_artwork">
                
                <!-- Artwork Image File -->
                <div class="form-group">
                    <label for="artwork_image">Artwork Image</label>
                    <input type="file" id="artwork_image" name="artwork_image" accept="image/*">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="collection_id">Collection</label>
                        <select id="collection_id" name="collection_id">
                            <option value="">No Collection</option>
                            <?php
                              // Rewind collections pointer
                              $collections->data_seek(0);
                              while ($collection = $collections->fetch_assoc()):
                            ?>
                                <option value="<?php echo $collection['collection_id']; ?>">
                                    <?php echo htmlspecialchars($collection['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="medium">Medium</label>
                        <select id="medium" name="medium" required>
                            <option value="">Select Medium</option>
                            <option value="Oil Paint">Oil Paint</option>
                            <option value="Acrylic">Acrylic</option>
                            <option value="Watercolor">Watercolor</option>
                            <option value="Digital">Digital</option>
                            <option value="Photography">Photography</option>
                            <option value="Sculpture">Sculpture</option>
                            <option value="Mixed Media">Mixed Media</option>
                            <option value="Charcoal">Charcoal</option>
                            <option value="Pencil">Pencil</option>
                            <option value="Pastel">Pastel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="style">Style</label>
                        <select id="style" name="style" required>
                            <option value="">Select Style</option>
                            <option value="Abstract">Abstract</option>
                            <option value="Realistic">Realistic</option>
                            <option value="Impressionist">Impressionist</option>
                            <option value="Modern">Modern</option>
                            <option value="Contemporary">Contemporary</option>
                            <option value="Pop Art">Pop Art</option>
                            <option value="Minimalist">Minimalist</option>
                            <option value="Surrealist">Surrealist</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="dimensions">Dimensions</label>
                        <input type="text" id="dimensions" name="dimensions" placeholder="e.g., 24x36 inches" required>
                    </div>
                    <div class="form-group">
                        <label for="year_created">Year</label>
                        <input type="number" id="year_created" name="year_created" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="starting_price">Starting Price ($)</label>
                    <input type="number" id="starting_price" name="starting_price" step="0.01" required>
                </div>

                <button type="submit" class="submit-btn">Add Artwork</button>
            </form>
        </div>
    </div>

    <!-- Edit Artwork Modal -->
    <div id="editArtworkModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Artwork</h2>
            <form method="POST" class="artwork-form" id="editArtworkForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_artwork">
                <input type="hidden" name="artwork_id" id="edit_artwork_id">
                
                <!-- Artwork Image File -->
                <div class="form-group">
                    <label for="edit_artwork_image">Artwork Image (Optional)</label>
                    <input type="file" id="edit_artwork_image" name="artwork_image" accept="image/*">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_collection_id">Collection</label>
                        <select id="edit_collection_id" name="collection_id">
                            <option value="">No Collection</option>
                            <?php
                            // Reset the pointer for the edit form
                            $collections->data_seek(0);
                            while ($collection = $collections->fetch_assoc()):
                            ?>
                                <option value="<?php echo $collection['collection_id']; ?>">
                                    <?php echo htmlspecialchars($collection['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_medium">Medium</label>
                        <select id="edit_medium" name="medium" required>
                            <option value="">Select Medium</option>
                            <option value="Oil Paint">Oil Paint</option>
                            <option value="Acrylic">Acrylic</option>
                            <option value="Watercolor">Watercolor</option>
                            <option value="Digital">Digital</option>
                            <option value="Photography">Photography</option>
                            <option value="Sculpture">Sculpture</option>
                            <option value="Mixed Media">Mixed Media</option>
                            <option value="Charcoal">Charcoal</option>
                            <option value="Pencil">Pencil</option>
                            <option value="Pastel">Pastel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_style">Style</label>
                        <select id="edit_style" name="style" required>
                            <option value="">Select Style</option>
                            <option value="Abstract">Abstract</option>
                            <option value="Realistic">Realistic</option>
                            <option value="Impressionist">Impressionist</option>
                            <option value="Modern">Modern</option>
                            <option value="Contemporary">Contemporary</option>
                            <option value="Pop Art">Pop Art</option>
                            <option value="Minimalist">Minimalist</option>
                            <option value="Surrealist">Surrealist</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_dimensions">Dimensions</label>
                        <input type="text" id="edit_dimensions" name="dimensions" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_year_created">Year</label>
                        <input type="number" id="edit_year_created" name="year_created" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_starting_price">Starting Price ($)</label>
                    <input type="number" id="edit_starting_price" name="starting_price" step="0.01" required>
                </div>

                <button type="submit" class="submit-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <script src="../JS/dropdown.js"></script>
    <script>
        function showNewArtworkForm() {
            document.getElementById('newArtworkModal').style.display = 'block';
        }

        function editArtwork(artworkId) {
            // Fetch artwork data using AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../php/get_artwork.php?id=${artworkId}`, true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const artwork = JSON.parse(xhr.responseText);
                    
                    // Populate the edit form
                    document.getElementById('edit_artwork_id').value = artwork.artwork_id;
                    document.getElementById('edit_title').value = artwork.title;
                    document.getElementById('edit_collection_id').value = artwork.collection_id;
                    document.getElementById('edit_medium').value = artwork.medium;
                    document.getElementById('edit_style').value = artwork.style;
                    document.getElementById('edit_description').value = artwork.description;
                    document.getElementById('edit_dimensions').value = artwork.dimensions;
                    document.getElementById('edit_year_created').value = artwork.year_created;
                    document.getElementById('edit_starting_price').value = artwork.starting_price;
                    
                    // Show the modal
                    document.getElementById('editArtworkModal').style.display = 'block';
                } else {
                    console.error('Error fetching artwork data');
                }
            };
            
            xhr.onerror = function() {
                console.error('Request failed');
            };
            
            xhr.send();
        }

        function closeEditModal() {
            document.getElementById('editArtworkModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Close button functionality for all modals
        document.querySelectorAll('.close').forEach(function(closeBtn) {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });
    </script>
</body>
</html>












