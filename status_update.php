<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");
$message = '';

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT id, status FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            $message = "Project not found.";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_status = $_POST['status'] ?? $project['status'];
            $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $project_id]);
            $message = "Status updated successfully!";
            $project['status'] = $new_status; // Refresh status
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    }
} else {
    header("Location: status.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Status | Crowdfunding</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e0eafc, #cfdef3);
  min-height: 100vh;
  overflow-x: hidden;
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
  transition: transform 0.3s ease;
  z-index: 1000;
}
.sidebar.hidden {
  transform: translateX(-100%);
}
.sidebar .logo {
  text-align: center;
  font-weight: 700;
  font-size: 20px;
  margin-bottom: 30px;
}
.sidebar .logo i {
  font-size: 22px;
  margin-right: 6px;
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
  transition: margin-left 0.3s ease;
}
.main.fullwidth {
  margin-left: 0;
}
.toggle-btn {
  display: none;
  position: fixed;
  top: 20px;
  left: 20px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 18px;
  z-index: 1100;
}
.toggle-btn:hover {
  background: #1e3a8a;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 35px;
}
h4.title {
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}
.date {
  color: #64748b;
  font-size: 15px;
}
.profile-pic {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #2563eb;
}
.card {
  background: linear-gradient(135deg, #ffffff, #f0f4f8);
  backdrop-filter: blur(5px);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  max-width: 500px;
  margin: 0 auto;
  transition: all 0.3s ease;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}
.message {
  margin-bottom: 15px;
  padding: 10px;
  border-radius: 5px;
}
.message.success {
  background-color: #d4edda;
  color: #155724;
}
.message.error {
  background-color: #f8d7da;
  color: #721c24;
}
@media (max-width: 768px) {
  .toggle-btn {
    display: block;
  }
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar.show {
    transform: translateX(0);
  }
  .main {
    margin-left: 0;
    padding: 80px 20px 40px;
  }
  .header {
    flex-direction: column;
    align-items: flex-start;
  }
  .profile-pic {
    margin-top: 10px;
  }
}
</style>
</head>
<body>

<button class="toggle-btn" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>

<div class="sidebar" id="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main" id="mainContent">
  <div class="header">
    <div>
      <h4 class="title">Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    <img src="https://via.placeholder.com/40" alt="Profile Picture" class="profile-pic">
  </div>

  <div class="card">
    <?php if ($message): ?>
      <div class="message <?= strpos($message, 'success') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <h5>Update Project Status</h5>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Project ID</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($project['id'] ?? '') ?>" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="pending" <?= isset($project['status']) && $project['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= isset($project['status']) && $project['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="rejected" <?= isset($project['status']) && $project['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a href="status.php" class="btn btn-secondary">Back</a>
    </form>
  </div>

  <footer>
    © <?= date("Y") ?> Crowdfunding Platform — Admin Dashboard
  </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>