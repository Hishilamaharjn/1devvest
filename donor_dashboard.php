<?php   
session_start();
require 'db_connect.php';

// ✅ Ensure donor is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'donor') {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($_SESSION['role'] === 'client') {
        header("Location: client_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$user_id = $_SESSION['user_id'];
$donor_name = $_SESSION['username'] ?? 'Donor';
$date = date("l, F d, Y");

// ✅ Fetch profile picture
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

// ✅ Total donated amount
$stmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM donations WHERE donor_id = ?");
$stmt->execute([$user_id]);
$total_donated = $stmt->fetchColumn();

// ✅ Total projects supported
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT project_id) FROM donations WHERE donor_id = ?");
$stmt->execute([$user_id]);
$total_projects = $stmt->fetchColumn();

// ✅ Recent donations
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(p.project_name, p.title, 'Untitled Project') AS project_name,
        p.image,
        d.amount,
        d.created_at,
        COALESCE(u.username, CONCAT('Client #', p.client_id), 'Unknown Client') AS client_name
    FROM donations d
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN users u ON p.client_id = u.id
    WHERE d.donor_id = ?
    ORDER BY d.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donor Dashboard | Crowdfunding</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f3f6fb;margin:0;}
.sidebar{position:fixed;width:260px;height:100vh;background:linear-gradient(200deg,#2563eb,#4f46e5);color:#fff;padding:25px;display:flex;flex-direction:column;gap:20px;transition:left 0.3s ease;z-index:1050;}
.sidebar .logo{text-align:center;font-weight:700;font-size:20px;margin-bottom:30px;}
.sidebar a{color:#fff;text-decoration:none;padding:12px 15px;border-radius:10px;display:flex;align-items:center;gap:12px;font-weight:500;}
.sidebar a:hover, .sidebar a.active{background:rgba(255,255,255,0.15);transform:translateX(5px);}
.main{margin-left:280px;padding:40px 50px;transition:margin-left 0.3s ease;}
.welcome-section{display:flex;align-items:center;justify-content:space-between;margin-bottom:35px;flex-wrap:wrap;}
.welcome-section h4{font-weight:700;color:#1e293b;}
.welcome-section .date{color:#64748b;font-size:15px;}
.profile-pic{width:60px;height:60px;border-radius:50%;object-fit:cover;cursor:pointer;transition:all 0.3s;}
.profile-pic:hover{transform:scale(1.15);box-shadow:0 5px 15px rgba(0,0,0,0.3);}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;margin-bottom:40px;}
.stat-card{background:#fff;border-radius:20px;padding:25px;text-align:center;transition:0.3s ease;box-shadow:0 10px 25px rgba(0,0,0,0.08);}
.stat-card:hover{transform:translateY(-6px);box-shadow:0 12px 30px rgba(0,0,0,0.15);}
.stat-card i{font-size:30px;margin-bottom:10px;color:#2563eb;}
.stat-card h5{font-weight:600;color:#374151;}
.stat-card h3{color:#2563eb;font-weight:700;font-size:26px;}
.projects-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;}
.project-card{background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 8px 20px rgba(0,0,0,0.1);transition:all 0.3s;position:relative;cursor:pointer;}
.project-card:hover{transform:translateY(-7px) scale(1.02);box-shadow:0 12px 35px rgba(0,0,0,0.15);}
.project-card img{width:100%;height:150px;object-fit:cover;transition:all 0.3s;}
.project-card:hover img{transform:scale(1.05);}
.project-body{padding:15px;}
.project-body h5{font-weight:600;font-size:1.1rem;margin-bottom:5px;}
.project-body p{font-size:0.9rem;color:#6b7280;margin-bottom:8px;}
footer{text-align:center;margin-top:60px;color:#777;font-size:14px;}
#profileModal{display:none;position:fixed;z-index:1100;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.6);}
#profileModal img{display:block;margin:10% auto;max-width:400px;width:80%;border-radius:15px;}
/* ✅ Responsive */
.toggle-btn{display:none;}
@media(max-width:991px){
  .toggle-btn{display:block;position:fixed;top:15px;left:20px;background:#2563eb;color:#fff;padding:10px 12px;border-radius:8px;cursor:pointer;z-index:1200;}
  .sidebar{left:-270px;}
  .sidebar.active{left:0;}
  .main{margin-left:0;padding:30px 20px;}
}
@media(max-width:600px){
  .welcome-section h4{font-size:18px;}
  .stat-card h3{font-size:22px;}
  .stat-card i{font-size:24px;}
}
</style>
</head>
<body>

<div class="toggle-btn"><i class="fa-solid fa-bars"></i></div>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="donor_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php"><i class="fa-solid fa-hand-holding-dollar"></i> Fund Projects</a>
  <a href="donation_history.php"><i class="fa-solid fa-clock-rotate-left"></i> My Donations</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="welcome-section">
    <div>
      <h4>Welcome, <?= htmlspecialchars($donor_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <div>
      <img src="<?= htmlspecialchars($profile_path) ?>" id="profilePic" class="profile-pic" alt="Profile">
    </div>
  </div>

  <div class="stats">
    <div class="stat-card">
      <i class="fa-solid fa-hand-holding-heart"></i>
      <h5>Total Donated</h5>
      <h3>Rs. <?= number_format($total_donated, 2) ?></h3>
    </div>
    <div class="stat-card">
      <i class="fa-solid fa-folder-open"></i>
      <h5>Projects Supported</h5>
      <h3><?= $total_projects ?></h3>
    </div>
  </div>

  <h4 class="mb-3">Recent Donations</h4>
  <?php if($recent_donations): ?>
  <div class="projects-grid">
    <?php foreach($recent_donations as $donation): ?>
    <div class="project-card">
      <img src="<?= htmlspecialchars($donation['image'] ?? 'uploads/default.png') ?>" alt="Project">
      <div class="project-body">
        <h5><?= htmlspecialchars($donation['project_name']) ?></h5>
        <p>To: <?= htmlspecialchars($donation['client_name']) ?></p>
        <p>Donated: <strong>Rs. <?= number_format($donation['amount'], 2) ?></strong></p>
        <small class="text-muted"><?= date("F d, Y - h:i A", strtotime($donation['created_at'])) ?></small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p>You haven’t donated to any project yet.</p>
  <?php endif; ?>

  <footer>© <?= date("Y") ?> Crowdfunding Platform</footer>
</div>

<div id="profileModal"><img src="<?= htmlspecialchars($profile_path) ?>"></div>

<script>
const sidebar=document.querySelector('.sidebar');
document.querySelector('.toggle-btn').onclick=()=>sidebar.classList.toggle('active');
const modal=document.getElementById('profileModal');
const pic=document.getElementById('profilePic');
pic.onclick=()=>{modal.style.display="block";}
modal.onclick=()=>{modal.style.display="none";}
</script>

</body>
</html>
