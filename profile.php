<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? '';

switch ($role) {
    case 'admin':
        header("Location: admin_profile.php");
        break;
    case 'client':
        header("Location: client_profile.php");
        break;
    case 'donor':
        header("Location: donor_profile.php");
        break;
    default:
        header("Location: login.php");
        break;
}
exit;
