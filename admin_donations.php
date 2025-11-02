<?php
session_start();
require 'db_connect.php';

// ✅ Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");
$user_id = $_SESSION['user_id'];

// ✅ Fetch profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetchColumn();
$profile_pic = (!empty($profile) && file_exists(__DIR__ . "/" . $profile)) ? $profile : "uploads/default.png";

// ✅ Fetch all donations with project & client info
$stmt = $pdo->query("
    SELECT 
        d.id, 
        d.donor_name, 
        d.amount, 
        d.message, 
        d.created_at, 
        COALESCE(p.project_name, p.title, 'Untitled Project') AS project_name, 
        u.username AS client_name
    FROM donations AS d
    LEFT JOIN projects AS p ON d.project_id = p.id
    LEFT JOIN users AS u ON p.client_id = u.id
    ORDER BY d.created_at DESC
");
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Donations | Crowdfunding</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e0eafc, #cfdef3);
  min-height: 100vh;
  margin: 0;
  overflow-x: hidden;
}
.sidebar {
  position: fixed;
  width: 260px;
  height: 100vh;
  background: linear-gradient(200deg, #2563eb, #4f46e5);
  color: #fff;
  padding: 25px 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: 5px 0 15px rgba(0,0,0,0.15);
}
.sidebar .logo {
  text-align: center;
  font-weight: 700;
  font-size: 20px;
  margin-bottom: 30px;
}
.sidebar a {
  color: #fff;
  text-decoration: none;
  padding: 12px 15px;
  border-radius: 10px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}
.sidebar a:hover,
.sidebar a.active {
  background: rgba(255,255,255,0.15);
  transform: translateX(5px);
}
.main {
  margin-left: 280px;
  padding: 40px 50px;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 35px;
  flex-wrap: wrap;
}
.header-left h4 {
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.header-left .date {
  color: #64748b;
  font-size: 14px;
}
.profile-pic {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #2563eb;
}
.section-title {
  font-weight: 600;
  color: #1e293b;
  margin-top: 20px;
  margin-bottom: 20px;
}
.table {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.table thead {
  background: linear-gradient(90deg, #4f46e5, #2563eb);
  color: white;
}
.table tbody tr:hover {
  background-color: #f3f6fc;
}
footer {
  text-align: center;
  margin-top: 50px;
  color: #777;
  font-size: 14px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="admin_donations.php" class="active"><i class="fa-solid fa-hand-holding-heart"></i> Donations</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="profile.php?role=admin"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Main Content -->
<div class="main">
  <div class="header">
    <div class="header-left">
      <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="profile-pic">
  </div>

  <h4 class="section-title"><i class="fa-solid fa-hand-holding-heart text-danger"></i> All Donations</h4>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Donor</th>
          <th>Project</th>
          <th>Client</th>
          <th>Amount (Rs.)</th>
          <th>Message</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($donations): foreach ($donations as $index => $d): ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td><?= htmlspecialchars($d['donor_name'] ?? 'Unknown Donor') ?></td>
          <td><?= htmlspecialchars($d['project_name'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($d['client_name'] ?? 'N/A') ?></td>
          <td><strong><?= number_format($d['amount'], 2) ?></strong></td>
          <td><?= htmlspecialchars($d['message'] ?: '—') ?></td>
          <td><?= date("M d, Y h:i A", strtotime($d['created_at'])) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7" class="text-center text-muted py-3">No donations found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <footer>
    © <?= date("Y") ?> Crowdfunding Platform — Admin Donations
  </footer>
</div>

</body>
</html>
