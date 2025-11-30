<?php  
session_start();
require '../db_connect.php';

// Only admin allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Client Stats
$total_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$active_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client' AND status='active'")->fetchColumn();
$inactive_clients = $total_clients - $active_clients;

// Variables
$edit_mode = false;
$client_id = '';
$username = '';
$email = '';

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ? AND role='client'")->execute([$id]);
    $_SESSION['success'] = "Status updated!";
    header("Location: manage_clients.php");
    exit;
}

// Permanent Delete
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'client'")->execute([$id]);
    $_SESSION['success'] = "Client deleted permanently.";
    header("Location: manage_clients.php");
    exit;
}

// Edit Mode
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $client_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    if ($client) {
        $username = $client['username'];
        $email = $client['email'];
    } else {
        $_SESSION['error'] = "Client not found.";
        header("Location: manage_clients.php");
        exit;
    }
}

// Add / Update Client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : 0;

    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check duplicate email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND role='client'");
        $check->execute([$email, $client_id]);
        if ($check->rowCount() > 0) {
            $error = "This email is already registered.";
        } else {
            if ($client_id) {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET username=?, email=?, password=? WHERE id=?")->execute([$username, $email, $hash, $client_id]);
                } else {
                    $pdo->prepare("UPDATE users SET username=?, email=? WHERE id=?")->execute([$username, $email, $client_id]);
                }
                $_SESSION['success'] = "Client updated successfully!";
            } else {
                // Add new
                $default_pass = empty($password) ? '123456' : $password;
                $hash = password_hash($default_pass, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'client', 'active')")
                     ->execute([$username, $email, $hash]);
                $_SESSION['success'] = "Client added! Password: <strong>$default_pass</strong>";
            }
            $username = $email = '';
            $edit_mode = false;
        }
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM users WHERE role='client'";
if ($search) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
}
$sql .= " ORDER BY id DESC";

if ($search) {
    $stmt = $pdo->prepare($sql);
    $like = "%$search%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query($sql);
}
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Manage Clients</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background:#f5f7fa; min-height:100vh; margin:0; overflow-x:hidden; }
.sidebar { position: fixed; width: 260px; height: 100vh; background: linear-gradient(180deg,#4f46e5,#2563eb); color:#fff; padding:25px 20px; display:flex; flex-direction:column; gap:20px; box-shadow:5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo { text-align:center; font-weight:700; font-size:22px; margin-bottom:30px; color:#fff; letter-spacing:1px;}
.sidebar a { color:#fff; text-decoration:none; padding:12px 15px; border-radius:12px; display:flex; align-items:center; gap:12px; font-weight:500; transition:0.3s;}
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.2); transform: translateX(5px);}
.main { margin-left:280px; padding:40px 50px;}
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:35px; flex-wrap:wrap;}
.header-left h4 { font-weight:700; color:#1e293b; margin:0; }
.header-left .date { color:#64748b; font-size:14px;}
.stats { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:25px; margin-bottom:40px;}
.stat-card { border-radius:15px; padding:20px; text-align:center; color:#fff; box-shadow:0 8px 25px rgba(0,0,0,0.1);}
.stat-card i { font-size:36px; margin-bottom:12px;}
.stat-card h5 { font-weight:600; font-size:16px; margin-bottom:5px;}
.stat-card h3 { font-weight:700; font-size:28px;}
.stat-card:nth-child(1){ background: linear-gradient(135deg,#2563eb,#4f46e5);}
.stat-card:nth-child(2){ background: linear-gradient(135deg,#22c55e,#16a34a);}
.stat-card:nth-child(3){ background: linear-gradient(135deg,#facc15,#f59e0b);}
.table {background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
.table thead {background:#4f46e5;color:white;}
.table tbody tr:hover {background-color:#f3f6fc;}
.section-title {font-weight:600;color:#1e293b;margin-top:30px;margin-bottom:20px;}
.card {background:#fff;border-radius:15px; padding:20px; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
.toast-container { position:fixed; bottom:20px; right:20px; z-index:9999; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="admin_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="manage_clients.php" class="active"><i class="fa-solid fa-users"></i> Manage Clients</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <div class="header-left">
      <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
  </div>

  <!-- Toast Notifications -->
  <div class="toast-container">
    <?php if($success): ?>
      <div class="toast show align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body"><?= $success ?></div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="toast show align-items-center text-bg-danger border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body"><?= htmlspecialchars($error) ?></div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Stats Cards -->
  <div class="stats">
    <div class="stat-card"><i class="fa-solid fa-users"></i><h5>Total Clients</h5><h3><?= $total_clients ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-user-check"></i><h5>Active Clients</h5><h3><?= $active_clients ?></h3></div>
    <div class="stat-card"><i class="fa-solid fa-user-slash"></i><h5>Inactive Clients</h5><h3><?= $inactive_clients ?></h3></div>
  </div>

  <!-- Search -->
  <form class="mb-4">
    <div class="input-group" style="max-width:400px;">
      <input type="text" name="search" class="form-control" placeholder="Search by username or email..." value="<?= htmlspecialchars($search) ?>">
      <button class="btn btn-primary"><i class="fa-solid fa-search"></i></button>
    </div>
  </form>

  <!-- Add/Edit Form -->
  <h4 class="section-title"><?= $edit_mode ? 'Edit Client' : 'Add New Client' ?></h4>
  <div class="card mb-4">
    <form method="POST">
      <input type="hidden" name="client_id" value="<?= $edit_mode ? $client_id : '' ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label>Username</label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
        </div>
        <div class="col-md-4">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="col-md-4">
          <label>Password <small class="text-muted">(leave blank = <?= $edit_mode?'keep current':'123456' ?>)</small></label>
          <input type="password" name="password" class="form-control">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Update Client' : 'Add Client' ?></button>
        <?php if($edit_mode): ?><a href="manage_clients.php" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Clients Table -->
  <h4 class="section-title">All Clients</h4>
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead>
        <tr><th>#</th><th>Username</th><th>Email</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $i => $c): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($c['username']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td>
            <a href="?toggle=<?= $c['id'] ?>" class="badge bg-<?= $c['status']==='active'?'success':'danger' ?>">
              <?= ucfirst($c['status'] ?? 'active') ?>
            </a>
          </td>
          <td>
            <a href="?edit_id=<?= $c['id'] ?>" class="btn btn-sm btn-warning"><i class="fa-solid fa-pen"></i></a>
            <a href="?delete_id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this client permanently?')"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($clients)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No clients found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Auto hide toasts after 4 seconds
  document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t, {delay: 4000}).show());
</script>
</body>
</html>