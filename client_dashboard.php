<?php       
session_start();
require 'db_connect.php'; // PDO connection

// ✅ Ensure client is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$client_name = $_SESSION['username'] ?? 'Client';
$date = date("l, F d, Y");
$user_id = $_SESSION['user_id'];

// ✅ Fetch profile picture
$stmt = $pdo->prepare("SELECT profile_pic, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_path = (!empty($profile['profile_pic']) && file_exists(__DIR__ . '/' . $profile['profile_pic']))
    ? $profile['profile_pic']
    : ((!empty($profile['profile_image']) && file_exists(__DIR__ . '/' . $profile['profile_image']))
        ? $profile['profile_image']
        : 'uploads/default.png');

// ✅ Project stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected
FROM projects WHERE user_id = ? OR client_id = ?");
$stmt->execute([$user_id, $user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_projects = $stats['total'] ?? 0;
$pending_projects = $stats['pending'] ?? 0;
$approved_projects = $stats['approved'] ?? 0;
$rejected_projects = $stats['rejected'] ?? 0;

// ✅ Donations stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) AS total_donations,
    COALESCE(SUM(amount), 0) AS total_amount
FROM donations WHERE donor_id = ?");
$stmt->execute([$user_id]);
$donation_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total_donations = $donation_stats['total_donations'] ?? 0;
$total_donation_amount = $donation_stats['total_amount'] ?? 0;

// ✅ Fetch projects
$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? OR client_id = ? ORDER BY id DESC");
$stmt->execute([$user_id, $user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Dashboard | Crowdfunding</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
html, body {
  margin: 0 !important;
  padding: 0 !important;
  height: 100%;
  overflow-x: hidden;
}
body {
  font-family: 'Poppins', sans-serif;
  background: #e8f0ff;
}

/* ✅ Sidebar */
.sidebar {
  position: fixed;
  width: 260px;
  height: 100vh;
  background: linear-gradient(200deg, #2563eb, #4f46e5);
  color: #fff;
  padding: 25px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  transition: left 0.3s ease;
  top: 0 !important;
  left: 0 !important;
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
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}
.sidebar a:hover, .sidebar a.active {
  background: rgba(255, 255, 255, 0.15);
  transform: translateX(5px);
}

/* ✅ Main content */
.main {
  margin-left: 280px;
  padding: 40px 50px;
  margin-top: 0 !important;
  padding-top: 0 !important;
}
.welcome-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 35px;
  flex-wrap: wrap;
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
  box-shadow: 0 3px 10px rgba(0,0,0,0.2);
  transition: all 0.3s;
}
.profile-pic:hover { transform: scale(1.15); }

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 25px;
  margin-bottom: 40px;
}
.stat-card {
  background: #fff;
  border-radius: 15px;
  padding: 25px;
  text-align: center;
  transition: 0.3s ease;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}
.stat-card i { font-size: 28px; margin-bottom: 10px; }
.stat-card h5 { font-weight: 600; color: #374151; }
.stat-card h3 { font-weight: 700; font-size: 24px; color: #111827; }

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}
.project-card {
  background: #fff;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  transition: all 0.3s;
  position: relative;
  cursor: pointer;
}
.project-card:hover { transform: translateY(-7px) scale(1.02); }
.project-card img {
  width: 100%;
  height: 150px;
  object-fit: cover;
}
.project-body { padding: 15px; }
.project-body h5 {
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 5px;
}
.project-body p {
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 10px;
}
.progress { height: 6px; border-radius: 10px; background: #e5e7eb; margin-bottom: 5px; }
.progress-bar { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.badge-status {
  position: absolute;
  top: 10px; right: 10px;
  padding: 5px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}
footer {
  text-align: center;
  margin-top: 60px;
  color: #777;
  font-size: 14px;
}

/* ✅ Modal */
#profileModal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.6);
}
#profileModal img {
  display: block;
  margin: 10% auto;
  max-width: 400px;
  width: 80%;
  border-radius: 15px;
}

/* ✅ Toggle button for mobile */
.toggle-btn {
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
  z-index: 2000;
}

/* ✅ Responsive Design */
@media (max-width: 992px) {
  .main { margin-left: 0; padding: 20px; }
  .sidebar { left: -260px; }
  .sidebar.active { left: 0; }
  .stats { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
  .projects-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
  .profile-pic { width: 50px; height: 50px; }
  .welcome-section { padding-left: 50px; }
}

/* ✅ Hide toggle icon on large screens */
@media (min-width: 993px) {
  .toggle-btn { display: none !important; }
}
</style>
</head>
<body>

<!-- ✅ Toggle Button -->
<button class="toggle-btn" onclick="document.querySelector('.sidebar').classList.toggle('active')">
  <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="client_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php"><i class="fa-solid fa-plus-circle"></i> Create Project</a>
  <a href="client_project.php"><i class="fa-solid fa-folder-open"></i> My Projects</a>
  <a href="client_donations.php"><i class="fa-solid fa-hand-holding-dollar"></i> Donations</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="welcome-section">
    <div>
      <h4>Welcome, @<?= htmlspecialchars($client_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <div><img src="<?= htmlspecialchars($profile_path) ?>" id="profilePic" class="profile-pic"></div>
  </div>

  <div class="stats">
    <div class="stat-card"><i class="fa-solid fa-folder-open text-primary"></i><h5>Total Projects</h5><h3><?= $total_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-hourglass-half text-warning"></i><h5>Pending</h5><h3><?= $pending_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-circle-check text-success"></i><h5>Approved</h5><h3><?= $approved_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-circle-xmark text-danger"></i><h5>Rejected</h5><h3><?= $rejected_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-hand-holding-dollar text-info"></i><h5>Donations</h5><h3><?= $total_donations ?> (Rs. <?= number_format($total_donation_amount) ?>)</h3></div>
  </div>

  <h4 class="mb-3">Your Projects</h4>
  <?php if($projects): ?>
  <div class="projects-grid">
    <?php foreach($projects as $row):
      $status = $row['status'];
      $color = ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$status] ?? 'secondary';
      $progress = rand(20,95);
    ?>
    <div class="project-card position-relative">
      <img src="<?= !empty($row['image']) ? htmlspecialchars($row['image']) : 'uploads/default.png' ?>" alt="Project">
      <span class="badge bg-<?= $color ?> badge-status"><?= htmlspecialchars($status) ?></span>
      <div class="project-body">
        <h5><?= htmlspecialchars($row['title']) ?></h5>
        <p><?= htmlspecialchars(substr($row['description'],0,60)) ?>...</p>
        <div class="progress"><div class="progress-bar" style="width: <?= $progress ?>%;"></div></div>
        <small class="text-muted"><?= $progress ?>% funded</small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p>No projects yet. <a href="create_project.php">Create one now</a></p>
  <?php endif; ?>

  <footer>© <?= date("Y") ?> Crowdfunding Platform</footer>
</div>

<div id="profileModal"><img src="<?= htmlspecialchars($profile_path) ?>"></div>
<script>
const modal = document.getElementById('profileModal');
const pic = document.getElementById('profilePic');
pic.onclick = ()=> modal.style.display="block";
modal.onclick = ()=> modal.style.display="none";
</script>
</body>
</html>
