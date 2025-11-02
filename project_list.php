<?php
session_start();
require 'db_connect.php'; // expects $conn (MySQLi)

// Access control: allow only client or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['client', 'admin'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'];
$success = $_GET['success'] ?? '';

// Fetch projects
if ($role === 'admin') {
    // Admin: fetch all projects with client username
    $sql = "SELECT p.*, u.username AS client_username
            FROM projects p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.id DESC";
    $result = $conn->query($sql);
    $projects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} else {
    // Client: fetch only their projects (prepared)
    $stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $projects = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Helper to map status to bootstrap badge classes
function status_class($status) {
    $map = [
        'pending' => 'badge bg-warning text-dark',
        'approved' => 'badge bg-success text-white',
        'rejected' => 'badge bg-danger text-white',
    ];
    return $map[$status] ?? 'badge bg-secondary text-white';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= $role === 'admin' ? 'Admin - Projects' : 'My Projects' ?> | Crowdfunding</title>

<!-- Bootstrap & FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* Simple admin-panel-like layout */
:root {
    --sidebar-width: 220px;
    --brand-color: #0d6efd;
    --bg: #eef2f7;
}
body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    margin: 0;
    padding: 0;
}
.app {
    display: flex;
    min-height: 100vh;
}
/* Sidebar */
.sidebar {
    width: var(--sidebar-width);
    background: #0b3b66; /* dark blue */
    color: #fff;
    padding: 20px;
    flex-shrink: 0;
}
.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}
.brand img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }
.brand h4 { margin: 0; font-size: 1.05rem; font-weight: 600; }
.nav-links { margin-top: 10px; }
.nav-links a {
    display: block;
    color: #e6f0ff;
    padding: 10px 12px;
    border-radius: 6px;
    margin-bottom: 6px;
    text-decoration: none;
}
.nav-links a.active, .nav-links a:hover { background: rgba(255,255,255,0.08); color: #fff; }

/* Main content */
.main {
    flex: 1;
    padding: 28px;
}
.header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom: 18px;
}
.header .welcome { font-size:1.15rem; }
.card {
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(15,39,60,0.06);
}

/* Table small tweaks */
.table thead th { vertical-align: middle; }
.table td .btn { margin-right: 6px; }

/* Responsive adjustments */
@media (max-width: 900px) {
    .sidebar { display: none; }
    .app { flex-direction: column; }
    .main { padding: 14px; }
}
</style>
</head>
<body>
<div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <!-- Replace src with your logo path -->
            <img src="assets/logo.png" alt="Logo" onerror="this.src='https://via.placeholder.com/40'">
            <h4>Crowdfund</h4>
        </div>

        <div class="nav-links">
            <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            <a href="project_list.php" class="active"><i class="fa-solid fa-briefcase"></i> Projects</a>
            <?php if($role === 'client'): ?>
                <a href="create_project.php"><i class="fa-solid fa-plus"></i> Create Project</a>
            <?php else: ?>
                <a href="create_project.php"><i class="fa-solid fa-plus"></i> Create Project</a>
            <?php endif; ?>
            <a href="logout.php" class="mt-2"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>

        <hr style="border-color: rgba(255,255,255,0.06); margin: 18px 0;">
        <div>
            <small>Signed in as</small>
            <div style="font-weight:600; margin-top:6px;"><?= htmlspecialchars($username) ?></div>
            <div style="font-size:0.85rem; opacity:0.9; margin-top:4px; text-transform:capitalize"><?= htmlspecialchars($role) ?></div>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <div class="header">
            <div class="welcome">
                <h3 class="mb-0"><?= $role === 'admin' ? 'All Projects (Admin)' : 'My Projects' ?></h3>
                <small class="text-muted">Manage your projects from here</small>
            </div>

            <div>
                <?php if($role === 'client'): ?>
                    <a href="create_project.php" class="btn btn-success">
                        <i class="fa-solid fa-plus"></i> Create Project
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card p-3">
            <?php if(empty($projects)): ?>
                <div class="p-4 text-center">
                    <h5 class="mb-2">No projects found</h5>
                    <?php if($role === 'client'): ?>
                        <p>Create your first project to start raising funds.</p>
                        <a href="create_project.php" class="btn btn-primary">Create Project</a>
                    <?php else: ?>
                        <p>There are no projects yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <?php if($role === 'admin'): ?><th>Client</th><?php endif; ?>
                                <th>Status</th>
                                <th>Goal (Rs.)</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th style="min-width:160px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($projects as $p): ?>
                                <tr>
                                    <td><?= (int)$p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['title']) ?></td>
                                    <?php if($role === 'admin'): ?>
                                        <td><?= htmlspecialchars($p['client_username'] ?? '—') ?></td>
                                    <?php endif; ?>
                                    <td><span class="<?= status_class($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                                    <td><?= number_format((float)$p['goal'], 2) ?></td>
                                    <td><?= htmlspecialchars($p['start_date']) ?></td>
                                    <td><?= htmlspecialchars($p['end_date']) ?></td>
                                    <td>
                                        <a href="view_project.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
                                        <?php if($role === 'admin'): ?>
                                            <a href="update_status.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fa-solid fa-pen"></i> Update
                                            </a>
                                            <a href="delete_project.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this project?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <a href="edit_project.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
