<?php
session_start();
require 'db_connect.php'; // ✅ your PDO connection file

// ✅ Ensure donor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$donor_name = $_SESSION['username'] ?? 'Donor';
$date = date("l, F d, Y");

// ✅ Fetch donation history for this donor
$stmt = $pdo->prepare("
    SELECT 
        p.title AS project_title,
        p.image AS project_image,
        d.amount,
        d.message,
        d.created_at
    FROM donations d
    JOIN projects p ON d.project_id = p.id
    WHERE d.donor_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Donations</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
}
.main-content {
    margin-left: 250px;
    padding: 20px;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 15px 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.header h2 {
    color: #333;
    margin: 0;
}
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 25px;
}
.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 15px;
    transition: 0.3s;
}
.card:hover {
    transform: translateY(-5px);
}
.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 10px;
}
.card h3 {
    margin: 10px 0 5px;
    font-size: 18px;
}
.card p {
    font-size: 14px;
    color: #555;
}
.amount {
    color: #28a745;
    font-weight: 600;
}
.date {
    font-size: 12px;
    color: #888;
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h2>My Donations</h2>
        <div><?php echo $date; ?></div>
    </div>

    <div class="cards">
        <?php if (count($donations) > 0): ?>
            <?php foreach ($donations as $donation): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($donation['project_image']); ?>" alt="Project Image">
                    <h3><?php echo htmlspecialchars($donation['project_title']); ?></h3>
                    <p class="amount">Donated: $<?php echo htmlspecialchars($donation['amount']); ?></p>
                    <?php if (!empty($donation['message'])): ?>
                        <p><strong>Message:</strong> <?php echo htmlspecialchars($donation['message']); ?></p>
                    <?php endif; ?>
                    <p class="date">Date: <?php echo htmlspecialchars($donation['created_at']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No donations made yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
