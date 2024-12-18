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

// Now fetch artworks using artist_id instead of user_id
$artworks_query = "
    SELECT 
        a.*,
        c.name as collection_name,
        c.description as collection_description
    FROM artworks a
    LEFT JOIN collections c ON a.collection_id = c.collection_id
    WHERE a.artist_id = ?
    ORDER BY a.artwork_id DESC";

try {
    $stmt = $conn->prepare($artworks_query);
    $stmt->bind_param("i", $artist_id);  // Use artist_id instead of user_id
    $stmt->execute();
    $artworks = $stmt->get_result();
    
    if (!$artworks) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $artworks = false;
}

// Fetch collections for the dropdown in the add artwork form
$collections_query = "SELECT collection_id, name FROM collections";
$collections = $conn->query($collections_query);

// Handle artwork creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_artwork') {
        $title = $_POST['title'];
        $medium = $_POST['medium'];
        $style = $_POST['style'];
        $description = $_POST['description'];
        $dimensions = $_POST['dimensions'];
        $year_created = $_POST['year_created'];
        $starting_price = $_POST['starting_price'];
        $collection_id = $_POST['collection_id'];
        $image_url = $_POST['image_url'];  // Get image URL directly

        $insert_query = "INSERT INTO artworks (artist_id, title, medium, style, description, dimensions, 
                        year_created, starting_price, image_url, collection_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issssssdsi", $artist_id, $title, $medium, $style, $description, 
                         $dimensions, $year_created, $starting_price, $image_url, $collection_id);
        
        if ($stmt->execute()) {
            $success_message = "Artwork added successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Error adding artwork.";
        }
    }

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit_artwork') {
            $artwork_id = $_POST['artwork_id'];
            $title = $_POST['title'];
            $medium = $_POST['medium'];
            $style = $_POST['style'];
            $description = $_POST['description'];
            $dimensions = $_POST['dimensions'];
            $year_created = $_POST['year_created'];
            $starting_price = $_POST['starting_price'];
            $collection_id = $_POST['collection_id'];
            $image_url = $_POST['image_url'];

            $update_query = "UPDATE artworks SET 
                title = ?, medium = ?, style = ?, description = ?, 
                dimensions = ?, year_created = ?, starting_price = ?, 
                image_url = ?, collection_id = ?
                WHERE artwork_id = ? AND artist_id = ?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssssdssii", $title, $medium, $style, $description, 
                            $dimensions, $year_created, $starting_price, 
                            $image_url, $collection_id, $artwork_id, $artist_id);
            
            if ($stmt->execute()) {
                $success_message = "Artwork updated successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error updating artwork.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collections</title>
    <link rel="stylesheet" href="../css/css.css?v2">
    <link rel="stylesheet" href="../css/collections.css?v2">
</head>
<body>
    <!-- Include your header here (same as profile.php) -->
    <header>
      <div>
        <div class="nav-logo">
          <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
        </div>
        <ul id="homepageNav">
          <li><a href="index.php">Home</a></li>
          <!-- <li><a href="artworks.html">Artwork</a></li> -->
          <li><a href="collections.php">Collections</a></li>
          <li><a href="auctions.php">Auctions</a></li>
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
      </div>
    </header>
    <div class="collections-container">
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
                    <div class="artwork-card">
                        <div class="artwork-preview">
                            <img src="<?php echo !empty($artwork['image_url']) ? 
                                htmlspecialchars($artwork['image_url']) : 
                                '../img/placeholder.jpg'; ?>" 
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
                                <button onclick="deleteArtwork(<?php echo $artwork['artwork_id']; ?>)" class="delete-btn">Delete</button>
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
                
                <div class="form-group">
                    <label for="artwork_image">Artwork Image URL</label>
                    <input type="url" id="artwork_image" name="image_url" 
                           placeholder="https://example.com/image.jpg" required>
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
                            <?php while ($collection = $collections->fetch_assoc()): ?>
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
            <form method="POST" class="artwork-form" id="editArtworkForm">
                <input type="hidden" name="action" value="edit_artwork">
                <input type="hidden" name="artwork_id" id="edit_artwork_id">
                
                <div class="form-group">
                    <label for="edit_image_url">Artwork Image URL</label>
                    <input type="url" id="edit_image_url" name="image_url" 
                           placeholder="https://example.com/image.jpg" required>
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
                            // Reset the collections result pointer
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

    <script>
        // Modal functionality
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
                    document.getElementById('edit_image_url').value = artwork.image_url;
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

        function deleteArtwork(artworkId) {
            if (confirm('Are you sure you want to delete this artwork?')) {
                // Implement delete functionality
                console.log('Delete artwork:', artworkId);
            }
        }

        // Replace the current dropdown JavaScript with this:
        document.addEventListener('DOMContentLoaded', () => {
            const dropdownBtn = document.querySelector('.dropbtn');
            const dropdownContent = document.querySelector('.dropdown-content');
            const arrow = document.querySelector('.arrow');

            if (dropdownBtn && dropdownContent) {
                dropdownBtn.onclick = (e) => {
                    e.preventDefault();
                    dropdownContent.classList.toggle('show');
                    if (arrow) {
                        arrow.classList.toggle('up');
                    }
                }

                window.onclick = (e) => {
                    if (!e.target.matches('.dropbtn') && !e.target.matches('.arrow')) {
                        if (dropdownContent.classList.contains('show')) {
                            dropdownContent.classList.remove('show');
                            if (arrow) {
                                arrow.classList.remove('up');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 