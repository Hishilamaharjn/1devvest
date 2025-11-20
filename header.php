<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pages = ['index.php', 'login.php', 'register.php', 'about.php'];

// Show header only in these pages
$show_header = in_array($current_page, $allowed_pages);
?>

<?php if ($show_header): ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DevVest</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>

<body>

<header class="navbar">
    <div class="logo" onclick="location.href='index.php'">
        <i class="fa-solid fa-lightbulb"></i> DevVest
    </div>

    <nav>
        <a class="btn-nav" href="about.php">About Us</a>
        <a class="btn-nav" href="login.php">Login</a>
        <a class="btn-nav" href="register.php">Register</a>
    </nav>
</header>

<?php endif; ?>
