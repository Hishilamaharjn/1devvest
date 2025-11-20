<?php
session_start();
require '../db_connect.php'; // ✅ PDO connection

// ✅ Ensure investor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'investor') {
    header("Location: ../login.php");
    exit;
}

$investor_id = $_SESSION['user_id'];
$investor_name = $_SESSION['username'] ?? 'Investor';
$date = date("l, F d, Y");

// ✅ Fetch investments made by this investor (standardized)
$stmt = $pdo->prepare("
    SELECT 
        ip.id,
        COALESCE(ip.invested_amount, ip.amount, 0) AS amount,
        COALESCE(ip.invested_at, ip.created_at) AS created_at,
        COALESCE(p.title, p.project_name, 'Untitled Project') AS project_title,
        COALESCE(u.username, CONCAT('Client #', p.client_id), 'Client') AS client_name
    FROM investor_projects ip
    LEFT JOIN projects p ON ip.project_id = p.id
    LEFT JOIN users u ON p.client_id = u.id
    WHERE ip.investor_id = ?
    ORDER BY COALESCE(ip.invested_at, ip.created_at) DESC
");
$stmt->execute([$investor_id]);
$investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Investments | DevVest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif;background:#eef3ff;margin:0;}
.sidebar { position:fixed;width:260px;height:100vh;background:linear-gradient(200deg,#2563eb,#4f46e5);color:#fff;padding:25px;display:flex;flex-direction:column;gap:15px;}
.sidebar .logo{text-align:center;font-weight:700;font-size:20px;margin-bottom:25px;}
.sidebar a{color:#fff;text-decoration:none;padding:12px 15px;border-radius:10px;display:flex;align-items:center;gap:12px;font-weight:500;transition:0.3s;}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,0.2);transform:translateX(6px);}
.main{margin-left:280px;padding:40px 50px;}
.welcome-section{display:flex;justify-content:space-between;align-items:center;margin-bottom:35px;}
.welcome-section h4{font-weight:700;color:#1e293b;}
.welcome-section .date{color:#64748b;font-size:15px;}
.profile-pic{width:60px;height:60px;border-radius:50%;object-fit:cover;cursor:pointer;border:2px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.25);transition:transform 0.3s ease;}
.profile-pic:hover{transform:scale(1.15);}
.table-container{background:#fff;border-radius:15px;box-shadow:0 10px 25px rgba(0,0,0,0.1);padding:25px;}
.table thead{background:#2563eb;color:#fff;}
.table tbody tr:hover{background:#f1f5ff;transition:0.2s;}
footer{text-align:center;margin-top:60px;color:#777;font-size:14px;}
#profileModal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.6);}
#profileModal img{display:block;margin:10% auto;max-width:400px;width:80%;border-radius:15px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="investor_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php"><i class="fa-solid fa-compass"></i> Browse Projects</a>
  <a href="investor_investments.php" class="active"><i class="fa-solid fa-hand-holding-dollar"></i> My Investments</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="welcome-section">
    <div>
      <h4>Welcome, @<?= htmlspecialchars($investor_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <div></div>
  </div>

  <div class="table-container">
    <h4 class="mb-4"><i class="fa-solid fa-hand-holding-dollar text-success"></i> My Investments</h4>

    <?php if ($investments): ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Project</th>
            <th>Client</th>
            <th>Amount (Rs.)</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($investments as $i => $row): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($row['project_title']) ?></td>
            <td><?= htmlspecialchars($row['client_name']) ?></td>
            <td><strong>Rs. <?= number_format($row['amount'], 2) ?></strong></td>
            <td><?= date("F d, Y - h:i A", strtotime($row['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p>No investments made yet.</p>
    <?php endif; ?>
  </div>

  <footer>© <?= date("Y") ?> DevVest</footer>
</div>

<script>
// no-op
</script>
</body>
</html>
