<?php 
session_start();
require '../db_connect.php';

// Only client allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
    header("Location: ../login.php");
    exit;
}

$client_name = $_SESSION['username'] ?? 'Client';
$date = date("l, F d, Y");
$user_id = $_SESSION['user_id'];

$success = $error = "";

// Upload directory
$upload_dir = __DIR__ . '/images/'; // Server path
$upload_web_path = 'images/';       // Browser path
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Handle project image upload/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    $project_id = (int)$_POST['project_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category'] ?? '');
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Get current image
    $stmt = $pdo->prepare("SELECT image FROM projects WHERE id=? AND user_id=?");
    $stmt->execute([$project_id, $user_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_image = $project['image'] ?? null;

    $image_path = $current_image;

    // Upload new image if exists
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $upload_web_path . $filename;
        } else {
            $error = "❌ Failed to upload image!";
        }
    }

    // Update project
    if (!$error) {
        $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, category=?, start_date=?, end_date=?, image=? WHERE id=? AND user_id=?");
        if ($stmt->execute([$title, $description, $category, $start_date, $end_date, $image_path, $project_id, $user_id])) {
            $success = "✅ Project updated successfully!";
        } else {
            $error = "❌ Failed to update project!";
        }
    }
}

// Fetch stats
function fetchCount($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

$total_projects = fetchCount($pdo, "SELECT COUNT(*) FROM projects WHERE user_id=?", [$user_id]);
$pending_projects = fetchCount($pdo, "SELECT COUNT(*) FROM projects WHERE user_id=? AND status='pending'", [$user_id]);
$approved_projects = fetchCount($pdo, "SELECT COUNT(*) FROM projects WHERE user_id=? AND status='approved'", [$user_id]);
$rejected_projects = fetchCount($pdo, "SELECT COUNT(*) FROM projects WHERE user_id=? AND status='rejected'", [$user_id]);

// Total approved investments
$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(ip.invested_amount),0) 
    FROM investor_projects ip 
    LEFT JOIN projects p ON ip.project_id = p.id 
    WHERE p.user_id=? AND p.status='approved' AND ip.status='approved'
");
$stmt->execute([$user_id]);
$total_investments = $stmt->fetchColumn();

// Featured projects
$stmt = $pdo->prepare("
    SELECT p.*, IFNULL(SUM(ip.invested_amount),0) AS total_funded
    FROM projects p
    LEFT JOIN investor_projects ip ON p.id = ip.project_id AND ip.status='approved'
    WHERE p.user_id=? AND p.status='approved' AND p.is_featured=1
    GROUP BY p.id
    ORDER BY total_funded DESC
");
$stmt->execute([$user_id]);
$featured_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to fetch project detail
function getProjectDetail($pdo, $project_id, $user_id){
    $stmt = $pdo->prepare("
        SELECT p.*, IFNULL(SUM(ip.invested_amount),0) AS total_funded
        FROM projects p
        LEFT JOIN investor_projects ip ON p.id = ip.project_id AND ip.status='approved'
        WHERE p.id=? AND p.user_id=? AND p.status='approved'
        GROUP BY p.id
    ");
    $stmt->execute([$project_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// AJAX request
if(isset($_GET['project_id'])){
    $pid = (int)$_GET['project_id'];
    $project = getProjectDetail($pdo,$pid,$user_id);
    if(!$project){
        echo "<p class='text-danger'>Project not found or not approved yet.</p>";
        exit;
    }
    $proj_img = !empty($project['image']) && file_exists(__DIR__ . '/' . $project['image']) 
                ? $project['image'] 
                : 'images/default_project.png';
    $goal = (float)($project['goal_amount'] ?? 0);
    $funded = (float)($project['total_funded'] ?? 0);
    $progress = $goal>0 ? min(100, round(($funded/$goal)*100)) : 0;
    ?>
    <div class="text-center mb-3">
        <img src="<?= htmlspecialchars($proj_img) ?>" class="img-fluid rounded mb-3" style="max-height:300px;object-fit:cover;">
    </div>
    <h4><?= htmlspecialchars($project['title']) ?></h4>
    <p><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    <p><strong>Goal:</strong> Rs. <?= number_format($goal) ?></p>
    <p><strong>Funded:</strong> Rs. <?= number_format($funded) ?> (<?= $progress ?>%)</p>
    <div class="progress mb-3" style="height:8px;">
        <div class="progress-bar" role="progressbar" style="width:<?= $progress ?>%;"></div>
    </div>
    <p><strong>Start Date:</strong> <?= htmlspecialchars($project['start_date']) ?> | <strong>End Date:</strong> <?= htmlspecialchars($project['end_date']) ?></p>
    <?php exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Dashboard | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* Same CSS as before */
body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin:0; }
.sidebar { position: fixed; width: 260px; height: 100vh; background: linear-gradient(180deg,#4f46e5,#2563eb); color:#fff; padding:25px 20px; display:flex; flex-direction:column; gap:20px; z-index:1500; box-shadow:5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo { text-align:center; font-weight:700; font-size:22px; margin-bottom:30px; cursor:pointer; }
.sidebar a { color:#fff; text-decoration:none; padding:12px 15px; border-radius:12px; display:flex; align-items:center; gap:12px; transition:0.3s; }
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.2); transform:translateX(5px); }
.main { margin-left:280px; padding:40px 50px; }
.header-left h4 { font-weight:700; color:#1e293b; }
.header-left .date { color:#64748b; font-size:14px; }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:25px; margin-bottom:40px; }
.stat-card { border-radius:15px; padding:20px; text-align:center; color:#fff; box-shadow:0 8px 25px rgba(0,0,0,0.1);}
.stat-card i { font-size:36px; margin-bottom:12px; }
.stat-card:nth-child(1){ background: linear-gradient(135deg,#2563eb,#4f46e5);}
.stat-card:nth-child(2){ background: linear-gradient(135deg,#facc15,#f59e0b);}
.stat-card:nth-child(3){ background: linear-gradient(135deg,#22c55e,#16a34a);}
.stat-card:nth-child(4){ background: linear-gradient(135deg,#ef4444,#b91c1c);}
.stat-card:nth-child(5){ background: linear-gradient(135deg,#06b6d4,#0e7490);}
.card-img-top { border-top-left-radius:18px; border-top-right-radius:18px; }
.progress { background-color: #e2e8f0; }
.progress-bar { background-color:#22c55e; }
footer {text-align:center;margin-top:60px;color:#777;font-size:14px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo" onclick="location.href='client_dashboard.php'"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="client_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php"><i class="fa-solid fa-plus"></i> Create Project</a>
  <a href="client_project.php"><i class="fa-solid fa-folder-open"></i> My Projects</a>
  <a href="client_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
  <a href="client_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <div class="header d-flex justify-content-between align-items-center mb-4">
    <div class="header-left">
      <h4>Welcome, <?= htmlspecialchars($client_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
  </div>

  <div class="stats">
    <div class="stat-card"><i class="fa-solid fa-folder-open"></i><h5>Total Projects</h5><h3><?= $total_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><h5>Pending</h5><h3><?= $pending_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-circle-check"></i><h5>Approved</h5><h3><?= $approved_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-circle-xmark"></i><h5>Rejected</h5><h3><?= $rejected_projects ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-coins"></i><h5>Total Investment</h5><h3>Rs. <?= number_format($total_investments,2) ?></h3></div>
  </div>

  <h3 class="mt-5 mb-3" style="font-weight:700;">🌟 Featured Projects</h3>
  <div class="row">
    <?php if(count($featured_projects)===0): ?>
      <div class="col-12 text-center text-muted py-4">No featured projects yet!</div>
    <?php endif; ?>

    <?php foreach ($featured_projects as $project):
    $goal = (float)($project['goal_amount'] ?? 0);
    $funded = (float)($project['total_funded'] ?? 0);
    $progress = ($goal>0) ? min(100, round(($funded/$goal)*100)) : 0;

    // Correct image path
    $proj_img = (!empty($project['image']) && file_exists(__DIR__ . '/' . $project['image'])) 
                ? $project['image'] 
                : "images/default_project.png";
?>
<div class="col-md-4">
  <div class="card shadow-sm border-0 mb-4" style="border-radius:18px; overflow:hidden;">
      <img src="<?= htmlspecialchars($proj_img) ?>" class="card-img-top" style="height:180px; object-fit:cover;">
      <div class="card-body">
          <h5 class="card-title" style="font-weight:600;"><?= htmlspecialchars($project['title'] ?? '-') ?></h5>
          <p class="text-muted" style="font-size:14px;"><?= mb_substr(htmlspecialchars($project['description'] ?? ''),0,90) ?>...</p>
          <div class="progress mb-2" style="height:8px; border-radius:5px;">
            <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;"></div>
          </div>
          <small class="text-muted"><?= $progress ?>% funded</small>
          <div class="d-flex justify-content-between align-items-center mt-3">
              <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-coins"></i> Rs. <?= number_format($funded,2) ?> Raised</span>
              <span class="text-muted" style="font-size:13px;"><i class="fa-solid fa-eye"></i> <?= $project['views'] ?? 0 ?> Views</span>
              <button class="btn btn-primary btn-sm viewProjectBtn" data-id="<?= $project['id'] ?>" data-bs-toggle="modal" data-bs-target="#projectModal">View</button>
          </div>
      </div>
  </div>
</div>
<?php endforeach; ?>

  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectModalLabel">Project Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="projectModalBody">Loading...</div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    $('.viewProjectBtn').click(function(){
        let projectId = $(this).data('id');
        $('#projectModalBody').html('Loading...');
        $.get('', {project_id: projectId}, function(data){
            $('#projectModalBody').html(data);
        });
    });
});
</script>
</body>
</html>





