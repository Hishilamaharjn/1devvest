<?php 
session_start();
require '../db_connect.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$date = date("l, F d, Y");

// Fetch all investments with investor names
$stmt = $pdo->query("
    SELECT 
        ip.id AS investment_id,
        COALESCE(ip.invested_amount,0) AS amount,
        ip.project_id,
        ip.investor_id,
        ip.invested_at,
        ip.status,
        p.title AS project_title,
        COALESCE(u.username, 'Anonymous') AS investor_name
    FROM investor_projects ip
    LEFT JOIN projects p ON ip.project_id = p.id
    LEFT JOIN investor i ON ip.investor_id = i.investor_id
    LEFT JOIN users u ON i.user_id = u.id
    ORDER BY ip.id DESC
");
$investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatCurrency($amount) {
    return is_numeric($amount) ? 'Rs.' . number_format($amount, 2) : htmlspecialchars($amount);
}

function statusColor($status){
    return match(strtolower($status)){
        'approved' => '#16a34a',
        'pending' => '#f59e0b',
        'rejected' => '#dc2626',
        default => '#94a3b8'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Investments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #f8fafc; margin:0; overflow-x:hidden; }
.sidebar { position: fixed; width: 250px; height: 100vh; background: linear-gradient(180deg, #4f46e5, #2563eb); padding: 25px 20px; color: white; display: flex; flex-direction: column; gap: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.2); }
.sidebar .logo { font-size: 22px; font-weight: 700; margin-bottom: 30px; text-align: center; }
.sidebar a { color:#fff; text-decoration:none; font-weight:500; padding:12px 15px; border-radius:12px; display:flex; align-items:center; gap:12px; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
.main { margin-left:270px; padding:40px 50px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:35px; flex-wrap:wrap; }
.header-left h4 { font-weight:700; color:#1e293b; }
.header-left .date { color:#64748b; font-size:14px; }
.table-container { background:#fff; border-radius:15px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.08); }
.table th, .table td { text-align:center; padding:10px; }
.status-dropdown { font-weight:600; color:white; border:none; padding:6px 10px; border-radius:8px; cursor:pointer; }
button.update-btn { padding:4px 10px; border:none; border-radius:6px; background:#2563eb; color:white; cursor:pointer; transition:0.2s; }
button.update-btn:hover { background:#4f46e5; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="projects.php"><i class="fa-solid fa-folder-open"></i> Projects</a>
  <a href="admin_investments.php" class="active"><i class="fa-solid fa-coins"></i> Investments</a>
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

  <h4 class="section-title"><i class="fa-solid fa-coins text-primary"></i> All Investments</h4>
  <p class="text-muted mb-3">Total Investments: <?= count($investments) ?></p>

  <div class="table-container">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Investor</th>
          <th>Project</th>
          <th>Amount</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if($investments): foreach($investments as $i => $inv): 
            $status_class = strtolower($inv['status'] ?? 'pending');
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($inv['investor_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($inv['project_title'] ?? '-') ?></td>
          <td><?= formatCurrency($inv['amount']) ?></td>
          <td><?= $inv['invested_at'] ? date("M d, Y", strtotime($inv['invested_at'])) : '-' ?></td>
          <td>
            <select class="status-dropdown" data-id="<?= $inv['investment_id'] ?>" style="background-color: <?= statusColor($status_class) ?>">
              <option value="approved" <?= $status_class=='approved'?'selected':'' ?>>Approved</option>
              <option value="pending" <?= $status_class=='pending'?'selected':'' ?>>Pending</option>
              <option value="rejected" <?= $status_class=='rejected'?'selected':'' ?>>Rejected</option>
            </select>
          </td>
          <td>
            <button class="update-btn" data-id="<?= $inv['investment_id'] ?>">Update</button>
          </td>
        </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" class="text-center text-muted">No investments found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function(){

  function getStatusColor(status){
    switch(status){
      case 'approved': return '#16a34a';
      case 'pending': return '#f59e0b';
      case 'rejected': return '#dc2626';
      default: return '#94a3b8';
    }
  }

  $('.update-btn').click(function(){
    let row = $(this).closest('tr');
    let investmentId = $(this).data('id');
    let select = row.find('select.status-dropdown');
    let newStatus = select.val();

    $.post('update_investments_status.php', {investment_id: investmentId, status: newStatus}, function(response){
        // Success/failure messages removed
        select.css('background-color', getStatusColor(newStatus));
    });
  });

  $('select.status-dropdown').each(function(){
    $(this).css('background-color', getStatusColor($(this).val()));
  });
});
</script>
</body>
</html>
