<?php      
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'investor') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$investor_name = $_SESSION['username'] ?? 'Investor';
$date = date("l, F d, Y");

// ------------------------
// FETCH PROJECTS
// ------------------------
$projects = $pdo->query("SELECT * FROM projects WHERE status='approved' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch total invested per project
$investments = $pdo->query("SELECT project_id, SUM(invested_amount) as total FROM investor_projects WHERE status='approved' GROUP BY project_id")->fetchAll(PDO::FETCH_ASSOC);
$investment_totals = [];
foreach($investments as $inv){
    $investment_totals[$inv['project_id']] = $inv['total']; // ← Fixed: was $investment_tot
}

// Fetch logged-in investor's own investments + status
$stmt = $pdo->prepare("
    SELECT ip.project_id, SUM(ip.invested_amount) as my_amount, MAX(ip.status) as my_status
    FROM investor_projects ip
    JOIN investor i ON i.investor_id = ip.investor_id
    WHERE i.user_id = ?
    GROUP BY ip.project_id
");
$stmt->execute([$user_id]);
$my_investments = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
    $my_investments[$row['project_id']] = [
        'amount' => $row['my_amount'],
        'status' => $row['my_status']
    ];
}

// ------------------------
// HANDLE INVESTMENT
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'], $_POST['amount'])) {
    $project_id = intval($_POST['project_id']);
    $amount = floatval($_POST['amount']);

    $stmt = $pdo->prepare("SELECT project_name, goal FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Project not found.";
        header("Location: browse_projects.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(invested_amount),0) as total FROM investor_projects WHERE project_id = ? AND status='approved'");
    $stmt->execute([$project_id]);
    $total_invested_db = floatval($stmt->fetchColumn());
    $remaining = floatval($project['goal']) - $total_invested_db;

    if ($amount <= 0) {
        $_SESSION['error'] = "Enter a valid amount.";
    } elseif ($remaining <= 0) {
        $_SESSION['error'] = "Project is already fully funded.";
    } elseif ($amount > $remaining) {
        $_SESSION['error'] = "You can only invest up to Rs. " . number_format($remaining) . ".";
    } else {
        $stmt = $pdo->prepare("SELECT investor_id FROM investor WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$inv){
            $pdo->prepare("INSERT INTO investor (user_id) VALUES (?)")->execute([$user_id]);
            $investor_id = $pdo->lastInsertId();
        } else {
            $investor_id = $inv['investor_id'];
        }

        $insert = $pdo->prepare("INSERT INTO investor_projects (investor_id, project_id, invested_amount, status, invested_at) VALUES (?,?,?,?,NOW())");
        $insert->execute([$investor_id, $project_id, $amount, 'Pending']);

        $_SESSION['success'] = "You invested Rs. " . number_format($amount) . " in '" . htmlspecialchars($project['project_name'], ENT_QUOTES) . "'! (Pending Approval)";
    }

    header("Location: browse_projects.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Browse Projects | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f5f7fa;margin:0;overflow-x:hidden;}
.sidebar {position: fixed;width: 250px;height: 100vh;background: linear-gradient(180deg,#4f46e5,#2563eb);color:#fff;padding:25px 20px;display:flex;flex-direction:column;gap:20px;box-shadow:5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo {text-align:center;font-weight:700;font-size:22px;margin-bottom:30px;}
.sidebar a {color:#fff;text-decoration:none;font-weight:500;padding:12px 15px;border-radius:12px;display:flex;align-items:center;gap:12px;transition:0.3s;}
.sidebar a.active, .sidebar a:hover {background: rgba(255,255,255,0.2);}
.main { margin-left:270px; padding:40px 50px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:35px; flex-wrap:wrap; }
.header-left h4{ font-weight:700; color:#1e293b; }
.header-left .date{ color:#64748b; font-size:14px; }
.project-card { background:#fff; border-radius:15px; overflow:hidden; border:1px solid #e2eaf3; 
    box-shadow:0 8px 20px rgba(0,0,0,0.08); transition:.3s; }
.project-card:hover { transform:translateY(-5px); box-shadow:0 16px 32px rgba(0,0,0,0.15); }
.project-card img { width:100%; height:180px; object-fit:cover; }
.small-meta { font-size:13px; color:#6b7280; }
.preset-amounts .btn { font-size:12px; margin-right:5px; margin-top:5px; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="investor_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php" class="active"><i class="fa-solid fa-folder-open"></i> Browse</a>
  <a href="investment_history.php"><i class="fa-solid fa-coins"></i> My Investments</a>
  <a href="investor_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <div class="header">
        <div class="header-left">
            <h4>Welcome, <?= htmlspecialchars($investor_name) ?> Wave</h4>
            <div class="date"><?= $date ?></div>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <h4>Available Projects</h4>
    <div class="projects-grid row">
        <?php foreach ($projects as $p): 
    $total_invested = $investment_totals[$p['id']] ?? 0;
    $my_data = $my_investments[$p['id']] ?? ['amount' => 0, 'status' => null];
    $my_amount = $my_data['amount'];
    $my_status = $my_data['status'];
    $progress = ($p['goal'] > 0) ? min(100, ($total_invested / $p['goal']) * 100) : 0;
    
    $is_rejected = ($my_status === 'rejected');

    $db_image = trim($p['image'] ?? '');
    if (!empty($db_image)) {
        if (file_exists(__DIR__ . '/../' . $db_image)) {
            $img_path = '../' . $db_image;
        } elseif (file_exists(__DIR__ . '/../client/images/' . basename($db_image))) {
            $img_path = '../client/images/' . basename($db_image);
        } else {
            $img_path = '../client/images/default_project.png';
        }
    } else {
        $img_path = '../client/images/default_project.png';
    }

    $remaining_ui = max(0, $p['goal'] - $total_invested);
?>

<div class="col-md-4 mb-4">
    <div class="project-card">
        <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($p['project_name']) ?>">

        <div class="p-3">
            <h5><?= htmlspecialchars($p['project_name']) ?></h5>

            <p class="small-meta"><strong>Goal:</strong> Rs. <?= number_format($p['goal']) ?> &nbsp; | &nbsp;
            <strong>Total Invested:</strong> Rs. <?= number_format($total_invested) ?></p>

            <?php if($my_amount > 0): ?>
                <?php if($is_rejected): ?>
                    <div class="alert alert-danger p-2 mb-2 text-center">
                        <strong><i class="fa-solid fa-times-circle"></i> Your investment of Rs. <?= number_format($my_amount) ?> was rejected.</strong><br>
                        <small>The amount has been refunded to your account.</small>
                    </div>
                <?php else: ?>
                    <p class="small-meta text-success">
                        <strong>Your Investment:</strong> Rs. <?= number_format($my_amount) ?> 
                        <span class="badge bg-<?= $my_status === 'approved' ? 'success' : 'warning' ?> fs-6">
                            <?= $my_status === 'approved' ? 'Approved' : 'Pending' ?>
                        </span>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="progress mb-2">
                <div class="progress-bar bg-success" style="width: <?= $progress ?>%"><?= round($progress) ?>%</div>
            </div>

            <p><?= htmlspecialchars(substr($p['description'], 0, 100)) ?>...</p>

            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#view<?= $p['id'] ?>">
                    <i class="fa-solid fa-eye"></i> View
                </button>

                <!-- ONLY THIS PART IS FIXED — SAME BUTTON, SAME ICON, JUST TEXT CHANGES WHEN APPROVED -->
                <?php if($my_status === 'approved'): ?>
                    <button class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#invest<?= $p['id'] ?>">
                        <i class="fa-solid fa-plus"></i> Invest More
                    </button>
                <?php elseif(!$is_rejected): ?>
                    <button class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#invest<?= $p['id'] ?>">
                        <i class="fa-solid fa-plus"></i> Invest
                    </button>
                <?php endif; ?>
                <!-- END OF FIX -->

                <?php if($my_status === 'Pending'): ?>
                    <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal" data-bs-target="#edit<?= $p['id'] ?>">
                        <i class="fa-solid fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete this pending investment?')) { document.getElementById('deleteForm<?= $p['id'] ?>').submit(); }">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>

                    <form id="deleteForm<?= $p['id'] ?>" method="POST" style="display:none;">
                        <input type="hidden" name="delete_investment" value="<?= $p['id'] ?>">
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- All modals exactly same as yours — no change -->
        <!-- VIEW MODAL -->
        <div class="modal fade" id="view<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <img src="<?= htmlspecialchars($img_path) ?>" class="w-100" style="height:300px;object-fit:cover;">
                    <div class="modal-body">
                        <h4><?= htmlspecialchars($p['project_name']) ?></h4>
                        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                        <p><strong>Goal:</strong> Rs. <?= number_format($p['goal']) ?></p>
                        <p><strong>Total Invested:</strong> Rs. <?= number_format($total_invested) ?></p>
                        <p><strong>Remaining:</strong> Rs. <?= number_format($remaining_ui) ?></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- INVEST MODAL -->
        <div class="modal fade" id="invest<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="mb-3">Invest in <strong><?= htmlspecialchars($p['project_name']) ?></strong></h5>
                        <form method="POST">
                            <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Amount (Rs.)</label>
                                <input type="number" name="amount" class="form-control form-control-lg" min="100" step="100" required placeholder="Enter amount">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Confirm Investment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- EDIT MODAL -->
        <?php if($my_status === 'Pending'): ?>
        <div class="modal fade" id="edit<?= $p['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5>Edit Your Pending Investment</h5>
                        <form method="POST">
                            <input type="hidden" name="edit_investment" value="<?= $p['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Current: Rs. <?= number_format($my_amount) ?></label>
                                <input type="number" name="new_amount" class="form-control" value="<?= $my_amount ?>" min="100" required>
                            </div>
                            <button type="submit" class="btn btn-warning text-white">Update Amount</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>