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

// ✅ Project stats
$total_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$pending_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='pending'")->fetchColumn();
$approved_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='approved'")->fetchColumn();
$rejected_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='rejected'")->fetchColumn();

// ✅ Recent Projects
$projects_stmt = $pdo->query("
    SELECT 
        p.id,
        COALESCE(p.project_name, p.title, 'Untitled Project') AS project_name,
        COALESCE(u.username, 'Unknown Client') AS client_name,
        p.goal,
        p.collected,
        p.status,
        p.start_date,
        p.end_date
    FROM projects AS p
    LEFT JOIN users AS u ON (p.client_id = u.id OR p.user_id = u.id)
    ORDER BY p.created_at DESC
    LIMIT 8
");
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Recent Donations
$donations_stmt = $pdo->query("
    SELECT 
        d.id, 
        COALESCE(d.donor_name, 'Anonymous') AS donor_name, 
        COALESCE(p.project_name, p.title, 'Untitled Project') AS project_name, 
        COALESCE(u.username, 'Unknown Client') AS client_name,
        d.amount, 
        d.message, 
        d.created_at
    FROM donations AS d
    LEFT JOIN projects AS p ON d.project_id = p.id
    LEFT JOIN users AS u ON (p.client_id = u.id OR p.user_id = u.id)
    ORDER BY d.created_at DESC
    LIMIT 8
");
$donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Crowdfunding</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e0eafc, #cfdef3);
  min-height: 100vh;
  overflow-x: hidden;
  margin: 0;
}

/* Sidebar */
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
  transition: all 0.3s ease;
  z-index: 1500;
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

/* Main */
.main {
  margin-left: 280px;
  padding: 40px 50px;
  transition: margin-left 0.3s ease;
}

/* Header */
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

/* Stats */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 25px;
}
.stat-card {
  background: linear-gradient(135deg, #ffffff, #f0f4f8);
  border-radius: 15px;
  padding: 20px;
  text-align: center;
  transition: all 0.3s ease;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}
.stat-card i {
  font-size: 32px;
  margin-bottom: 12px;
  color: #2563eb;
}
.stat-card h5 {
  font-weight: 600;
  color: #374151;
  font-size: 16px;
}
.stat-card h3 {
  color: #2d3748;
  font-weight: 700;
  font-size: 28px;
  margin-top: 10px;
}

/* Table */
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

/* Other */
.section-title {
  font-weight: 600;
  color: #1e293b;
  margin-top: 50px;
  margin-bottom: 20px;
}
.view-link {
  float: right;
  font-size: 14px;
  text-decoration: none;
  color: #2563eb;
  font-weight: 600;
}
.view-link:hover {
  text-decoration: underline;
}
footer {
  text-align: center;
  margin-top: 60px;
  color: #777;
  font-size: 14px;
}

/* ✅ Responsive Enhancements */
.toggle-btn {
  display: none;
}
.overlay {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.4);
  z-index: 1200;
}
.overlay.active { display: block; }

@media (max-width: 992px) {
  .sidebar { left: -260px; }
  .sidebar.active { left: 0; }
  .toggle-btn {
    display: block;
    position: fixed;
    top: 15px;
    left: 15px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 10px 12px;
    font-size: 20px;
    cursor: pointer;
    z-index: 2001;
  }
  .main { margin-left: 0; padding: 25px; }
  .profile-pic { width: 50px; height: 50px; }
}
@media (max-width: 576px) {
  .header-left h4 { font-size: 16px; }
  .header-left .date { font-size: 12px; }
  .stat-card h3 { font-size: 22px; }
}
</style>
</head>
<body>

<button class="toggle-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="admin_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="admin_donations.php"><i class="fa-solid fa-hand-holding-heart"></i> Donations</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="profile.php?role=admin"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <div class="header-left">
      <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="profile-pic">
  </div>

  <div class="stats">
    <div class="stat-card"><i class="fa-solid fa-folder-open"></i><h5>Total Projects</h5><h3><?= $total_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-hourglass-half text-warning"></i><h5>Pending</h5><h3><?= $pending_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-circle-check text-success"></i><h5>Approved</h5><h3><?= $approved_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-circle-xmark text-danger"></i><h5>Rejected</h5><h3><?= $rejected_projects ?></h3></div>
  </div>

  <h4 class="section-title"><i class="fa-solid fa-briefcase text-primary"></i> Recent Client Projects</h4>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr><th>#</th><th>Project Name</th><th>Client</th><th>Goal (Rs.)</th><th>Collected (Rs.)</th><th>Status</th><th>Timeline</th></tr>
      </thead>
      <tbody>
      <?php if ($projects): foreach ($projects as $i => $p): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($p['project_name']) ?></td>
          <td><?= htmlspecialchars($p['client_name']) ?></td>
          <td><?= number_format($p['goal']) ?></td>
          <td><?= number_format($p['collected']) ?></td>
          <td><?= ucfirst($p['status']) ?></td>
          <td><?= date("M d, Y", strtotime($p['start_date'])) ?> - <?= date("M d, Y", strtotime($p['end_date'])) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7" class="text-center text-muted">No projects found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <h4 class="section-title"><i class="fa-solid fa-hand-holding-heart text-danger"></i> Recent Donations</h4>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr><th>#</th><th>Donor</th><th>Project</th><th>Client</th><th>Amount (Rs.)</th><th>Message</th><th>Date</th></tr>
      </thead>
      <tbody>
      <?php if ($donations): foreach ($donations as $i => $d): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($d['donor_name']) ?></td>
          <td><?= htmlspecialchars($d['project_name']) ?></td>
          <td><?= htmlspecialchars($d['client_name']) ?></td>
          <td><?= number_format($d['amount']) ?></td>
          <td><?= htmlspecialchars($d['message'] ?: '—') ?></td>
          <td><?= date("M d, Y h:i A", strtotime($d['created_at'])) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7" class="text-center text-muted">No donations yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <footer>© <?= date("Y") ?> Crowdfunding Platform — Admin Dashboard</footer>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('overlay');
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
  document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
}
</script>

</body>
</html>
