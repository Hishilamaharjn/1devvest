<?php
session_start();
require 'db_connect.php';

// Redirect if not client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $goal = trim($_POST['goal']);
    $description = trim($_POST['description']);

    if ($title === '' || $goal === '' || $description === '') {
        $msg = "All fields are required!";
    } else {
        $stmt = $conn->prepare("INSERT INTO projects (user_id, title, goal, description, status, start_date) VALUES (?, ?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("isds", $_SESSION['user_id'], $title, $goal, $description);
        if ($stmt->execute()) {
            $msg = "✅ Project submitted successfully and awaiting admin approval!";
        } else {
            $msg = "❌ Something went wrong!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Project — Crowdfunding</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
  margin: 0; font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #007bff, #00aaff);
}
header {
  background: #004aad; color: #fff;
  padding: 15px 40px; display: flex; justify-content: space-between; align-items: center;
}
header .logo { font-size: 1.4rem; font-weight: 600; }
nav a { color: #fff; text-decoration: none; margin-left: 25px; transition: 0.3s; }
nav a:hover { color: #00ffd1; }

.container {
  max-width: 600px; margin: 60px auto; background: #fff; padding: 30px;
  border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
h2 { color: #004aad; }
input, textarea {
  width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 8px;
}
button {
  background: #004aad; color: white; border: none; padding: 10px 20px; border-radius: 8px;
  cursor: pointer; transition: 0.3s;
}
button:hover { background: #007bff; }
.msg { margin-bottom: 10px; color: green; font-weight: 500; }
.error { color: red; font-weight: 500; }
</style>
</head>
<body>
<header>
  <div class="logo">🌐 Crowdfunding</div>
  <nav>
    <a href="client_dashboard.php">Dashboard</a>
    <a href="status.php">Status</a>
    <a href="edit_profile.php">My Profile</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="container">
  <h2>➕ Create New Project</h2>
  <?php if ($msg): ?>
    <div class="<?= strpos($msg, '✅') !== false ? 'msg' : 'error' ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <input type="text" name="title" placeholder="Project Title" required>
    <input type="number" name="goal" placeholder="Funding Goal (e.g. 5000)" required>
    <textarea name="description" placeholder="Project Description" rows="5" required></textarea>
    <button type="submit">Submit Project</button>
  </form>
</div>

</body>
</html>
