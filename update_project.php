<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $goal = floatval($_POST['goal']);
    $user_id = $_SESSION['user_id'];

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
            $image_path = $target_path;
        }
    }

    try {
        $pdo->beginTransaction();
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, goal = ?, image_path = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $description, $goal, $image_path, $id, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, goal = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $description, $goal, $id, $user_id]);
        }
        $pdo->commit();
        $_SESSION['msg'] = "Project updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['msg'] = "Failed to update project: " . $e->getMessage();
        error_log("Update error: " . $e->getMessage());
    }

    header("Location: projects.php");
    exit;
} else {
    header("Location: projects.php");
    exit;
}
?>