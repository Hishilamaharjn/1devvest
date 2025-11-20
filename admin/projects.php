<?php  
session_start();
require '../db_connect.php';

// ----------------------------
// Only admin can access
// ----------------------------
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");
$user_id = $_SESSION['user_id'];
$msg = "";

 

// ----------------------------
// Handle status update
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'], $_POST['status'])) {
    $project_id = (int)$_POST['project_id'];
    $new_status = $_POST['status'];
    if (in_array($new_status, ['approved','pending','rejected'])) {
        $stmt = $pdo->prepare("UPDATE projects SET status=? WHERE id=?");
        $stmt->execute([$new_status, $project_id]);
        $msg = "Project status updated successfully!";
    }
}

// ----------------------------
// Fetch all projects
// ----------------------------
$stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------
// Status color helper
// ----------------------------
function statusColor($status){
    return match(strtolower($status)){
        'approved' => '#22c55e',
        'pending' => '#facc15',
        'rejected' => '#ef4444',
        default => '#64748b'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Projects</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; background:#f5f7fa; margin:0; overflow-x:hidden; }
.sidebar { position:fixed; width:250px; height:100vh; background:linear-gradient(180deg,#4f46e5,#2563eb); padding:25px 20px; color:white; display:flex; flex-direction:column; gap:20px; box-shadow:5px 0 15px rgba(0,0,0,0.2); }
.sidebar .logo { font-size:22px; font-weight:700; margin-bottom:30px; text-align:center; }
.sidebar a { color:#fff; text-decoration:none; font-weight:500; padding:12px 15px; border-radius:12px; display:flex; align-items:center; gap:12px; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.2); }
.main { margin-left:270px; padding:40px 50px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:35px; flex-wrap:wrap; }
.header-left h4 { font-weight:700; color:#1e293b; }
.header-left .date { color:#64748b; font-size:14px; }
.profile-pic { width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid #2563eb; transition:0.3s; }
.profile-pic:hover { transform:scale(1.05); }
.table-container { background:#fff; border-radius:12px; padding:20px; box-shadow:0 8px 25px rgba(0,0,0,0.08); }
.table { width:100%; border-collapse:separate; border-spacing:0; }
.table thead { background:#4f46e5; color:white; }
.table th, .table td { padding:12px 15px; vertical-align:middle; }
.table tbody tr:hover { background-color:#f3f6fc; transition:0.2s; }
.status-form select { padding:4px 8px; border-radius:8px; border:1px solid #ccc; font-weight:600; appearance:none; }
.status-form button { padding:4px 10px; border:none; border-radius:8px; background-color:#2563eb; color:white; font-weight:600; cursor:pointer; transition:0.2s; }
.status-form button:hover { background-color:#4f46e5; }
.section-title { font-weight:600; color:#1e293b; margin:25px 0 15px; }
.msg { margin-bottom:15px; padding:10px 15px; border-radius:8px; background-color:#22c55e; color:white; font-weight:600; }
.featured-checkbox { width:18px; height:18px; cursor:pointer; }
@media (max-width:992px) { .sidebar { position:absolute; left:-250px; transition:0.3s; } .sidebar.active { left:0; } .main { margin-left:0; padding:25px; } }

/* Sidebar standardization */
.sidebar { padding:25px 20px !important; display:flex !important; flex-direction:column !important; gap:20px !important; }
.sidebar .logo { text-align:center !important; font-weight:700 !important; font-size:22px !important; margin-bottom:30px !important; }
.sidebar a { padding:12px 15px !important; border-radius:12px !important; display:flex !important; align-items:center !important; gap:12px !important; font-weight:500 !important; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php" class="active"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="admin_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
  <a href="status.php"><i class="fa-solid fa-list-check"></i> Status</a>
  <a href="manage_clients.php"><i class="fa-solid fa-users"></i> Manage Clients</a>
  
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <div class="header-left">
      <h4>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</h4>
      <div class="date"><?= $date ?></div>
    </div>
    
  </div>

  <?php if($msg): ?>
    <div class="msg"><?= $msg ?></div>
  <?php endif; ?>

  <h4 class="section-title"><i class="fa-solid fa-table-list"></i> All Projects</h4>
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Client ID</th>
          <th>Goal Amount</th>
          <th>Status</th>
          <th>Featured</th>
          <th>Date Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if($projects): foreach($projects as $i => $p): 
            $goal_raw = $p['goal_amount'] ?? $p['goal'] ?? 0;
            $goal_display = is_numeric($goal_raw) ? number_format($goal_raw, 2) : htmlspecialchars($goal_raw);
            $status_class = strtolower($p['status'] ?? 'pending');
            $color = statusColor($status_class);
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($p['title'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['client_id'] ?? '-') ?></td>
          <td>Rs.<?= $goal_display ?></td>
          <td>
            <form method="post" class="status-form d-flex gap-1 align-items-center">
              <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
              <select name="status" style="background-color:<?= $color ?>;">
                <option value="approved" <?= $status_class=='approved'?'selected':'' ?> style="background-color:#22c55e;">Approved</option>
                <option value="pending" <?= $status_class=='pending'?'selected':'' ?> style="background-color:#facc15; color:#1f2937;">Pending</option>
                <option value="rejected" <?= $status_class=='rejected'?'selected':'' ?> style="background-color:#ef4444;">Rejected</option>
              </select>
              <button type="submit"><i class="fa-solid fa-floppy-disk"></i> Update</button>
            </form>
          </td>
          <td>
            <form method="POST" action="update_featured.php">
              <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
              <input type="checkbox" name="is_featured" value="1" class="featured-checkbox" <?= !empty($p['is_featured']) ? 'checked' : '' ?> onchange="this.form.submit()">
            </form>
          </td>
          <td><?= isset($p['created_at']) ? date("F d, Y", strtotime($p['created_at'])) : 'N/A' ?></td>
        </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" class="text-center text-muted">No projects found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
