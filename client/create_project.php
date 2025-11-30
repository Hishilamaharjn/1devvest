<?php 
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../login.php");
    exit;
}

$error = '';
$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['username'] ?? 'Client';

// ====================== FORM SUBMISSION HANDLING ======================
$title       = '';
$description = '';
$goal        = '';
$start_date  = '';
$end_date    = '';
$category    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $goal        = (float)($_POST['goal'] ?? 0);
    $start_date  = $_POST['start_date'] ?? '';
    $end_date    = $_POST['end_date'] ?? '';
    $category    = trim($_POST['category'] ?? '');

    // Server-side validation
    if ($goal < 10000) {
        $error = "Goal amount must be at least Rs.10,000.";
    } elseif ($goal > 1000000) {
        $error = "Maximum allowed goal amount is Rs.10,00,000.";
    }

    // Image handling
    $image_path = 'images/default_project.png';
    if (!$error && !empty($_FILES['image']['name'])) {
        $target_dir = __DIR__ . "/images/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_name  = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = 'images/' . $image_name;
        } else {
            $error = "Failed to upload image. Check folder permissions.";
        }
    }

    // Insert into database
    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects 
                (client_id, client_name, user_id, project_name, title, description, goal, start_date, end_date, category, image, status)
                VALUES 
                (:client_id, :client_name, :user_id, :project_name, :title, :description, :goal, :start_date, :end_date, :category, :image, 'pending')
            ");

            $stmt->execute([
                ':client_id'    => $user_id,
                ':client_name'  => $client_name,
                ':user_id'      => $user_id,
                ':project_name' => $title,
                ':title'        => $title,
                ':description'  => $description,
                ':goal'         => $goal,
                ':start_date'   => $start_date,
                ':end_date'     => $end_date,
                ':category'     => $category,
                ':image'        => $image_path
            ]);

            // SUCCESS → Redirect!
            header("Location: client_project.php?success=1");
            exit;

        } catch (PDOException $e) {
            $error = "Error: Unable to create project. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Project | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; background:#f8fafc; margin:0; }
.sidebar { position:fixed; width:260px; height:100vh; background:linear-gradient(180deg,#4f46e5,#2563eb); color:#fff; padding:25px 20px; display:flex; flex-direction:column; gap:20px; z-index:1500; box-shadow:5px 0 15px rgba(0,0,0,0.2); }
.sidebar .logo {text-align:center; font-weight:700; font-size:22px; margin-bottom:30px; cursor:pointer;}
.sidebar a {color:#fff; text-decoration:none; padding:12px 15px; border-radius:12px; display:flex; align-items:center; gap:12px; transition:0.3s;}
.sidebar a.active, .sidebar a:hover {background:rgba(255,255,255,0.2); transform:translateX(5px);}
.main-content {margin-left:280px; padding:40px 50px; min-height:100vh;}

.welcome-banner h2 { font-size: 28px; font-weight: 700; margin: 0; }
.welcome-banner .wave { font-size: 32px; animation: wave 2s infinite; }
.welcome-banner .date { font-size: 16px; opacity:0.9; }
@keyframes wave { 0%,100%{transform:rotate(0deg)} 25%{transform:rotate(15deg)} 50%{transform:rotate(-10deg)} 75%{transform:rotate(10deg)} }

.project-card { background: white; border-radius: 24px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; max-width: 750px; margin: 0 auto; }
.project-card h3 { font-size: 26px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.project-card p { color: #64748b; margin-bottom: 30px; }

.form-control, textarea.form-control { border-radius: 14px; border: 1.5px solid #e2e8f0; padding: 14px 18px; font-size: 15px; transition: all 0.3s; margin-bottom: 8px; }
.form-control:focus, textarea.form-control:focus { border-color: #667eea; box-shadow: 0 0 12px rgba(102,126,234,0.25); transform: translateY(-2px); }

.btn-create { background: linear-gradient(135deg, #667eea, #0D6EFD); border: none; color: white; padding: 16px; font-size: 17px; font-weight: 600; border-radius: 50px; width: 100%; margin-top: 20px; box-shadow: 0 10px 25px rgba(102,126,234,0.4); transition: all 0.3s; }
.btn-create:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(102,126,234,0.5); }

.btn-back { background:#0D6EFD; color:#fff; padding:10px 20px; border-radius:50px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; font-weight:600; margin-bottom:25px; transition:0.3s; }
.btn-back:hover { background:#0d5acd; }

.field-error { color:#ef4444; font-size:14px; margin-top:6px; opacity:0; transition:opacity 0.4s; }
.field-error.show { opacity:1; }
.invalid-field { border-color:#ef4444 !important; box-shadow:0 0 12px rgba(239,68,68,0.3) !important; }

#imagePreview {display:none; width:100%; max-height:320px; object-fit:cover; border-radius:16px; margin-top:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1);}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo" onclick="location.href='client_dashboard.php'"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="client_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="create_project.php" class="active"><i class="fa-solid fa-plus"></i> Create Project</a>
  <a href="client_project.php"><i class="fa-solid fa-folder-open"></i> My Projects</a>
  <a href="client_investments.php"><i class="fa-solid fa-coins"></i> Investments</a>
  <a href="client_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">

    <div class="welcome-banner">
        <div>
            <h4>Welcome, <?= htmlspecialchars($client_name) ?> 👋</h4>
            <div class="date"><?= date('l, F j, Y') ?></div>
        </div>
    </div><br>

    <a href="client_project.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i>My Projects</a>

    <?php if($error): ?>
        <div class="alert alert-danger rounded-4 mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="project-card">
        <h3>Create New Project</h3>
        <p>Bring your idea to life — fill in the details below and Create Your Project!</p>

        <form id="projectForm" method="POST" enctype="multipart/form-data" novalidate>
            <label class="form-label fw-600">Project Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. AI-Powered Fitness App" value="<?= htmlspecialchars($title) ?>" required>
            <div class="field-error" id="titleError"></div>

            <label class="form-label fw-600 mt-3">Description</label>
            <textarea name="description" class="form-control" rows="5" placeholder="Tell investors why your project matters..." required><?= htmlspecialchars($description) ?></textarea>
            <div class="field-error" id="descError"></div>

            <label class="form-label fw-600 mt-3">Goal Amount (Rs.)</label>
            <input type="number" name="goal" id="goal" class="form-control" placeholder="Minimum Rs.10,000 – Maximum Rs.10,00,000" value="<?= htmlspecialchars($goal) ?>" required>
            <div class="field-error" id="goalError"></div>

            <label class="form-label fw-600 mt-3">Category</label>
            <input list="categories" name="category" class="form-control" placeholder="Choose or type your category" value="<?= htmlspecialchars($category) ?>" required>
            <datalist id="categories">
                <option value="Web Development"><option value="Mobile App"><option value="AI / Machine Learning">
                <option value="Blockchain"><option value="Cybersecurity"><option value="Data Science"><option value="DevOps">
            </datalist>
            <div class="field-error" id="categoryError"></div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label fw-600">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
                    <div class="field-error" id="startError"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
                    <div class="field-error" id="endError"></div>
                </div>
            </div>

            <label class="form-label fw-600 mt-4">Project Image (Optional)</label>
            <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(event)">
            <img id="imagePreview" alt="Preview">

            <button type="submit" class="btn-create">
                <i class="fa-solid fa-star me-2"></i> Create Project
            </button>
        </form>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = "block"; }
        reader.readAsDataURL(file);
    }
}

function clearErrors() {
    document.querySelectorAll('.invalid-field').forEach(el => el.classList.remove('invalid-field'));
    document.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; el.classList.remove('show'); });
}

function showError(id, msg) {
    const el = document.getElementById(id + 'Error');
    const input = document.querySelector(id === 'desc' ? '[name="description"]' : 
                                  id === 'start' ? '#start_date' : 
                                  id === 'end' ? '#end_date' : `[name="${id === 'goal' ? 'goal' : id}"]`);
    if (input) input.classList.add('invalid-field');
    if (el) {
        el.textContent = msg;
        el.classList.add('show');
        setTimeout(() => el.classList.remove('show'), 4000);
    }
}

document.getElementById('projectForm').addEventListener('submit', function(e) {
    clearErrors();
    let hasError = false;

    const title = document.querySelector('[name="title"]').value.trim();
    const desc  = document.querySelector('[name="description"]').value.trim();
    const goal  = document.querySelector('[name="goal"]').value;
    const cat   = document.querySelector('[name="category"]').value.trim();
    const start = document.getElementById('start_date').value;
    const end   = document.getElementById('end_date').value;

    if (!title) { showError('title', 'Please enter a project title'); hasError = true; }
    if (!desc)  { showError('desc', 'Please write a description'); hasError = true; }
    if (!goal)  { showError('goal', 'Please enter goal amount'); hasError = true; }
    if (!cat)   { showError('category', 'Please select a category'); hasError = true; }
    if (!start) { showError('start', 'Please choose start date'); hasError = true; }
    if (!end)   { showError('end', 'Please choose end date'); hasError = true; }

    const goalNum = parseFloat(goal);
    if (goal && (goalNum < 10000 || goalNum > 1000000)) {
        showError('goal', 'Goal must be between Rs.10,000 and Rs.10,00,000');
        hasError = true;
    }

    if (start) {
        const today = new Date(); today.setHours(0,0,0,0);
        if (new Date(start) < today) {
            showError('start', 'Start date cannot be in the past');
            hasError = true;
        }
    }

    if (start && end) {
        const s = new Date(start);
        const e = new Date(end);
        const twoMonths = new Date(s); twoMonths.setMonth(s.getMonth() + 2);

        if (e <= s) {
            showError('end', 'End date must be after start date');
            hasError = true;
        } else if (e > twoMonths) {
            showError('end', 'End date must be within 2 months from start date');
            hasError = true;
        }
    }

    if (hasError) e.preventDefault();
});
</script>
</body>
</html>
