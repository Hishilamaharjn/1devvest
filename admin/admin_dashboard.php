<?php   
session_start();
require '../db_connect.php';

// ✅ Only admin allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");

// ✅ Stats
$total_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn() ?: 0;
$pending_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='pending'")->fetchColumn() ?: 0;
$approved_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='approved'")->fetchColumn() ?: 0;
$rejected_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='rejected'")->fetchColumn() ?: 0;
$total_investors = $pdo->query("SELECT COUNT(DISTINCT investor_id) FROM investor_projects")->fetchColumn() ?: 0;
$total_amount_invested = $pdo->query("SELECT IFNULL(SUM(invested_amount),0) FROM investor_projects")->fetchColumn() ?: 0;

// ✅ Featured Projects
$featured_projects = [];
try {
    $featured_stmt = $pdo->query("
        SELECT id, project_name AS title, goal, status, created_at
        FROM projects
        WHERE is_featured = 1
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $featured_projects = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featured_projects = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; background: #f3f4f8; margin: 0; }

/* Sidebar */
.sidebar {
    position: fixed; width: 250px; height: 100vh;
    background: linear-gradient(180deg, #4f46e5, #2563eb);
    color: #fff; padding: 30px 20px 20px 20px;
    box-shadow: 4px 0 15px rgba(0,0,0,0.2);
}
.sidebar .logo { font-size: 24px; text-align: center; font-weight: 700; margin-bottom: 35px; }
.sidebar a {
    display: flex; align-items: center; gap: 12px;
    color: #fff; padding: 12px 16px; border-radius: 10px;
    margin-bottom: 12px; text-decoration: none; font-weight: 500;
    transition: 0.3s;
}
.sidebar a:hover, .sidebar a.active {
    background: rgba(255,255,255,0.2);
}

/* Main */
.main { margin-left: 270px; padding: 35px; }

/* Header */
.header { display: flex; justify-content: space-between; align-items: center; }
.header h4 { font-weight: 700; }

/* Stats Cards */
.stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 25px; margin-top: 25px;
}
.stat-card {
    padding: 20px; border-radius: 15px; color: #fff;
    text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}
.stat-card i { font-size: 36px; margin-bottom: 10px; }
.stat-card:nth-child(1){ background: linear-gradient(135deg,#2563eb,#4f46e5); }
.stat-card:nth-child(2){ background: linear-gradient(135deg,#facc15,#f59e0b); }
.stat-card:nth-child(3){ background: linear-gradient(135deg,#22c55e,#16a34a); }
.stat-card:nth-child(4){ background: linear-gradient(135deg,#ef4444,#b91c1c); }
.stat-card:nth-child(5){ background: linear-gradient(135deg,#06b6d4,#0e7490); }
.stat-card:nth-child(6){ background: linear-gradient(135deg,#f59e0b,#d97706); }

/* Featured table */
.featured-box {
    margin-top: 50px; background: #ffffff;
    border-radius: 16px; padding: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.featured-title {
    font-weight: 600; font-size: 22px;
    margin-bottom: 20px; color: #1e40af;
    text-align: center;
}
.table thead {
    background: linear-gradient(90deg, #4f46e5, #2563eb);
    color: white;
}
.table tbody td { text-align: center; padding: 12px 10px; }
.table tbody tr.status-pending { background: #fde047; font-weight: 600; }
.table tbody tr.status-approved { background: #4ade80; font-weight: 600; color: #fff; }
.table tbody tr.status-rejected { background: #f87171; font-weight: 600; color: #fff; }

@media (max-width: 992px) {
    .sidebar { display: none; }
    .main { margin-left: 0; padding: 20px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>

    <a href="admin_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
    <a href="admin_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
    <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
    <a href="manage_clients.php"><i class="fa-solid fa-users-gear"></i> Manage Clients</a>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">

    <!-- Header -->
    <div class="header">
        <div>
            <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
            <small><?= $date ?></small>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card"><i class="fa-solid fa-folder-open"></i><h5>Total Projects</h5><h3><?= $total_projects ?></h3></div>
        <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><h5>Pending</h5><h3><?= $pending_projects ?></h3></div>
        <div class="stat-card"><i class="fa-solid fa-circle-check"></i><h5>Approved</h5><h3><?= $approved_projects ?></h3></div>
        <div class="stat-card"><i class="fa-solid fa-circle-xmark"></i><h5>Rejected</h5><h3><?= $rejected_projects ?></h3></div>
        <div class="stat-card"><i class="fa-solid fa-users"></i><h5>Total Investors</h5><h3><?= $total_investors ?></h3></div>
        <div class="stat-card"><i class="fa-solid fa-coins"></i><h5>Total Investment</h5><h3>Rs. <?= number_format($total_amount_invested, 2) ?></h3></div>
    </div>

    <!-- Featured -->
    <div class="featured-box">
        <div class="featured-title"><i class="fa-solid fa-star text-warning"></i> Featured Projects</div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Project Name</th>
                        <th>Goal</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($featured_projects): ?>
                        <?php foreach ($featured_projects as $i => $p): ?>
                            <tr class="status-<?= strtolower($p['status']) ?>">
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($p['title']) ?></td>
                                <td>Rs. <?= number_format($p['goal'], 2) ?></td>
                                <td><?= ucfirst($p['status']) ?></td>
                                <td><?= date("M d, Y", strtotime($p['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No featured projects available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
