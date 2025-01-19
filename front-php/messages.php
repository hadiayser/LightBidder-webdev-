<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Internal Messaging</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../css/messages.css?v=<?php echo time(); ?>" />
  <!-- <link rel="stylesheet" href="../css/css.css?v=<?php echo time(); ?>" /> -->
</head>
<body>
<header id="messagesHeader">
      <div>
        <div class="nav-logo">
          <a href="#" class="logo">
            <img src="./img/bidder-high-resolution-logo-black-transparent.png" alt="">
          </a>
        </div>

        <!-- Hamburger Menu Button for Mobile -->
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
          <li><a href="faq.php">FAQ</a></li>
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
                <a href="messages.php">Messages</a> <!-- Added Messages Link in Dropdown -->
                <a href="../php/logout.php" style="background-color: #cb5050; !important;">Logout</a>
              </div>
            </li>
          <?php else: ?>
            <li><a href="./HTML/web.html">Login/Signup</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </header>

  <h2>Internal Messaging</h2>

  <!-- Chat Container -->
  <div class="chat-container">
    <!-- User List -->
    <div class="user-list">
      <header class="section-header">Users</header>
      <ul id="userList">
        <!-- Users will be loaded here via AJAX -->
      </ul>
    </div>

    <!-- Chat Box -->
    <div class="chat-box">
      <header class="section-header">
        <img src="default-avatar.png" alt="User Avatar" id="chatWithAvatar">
        <div class="chat-with" id="chatWithName">Chat</div>
      </header>
      <div id="chatMessages">
        <p>Select a user to start chatting.</p>
      </div>
      
      <!-- Send Message Form -->
      <form id="messageForm">
        <input type="hidden" name="receiver_id" id="receiver_id" value="">
        <textarea name="message_content" id="message_content" rows="2" placeholder="Type your message here..."></textarea>
        <button type="submit" id="sendMessageBtn">Send</button>
      </form>
    </div>
  </div>

  <script>
$(document).ready(function() {
  // Load user list on page load
  loadUserList();

  // Function to load the user list
  function loadUserList() {
    $.ajax({
      url: 'load_users.php',
      method: 'GET',
      dataType: 'json', // Expect JSON response
      success: function(response) {
        if (response.status === 'success') {
          var usersHtml = '';
          if (response.users.length > 0) {
            response.users.forEach(function(user) {
              // Adjust avatar path relative to messaging.php
              var avatar = user.avatar_url ? user.avatar_url : '../uploads/profile_pictures/default-avatar.png';
              usersHtml += "<li class='user-item' data-user-id='" + user.user_id + "'>";
              usersHtml += "<img src='" + avatar + "' alt='Avatar'>";
              usersHtml += "<span class='username'>" + user.username + "</span>";
              usersHtml += "</li>";
            });
          } else {
            usersHtml = "<li>No other users found.</li>";
          }
          $('#userList').html(usersHtml);
        } else {
          $('#userList').html('<li>' + response.message + '</li>');
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        $('#userList').html('<li>Error loading users: ' + textStatus + '</li>');
      }
    });
  }

  // Function to load chat messages for a specific user
  function loadChatMessages(userId) {
    if (!userId) {
      $('#chatMessages').html('<p>Select a user to start chatting.</p>');
      $('#chatWithName').text('Chat');
      $('#chatWithAvatar').attr('src', '../uploads/profile_pictures/default-avatar.png');
      return;
    }

    $.ajax({
      url: 'load_chat.php',
      method: 'GET',
      data: { user_id: userId },
      dataType: 'json', // Expect JSON response
      success: function(response) {
        console.log(response); // For debugging
        if (response.status === 'success') {
          if (response.messages.length > 0) {
            var messagesHtml = '';
            response.messages.forEach(function(msg) {
              // Determine message alignment and avatar based on message class
              if (msg.message_class === 'sent') {
                // Sent messages: avatar on the right
                var senderAvatar = msg.sender_avatar ? '../' + msg.sender_avatar : '../uploads/profile_pictures/default-avatar.png';
                messagesHtml += "<div class='message sent'>";
                messagesHtml += "<div class='message-content'>" + msg.message_content + "</div>";
                messagesHtml += "<img src='" + senderAvatar + "' alt='Avatar' class='message-avatar'>";
                messagesHtml += "</div>";
              } else if (msg.message_class === 'received') {
                // Received messages: avatar on the left
                var senderAvatar = msg.sender_avatar ? '../' + msg.sender_avatar : '../uploads/profile_pictures/default-avatar.png';
                messagesHtml += "<div class='message received'>";
                messagesHtml += "<img src='" + senderAvatar + "' alt='Avatar' class='message-avatar'>";
                messagesHtml += "<div class='message-content'>" + msg.message_content + "</div>";
                messagesHtml += "</div>";
              }
            });
            $('#chatMessages').html(messagesHtml);
          } else {
            $('#chatMessages').html('<p>No messages yet. Start the conversation!</p>');
          }

          // Update chat header with the selected user's name and avatar
          if (response.avatar_url) {
            var headerAvatar = '../' + response.avatar_url;
            $('#chatWithAvatar').attr('src', headerAvatar);
          } else {
            $('#chatWithAvatar').attr('src', '../uploads/profile_pictures/default-avatar.png');
          }

          if (response.chat_with_name) {
            $('#chatWithName').text(response.chat_with_name);
          } else {
            $('#chatWithName').text('Chat');
          }

          // Scroll to the bottom of the chat
          $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
        } else {
          $('#chatMessages').html('<p>' + response.message + '</p>');
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        $('#chatMessages').html('<p>Error loading messages: ' + textStatus + '</p>');
      }
    });
  }

  // Handle user selection
  $(document).on('click', '.user-item', function() {
    var userId = $(this).data('user-id');
    var username = $(this).find('.username').text();
    var avatar = $(this).find('img').attr('src');

    // Set receiver_id in the form
    $('#receiver_id').val(userId);

    // Update chat header with selected user's name and avatar
    $('#chatWithName').text(username);
    $('#chatWithAvatar').attr('src', avatar);

    // Highlight the selected user
    $('.user-item').removeClass('active');
    $(this).addClass('active');

    // Load chat messages for the selected user
    loadChatMessages(userId);
  });

  // Handle message sending
  $('#messageForm').on('submit', function(e) {
    e.preventDefault();

    var receiverId = $('#receiver_id').val();
    var messageContent = $('#message_content').val().trim();

    if (!receiverId) {
      alert("Please select a user to send a message.");
      return;
    }

    if (messageContent === "") {
      alert("Message cannot be empty.");
      return;
    }

    $.ajax({
      url: 'send_message.php',
      method: 'POST',
      data: {
        receiver_id: receiverId,
        message_content: messageContent
      },
      dataType: 'json', // Expect JSON response
      success: function(response) {
        if (response.status === 'success') {
          // Clear the textarea
          $('#message_content').val('');
          // Reload the chat messages
          loadChatMessages(receiverId);
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        alert("Error sending message: " + textStatus);
      }
    });
  });
});
</script>


</body>
</html>
