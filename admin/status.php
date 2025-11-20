<?php      
session_start();
require '../db_connect.php';

// ✅ Only admin allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");

// ✅ User context
$user_id = $_SESSION['user_id'];

// ✅ Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

// ✅ Fetch filtered projects
try {
    $query = "SELECT p.id, p.title, p.status, u.username AS client_name
              FROM projects p
              JOIN users u ON p.user_id = u.id
              WHERE 1=1";
    $params = [];

    // ✅ Search filter
    if (!empty($search)) {
        $query .= " AND (LOWER(u.username) LIKE ? OR LOWER(p.title) LIKE ?)";
        $params[] = "%" . strtolower($search) . "%";
        $params[] = "%" . strtolower($search) . "%";
    }

    // ✅ Status filter (only if not 'all')
    if ($filter_status !== 'all') {
        $query .= " AND LOWER(p.status) = ?";
        $params[] = strtolower($filter_status);
    }

    $query .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Project Status</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #e0eafc, #cfdef3); min-height: 100vh; margin: 0; }
.sidebar { position: fixed; width: 260px; height: 100vh; background: linear-gradient(200deg, #2563eb, #4f46e5); color: #fff; padding: 25px 20px; display: flex; flex-direction: column; gap: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.15); }
.sidebar .logo { text-align: center; font-weight: 700; font-size: 20px; margin-bottom: 30px; }
.sidebar a { color: #fff; text-decoration: none; padding: 12px 15px; border-radius: 10px; display: flex; align-items: center; gap: 12px; transition: 0.3s; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.15); transform: translateX(5px); }
.main { margin-left: 280px; padding: 40px 50px; transition: margin-left 0.3s ease; }
.header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 30px; }
.header-left { display: flex; flex-direction: column; }
.header-left h4 { font-weight: 700; margin: 0; }
.header-left small { color: #64748b; }
.profile-pic { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #2563eb; }

.card-white { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.table th { background: #2563eb; color: #fff; }
.table td { vertical-align: middle; }
.badge { text-transform: capitalize; padding: 5px 10px; border-radius: 10px; font-size: 0.9em; }

.filters { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.filters input, .filters select { padding: 8px 12px; border-radius: 10px; border: 1px solid #ccc; }

@media (max-width: 768px) {
    .main { margin-left: 0; padding: 20px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
    <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
    <a href="admin_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
    <a href="status.php" class="active"><i class="fa-solid fa-list-check"></i> Status</a>
    <a href="manage_clients.php"><i class="fa-solid fa-users"></i> Manage Clients</a>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <div class="header">
        <div class="header-left">
            <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
            <small><?= $date ?></small>
        </div>
        
    </div>

    <div class="card-white">
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

        <table class="table table-striped align-middle mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project Title</th>
                    <th>Client</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($projects)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No projects found.</td></tr>
                <?php else: ?>
                    <?php foreach($projects as $p): 
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
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
