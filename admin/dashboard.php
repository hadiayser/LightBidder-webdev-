<?php
require_once('admin_auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/admin.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
  <header>
    <h1>Admin Dashms-appid:W~C:\xampp\xampp-control.exeboard</h1>
  </header>
  <div class="wrapper">
    <nav class="sidebar">
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_faq.php">Manage FAQs</a></li>
        <li><a href="manage_terms.php">Manage Terms &amp; Conditions</a></li>
        <li><a href="manage_legal.php">Manage Legal Notices</a></li>
        <!-- Add more navigation links as needed -->
      </ul>
    </nav>
    <main class="content">
      <section class="dashboard-cards">
        <div class="card">
          <h2>Total Users</h2>
          <p>123</p>
        </div>
        <div class="card">
          <h2>Total FAQs</h2>
          <p>45</p>
        </div>
        <!-- Add more cards or dashboard widgets as needed -->
      </section>
      <!-- Add additional dashboard content here -->
    </main>
  </div>
</body>
</html>
