<?php 
if (!isset($_SESSION)) session_start();
require 'db_connect.php';

// ✅ Only admin can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// Admin info
$admin_name = $_SESSION['username'] ?? 'Admin';
$user_id = $_SESSION['user_id'];

// ✅ Fetch profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profile_pic = (!empty($user['profile_pic']) && file_exists(__DIR__ . "/uploads/" . $user['profile_pic']))
    ? "uploads/" . $user['profile_pic']
    : "uploads/default.png";

// ✅ Current date
date_default_timezone_set('Asia/Kathmandu');
$current_date = date("l, F j, Y");
?>

<!-- Modern Admin Header -->
<header class="admin-header">
  <div class="header-container">
      <!-- Logo / Title -->
      <div class="logo-section">
          <h2 class="logo-text">🌐 Crowdfunding Admin</h2>
      </div>

      <!-- Navigation Links -->
      <nav class="nav-links">
          <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
          <a href="admin_projects.php" class="nav-item">Projects</a>
          <a href="admin_status.php" class="nav-item">Status</a>
          <a href="admin_users.php" class="nav-item">Users</a>
          <a href="admin_profile.php" class="nav-item">Profile</a>
          <a href="logout.php" class="logout-btn">Logout</a>
      </nav>

      <!-- Profile Section -->
      <div class="profile-section">
          <div class="profile-info">
              <span class="admin-name"><?= htmlspecialchars($admin_name) ?></span><br>
              <small class="date"><?= $current_date ?></small>
          </div>
          <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="profile-pic">
      </div>
  </div>
</header>

<style>
/* Overall Header */
.admin-header {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    padding: 12px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 100;
}

/* Header Inner Container */
.header-container {
    width: 92%;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

/* Logo */
.logo-text {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

/* Navigation Links */
.nav-links {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.nav-item {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s ease;
    padding: 6px 12px;
    border-radius: 8px;
}

.nav-item:hover {
    background: rgba(255,255,255,0.15);
    color: #ffeb3b;
}

/* Logout Button */
.logout-btn {
    background: #dc3545;
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    font-weight: 600;
    transition: 0.3s;
}

.logout-btn:hover {
    background: #b91c1c;
}

/* Profile Section */
.profile-section {
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-info {
    text-align: right;
}

.admin-name {
    font-weight: 600;
    font-size: 16px;
}

.date {
    color: #e0e0e0;
    font-size: 13px;
}

/* Profile Picture */
.profile-pic {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
}

/* Responsive */
@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        text-align: center;
    }

    .nav-links {
        margin-top: 8px;
        justify-content: center;
    }

    .profile-section {
        margin-top: 10px;
        justify-content: center;
    }
}
</style>
