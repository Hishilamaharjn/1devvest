<?php 
if (!isset($_SESSION)) session_start();
require 'db_connect.php';

// Optional: If logged in, show username instead of login/register
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
?>

<!-- 🌐 Main Site Header -->
<header class="main-header">
  <div class="header-container">
      <!-- Logo / Title -->
      <div class="logo-section">
          <h2 class="logo-text">🌐 Crowdfunding Platform</h2>
      </div>

      <!-- Navigation Links -->
      <nav class="nav-links">
          <a href="index.php" class="nav-item">Home</a>
          <a href="about.php" class="nav-item">About</a>
          <a href="browse_projects.php" class="nav-item">Browse Projects</a>
          <a href="contact.php" class="nav-item">Contact</a>

          <?php if ($username): ?>
              <a href="logout.php" class="logout-btn">Logout</a>
          <?php else: ?>
              <a href="login.php" class="nav-item">Login</a>
              <a href="register.php" class="nav-item">Register</a>
          <?php endif; ?>
      </nav>
  </div>
</header>

<style>
/* Header Styles (same theme as admin) */
.main-header {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    padding: 12px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-container {
    width: 92%;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.logo-text {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

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

/* Logout button */
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

/* Responsive Design */
@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        text-align: center;
    }

    .nav-links {
        margin-top: 8px;
        justify-content: center;
    }
}
</style>
