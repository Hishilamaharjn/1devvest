<?php   
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT username, email, country, phone, education, role, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Handle form submission (without image upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $education = trim($_POST['education']);
    $profile_image = $user['profile_image']; // keep existing photo

    $update = $pdo->prepare("UPDATE users SET username=?, email=?, country=?, phone=?, education=?, profile_image=? WHERE id=?");
    $update->execute([$username, $email, $country, $phone, $education, $profile_image, $user_id]);

    // ✅ FIX added here: refresh session data after update
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['country'] = $country;
    $_SESSION['phone'] = $phone;
    $_SESSION['education'] = $education;

    $_SESSION['success'] = "✅ Profile updated successfully!";

    // ✅ Redirect to correct profile page based on role
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_profile.php");
    } elseif ($_SESSION['role'] === 'client') {
        header("Location: client_profile.php");
    } elseif ($_SESSION['role'] === 'donor') {
        header("Location: donor_profile.php");
    } else {
        header("Location: login.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile | Crowdfunding</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
:root {
  --primary:#3b82f6;
  --secondary:#2563eb;
  --bg:#f3f4f6;
  --text-dark:#1e293b;
  --text-light:#64748b;
  --white:#ffffff;
}
*{box-sizing:border-box;margin:0;padding:0}
body {
  font-family: "Poppins", sans-serif;
  background: var(--bg);
  display:flex;
  justify-content:center;
  align-items:center;
  min-height:100vh;
  padding:20px;
}
.card {
  background: var(--white);
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 500px;
  padding: 40px 35px 50px;
  text-align: center;
}
h2 {
  color: var(--primary);
  margin-bottom: 25px;
  font-weight:600;
}
.profile-pic {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 4px solid var(--primary);
  object-fit: cover;
  box-shadow:0 6px 25px rgba(37,99,235,0.3);
  margin-bottom: 15px;
}
label {
  display: block;
  text-align: left;
  margin-top: 12px;
  font-weight: 500;
  color: var(--text-dark);
}
input[type="text"], input[type="email"], input[type="tel"] {
  width: 100%;
  padding: 10px 12px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 10px;
  font-size: 14px;
  background-color: #fff;
  transition: 0.2s;
}
input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
.done-btn {
  width: 100%;
  margin-top: 25px;
  padding: 12px;
  background-color: var(--primary);
  border: none;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  transition: 0.3s;
}
.done-btn:hover {
  background-color: var(--secondary);
  transform: translateY(-2px);
}
.msg {
  margin-bottom: 10px;
  color: green;
  font-weight: 500;
}
a.back {
  display:inline-block;
  margin-top:20px;
  color:var(--primary);
  text-decoration:none;
  font-weight:500;
}
a.back:hover {text-decoration:underline;}
</style>
</head>
<body>

<div class="card">
    <h2>Edit Profile</h2>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="msg"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <img src="<?= htmlspecialchars($user['profile_image'] ?: 'uploads/default.png') ?>" class="profile-pic" alt="Profile Picture">

        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Country</label>
        <input type="text" name="country" value="<?= htmlspecialchars($user['country'] ?? '') ?>">

        <label>Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

        <label>Education</label>
        <input type="text" name="education" value="<?= htmlspecialchars($user['education'] ?? '') ?>">

        <button type="submit" class="done-btn">Done</button>
    </form>

    <a href="profile.php" class="back"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
</div>

</body>
</html>
