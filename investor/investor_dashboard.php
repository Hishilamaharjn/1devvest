<?php  
session_start();
require '../db_connect.php';

// ------------------------
// CHECK SESSION & ROLE
// ------------------------
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'investor') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$investor_name = $_SESSION['username'] ?? 'Investor';
$date = date("l, F d, Y");

// ------------------------
// GET INVESTOR ID
// ------------------------
$stmt = $pdo->prepare("SELECT investor_id FROM investor WHERE user_id=?");
$stmt->execute([$user_id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);
$investor_id = $inv['investor_id'] ?? null;

$total_invested = 0;
$total_projects = 0;
$recent_investments = [];

if($investor_id){
    // Total invested
    $stmt = $pdo->prepare("SELECT SUM(invested_amount) FROM investor_projects WHERE investor_id=?");
    $stmt->execute([$investor_id]);
    $total_invested = $stmt->fetchColumn() ?? 0;

    // Total projects invested
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT project_id) FROM investor_projects WHERE investor_id=?");
    $stmt->execute([$investor_id]);
    $total_projects = $stmt->fetchColumn() ?? 0;

    // Recent investments
    $stmt = $pdo->prepare("
        SELECT ip.project_id, ip.invested_amount, ip.invested_at
        FROM investor_projects ip
        INNER JOIN (
            SELECT project_id, MAX(invested_at) as latest
            FROM investor_projects
            WHERE investor_id = ?
            GROUP BY project_id
        ) as latest_invest
        ON ip.project_id = latest_invest.project_id AND ip.invested_at = latest_invest.latest
        WHERE ip.investor_id = ?
        ORDER BY ip.invested_at DESC
        LIMIT 5
    ");
    $stmt->execute([$investor_id, $investor_id]);
    $recent_investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ------------------------
// TOTAL INVESTED PER PROJECT
// ------------------------
$projects_investments = $pdo->query("SELECT project_id, SUM(invested_amount) as total FROM investor_projects GROUP BY project_id")->fetchAll(PDO::FETCH_ASSOC);
$project_totals = [];
foreach($projects_investments as $p){
    $project_totals[$p['project_id']] = $p['total'];
}

// ------------------------
// FETCH PROJECT DETAILS FOR RECENT INVESTMENTS
// ------------------------
$recent_projects_details = [];
$project_map = [];
if(!empty($recent_investments)){
    $ids = array_column($recent_investments, 'project_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $stmt = $pdo->prepare("SELECT id, project_name, goal, image FROM projects WHERE id IN ($placeholders) AND status='approved'");
    $stmt->execute($ids);
    $recent_projects_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($recent_projects_details as $p){
        $project_map[$p['id']] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Investor Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f5f7fa;margin:0;overflow-x:hidden;}
.sidebar{position:fixed;width:250px;height:100vh;background:linear-gradient(180deg,#4f46e5,#2563eb);color:#fff;padding:25px 20px;display:flex;flex-direction:column;gap:20px;box-shadow:5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo{text-align:center;font-weight:700;font-size:22px;margin-bottom:30px;}
.sidebar a{color:#fff;text-decoration:none;font-weight:500;padding:12px 15px;border-radius:12px;display:flex;align-items:center;gap:12px;transition:0.3s;}
.sidebar a.active, .sidebar a:hover{background: rgba(255,255,255,0.2);}
.main{margin-left:270px;padding:40px 50px;}
.header h4{font-weight:700;color:#1e293b;}
.header .date{color:#64748b;font-size:14px;}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;margin-bottom:40px;}
.stat-card{border-radius:15px;padding:20px;text-align:center;color:#fff;box-shadow:0 8px 25px rgba(0,0,0,0.1);}
.stat-card i{font-size:36px;margin-bottom:12px;}
.stat-card h5{font-weight:600;font-size:16px;margin-bottom:5px;}
.stat-card h3{font-weight:700;font-size:28px;}
.stat-card:nth-child(1){background:linear-gradient(135deg,#2563eb,#4f46e5);}
.stat-card:nth-child(2){background:linear-gradient(135deg,#22c55e,#16a34a);}
.projects-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;}
.project-card{background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 8px 20px rgba(0,0,0,0.1);}
.project-card img{width:100%;height:160px;object-fit:cover;}
.project-body{padding:15px;}
.project-body h5{font-weight:600;}
.progress{height:10px;border-radius:10px;overflow:hidden;background:#e5e7eb;}
.progress-bar{background:#2563eb;}
</style>
</head>
<body>
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="investor_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php"><i class="fa-solid fa-folder-open"></i> Browse Projects</a>
  <a href="investment_history.php"><i class="fa-solid fa-coins"></i> My Investments</a>
  <a href="investor_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <h4>Welcome, <?= htmlspecialchars($investor_name) ?> </h4>
    <small class="date"><?= $date ?></small>
  </div>

  <div class="stats">
    <div class="stat-card">
      <i class="fa-solid fa-hand-holding-heart"></i>
      <h5>Total Invested</h5>
      <h3>Rs. <?= number_format($total_invested,2) ?></h3>
    </div>
    <div class="stat-card">
      <i class="fa-solid fa-folder-open"></i>
      <h5>Projects Invested</h5>
      <h3><?= $total_projects ?></h3>
    </div>
  </div>

  <h4>Recent Investments</h4>
  <?php if(!empty($recent_investments)): ?>
    <div class="projects-grid">
      <?php foreach($recent_investments as $inv):
        $proj = $project_map[$inv['project_id']] ?? null;
        if(!$proj) continue;
        $progress = ($proj['goal']>0) ? min(100, ($project_totals[$proj['id']] ?? 0)/$proj['goal']*100) : 0;

        // ============ FIXED IMAGE PATH LOGIC (SAME AS BROWSE_PROJECTS.PHP) ============
        $db_image = trim($proj['image'] ?? ''); // e.g. uploads/17633...jpg

        if (!empty($db_image)) {
            // First: use exact path from database (uploads/...)
            if (file_exists(__DIR__ . '/../' . $db_image)) {
                $proj_img = '../' . $db_image;
            }
            // Second: fallback if image moved to client/images/
            elseif (file_exists(__DIR__ . '/../client/images/' . basename($db_image))) {
                $proj_img = '../client/images/' . basename($db_image);
            }
            // Final fallback
            else {
                $proj_img = '../client/images/default_project.png';
            }
        } else {
            $proj_img = '../client/images/default_project.png';
        }
        // ==========================================================================
      ?>
      <div class="project-card">
        <img src="<?= htmlspecialchars($proj_img) ?>" alt="<?= htmlspecialchars($proj['project_name']) ?>">
        <div class="project-body">
          <h5><?= htmlspecialchars($proj['project_name']) ?></h5>
          <p><strong>Your Latest Investment:</strong> Rs. <?= number_format($inv['invested_amount'],2) ?></p>
          <p><strong>Total Invested:</strong> Rs. <?= number_format($project_totals[$proj['id']] ?? 0,2) ?></p>
          <div class="progress mt-2 mb-2">
            <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
          </div>
          <small><?= date("F d, Y - h:i A", strtotime($inv['invested_at'])) ?></small>
          <div class="mt-2">
            <a href="browse_projects.php?project_id=<?= $proj['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-coins"></i> Invest More</a>
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $proj['id'] ?>"><i class="fa-solid fa-eye"></i> View</button>
          </div>
        </div>
      </div>

      <!-- VIEW MODAL -->
      <div class="modal fade" id="viewModal<?= $proj['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?= htmlspecialchars($proj['project_name']) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <img src="<?= htmlspecialchars($proj_img) ?>" class="w-100 mb-3" style="height:300px;object-fit:cover;">
              <p><strong>Goal:</strong> Rs. <?= number_format($proj['goal'],2) ?></p>
              <p><strong>Total Invested:</strong> Rs. <?= number_format($project_totals[$proj['id']] ?? 0,2) ?></p>
            </div>
          </div>
        </div>
      </div>

      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>You haven’t invested in any project yet.</p>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>