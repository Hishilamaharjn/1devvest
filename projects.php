<?php   
session_start();
require 'db_connect.php'; // keep your current db_connect.php

// ✅ Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");

// ✅ Fetch profile picture (FIXED)
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profile_path = $user['profile_pic'] ?? '';

if (!empty($profile_path)) {
    // handle both types: "uploads/filename.jpg" or just "filename.jpg"
    if (file_exists(__DIR__ . "/" . $profile_path)) {
        $profile_pic = $profile_path;
    } elseif (file_exists(__DIR__ . "/uploads/" . $profile_path)) {
        $profile_pic = "uploads/" . $profile_path;
    } else {
        $profile_pic = "uploads/default.png";
    }
} else {
    $profile_pic = "uploads/default.png";
}

// ✅ Handle status update
$success = $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'], $_POST['status'])) {
    $project_id = intval($_POST['project_id']);
    $status = $_POST['status'];
    $allowed = ['pending', 'approved', 'rejected'];

    if (in_array($status, $allowed)) {
        try {
            $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
            $stmt->execute([$status, $project_id]);
            $success = "✅ Status updated successfully!";
        } catch (PDOException $e) {
            $error = "❌ Database error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid status selected.";
    }
}

// ✅ Fetch all projects (with client name)
try {
    $stmt = $pdo->query("
        SELECT p.id, p.title, p.status, p.goal, p.start_date, p.end_date, u.username AS client_name
        FROM projects p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.id DESC
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Projects</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* No other changes made */
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #e0eafc, #cfdef3); min-height: 100vh; margin: 0; overflow-x: hidden; }
.sidebar { position: fixed; width: 260px; height: 100vh; background: linear-gradient(200deg, #2563eb, #4f46e5); color: #fff; padding: 25px 20px; display: flex; flex-direction: column; gap: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.15); transition: transform 0.3s ease; z-index: 1000; }
.sidebar.hidden { transform: translateX(-100%); }
.sidebar .logo { text-align: center; font-weight: 700; font-size: 20px; margin-bottom: 30px; }
.sidebar .logo i { font-size: 22px; margin-right: 6px; }
.sidebar a { color: #fff; text-decoration: none; padding: 12px 15px; border-radius: 10px; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px; font-weight: 500; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.15); transform: translateX(5px); }
.main { margin-left: 280px; padding: 40px 50px; transition: margin-left 0.3s ease; }
.main.fullwidth { margin-left: 0; }
.toggle-btn { display: none; position: fixed; top: 20px; left: 20px; background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 10px 14px; font-size: 18px; z-index: 1100; }
.toggle-btn:hover { background: #1e3a8a; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; flex-wrap: wrap; }
.header-left h4.title { font-weight: 700; color: #1e293b; margin: 0; }
.header-left .date { color: #64748b; font-size: 14px; }
.profile-pic { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #2563eb; cursor: pointer; }
.table-container { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.table th { background: #2563eb; color: #fff; }
.badge { text-transform: capitalize; }
.btn-update { background-color: #2563eb; color: #fff; }
.btn-update:hover { background-color: #1e3a8a; }
@media (max-width: 768px) { .toggle-btn { display: block; } .sidebar { transform: translateX(-100%); } .sidebar.show { transform: translateX(0); } .main { margin-left: 0; padding: 80px 20px 40px; } .header { flex-direction: column; align-items: flex-start; gap: 10px; } }
</style>
</head>
<body>

<button class="toggle-btn" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>

<div class="sidebar" id="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php" class="active"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main" id="mainContent">
  <div class="header">
    <h4>Manage Projects</h4>
    <div><?= $date ?></div>
    <a href="profile.php">
      <img src="<?= htmlspecialchars($profile_pic) ?>" class="profile-pic" title="Go to Profile">
    </a>
  </div>

  <div class="table-container">
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($projects)): ?>
      <p>No projects found.</p>
    <?php else: ?>
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Client</th>
            <th>Status</th>
            <th>Goal (Rs.)</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $p): ?>
            <?php 
              $status_class = [
                  'pending' => 'bg-warning text-dark',
                  'approved' => 'bg-success text-white',
                  'rejected' => 'bg-danger text-white'
              ][$p['status']] ?? 'bg-secondary text-white';
            ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['title']) ?></td>
              <td><?= htmlspecialchars($p['client_name']) ?></td>
              <td><span class="badge <?= $status_class ?>"><?= htmlspecialchars($p['status']) ?></span></td>
              <td><?= number_format($p['goal'], 2) ?></td>
              <td><?= htmlspecialchars($p['start_date']) ?></td>
              <td><?= htmlspecialchars($p['end_date']) ?></td>
              <td>
                <form method="POST" class="d-flex gap-2">
                  <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                  <select name="status" class="form-select form-select-sm">
                    <option value="pending" <?= $p['status']==='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $p['status']==='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $p['status']==='rejected'?'selected':'' ?>>Rejected</option>
                  </select>
                  <button type="submit" class="btn btn-update btn-sm">
                    <i class="fa-solid fa-rotate"></i> Update
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('mainContent');
toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('show');
  main.classList.toggle('fullwidth');
});
</script>
</body>
</html>
