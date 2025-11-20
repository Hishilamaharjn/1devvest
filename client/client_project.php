<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
    header("Location: ../login.php");
    exit;
}

$client_name = $_SESSION['username'] ?? 'Client';
$date = date("l, F d, Y");
$user_id = $_SESSION['user_id'];

// === AJAX Delete Request ===
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['project_id'])) {
    header('Content-Type: application/json');
    $project_id = (int)$_POST['project_id'];

    $stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        $deleteStmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        if ($deleteStmt->execute([$project_id, $user_id])) {
            if (!empty($project['image']) && $project['image'] !== 'images/default_project.png' && file_exists(__DIR__ . '/' . $project['image'])) {
                @unlink(__DIR__ . '/' . $project['image']);
            }
            echo json_encode(['success' => true, 'message' => 'Project deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete project.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Project not found.']);
    }
    exit;
}

// === AJAX Edit Save ===
if (isset($_POST['action']) && $_POST['action'] === 'edit_save') {
    header('Content-Type: application/json');
    $id = (int)$_POST['project_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $goal = (float)($_POST['goal'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $category = trim($_POST['category'] ?? '');

    if ($goal < 10000 || $goal > 1000000) {
        echo json_encode(['success' => false, 'message' => 'Goal must be between Rs.10,000 and Rs.10,00,000']);
        exit;
    }

    // Handle image
    $image_path = $_POST['current_image'];
    if (!empty($_FILES['new_image']['name'])) {
        $target_dir = __DIR__ . "/images/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $image_name = time() . "_" . basename($_FILES["new_image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["new_image"]["tmp_name"], $target_file)) {
            if ($image_path !== 'images/default_project.png' && file_exists(__DIR__ . '/' . $image_path)) {
                @unlink(__DIR__ . '/' . $image_path);
            }
            $image_path = 'images/' . $image_name;
        }
    }

    $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, goal=?, start_date=?, end_date=?, category=?, image=? WHERE id=? AND user_id=?");
    if ($stmt->execute([$title, $description, $goal, $start_date, $end_date, $category, $image_path, $id, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Project updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update project.']);
    }
    exit;
}

// === Fetch Projects ===
$stmt = $pdo->prepare("
    SELECT p.*, IFNULL(SUM(ip.invested_amount),0) AS total_funded
    FROM projects p
    LEFT JOIN investor_projects ip ON p.id = ip.project_id AND ip.status='approved'
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.id DESC
");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Load Project for Edit Modal ===
if (isset($_GET['edit_id'])) {
    $pid = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=? AND user_id=?");
    $stmt->execute([$pid, $user_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p) {
        echo json_encode($p);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// === Modal Project Detail (View) ===
function getProjectDetail($pdo, $project_id, $user_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, IFNULL(SUM(ip.invested_amount),0) AS total_funded
        FROM projects p
        LEFT JOIN investor_projects ip ON p.id = ip.project_id AND ip.status='approved'
        WHERE p.id=? AND p.user_id=?
        GROUP BY p.id
    ");
    $stmt->execute([$project_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['project_id'])) {
    $pid = (int)$_GET['project_id'];
    $project = getProjectDetail($pdo, $pid, $user_id);
    if (!$project) {
        echo "<p class='text-danger text-center'>Project not found or access denied.</p>";
        exit;
    }

    $goal = (float)($project['goal'] ?? 0);
    $funded = (float)($project['total_funded'] ?? 0);
    $progress = $goal > 0 ? min(100, round(($funded/$goal)*100)) : 0;
    $proj_img = !empty($project['image']) && file_exists(__DIR__ . '/' . $project['image']) 
                ? $project['image'] 
                : 'images/default_project.png';
    ?>
    <div class="text-center mb-4">
        <img src="<?= htmlspecialchars($proj_img) ?>" class="img-fluid rounded shadow" style="max-height:320px; object-fit:cover;">
    </div>
    <h4 class="fw-bold text-primary"><?= htmlspecialchars($project['title']) ?></h4>
    <p class="text-muted"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    <hr>
    <div class="row text-center">
        <div class="col-6"><strong>Goal</strong><br>Rs.<?= number_format($goal) ?></div>
        <div class="col-6"><strong>Raised</strong><br>Rs.<?= number_format($funded) ?> (<?= $progress ?>%)</div>
    </div>
    <div class="progress my-3" style="height:12px;">
        <div class="progress-bar bg-success" style="width:<?= $progress ?>%;"></div>
    </div>
    <p><strong>Category:</strong> <?= htmlspecialchars($project['category'] ?? 'N/A') ?></p>
    <p><strong>Duration:</strong> <?= htmlspecialchars($project['start_date']) ?> → <?= htmlspecialchars($project['end_date']) ?></p>
    <p class="mt-2"><span class="badge bg-<?= $project['status']=='approved'?'success':($project['status']=='pending'?'warning':'danger') ?>"><?= ucfirst($project['status']) ?></span></p>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Projects | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; background:#f5f7fa; margin:0; }
.sidebar { position:fixed; width:260px; height:100vh; background: linear-gradient(180deg,#4f46e5,#2563eb); color:#fff; padding:25px 20px; display:flex; flex-direction:column; gap:20px; z-index:1500; box-shadow:5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo { text-align:center; font-weight:700; font-size:22px; margin-bottom:30px; cursor:pointer; }
.sidebar a { color:#fff; text-decoration:none; padding:12px 15px; border-radius:12px; display:flex; align-items:center; gap:12px; transition:0.3s; }
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.2); transform:translateX(5px); }
.main { margin-left:280px; padding:40px 50px; }
.card { border-radius:18px; overflow:hidden; transition:0.3s; }
.card:hover { transform:translateY(-8px); box-shadow:0 15px 30px rgba(0,0,0,0.15)!important; }
.card-img-top { height:180px; object-fit:cover; }
.progress { height:8px; border-radius:10px; background:#e2e8f0; }
.progress-bar { background:#22c55e; }
.btn-delete { background:#ef4444; border:none; color:white; }
.btn-delete:hover { background:#dc2626; }
.btn-edit { background:#3b82f6; color:white; }
.btn-edit:hover { background:#2563eb; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo" onclick="location.href='client_dashboard.php'">
    <i class="fa-solid fa-lightbulb"></i> DevVest
  </div>
  <a href="client_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php"><i class="fa-solid fa-plus"></i> Create Project</a>
  <a href="client_project.php" class="active"><i class="fa-solid fa-folder-open"></i> My Projects</a>
  <a href="client_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
  <a href="client_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h4 class="fw-bold text-dark">Welcome back, <?= htmlspecialchars($client_name) ?> 👋</h4>
      <div class="text-muted"><i class="fa-regular fa-calendar"></i> <?= $date ?></div>
    </div>
    <a href="create_project.php" class="btn btn-primary shadow-sm">
      <i class="fa-solid fa-plus"></i> Create New Project
    </a>
  </div>

  <h3 class="mb-4 fw-bold text-dark"><i class="fa-solid fa-folder-open text-primary"></i> My Projects</h3>

  <?php if(empty($projects)): ?>
    <div class="text-center py-5">
      <i class="fa-solid fa-folder-open fa-5x text-muted mb-3"></i>
      <h5>No projects created yet!</h5>
      <a href="create_project.php" class="btn btn-primary mt-3">Create Your First Project</a>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($projects as $project):
        $goal = (float)($project['goal'] ?? 0);
        $funded = (float)($project['total_funded'] ?? 0);
        $progress = $goal>0 ? min(100, round(($funded/$goal)*100)) : 0;
        $proj_img = !empty($project['image']) && file_exists(__DIR__ . '/' . $project['image']) 
                    ? $project['image'] 
                    : 'images/default_project.png';
        $status_color = $project['status']=='approved'?'success':($project['status']=='pending'?'warning':'danger');
      ?>
        <div class="col-lg-4 col-md-6 mb-4 project-card" id="project-<?= $project['id'] ?>">
          <div class="card h-100 shadow-sm border-0">
            <img src="<?= htmlspecialchars($proj_img) ?>" class="card-img-top" alt="Project Image">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title fw-bold"><?= htmlspecialchars($project['title']) ?></h5>
              <p class="text-muted small flex-grow-1"><?= mb_substr(htmlspecialchars($project['description']),0,100) ?>...</p>

              <div class="progress my-3">
                <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted"><?= $progress ?>% Funded</small>
                <small class="fw-bold">Rs.<?= number_format($funded) ?> / <?= number_format($goal) ?></small>
              </div>

              <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-<?= $status_color ?>"><i class="fa-solid fa-circle-dot"></i> <?= ucfirst($project['status']) ?></span>
                <div>
                  <button class="btn btn-outline-primary btn-sm viewProjectBtn me-2" data-id="<?= $project['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewModal">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  <button class="btn btn-edit btn-sm me-2 editProjectBtn" data-id="<?= $project['id'] ?>" data-bs-toggle="modal" data-bs-target="#editModal">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button class="btn btn-delete btn-sm deleteProjectBtn" data-id="<?= $project['id'] ?>">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="fa-solid fa-eye text-primary"></i> Project Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewModalBody">
        <p class="text-center"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</p>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa-solid fa-pen-to-square"></i> Edit Project</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="editModalBody">
        <p class="text-center"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></p>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    // View Project
    $('.viewProjectBtn').on('click', function(){
        const id = $(this).data('id');
        $('#viewModalBody').html('<p class="text-center"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></p>');
        $.get('', { project_id: id }, function(data){
            $('#viewModalBody').html(data);
        });
    });

    // Edit Project - Load Form
    $('.editProjectBtn').on('click', function(){
        const id = $(this).data('id');
        $('#editModalBody').html('<p class="text-center"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></p>');
        $.get('', { edit_id: id }, function(project){
            if(project.error){
                $('#editModalBody').html('<p class="text-danger">Project not found.</p>');
                return;
            }
            const imgSrc = project.image && project.image !== 'images/default_project.png' ? project.image : 'images/default_project.png';
            $('#editModalBody').html(`
                <form id="editProjectForm" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" value="${id}">
                    <input type="hidden" name="current_image" value="${project.image}">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control" value="${project.title}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="4" required>${project.description}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Goal Amount (Rs.)</label>
                        <input type="number" name="goal" class="form-control" value="${project.goal}" min="10000" max="1000000" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="${project.start_date}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="${project.end_date}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <input type="text" name="category" class="form-control" value="${project.category}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Image</label><br>
                        <img src="${imgSrc}" class="img-fluid rounded mb-2" style="max-height:200px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Change Image (Optional)</label>
                        <input type="file" name="new_image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-save"></i> Save Changes</button>
                </form>
            `);

            // Handle Save
            $('#editProjectForm').on('submit', function(e){
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'edit_save');

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res){
                        if(res.success){
                            Swal.fire('Success!', res.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: () => Swal.fire('Error!', 'Something went wrong.', 'error')
                });
            });
        }, 'json');
    });

    // Delete Project
    $('.deleteProjectBtn').on('click', function(){
        const id = $(this).data('id');
        const card = $('#project-' + id);

        Swal.fire({
            title: 'Delete Project?',
            text: "This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if(result.isConfirmed){
                $.post('', { action:'delete', project_id: id }, function(res){
                    if(res.success){
                        card.fadeOut(400, function(){ $(this).remove(); });
                        Swal.fire('Deleted!', res.message, 'success');
                    } else {
                        Swal.fire('Error!', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>
</body>
</html>