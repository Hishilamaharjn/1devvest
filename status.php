<?php   
session_start();
require 'db_connect.php';

// ✅ Session check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");

// ✅ Profile image fetch
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Check file existence
if (!empty($user['profile_pic'])) {
    $profile_path = "uploads/" . basename($user['profile_pic']);
    $profile_pic = (file_exists($profile_path)) ? $profile_path : "uploads/default.png";
} else {
    $profile_pic = "uploads/default.png";
}

// ✅ Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

try {
    $query = "SELECT id, client_name, project_name, status FROM projects WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (LOWER(client_name) LIKE ? OR LOWER(project_name) LIKE ?)";
        $params[] = "%" . strtolower($search) . "%";
        $params[] = "%" . strtolower($search) . "%";
    }

    if ($filter_status !== 'all' && !empty($filter_status)) {
        $query .= " AND LOWER(status) = ?";
        $params[] = strtolower($filter_status);
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $filtered_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Status | Crowdfunding</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e0eafc, #cfdef3);
  min-height: 100vh;
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
  display: flex;
  align-items: center;
  gap: 12px;
  transition: 0.3s;
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
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}
.profile-pic {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #2563eb;
}
.filters {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  margin-bottom: 25px;
}
.filters input, .filters select {
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid #ccc;
  font-size: 14px;
}
.status-table {
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  padding: 20px;
}
.status-table th {
  background: #2563eb;
  color: #fff;
  padding: 12px;
  text-align: left;
}
.status-table td {
  padding: 12px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}
.status-pending { color: #f59e0b; font-weight: 600; }
.status-approved { color: #10b981; font-weight: 600; }
.status-rejected { color: #ef4444; font-weight: 600; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="status.php" class="active"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="profile.php?role=admin"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <div>
      <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
      <small><?= $date ?></small>
    </div>
    <img src="<?= htmlspecialchars($profile_pic) ?>" class="profile-pic" alt="Profile">
  </div>

  <form method="GET" class="filters">
    <input type="text" name="search" placeholder="Search by Client or Project..." value="<?= htmlspecialchars($search) ?>">
    <select name="filter_status">
      <option value="all" <?= $filter_status==='all'?'selected':'' ?>>All Statuses</option>
      <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
      <option value="approved" <?= $filter_status==='approved'?'selected':'' ?>>Approved</option>
      <option value="rejected" <?= $filter_status==='rejected'?'selected':'' ?>>Rejected</option>
    </select>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>

  <div class="status-table">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Client Name</th>
          <th>Project Name</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filtered_projects)): ?>
          <tr><td colspan="4" class="text-center text-muted">No projects found.</td></tr>
        <?php else: ?>
          <?php foreach ($filtered_projects as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td><?= htmlspecialchars($p['client_name']) ?></td>
            <td><?= htmlspecialchars($p['project_name']) ?></td>
            <td class="status-<?= strtolower($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
