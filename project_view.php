<?php 
session_start();
require 'db_connect.php'; // PDO connection

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'guest';
$success_message = $error_message = "";

// ------------------------
// Handle status update (admin only)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'], $_POST['new_status'])) {
    $project_id = (int)$_POST['project_id'];
    $new_status = trim($_POST['new_status']);
    
    if ($project_id > 0 && in_array($new_status, ['pending', 'approved', 'rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE projects SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $new_status,
                'id' => $project_id
            ]);
            $success_message = "✅ Status updated to " . ucfirst($new_status) . " successfully!";
        } catch (PDOException $e) {
            $error_message = "❌ Failed to update status: " . $e->getMessage();
        }
    } else {
        $error_message = "❌ Invalid status or project ID.";
    }
}

// ------------------------
// Fetch project if view_id exists
$selected_project = null;
if (isset($_GET['view_id'])) {
    $view_id = (int)$_GET['view_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute(['id' => $view_id]);
        $selected_project = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// If no project found, redirect back
if (!$selected_project) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Project Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #f3f6fb; margin: 0; padding: 20px; }
.project-detail { position: relative; max-width: 800px; margin: 40px auto; background: #fff; border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.1); padding: 25px; }
.project-detail img { width: 100%; max-height: 350px; object-fit: cover; border-radius: 20px 20px 0 0; margin-bottom: 20px; }
.guest-overlay {
    position: absolute; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
    display:flex; justify-content:center; align-items:center; flex-direction: column;
    color:white; font-weight:bold; font-size:1.2rem; border-radius: 20px;
    text-align:center;
}
.guest-overlay i { font-size: 2rem; margin-bottom: 10px; color: #ffd700; }
.btn { border-radius: 50px; padding: 8px 20px; }
</style>
</head>
<body>

<div class="project-detail">
    <?php if ($selected_project): ?>
        <img src="<?= !empty($selected_project['image']) ? htmlspecialchars($selected_project['image']) : 'uploads/default.png' ?>" alt="Project Image">
        <h2><?= htmlspecialchars($selected_project['title']) ?></h2>
        <p><strong>Dates:</strong> <?= htmlspecialchars($selected_project['start_date']) ?> - <?= htmlspecialchars($selected_project['end_date']) ?></p>
        <p><?= nl2br(htmlspecialchars($selected_project['description'])) ?></p>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="guest-overlay">
                <i class="fa-solid fa-lock"></i>
                Login or Register to view this project
                <div style="margin-top:10px;">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-success">Register</a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
