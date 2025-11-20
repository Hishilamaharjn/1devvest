<?php
session_start();
require '../db_connect.php';

// ✅ Only investors allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'investor') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: invest.php");
    exit;
}

$investor_id = $_SESSION['user_id'];
$investor_name = $_SESSION['username'] ?? 'Investor';
$project_id = $_POST['project_id'];
$amount = $_POST['amount'];
$message = $_POST['message'] ?? '';

// ✅ Fetch project
$stmt = $pdo->prepare("SELECT collected FROM projects WHERE id=?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "Invalid project selected.";
    header("Location: invest.php");
    exit;
}

// ✅ Insert investment record
$insert = $pdo->prepare("
    INSERT INTO investor_investments (investor_id, project_id, amount, message, created_at)
    VALUES (:investor_id, :project_id, :amount, :message, NOW())
");
$insert->execute([
    ':investor_id' => $investor_id,
    ':project_id' => $project_id,
    ':amount' => $amount,
    ':message' => $message
]);

// ✅ Update project collected amount
$update = $pdo->prepare("UPDATE projects SET collected = collected + :amount WHERE id = :project_id");
$update->execute([':amount' => $amount, ':project_id' => $project_id]);

$_SESSION['success'] = "Investment successful!";
header("Location: investor_history.php");
exit;
?>
