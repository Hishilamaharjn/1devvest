<?php
session_start();
require 'db_connect.php';

// ✅ Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ✅ Get project ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid project ID");
}

$project_id = (int)$_GET['id'];

// ✅ Fetch project details
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("Project not found.");
}

// ✅ Approve/Reject handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $update = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $update->execute([$status, $project_id]);
    header("Location: project_detail.php?id=$project_id&success=1");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Project Detail | Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e0eafc, #cfdef3);
  min-height: 100vh;
  margin: 0;
}
.sidebar {
  position: fixed;
  width: 260px;
  height: 100vh;
  background: linear-gradient(200deg, #2563eb, #4f46e5);
  color: #fff;
  padding: 25px 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: 5px 0 15px rgba(0,0,0,0.15);
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
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}
.sidebar a:hover,
.sidebar a.active {
  background: rgba(255,255,255,0.15);
  transform: translateX(5px);
}
.main {
  margin-left: 280px;
  padding: 40px 50px;
}
.project-card {
  background: #fff;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.project-card img {
  width: 100%;
  height: 350px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 20px;
}
.status-btns button {
  margin-right: 10px;
}
.back-btn {
  text-decoration: none;
  color: #2563eb;
  font-weight: 600;
}
.back-btn:hover {
  text-decoration: underline;
}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php" class="active"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="profile.php?role=admin"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <a href="projects.php" class="back-btn mb-3 d-inline-block"><i class="fa-solid fa-arrow-left"></i> Back to Projects</a>

  <div class="project-card">
    <h3 class="fw-bold mb-3"><?= htmlspecialchars($project['project_name']) ?></h3>
    <img src="<?= htmlspecialchars($project['image']) ?>" alt="Project Image">

    <p><strong>Client Name:</strong> <?= htmlspecialchars($project['client_name']) ?></p>
    <p><strong>Title:</strong> <?= htmlspecialchars($project['title']) ?></p>
    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($project['description'])) ?></p>

    <div class="row mt-3">
      <div class="col-md-4"><strong>Goal:</strong> Rs. <?= number_format($project['goal'], 2) ?></div>
      <div class="col-md-4"><strong>Collected:</strong> Rs. <?= number_format($project['collected'], 2) ?></div>
      <div class="col-md-4"><strong>Total Donors:</strong> <?= $project['total_donors'] ?></div>
    </div>

    <div class="row mt-3">
      <div class="col-md-6"><strong>Start Date:</strong> <?= htmlspecialchars($project['start_date']) ?></div>
      <div class="col-md-6"><strong>End Date:</strong> <?= htmlspecialchars($project['end_date']) ?></div>
    </div>

    <p class="mt-3"><strong>Status:</strong> 
      <span class="badge bg-<?=
        $project['status'] == 'approved' ? 'success' :
        ($project['status'] == 'rejected' ? 'danger' : 'warning')
      ?>">
        <?= ucfirst($project['status']) ?>
      </span>
    </p>

    <form method="post" class="status-btns mt-4">
      <button name="status" value="approved" class="btn btn-success"><i class="fa-solid fa-check"></i> Approve</button>
      <button name="status" value="rejected" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Reject</button>
    </form>
  </div>
</div>

</body>
</html>
