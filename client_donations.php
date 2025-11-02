<?php   
session_start();
require 'db_connect.php'; // ✅ PDO connection

// ✅ Ensure client is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['username'] ?? 'Client';
$date = date("l, F d, Y");

// ✅ Fetch profile picture (check both)
$stmt = $pdo->prepare("SELECT profile_pic, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$profile_path = '';
if (!empty($profile['profile_pic']) && file_exists(__DIR__ . '/' . $profile['profile_pic'])) {
    $profile_path = $profile['profile_pic'];
} elseif (!empty($profile['profile_image']) && file_exists(__DIR__ . '/' . $profile['profile_image'])) {
    $profile_path = $profile['profile_image'];
} else {
    $profile_path = 'uploads/default.png';
}

// ✅ Fixed query — correct join for your donations table
$stmt = $pdo->prepare("
    SELECT 
        d.id, 
        d.amount, 
        d.created_at, 
        COALESCE(d.donor_name, u.username, 'Anonymous') AS donor_name,
        COALESCE(d.project_name, p.project_name, 'Untitled Project') AS project_title
    FROM donations d
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN users u ON d.donor_id = u.id
    WHERE p.client_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Donations | Crowdfunding</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #eef3ff;
    margin: 0;
}
.sidebar {
    position: fixed;
    width: 260px;
    height: 100vh;
    background: linear-gradient(200deg, #2563eb, #4f46e5);
    color: #fff;
    padding: 25px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.sidebar .logo {
    text-align: center;
    font-weight: 700;
    font-size: 20px;
    margin-bottom: 25px;
}
.sidebar a {
    color: #fff;
    text-decoration: none;
    padding: 12px 15px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    transition: 0.3s;
}
.sidebar a:hover,
.sidebar a.active {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(6px);
}
.main {
    margin-left: 280px;
    padding: 40px 50px;
}
.welcome-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 35px;
}
.welcome-section h4 {
    font-weight: 700;
    color: #1e293b;
}
.welcome-section .date {
    color: #64748b;
    font-size: 15px;
}
.profile-pic {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    transition: transform 0.3s ease;
}
.profile-pic:hover {
    transform: scale(1.15);
}
.table-container {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 25px;
}
.table thead {
    background: #2563eb;
    color: #fff;
}
.table tbody tr:hover {
    background: #f1f5ff;
    transition: 0.2s;
}
footer {
    text-align: center;
    margin-top: 60px;
    color: #777;
    font-size: 14px;
}
#profileModal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
}
#profileModal img {
    display: block;
    margin: 10% auto;
    max-width: 400px;
    width: 80%;
    border-radius: 15px;
}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="client_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php"><i class="fa-solid fa-plus-circle"></i> Create Project</a>
  <a href="client_project.php"><i class="fa-solid fa-folder-open"></i> My Projects</a>
  <a href="client_donations.php" class="active"><i class="fa-solid fa-hand-holding-dollar"></i> Donations</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="welcome-section">
    <div>
      <h4>Welcome, @<?= htmlspecialchars($client_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <div>
      <img src="<?= htmlspecialchars($profile_path) ?>" id="profilePic" class="profile-pic">
    </div>
  </div>

  <div class="table-container">
    <h4 class="mb-4"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Donations to Your Projects</h4>

    <?php if ($donations): ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Project</th>
            <th>Donor</th>
            <th>Amount (Rs.)</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donations as $i => $row): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($row['project_title']) ?></td>
            <td><?= htmlspecialchars($row['donor_name']) ?></td>
            <td><strong>Rs. <?= number_format($row['amount'], 2) ?></strong></td>
            <td><?= date("F d, Y - h:i A", strtotime($row['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p>No donations received yet.</p>
    <?php endif; ?>
  </div>

  <footer>© <?= date("Y") ?> Crowdfunding Platform</footer>
</div>

<div id="profileModal"><img src="<?= htmlspecialchars($profile_path) ?>"></div>
<script>
const modal = document.getElementById('profileModal');
const pic = document.getElementById('profilePic');
pic.onclick = ()=>{ modal.style.display="block"; }
modal.onclick = ()=>{ modal.style.display="none"; }
</script>
</body>
</html>
