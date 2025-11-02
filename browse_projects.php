<?php  
session_start();
require 'db_connect.php'; // ✅ PDO connection

// ✅ Ensure donor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$donor_name = $_SESSION['username'] ?? 'Donor';
$date = date("l, F d, Y");

// ✅ Fetch profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetchColumn();
$profile_path = (!empty($profile) && file_exists(__DIR__ . '/' . $profile)) ? $profile : 'uploads/default.png';

// ✅ Fetch only approved projects (removed target_amount)
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.project_name,
        p.description,
        p.image,
        p.status,
        COALESCE(SUM(d.amount), 0) AS raised_amount
    FROM projects p
    LEFT JOIN donations d ON p.id = d.project_id
    WHERE p.status = 'approved'
    GROUP BY p.id, p.project_name, p.description, p.image, p.status
    ORDER BY p.id DESC
");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Browse Projects | Crowdfunding</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f3f6fb;margin:0;}
.sidebar{position:fixed;width:260px;height:100vh;background:linear-gradient(200deg,#2563eb,#4f46e5);color:#fff;padding:25px;display:flex;flex-direction:column;gap:20px;}
.sidebar .logo{text-align:center;font-weight:700;font-size:20px;margin-bottom:30px;}
.sidebar a{color:#fff;text-decoration:none;padding:12px 15px;border-radius:10px;display:flex;align-items:center;gap:12px;font-weight:500;}
.sidebar a:hover, .sidebar a.active{background:rgba(255,255,255,0.15);transform:translateX(5px);}
.main{margin-left:280px;padding:40px 50px;}
.welcome-section{display:flex;align-items:center;justify-content:space-between;margin-bottom:35px;}
.welcome-section h4{font-weight:700;color:#1e293b;}
.welcome-section .date{color:#64748b;font-size:15px;}
.profile-pic{width:60px;height:60px;border-radius:50%;object-fit:cover;cursor:pointer;transition:all 0.3s;}
.profile-pic:hover{transform:scale(1.15);box-shadow:0 5px 15px rgba(0,0,0,0.3);}
.projects-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:25px;}
.project-card{background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 8px 20px rgba(0,0,0,0.1);transition:all 0.3s;position:relative;cursor:pointer;}
.project-card:hover{transform:translateY(-7px) scale(1.02);box-shadow:0 12px 35px rgba(0,0,0,0.15);}
.project-card img{width:100%;height:160px;object-fit:cover;transition:all 0.3s;}
.project-card:hover img{transform:scale(1.05);}
.project-body{padding:18px;}
.project-body h5{font-weight:600;font-size:1.1rem;margin-bottom:5px;}
.project-body p{font-size:0.9rem;color:#6b7280;margin-bottom:12px;}
.btn-donate{background:linear-gradient(90deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:8px;padding:8px 15px;font-size:14px;font-weight:500;transition:0.3s;}
.btn-donate:hover{opacity:0.9;transform:translateY(-2px);}
.progress{height:6px;border-radius:10px;background:#e5e7eb;margin-bottom:5px;}
.progress-bar{background:linear-gradient(90deg,#6366f1,#8b5cf6);}
footer{text-align:center;margin-top:60px;color:#777;font-size:14px;}
#profileModal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.6);}
#profileModal img{display:block;margin:10% auto;max-width:400px;width:80%;border-radius:15px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="donor_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php" class="active"><i class="fa-solid fa-hand-holding-dollar"></i> Fund Projects</a>
  <a href="donation_history.php"><i class="fa-solid fa-clock-rotate-left"></i> My Donations</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="welcome-section">
    <div>
      <h4>Explore Projects 💡</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <div>
      <img src="<?= htmlspecialchars($profile_path) ?>" id="profilePic" class="profile-pic">
    </div>
  </div>

  <?php if ($projects): ?>
  <div class="projects-grid">
    <?php foreach ($projects as $p): 
      $progress = rand(10, 95); // demo progress
    ?>
      <div class="project-card">
        <img src="<?= htmlspecialchars($p['image'] ?? 'uploads/default.png') ?>" alt="Project Image">
        <div class="project-body">
          <h5><?= htmlspecialchars($p['project_name']) ?></h5>
          <p><?= htmlspecialchars(substr($p['description'], 0, 70)) ?>...</p>
          <div class="progress">
            <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
          </div>
          <small class="text-muted"><?= $progress ?>% funded</small><br>
          <a href="donate.php?project_id=<?= urlencode($p['id']) ?>" class="btn btn-donate mt-2">Donate</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p>No approved projects available at the moment.</p>
  <?php endif; ?>

  <footer>© <?= date("Y") ?> Crowdfunding Platform</footer>
</div>

<div id="profileModal"><img src="<?= htmlspecialchars($profile_path) ?>"></div>
<script>
const modal=document.getElementById('profileModal');
const pic=document.getElementById('profilePic');
pic.onclick=()=>{modal.style.display="block";}
modal.onclick=()=>{modal.style.display="none";}
</script>
</body>
</html>
