<?php
session_start();
require 'db_connect.php'; // PDO connection

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

// ✅ Fetch donation history
$stmt = $pdo->prepare("
    SELECT p.project_name, p.image, d.amount, d.created_at
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
<title>My Donations | Crowdfunding</title>
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
.table-container{background:#fff;border-radius:15px;box-shadow:0 8px 20px rgba(0,0,0,0.1);padding:25px;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(90deg,#6366f1,#4f46e5);color:#fff;padding:12px;border:none;text-align:left;font-weight:600;}
td{padding:12px;border-bottom:1px solid #e5e7eb;color:#374151;}
tr:hover{background:#f1f5ff;}
.project-img{width:60px;height:40px;border-radius:6px;object-fit:cover;margin-right:10px;}
footer{text-align:center;margin-top:60px;color:#777;font-size:14px;}
#profileModal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.6);}
#profileModal img{display:block;margin:10% auto;max-width:400px;width:80%;border-radius:15px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="donor_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php"><i class="fa-solid fa-hand-holding-dollar"></i> Fund Projects</a>
  <a href="donation_history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> My Donations</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="welcome-section">
    <div>
      <h4>My Donations 💖</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <div>
      <img src="<?= htmlspecialchars($profile_path) ?>" id="profilePic" class="profile-pic">
    </div>
  </div>

  <div class="table-container">
    <?php if (count($donations) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Project</th>
            <th>Amount (Rs.)</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donations as $d): ?>
          <tr>
            <td>
              <img src="<?= htmlspecialchars($d['image'] ?? 'uploads/default.png') ?>" class="project-img">
              <?= htmlspecialchars($d['project_name']) ?>
            </td>
            <td><strong><?= number_format($d['amount'], 2) ?></strong></td>
            <td><?= date("F d, Y", strtotime($d['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You haven’t donated to any projects yet.</p>
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
