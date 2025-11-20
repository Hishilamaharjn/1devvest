<?php 
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$project_id = $_POST['project_id'];
$is_featured = isset($_POST['is_featured']) ? 1 : 0;

$stmt = $pdo->prepare("UPDATE projects SET is_featured=? WHERE id=?");
$stmt->execute([$is_featured, $project_id]);

header("Location: projects.php");
exit;
