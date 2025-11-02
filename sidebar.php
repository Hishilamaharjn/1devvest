<?php         
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';

// Default values
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'client';
$username = $_SESSION['username'] ?? 'User';
$profile_image = $_SESSION['profile_image'] ?? 'uploads/default.png';

// ✅ Fetch latest profile image from DB only if user exists
if ($user_id) {
    $stmt = $pdo->prepare("SELECT username, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $username = htmlspecialchars($user['username']);
        if (!empty($user['profile_image']) && file_exists(__DIR__ . '/' . $user['profile_image'])) {
            $profile_image = htmlspecialchars($user['profile_image']);
            $_SESSION['profile_image'] = $profile_image;
        } else {
            $profile_image = 'uploads/default.png';
            $_SESSION['profile_image'] = $profile_image;
        }
    }
}
?>

<!-- Sidebar -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body {
        margin: 0;
        font-family: "Poppins", sans-serif;
        background-color: #f3f4f6;
    }

    /* ✅ Hide hamburger icon on desktop */
    .menu-toggle {
        display: none;
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #4f46e5;
        color: #fff;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 25px;
        box-shadow: 2px 0 10px rgba(0,0,0,0.15);
        z-index: 1000;
        transition: transform 0.3s ease-in-out;
    }

    .sidebar .profile {
        text-align: center;
        margin-bottom: 35px;
    }

    .sidebar .profile img {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 3px solid #c7d2fe;
        object-fit: cover;
        box-shadow: 0 0 8px rgba(0,0,0,0.2);
    }

    .sidebar .profile h3 {
        margin-top: 10px;
        font-size: 17px;
        color: #fff;
        font-weight: 600;
    }

    .sidebar .profile p {
        color: #dbeafe;
        font-size: 13px;
        margin-top: 3px;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        width: 100%;
    }

    .sidebar ul li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 25px;
        color: #e0e7ff;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border-radius: 10px;
        margin: 5px 10px;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
        background-color: #4338ca;
        color: #fff;
    }

    .sidebar ul li a i {
        width: 20px;
        text-align: center;
        font-size: 18px;
    }

    .logout {
        margin-top: auto;
        margin-bottom: 25px;
        width: 100%;
    }

    .logout a {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fee2e2;
        text-decoration: none;
        padding: 12px 25px;
        transition: 0.3s;
        border-radius: 10px;
        margin: 0 10px;
    }

    .logout a:hover {
        background-color: #ef4444;
        color: white;
    }

    /* ✅ Responsive part */
    @media (max-width: 992px) {
        .sidebar {
            width: 220px;
        }
        .sidebar .profile img {
            width: 70px;
            height: 70px;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.active {
            transform: translateX(0);
        }

        .menu-toggle {
            display: block;
            position: fixed;
            top: 15px;
            left: 15px;
            background: #4f46e5;
            color: white;
            border: none;
            font-size: 22px;
            border-radius: 5px;
            padding: 8px 12px;
            z-index: 1100;
        }

        /* main content shift when sidebar active */
        .content.active {
            margin-left: 220px;
            transition: 0.3s;
        }
    }

    /* ✅ Ensure main content not hidden */
    .content {
        margin-left: 250px;
        padding: 25px;
        transition: 0.3s;
    }

    @media (max-width: 768px) {
        .content {
            margin-left: 0;
        }
    }
</style>

<!-- ✅ Mobile Toggle Button -->
<button class="menu-toggle" onclick="toggleSidebar()">
  <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="profile">
        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile Picture">
        <h3><?= htmlspecialchars($username) ?></h3>
        <p><?= ucfirst($role) ?></p>
    </div>

    <ul>
        <?php if ($role === 'admin'): ?>
            <li><a href="admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="admin_projects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_projects.php' ? 'active' : '' ?>"><i class="fa-solid fa-briefcase"></i> Projects</a></li>
            <li><a href="admin_donations.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_donations.php' ? 'active' : '' ?>"><i class="fa-solid fa-hand-holding-dollar"></i> Donations</a></li>
            <li><a href="manage_clients.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_clients.php' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Manage Clients</a></li>
            <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
            <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profile</a></li>
        <?php elseif ($role === 'donor'): ?>
            <li><a href="donor_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'donor_dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="browse_projects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'browse_projects.php' ? 'active' : '' ?>"><i class="fa-solid fa-hand-holding-dollar"></i> Fund Projects</a></li>
            <li><a href="donation_history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'donation_history.php' ? 'active' : '' ?>"><i class="fa-solid fa-clock-rotate-left"></i> My Donations</a></li>
            <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profile</a></li>
        <?php else: ?>
            <li><a href="client_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'client_dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="client_project.php" class="<?= basename($_SERVER['PHP_SELF']) == 'client_project.php' ? 'active' : '' ?>"><i class="fa-solid fa-briefcase"></i> My Projects</a></li>
            <li><a href="create_project.php" class="<?= basename($_SERVER['PHP_SELF']) == 'create_project.php' ? 'active' : '' ?>"><i class="fa-solid fa-plus-circle"></i> Create Project</a></li>
            <li><a href="client_donations.php" class="<?= basename($_SERVER['PHP_SELF']) == 'client_donations.php' ? 'active' : '' ?>"><i class="fa-solid fa-hand-holding-dollar"></i> Donations</a></li>
            <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profile</a></li>
        <?php endif; ?>
    </ul>

    <div class="logout">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.content')?.classList.toggle('active');
}
</script>
