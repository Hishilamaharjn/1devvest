<?php  
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../db_connect.php';

// Only investors
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'investor') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$investor_name = $_SESSION['username'] ?? 'Investor';
$date = date("l, F d, Y");

// Fetch user info
$stmt = $pdo->prepare("SELECT username, email, country, phone, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$success = '';
$error = '';
// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);

    // Phone validation: 10 digits
    if (!preg_match('/^\d{10}$/', $phone)) {
        $error = "❌ Contact number must be exactly 10 digits.";
    } else {
        $update = $pdo->prepare("UPDATE users SET username=?, email=?, country=?, phone=? WHERE id=?");
        $update->execute([$username, $email, $country, $phone, $user_id]);

        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['country'] = $country;
        $_SESSION['phone'] = $phone;

        $success = "✅ Profile updated successfully!";
        // Refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Investor Profile | DevVest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #f0f4f8; margin:0; }
/* Sidebar */
.sidebar { position: fixed; width: 260px; height: 100vh; background: linear-gradient(180deg, #4f46e5, #2563eb); color: #fff; padding: 25px 20px; display: flex; flex-direction: column; gap: 20px; z-index: 1500; box-shadow: 5px 0 15px rgba(0,0,0,0.2);}
.sidebar .logo { text-align: center; font-weight: 700; font-size: 22px; margin-bottom: 30px; }
.sidebar a { color: #fff; text-decoration: none; padding: 12px 15px; border-radius: 12px; display: flex; align-items: center; gap: 12px; font-weight: 500; transition: all 0.3s ease; }
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.2); transform: translateX(5px); }
/* Main */
.main { margin-left: 280px; padding: 40px 50px; }
/* Header */
.header h4 { font-weight: 700; margin-bottom: 5px; }
.header small { color: #555; }
/* Profile card */
.profile-card { background: #fff; border-radius: 20px; box-shadow: 0 12px 30px rgba(0,0,0,0.12); padding: 40px; max-width: 800px; margin: 20px auto; text-align: left; position: relative; }
.profile-card h2 { color: #4f46e5; font-weight: 700; margin-bottom: 5px; }
.profile-card p { color: #555; margin-bottom: 25px; font-size: 1em; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 20px; margin-bottom: 25px; }
.info-grid div { display: flex; flex-direction: column; }
.info-grid label { font-weight: 600; color: #333; margin-bottom: 5px; }
.info-grid span { color: #555; font-size: 0.95em; }
.btn-primary { background: linear-gradient(90deg,#4f46e5,#2563eb); border: none; border-radius: 8px; padding: 10px 25px; color: white; font-weight: 500; transition: 0.3s; }
.btn-primary:hover { transform: translateY(-2px); opacity: 0.95; }
.modal .form-control { margin-bottom: 15px; }
@media (max-width:992px){ .sidebar { left:-260px; position: fixed; } .sidebar.active{ left:0; } .main { margin-left:0; padding:25px; } }
.msg { margin-bottom:15px; font-weight:500; }
.msg.success { color:green; }
.msg.error { color:red; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-lightbulb"></i> DevVest</div>
  <a href="investor_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="browse_projects.php"><i class="fa-solid fa-folder-open"></i> Browse Projects</a>
  <a href="investment_history.php"><i class="fa-solid fa-coins"></i> My Investments</a>
  <a href="investor_profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
  <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
  <div class="header">
    <h4>Welcome, <?=htmlspecialchars($investor_name)?> 👋</h4>
    <small><?= $date ?></small>
  </div>

  <div class="profile-card">
    <?php if($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>

    <h2>@<?= htmlspecialchars($user['username'] ?? $investor_name) ?></h2>
    <p><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? '') ?></p>

    <div class="info-grid">
        <div><label>Name:</label><span><?= htmlspecialchars($user['username'] ?? $investor_name) ?></span></div>
        <div><label>Country:</label><span><?= htmlspecialchars($user['country'] ?? 'N/A') ?></span></div>
        <div><label>Phone:</label><span><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span></div>
        <div><label>Joined:</label><span><?= isset($user['created_at']) ? date("F d, Y", strtotime($user['created_at'])) : 'N/A' ?></span></div>
    </div>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fa-solid fa-pen"></i> Edit Profile</button>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4">
      <div class="modal-header">
        <h5 class="modal-title">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" class="p-2">
        <input type="hidden" name="update_profile" value="1">
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="Username" required>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Email" required>
        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($user['country'] ?? '') ?>" placeholder="Country">
        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Phone" maxlength="10" pattern="\d{10}" title="Contact number must be exactly 10 digits" required>
        
        <button type="submit" class="btn btn-primary w-100 mt-2"><i class="fa-solid fa-save"></i> Save Changes</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
