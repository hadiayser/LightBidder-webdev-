<?php
require_once('../php/conn.php'); // Include your database connection

// Fetch all active Terms & Conditions documents
$query = "SELECT version, content, effective_date FROM terms_conditions WHERE is_active = TRUE ORDER BY effective_date DESC";
$result = $conn->query($query);

$termsList = [];
while ($row = $result->fetch_assoc()) {
    $termsList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms and Conditions - Bidder Art Auctions</title>
    <link rel="stylesheet" href="legal.css" />
  </head>
  <body>
    <div class="legal-container">
      <h2>Terms and Conditions</h2>
      <?php if (!empty($termsList)): ?>
        <?php foreach($termsList as $terms): ?>
          <div class="term-version">
            <p><em>Version: <?= htmlspecialchars($terms['version']); ?></em></p>
            <p><em>Effective Date: <?= htmlspecialchars($terms['effective_date']); ?></em></p>
            <div class="terms-content">
              <?= $terms['content']; ?>
            </div>
            <hr/>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No Terms and Conditions available at this time.</p>
      <?php endif; ?>
    </div>
  </body>
</html>
