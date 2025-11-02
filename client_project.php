<?php 
session_start();
require 'db_connect.php'; // PDO connection

// ✅ Check if logged in & correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['username'] ?? 'Client';
$date = date("l, F d, Y");
$success = $error = "";

// ---------------------- DELETE PROJECT ----------------------
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $delete_id, 'user_id' => $user_id]);
        $success = "✅ Project deleted successfully!";
    } catch (PDOException $e) {
        $error = "❌ Failed to delete project: " . $e->getMessage();
    }
}

// ---------------------- UPDATE PROJECT ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (!empty($title) && !empty($description)) {
        try {
            $stmt = $pdo->prepare("UPDATE projects 
                SET title = :title, description = :description, start_date = :start_date, end_date = :end_date 
                WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'id' => $edit_id,
                'user_id' => $user_id
            ]);
            $success = "✅ Project updated successfully!";
        } catch (PDOException $e) {
            $error = "❌ Update failed: " . $e->getMessage();
        }
    } else {
        $error = "❌ Please fill all required fields.";
    }
}

// ---------------------- FETCH PROJECTS ----------------------
try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = :user_id ORDER BY id DESC");
    $stmt->execute(['user_id' => $user_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// ---------------------- PROFILE PICTURE (FIXED) ----------------------
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!empty($user['profile_pic'])) {
    $profile_path = "uploads/" . basename($user['profile_pic']);
    $profile_pic = (file_exists($profile_path)) ? $profile_path : "uploads/default.png";
} else {
    $profile_pic = "uploads/default.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Projects | Crowdfunding</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f3f6fb;margin:0;}
.sidebar{position:fixed;width:260px;height:100vh;background:linear-gradient(200deg,#2563eb,#4f46e5);color:#fff;padding:25px;display:flex;flex-direction:column;gap:20px;}
.sidebar .logo{text-align:center;font-weight:700;font-size:20px;margin-bottom:30px;}
.sidebar a{color:#fff;text-decoration:none;padding:12px 15px;border-radius:10px;display:flex;align-items:center;gap:12px;font-weight:500;}
.sidebar a:hover, .sidebar a.active{background:rgba(255,255,255,0.15);transform:translateX(5px);}
.main{margin-left:280px;padding:40px 50px;}
.profile-pic{width:55px;height:55px;border-radius:50%;object-fit:cover;cursor:pointer;transition:all 0.3s;}
.profile-pic:hover{transform:scale(1.1);box-shadow:0 5px 15px rgba(0,0,0,0.3);}
.alert{border-radius:10px;}
.card{border-radius:15px;box-shadow:0 8px 20px rgba(0,0,0,0.1);transition:all 0.3s;}
.card:hover{transform:translateY(-7px) scale(1.02);}
.card img{height:180px;object-fit:cover;}
.btn{border-radius:50px;}
.modal-content{border-radius:20px;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="client_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'client_dashboard.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-gauge"></i> Dashboard
  </a>
  <a href="create_project.php" class="<?= basename($_SERVER['PHP_SELF']) === 'create_project.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-plus-circle"></i> Create Project
  </a>
  <a href="client_project.php" class="<?= basename($_SERVER['PHP_SELF']) === 'client_project.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-folder-open"></i> My Projects
  </a>
  <a href="client_donations.php" class="<?= basename($_SERVER['PHP_SELF']) === 'client_donations.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-hand-holding-dollar"></i> Donations
  </a>
  <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-user"></i> Profile
  </a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-1">Welcome, <?= htmlspecialchars($client_name) ?> 👋</h3>
      <div class="text-muted"><?= $date ?></div>
    </div>
    <img src="<?= htmlspecialchars($profile_pic) ?>" class="profile-pic" id="profilePic" alt="Profile">
  </div>

  <h4 class="mb-4"><i class="fa-solid fa-folder-open text-primary"></i> My Projects</h4>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="row">
    <?php if ($projects): ?>
      <?php foreach ($projects as $p): ?>
      <div class="col-md-4 mb-4">
        <div class="card">
          <img src="<?= htmlspecialchars($p['image'] ?? 'uploads/default.png') ?>" alt="Project Image">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($p['title']) ?></h5>
            <p class="text-muted small mb-1">
              <?= htmlspecialchars($p['start_date']) ?> → <?= htmlspecialchars($p['end_date']) ?>
            </p>
            <p class="card-text"><?= htmlspecialchars(substr($p['description'], 0, 80)) ?>...</p>

            <div class="d-flex justify-content-between mt-3">
              <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#viewModal<?= $p['id'] ?>">
                <i class="fa fa-eye"></i>
              </button>
              <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">
                <i class="fa fa-edit"></i>
              </button>
              <a href="?delete_id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this project?')">
                <i class="fa fa-trash"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW MODAL -->
      <div class="modal fade" id="viewModal<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?= htmlspecialchars($p['title']) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <img src="<?= htmlspecialchars($p['image'] ?? 'uploads/default.png') ?>" class="img-fluid rounded mb-3">
              <p><strong>Start Date:</strong> <?= htmlspecialchars($p['start_date']) ?></p>
              <p><strong>End Date:</strong> <?= htmlspecialchars($p['end_date']) ?></p>
              <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($p['description'])) ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- EDIT MODAL -->
      <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <form method="POST" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Project</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="edit_id" value="<?= $p['id'] ?>">
              <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($p['description']) ?></textarea>
              </div>
              <div class="mb-3">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($p['start_date']) ?>" class="form-control">
              </div>
              <div class="mb-3">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($p['end_date']) ?>" class="form-control">
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Save Changes</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-center text-muted">No projects found.</p>
    <?php endif; ?>
  </div>
</div>

<!-- IMAGE PREVIEW MODAL -->
<div id="profileModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.6);">
  <img src="<?= htmlspecialchars($profile_pic) ?>" style="display:block;margin:10% auto;max-width:400px;width:80%;border-radius:15px;">
</div>

<script>
const modal = document.getElementById('profileModal');
const pic = document.getElementById('profilePic');
pic.onclick = ()=>{ modal.style.display="block"; }
modal.onclick = ()=>{ modal.style.display="none"; }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
