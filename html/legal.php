<?php
require_once('../php/conn.php'); // Include your database connection

// Fetch all Legal Notices documents (without filtering by is_active)
$query = "SELECT version, content, effective_date 
          FROM legal_documents 
          WHERE doc_type = 'legal' 
          ORDER BY effective_date DESC";
$result = $conn->query($query);

$legalList = [];
while ($row = $result->fetch_assoc()) {
    $legalList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Legal Notices - Bidder Art Auctions</title>
  <link rel="stylesheet" href="legal.css" />
</head>
<body>
  <div class="legal-container">
    <h2>Legal Notices</h2>
    <?php if (!empty($legalList)): ?>
      <?php foreach($legalList as $legal): ?>
        <div class="legal-version">
          <p><em>Version: <?= htmlspecialchars($legal['version']); ?></em></p>
          <p><em>Effective Date: <?= htmlspecialchars($legal['effective_date']); ?></em></p>
          <div class="legal-content">
            <?= $legal['content']; ?>
          </div>
          <hr/>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No Legal Notices available at this time.</p>
    <?php endif; ?>
  </div>
</body>
</html>
