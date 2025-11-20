<?php
session_start();
require '../db_connect.php';

// ------------------------
// Ensure investor is logged in
// ------------------------
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'investor') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$investor_name = $_SESSION['username'] ?? 'Investor';
$date = date("l, F d, Y");

// ------------------------
// Resolve current investor_id
// ------------------------
$stmt = $pdo->prepare("SELECT investor_id FROM investor WHERE user_id = ?");
$stmt->execute([$user_id]);
$inv_row = $stmt->fetch(PDO::FETCH_ASSOC);
$investor_id = $inv_row['investor_id'] ?? null;

// ------------------------
// Fetch investments per project
// ------------------------
$investments = [];
if ($investor_id) {
    $stmt = $pdo->prepare("
        SELECT 
            ip.project_id,
            COALESCE(p.project_name, p.title, 'Untitled Project') AS project_name,
            COALESCE(u.username, CONCAT('Client #', p.client_id), 'Unknown Client') AS client_name,
            SUM(ip.invested_amount) AS total_invested,
            MAX(ip.invested_at) AS last_invested_at,
            MAX(ip.status) AS status
        FROM investor_projects ip
        LEFT JOIN projects p ON ip.project_id = p.id
        LEFT JOIN users u ON p.client_id = u.id
        WHERE ip.investor_id = ?
        GROUP BY ip.project_id
        ORDER BY last_invested_at DESC
    ");
    $stmt->execute([$investor_id]);
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ------------------------
// Prepare data for charts
// ------------------------
$status_counts = ["Pending" => 0, "Approved" => 0, "Completed" => 0];
$project_amounts = [];
$investments_view = [];

foreach ($investments as $inv) {
    $status_norm = ucfirst(strtolower($inv['status'] ?? 'Pending'));
    if (!isset($status_counts[$status_norm])) $status_norm = 'Pending';
    $status_counts[$status_norm]++;

    $pname = $inv['project_name'] ?? 'Untitled Project';
    $project_amounts[$pname] = (float)($inv['total_invested'] ?? 0);

    $status_class = ($status_norm === 'Approved') ? 'badge-approved' : (($status_norm === 'Completed') ? 'badge-completed' : 'badge-pending');

    $inv['display_status'] = $status_norm;
    $inv['status_class'] = $status_class;
    $investments_view[] = $inv;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Investments | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f5f7fa;margin:0;overflow-x:hidden;}
.sidebar{position:fixed;width:250px;height:100vh;background:linear-gradient(180deg,#4f46e5,#2563eb);color:#fff;padding:25px 20px;display:flex;flex-direction:column;gap:20px;box-shadow:5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo{text-align:center;font-weight:700;font-size:22px;margin-bottom:30px;}
.sidebar a{color:#fff;text-decoration:none;font-weight:500;padding:12px 15px;border-radius:12px;display:flex;align-items:center;gap:12px;transition:0.3s;}
.sidebar a.active, .sidebar a:hover{background: rgba(255,255,255,0.2);}
.main{margin-left:270px;padding:40px 50px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;}
.header-left h4{font-weight:700;color:#1e293b;}
.header-left .date{color:#64748b;font-size:14px;}
.table-container{background:#fff;border-radius:15px;box-shadow:0 8px 20px rgba(0,0,0,0.1);padding:20px;overflow-x:auto;}
th{background:linear-gradient(90deg,#6366f1,#4f46e5);color:#fff;padding:10px;border:none;text-align:left;font-weight:600;}
td{padding:10px;border-bottom:1px solid #e5e7eb;color:#374151;vertical-align:middle;}
tr:hover{background:#f1f5ff;}
.badge-status{padding:5px 10px;border-radius:6px;font-size:13px;}
.badge-pending{background:#facc15;color:#000;}
.badge-approved{background:#4ade80;color:#fff;}
.badge-completed{background:#3b82f6;color:#fff;}
.chart-container{display:flex;flex-wrap:wrap;gap:20px;margin-top:20px;justify-content:flex-start;}
.chart-box{flex:1;min-width:180px;max-width:300px;background:#fff;padding:15px;border-radius:12px;box-shadow:0 6px 15px rgba(0,0,0,0.08);}
footer{text-align:center;color:#64748b;margin-top:30px;font-size:14px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="investor_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php"><i class="fa-solid fa-folder-open"></i> Browse Projects</a>
  <a href="investment_history.php" class="active"><i class="fa-solid fa-coins"></i> My Investments</a>
  <a href="investor_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <div class="header-left">
      <h4>Welcome, <?=htmlspecialchars($investor_name)?> 👋</h4>
      <div class="date"><?=$date?></div>
    </div>
  </div>

  <h4>My Investments 💼</h4>
  <div class="table-container mt-3">
    <?php if($investments_view): ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Project</th>
          <th>Client</th>
          <th>Amount (Rs.)</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($investments_view as $i=>$inv): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><?=htmlspecialchars($inv['project_name'])?></td>
          <td><?=htmlspecialchars($inv['client_name'])?></td>
          <td><strong><?=number_format($inv['total_invested'],2)?></strong></td>
          <td><span class="badge-status <?=htmlspecialchars($inv['status_class'])?>"><?=htmlspecialchars($inv['display_status'])?></span></td>
          <td><?= $inv['last_invested_at'] ? date("F d, Y - h:i A", strtotime($inv['last_invested_at'])) : '-' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p>You haven’t invested in any project yet.</p>
    <?php endif; ?>
  </div>

  <!-- Compact Pie Charts -->
  <div class="chart-container">
      <div class="chart-box">
          <h6>Status Overview</h6>
          <canvas id="statusPie" style="height:140px;"></canvas>
      </div>
      <div class="chart-box">
          <h6>Amount by Project</h6>
          <canvas id="amountPie" style="height:140px;"></canvas>
      </div>
  </div>

  <footer>© <?= date("Y") ?> DevVest</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Pie Chart 1: Status
new Chart(document.getElementById("statusPie"), {
    type: "pie",
    data: {
        labels: ["Pending","Approved","Completed"],
        datasets:[{data:[<?= $status_counts["Pending"] ?>,<?= $status_counts["Approved"] ?>,<?= $status_counts["Completed"] ?>],
            backgroundColor:["#facc15","#4ade80","#3b82f6"]
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{position:'bottom',labels:{boxWidth:12}},tooltip:{enabled:true}}
    }
});

// Pie Chart 2: Amount per Project
const projectColors = ["#6366f1","#10b981","#f59e0b","#ef4444","#3b82f6","#8b5cf6","#f43f5e","#0ea5e9","#eab308","#14b8a6"];
new Chart(document.getElementById("amountPie"),{
    type:"pie",
    data:{labels:<?= json_encode(array_keys($project_amounts)) ?>,datasets:[{data:<?= json_encode(array_values($project_amounts)) ?>,backgroundColor:projectColors}]},
    options:{
        responsive:true,
        plugins:{
            legend:{position:'bottom',labels:{boxWidth:12}},
            tooltip:{
                callbacks:{
                    label:function(context){
                        let label = context.label || '';
                        let value = context.raw || 0;
                        return label + ': Rs. ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
