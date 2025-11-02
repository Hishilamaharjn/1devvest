<?php  
session_start();
require 'db_connect.php'; // ✅ Must define $pdo (PDO connection)

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $client_name = $_SESSION['username']; // ✅ added this line
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $goal = $_POST['goal'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = 'pending';

    // ✅ Handle image upload
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . time() . "_" . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        } else {
            $error = "Failed to upload image.";
        }
    }

    // ✅ Insert into DB using PDO
    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (
                    client_id, client_name, user_id, project_name, title, description, goal, start_date, end_date, image, status
                )
                VALUES (
                    :client_id, :client_name, :user_id, :project_name, :title, :description, :goal, :start_date, :end_date, :image, :status
                )
            ");
            $stmt->execute([
                ':client_id' => $user_id,
                ':client_name' => $client_name, // ✅ insert client name
                ':user_id' => $user_id,
                ':project_name' => $title, // ✅ same as title
                ':title' => $title,
                ':description' => $description,
                ':goal' => $goal,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':image' => $image_path,
                ':status' => $status
            ]);

            // ✅ Redirect
            header("Location: client_donations.php?success=1");
            exit;
        } catch (PDOException $e) {
            $error = "Error: Unable to create project. " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Project - Crowdfunding</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f3f6fb;margin:0;}
.sidebar{position:fixed;width:260px;height:100vh;background:linear-gradient(200deg,#2563eb,#4f46e5);color:#fff;padding:25px;display:flex;flex-direction:column;gap:20px;}
.sidebar .logo{text-align:center;font-weight:700;font-size:20px;margin-bottom:30px;}
.sidebar a{color:#fff;text-decoration:none;padding:12px 15px;border-radius:10px;display:flex;align-items:center;gap:12px;font-weight:500;}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,0.15);transform:translateX(5px);}
.main-content{margin-left:280px;padding:40px 50px;}
.card{background:#fff;border-radius:18px;max-width:650px;margin:auto;padding:35px 30px;box-shadow:0 10px 25px rgba(0,0,0,0.08);}
.card h3{text-align:center;font-weight:700;color:#1f2937;margin-bottom:25px;}
.form-control{border-radius:12px;padding:10px 14px;border:1px solid #cbd5e1;font-size:0.95rem;margin-bottom:15px;}
.form-control:focus{border-color:#6366f1;box-shadow:0 0 6px rgba(99,102,241,0.2);}
.btn-custom{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;font-weight:600;border-radius:50px;padding:12px;width:100%;font-size:0.95rem;}
.btn-custom:hover{background:linear-gradient(135deg,#4f46e5,#4338ca);transform:translateY(-2px);}
.btn-back{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;font-weight:600;border-radius:50px;padding:10px 18px;font-size:0.95rem;text-decoration:none;transition:all 0.3s;margin-bottom:25px;}
.btn-back:hover{background:linear-gradient(135deg,#4f46e5,#4338ca);transform:translateY(-2px);}
#imagePreview{display:none;max-width:100%;border-radius:12px;margin-top:10px;}
</style>
</head>
<body>

<!-- ✅ Sidebar -->
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <a href="client_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php" class="active"><i class="fa-solid fa-plus-circle"></i> Create Project</a>
  <a href="client_project.php"><i class="fa-solid fa-folder-open"></i> My Projects</a>
  <a href="client_donations.php"><i class="fa-solid fa-hand-holding-dollar"></i> Donations</a>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- ✅ Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fa-solid fa-rocket text-primary"></i> Create New Project</h3>
        <a href="client_donations.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> View Donations</a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" class="form-control" placeholder="Project Title" required>
            <textarea name="description" class="form-control" rows="4" placeholder="Briefly describe your project..." required></textarea>
            <input type="number" name="goal" step="0.01" class="form-control" placeholder="Goal Amount (Rs.)" required>

            <div class="row g-3">
                <div class="col-md-6">
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>

            <input type="file" name="image" accept="image/*" class="form-control" id="projectImage" onchange="previewImage(event)">
            <img id="imagePreview" src="#" alt="Image Preview">

            <button type="submit" class="btn-custom mt-3">Create Project ✨</button>
        </form>
    </div>
</div>

<script>
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}
</script>
</body>
</html>
