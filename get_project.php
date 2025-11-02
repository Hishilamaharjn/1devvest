<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$id) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($project ?: []);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>