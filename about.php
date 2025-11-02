<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About Us - Crowdfunding</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #007bff, #00aaff);
  margin: 0;
}
header {
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 40px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  position: fixed;
  width: 100%;
  top: 0;
  z-index: 1000;
}
header .logo {
  color: #fff;
  font-weight: 600;
  font-size: 1.5rem;
  cursor: pointer;
}
nav a {
  color: white;
  text-decoration: none;
  margin-left: 25px;
  font-weight: 500;
  transition: 0.3s;
}
nav a:hover {
  color: #00ffd1;
}
.container {
  max-width: 800px;
  background: #fff;
  margin: 120px auto;
  padding: 40px;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
h1 {
  color: #004aad;
  text-align: center;
  margin-bottom: 20px;
}
p {
  font-size: 16px;
  color: #333;
  line-height: 1.6;
}
ul {
  color: #333;
  font-size: 15px;
  margin-left: 20px;
  line-height: 1.6;
}
footer {
  background: rgba(0,0,0,0.3);
  color: white;
  text-align: center;
  padding: 20px;
  margin-top: 40px;
}
</style>
</head>
<body>
<header>
  <div class="logo" onclick="location.href='index.php'">🌐 Crowdfunding</div>
  <nav>
    <a href="index.php">Home</a>
    <a href="about.php" style="color:#00ffd1;">About</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </nav>
</header>

<div class="container">
  <h1>About Crowdfunding Platform</h1>
  <p>
    Welcome to our <strong>Crowdfunding Platform</strong> — a simple web-based system designed to connect dreamers with supporters.  
    Our goal is to help individuals and organizations raise funds for their innovative ideas, social causes, or creative projects.
  </p>

  <p><strong>Here’s what our website offers:</strong></p>
  <ul>
    <li>💡 Users can <strong>create projects</strong> and set funding goals.</li>
    <li>🤝 Supporters can <strong>view and contribute</strong> to listed projects.</li>
    <li>👩‍💼 Admins manage project approvals and monitor activities.</li>
    <li>📈 Transparent and user-friendly interface for both creators and donors.</li>
  </ul>

  <p>
    This platform was developed as a <strong>college project</strong> to demonstrate how crowdfunding systems function.
    It focuses on simplicity, clarity, and usability rather than advanced technologies.
  </p>

  <p style="text-align:center; margin-top:20px;">
    🚀 <strong>Empowering Dreams, Connecting Futures!</strong>
  </p>
</div>

<footer>
  <p>© 2025 Crowdfunding | Created for Educational Purposes</p>
</footer>
</body>
</html>
