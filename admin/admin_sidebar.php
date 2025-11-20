<?php 
session_start();
require '../db_connect.php';

// ✅ Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");
$user_id = $_SESSION['user_id'];

 

// ✅ Client Stats
$total_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$active_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client' AND status='active'")->fetchColumn();
$inactive_clients = $total_clients - $active_clients;

// --- Client Management Variables ---
$edit_mode = false;
$client_id = '';
$username = '';
$email = '';
$error = '';
$success = '';

// --- Handle Delete Client ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$delete_id]);
    header("Location: manage_clients.php");
    exit;
}

// --- Handle Edit Client ---
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $client_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($client) {
        $username = $client['username'];
        $email = $client['email'];
    } else {
        $error = "Client not found.";
        $edit_mode = false;
    }
}

// --- Handle Add/Update Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } else {
        if (!empty($_POST['client_id'])) {
            // Update client
            $client_id = $_POST['client_id'];
            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=? WHERE id=? AND role='client'");
                $stmt->execute([$username, $email, $password_hashed, $client_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=? WHERE id=? AND role='client'");
                $stmt->execute([$username, $email, $client_id]);
            }
            $success = "Client updated successfully.";
        } else {
            // Add new client
            $password_hashed = password_hash($password ?: '123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'client')");
            $stmt->execute([$username, $email, $password_hashed]);
            $success = "Client added successfully.";
        }
        // Reset form
        $username = '';
        $email = '';
        $password = '';
        $edit_mode = false;
    }
}

// --- Fetch All Clients ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'client' ORDER BY id DESC");
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Clients | Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #f8fafc;
  display: flex;
  margin: 0;
}

/* ✅ Sidebar – Uniform Spacing & Padding */
.sidebar {
  width: 260px;
  background: #1e293b;
  min-height: 100vh;
  position: fixed;
  color: white;
  padding-top: 30px;
  transition: 0.3s;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.sidebar div {
  text-align: center;
  margin-bottom: 25px;
}

.sidebar div img {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 3px solid #2563eb;
  object-fit: cover;
}

.sidebar div h5 {
  margin-top: 10px;
  font-weight: 600;
}

.sidebar a {
  display: flex;
  align-items: center;
  width: 90%;
  color: white;
  text-decoration: none;
  padding: 12px 15px;
  margin: 6px 0;
  border-radius: 10px;
  font-weight: 500;
  transition: 0.3s;
}

.sidebar a i {
  margin-right: 10px;
}

.sidebar a:hover, 
.sidebar a.active {
  background: #2563eb;
}

.content {
  margin-left: 260px;
  padding: 30px;
  width: 100%;
}

/* Stats Cards */
.stat-card {
  flex: 1;
  color: white;
  padding: 15px;
  border-radius: 8px;
  text-align: center;
}
</style>
</head>
<body>

<!-- ✅ Sidebar -->
<div class="sidebar" id="sidebar">
  <div>
    <h5><?= htmlspecialchars($admin_name) ?></h5>
  </div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="admin_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="manage_clients.php" class="active"><i class="fa-solid fa-users"></i> Manage Clients</a>
  <a href="admin_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- ✅ Main Content -->
<div class="content">
    <h2 class="mb-4">Manage Clients</h2>
    <p><strong>Date:</strong> <?= $date ?></p>

    <!-- Stats -->
    <div class="stats d-flex gap-3 mb-4">
        <div class="stat-card" style="background:#2563eb;"><h5>Total Clients</h5><h3><?= $total_clients ?></h3></div>
        <div class="stat-card" style="background:#22c55e;"><h5>Active</h5><h3><?= $active_clients ?></h3></div>
        <div class="stat-card" style="background:#f59e0b;"><h5>Inactive</h5><h3><?= $inactive_clients ?></h3></div>
    </div>

    <!-- Add/Edit Client Form -->
    <h4><?= $edit_mode ? 'Edit Client' : 'Add New Client' ?></h4>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST" action="manage_clients.php" class="mb-4">
        <input type="hidden" name="client_id" value="<?= htmlspecialchars($client_id) ?>">
        <div class="mb-3">
          <label>Username</label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
        </div>
        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="mb-3">
          <label>Password (leave blank to keep unchanged)</label>
          <input type="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Update Client' : 'Add Client' ?></button>
        <?php if($edit_mode): ?><a href="manage_clients.php" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
    </form>

    <!-- Clients Table -->
    <table class="table table-bordered bg-white">
        <thead class="table-dark">
            <tr><th>#</th><th>Username</th><th>Email</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if($clients): foreach($clients as $i=>$c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($c['username']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td>
                    <a href="manage_clients.php?edit_id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="manage_clients.php?delete_id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this client?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" class="text-center">No clients found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
