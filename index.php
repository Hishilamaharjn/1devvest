<?php 
session_start();
require 'db_connect.php'; 

$projects = [];

try {
    $stmt = $pdo->query("SELECT * FROM projects WHERE status = 'approved' ORDER BY id DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
    $projects = []; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crowdfunding - Empowering Dreams</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  margin: 0;
  background: linear-gradient(135deg, #007bff, #00aaff);
  color: #333;
  min-height: 100vh;
  padding-top: 80px;
}

/* Navbar */
header {
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  position: fixed;
  top: 0;
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 25px;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
header .logo {
  color: #fff;
  font-weight: 700;
  font-size: 1.4rem;
  cursor: pointer;
}
nav {
  display: flex;
  align-items: center;
  gap: 20px;
}
nav a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  border-radius: 6px;
  padding: 6px 12px;
  transition: 0.3s;
}
nav a:hover {
  background: rgba(255,255,255,0.2);
  color: #00ffd1;
}

/* Hero */
.hero {
  text-align: center;
  padding: 70px 20px 40px;
  color: white;
}
.hero h1 {
  font-size: 2.3rem;
  margin-bottom: 12px;
  text-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.hero p {
  font-size: 1.1rem;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto;
}

/* Projects */
.projects {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
  gap: 25px;
  max-width: 1100px;
  margin: 20px auto 60px;
  padding: 0 20px;
}
.card {
  background: rgba(255,255,255,0.95);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  overflow: hidden;
  transition: 0.3s ease;
  position: relative;
}
.card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}
.card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}
.card-body {
  padding: 15px;
}
.card-body h3 {
  color: #004aad;
  margin: 0 0 8px;
  font-size: 1.1rem;
}
.card-body p {
  font-size: 0.9rem;
  color: #555;
  line-height: 1.4;
}
.card-body .goal {
  font-weight: 600;
  color: #007bff;
  margin-top: 8px;
}

/* Overlay for guests */
.card .guest-overlay {
  position: absolute;
  top:0; left:0; width:100%; height:100%;
  display:flex; justify-content:center; align-items:center;
  color:white; font-weight:600;
  border-radius:12px;
  text-align:center;
  background: rgba(0,0,0,0.55);
  transition: all 0.3s ease;
}
.card:hover .guest-overlay { background: rgba(0,0,0,0.7); }

/* Footer */
footer {
  background: rgba(0,0,0,0.3);
  color: white;
  text-align: center;
  padding: 18px;
  backdrop-filter: blur(10px);
}
footer p { margin: 0; opacity: 0.85; }

/* Responsive Nav */
@media(max-width:768px) {
  header {
    flex-wrap: wrap;
    padding: 12px 18px;
  }
  nav {
    width: 100%;
    justify-content: center;
    margin-top: 10px;
  }
}
</style>
</head>
<body>

<header>
  <div class="logo" onclick="location.href='index.php'"><i class="fa-solid fa-globe"></i> Crowdfunding</div>
  <nav>
    <a href="about.php">About Us</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </nav>
</header>

<section class="hero">
  <h1>Empowering Dreams, Connecting Futures</h1>
  <p>Discover and support amazing projects that shape the future.</p>
</section>

<main>
  <div class="projects">
    <?php if (empty($projects)): ?>
      <p style="grid-column:1 / -1; text-align:center; color:white; font-size:1.2rem;">
        No approved projects yet. Check back soon!
      </p>
    <?php else: ?>
      <?php foreach ($projects as $p): 
        $is_logged_in = isset($_SESSION['user_id']);
        $image = htmlspecialchars($p['image'] ?? 'https://via.placeholder.com/300x180?text=Project+Image');
        $title = htmlspecialchars($p['title']);
        $desc = htmlspecialchars(substr($p['description'], 0, 80));
        $goal = number_format($p['goal_amount'] ?? 0, 2);
      ?>
      <div class="card" onclick="<?= $is_logged_in ? "location.href='project_view.php?id={$p['id']}'" : "" ?>">
        <img src="<?= $image ?>" alt="<?= $title ?>">
        <?php if (!$is_logged_in): ?>
          <div class="guest-overlay"><i class="fa-solid fa-lock"></i>&nbsp; Login/Register to view</div>
        <?php endif; ?>
        <div class="card-body">
          <h3><?= $title ?></h3>
          <p><?= $desc ?>...</p>
          <p class="goal">Goal: $<?= $goal ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<footer>
  <p>© <?= date("Y") ?> Crowdfunding | Empowering Innovation & Community Growth</p>
</footer>

</body>
</html>
