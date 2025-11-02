<?php
// Show errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connect.php'; // Must define $pdo (PDO connection)

// Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if file uploaded
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . "/uploads/"; // Absolute path
    $db_path_prefix = "uploads/"; // Relative path for DB

    // Make directory if not exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_tmp  = $_FILES['profile_pic']['tmp_name'];
    $file_name = basename($_FILES['profile_pic']['name']);
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "❌ Only JPG, JPEG, PNG, or GIF images are allowed.";
        header("Location: profile.php");
        exit;
    }

    // Create new filename
    $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
    $target_path = $upload_dir . $new_filename;
    $db_file_path = $db_path_prefix . $new_filename;

    // Fetch and delete old image if exists
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_pic = $stmt->fetchColumn();

    if ($old_pic && $old_pic !== 'uploads/default.png' && file_exists(__DIR__ . "/" . $old_pic)) {
        unlink(__DIR__ . "/" . $old_pic);
    }

    // Move new file
    if (move_uploaded_file($file_tmp, $target_path)) {
        $update = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $update->execute([$db_file_path, $user_id]);

        $_SESSION['success'] = "✅ Profile picture updated successfully!";
    } else {
        $_SESSION['error'] = "⚠️ Failed to upload image.";
    }
} else {
    $_SESSION['error'] = "⚠️ No file selected or upload error.";
}

header("Location: profile.php");
exit;
?>
