<?php
include '../php/conn.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $question_field = $conn->real_escape_string($_POST['question_field']);
    $subject = $conn->real_escape_string($_POST['subject']);

    $stmt = $conn->prepare("INSERT INTO contact (firstname, lastname, email, writehere, subject) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $question_field, $subject);

    if ($stmt->execute()) {
        echo "<script>alert('Thank you for contacting us.');</script>";
    } else {
        echo "<script>alert('Error: {$stmt->error}');</script>";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us Page</title>
    <link rel="stylesheet" href="../css/css.css?v5" />
  </head>
  <body id="body_contact">
    <header>
      <div>
        <div class="nav-logo">
          <a href="#" class="logo"><img src="../img/bidder-high-resolution-logo-black-transparent.png" alt=""></a>
        </div>
        <ul id="homepageNav">
          <li><a href="index.php">Home</a></li>
          <li><a href="artworks.html">Artwork</a></li>
          <li><a href="collections.php">Collections</a></li>
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
                    <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
                </div>
            </li>
          <?php else: ?>
            <li><a href="web.html">Login/Signup</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </header>
    <div class="container">
      <form>
        <h1>Please let us know what's wrong</h1>
        <label for="fname">First Name</label>
        <input
          type="text"
          id="fname"
          name="firstname"
          placeholder="Your name.."
        />

        <label for="lname">Last Name</label>
        <input
          type="text"
          id="lname"
          name="lastname"
          placeholder="Your last name.."
        />
        <label for="fname">Email</label>
        <input type="text" id="email" name="email" placeholder="Your email.." />

        <label for="title_subject">Subject</label>
        <select id="question_field" name="question_field">
          <option value="questions">General Questions</option>
          <option value="art">Art</option>
          <option value="refund">Refund</option>
        </select>

        <label for="subject">Write here</label>
        <textarea
          id="subject"
          name="subject"
          placeholder="Write something.."
          style="height: 200px"
        ></textarea>

        <input type="submit" value="Submit" />
      </form>
    </div>
  </body>
</html>
