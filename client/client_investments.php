<?php       
session_start();
require '../db_connect.php'; // PDO connection

// Ensure client is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['username'] ?? 'Client';
$date = date("l, F d, Y");

// Fetch investments for APPROVED projects + APPROVED investments only
$stmt = $pdo->prepare("
    SELECT 
        ip.id,
        COALESCE(ip.invested_amount, 0) AS amount,
        COALESCE(ip.invested_at, NOW()) AS created_at,
        COALESCE(u.username, 'Anonymous') AS investor_name,
        COALESCE(p.title, p.project_name, 'Untitled Project') AS project_title
    FROM investor_projects ip
    LEFT JOIN projects p ON ip.project_id = p.id
    LEFT JOIN investor i ON ip.investor_id = i.investor_id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE 
        p.user_id = ? 
        AND p.status = 'approved'
        AND ip.status = 'approved'
    ORDER BY COALESCE(ip.invested_at, NOW()) DESC
");
$stmt->execute([$user_id]);
$investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Investments | DevVest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; background: #f3f6fb; margin:0; }

/* SIDEBAR */
.sidebar {
    position: fixed;
    width: 260px;
    height: 100vh;
    background: linear-gradient(200deg,#2563eb,#4f46e5);
    color: #fff;
    padding:25px;
    display:flex;
    flex-direction:column;
    gap:20px;
}
.sidebar .logo {
    text-align:center;
    font-weight:700;
    font-size:20px;
    margin-bottom:30px;
}
.sidebar a {
    color:#fff;
    text-decoration:none;
    padding:12px 15px;
    border-radius:10px;
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:500;
    transition:0.3s;
}
.sidebar a:hover, .sidebar a.active {
    background: rgba(255,255,255,0.15);
    transform: translateX(5px);
}

/* MAIN */
.main {
    margin-left:280px;
    padding:40px 50px;
}
.welcome-section {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:35px;
}

/* TABLE */
.table-container {
    background:#fff;
    border-radius:15px;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
    padding:25px;
}
.table thead {
    background:#2563eb;
    color:#fff;
}
.table tbody tr:hover {
    background:#f1f5ff;
    transition:0.2s;
}

footer {
    text-align:center;
    margin-top:60px;
    color:#777;
    font-size:14px;
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>

  <a href="client_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php"><i class="fa-solid fa-plus-circle"></i> Create Project</a>
  <a href="client_project.php"><i class="fa-solid fa-folder-open"></i> My Projects</a>

  <a href="client_investments.php" class="active">
      <i class="fa-solid fa-coins"></i> Investments
  </a>

  <a href="client_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">

  <div class="welcome-section">
    <div>
      <h4>Welcome, <?= htmlspecialchars($client_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
  </div>

  <div class="table-container">
    <h4 class="mb-4">
      <i class="fa-solid fa-coins text-warning"></i> Approved Investments to Your Projects
    </h4>

    <?php if ($investments): ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Project</th>
            <th>Investor</th>
            <th>Amount (Rs.)</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($investments as $i => $row): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($row['project_title']) ?></td>
            <td><?= htmlspecialchars($row['investor_name']) ?></td>
            <td><strong>Rs. <?= number_format($row['amount'], 2) ?></strong></td>
            <td><?= date("F d, Y - h:i A", strtotime($row['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php else: ?>
      <p class="text-center text-muted">No approved investments yet.</p>
    <?php endif; ?>

  </div>

  <footer>© <?= date("Y") ?> DevVest</footer>
</div>

</body>
</html>