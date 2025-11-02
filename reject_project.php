<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE projects SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg'] = "Project rejected successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error rejecting project: " . $e->getMessage();
    }
}

header("Location: admin_projects.php");
exit;
?>