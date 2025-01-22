<?php
require_once('admin_auth.php');
require_once('../php/conn.php');

// Fetch data for dashboard cards 
$stmt_users = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt_users->execute();
$result_users = $stmt_users->get_result();
$total_users = $result_users->fetch_assoc()['total_users'];
$stmt_users->close();

$stmt_faq = $conn->prepare("SELECT COUNT(*) as total_faqs FROM faqs"); 
$stmt_faq->execute();
$result_faq = $stmt_faq->get_result();
$total_faqs = $result_faq->fetch_assoc()['total_faqs'];
$stmt_faq->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
    </header>
    <div class="wrapper">
        <nav class="sidebar">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_faq.php">Manage FAQs</a></li>
                <li><a href="manage_terms.php">Manage Terms &amp; Conditions</a></li>
                <li><a href="manage_legal.php">Manage Legal Notices</a></li>
                <li><a href="manage_forum_threads.php">Manage Forum</a></li>
                <li><a href="../front-php/index.php" class="return-site">Return to Site</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>Dashboard</h1>
            <section class="dashboard-cards">
                <div class="card">
                    <h2>Total Users</h2>
                    <p><?= htmlspecialchars($total_users); ?></p>
                </div>
                <div class="card">
                    <h2>Total FAQs</h2>
                    <p><?= htmlspecialchars($total_faqs); ?></p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
