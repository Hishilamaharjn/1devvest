<?php
session_start();
require 'db_connect.php';

// ✅ Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: index.php'); 
    exit; 
}

// ✅ CSRF protection
$csrf = $_POST['csrf_token'] ?? '';
if ($csrf !== ($_SESSION['csrf_token'] ?? '')) { 
    die('Invalid CSRF token'); 
}

$project_id = (int)($_POST['project_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0.0);

// Default donor name
$donor_name = trim($_POST['donor_name'] ?? 'Anonymous');

// ✅ Identify donor if logged in
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? null;

// ✅ Optional: Restrict to donors only (comment out if you want everyone to donate)
if (!$user_id || $role !== 'donor') {
    // You can allow others by removing this block if desired
    // die('Only registered donors can make a contribution.');
}

// ✅ Use username if donor is logged in
if ($role === 'donor' && !empty($username)) {
    $donor_name = $username;
}

if ($project_id <= 0 || $amount <= 0) { 
    $_SESSION['msg'] = 'Invalid donation details'; 
    header('Location: index.php'); 
    exit; 
}

try {
    $pdo->beginTransaction();

    // ✅ Add donation record (include donor_id if available)
    if ($user_id) {
        $stmt = $pdo->prepare("INSERT INTO donations (project_id, donor_id, donor_name, amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$project_id, $user_id, $donor_name, $amount]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO donations (project_id, donor_name, amount) VALUES (?, ?, ?)");
        $stmt->execute([$project_id, $donor_name, $amount]);
    }

    // ✅ Update project stats
    $stmt = $pdo->prepare("UPDATE projects SET collected = collected + ?, total_donors = total_donors + 1 WHERE id = ?");
    $stmt->execute([$amount, $project_id]);

    $pdo->commit();
    $_SESSION['msg'] = '🎉 Thanks for donating! Your contribution has been recorded.';
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['msg'] = 'Donation failed. Please try again.';
    error_log("Donation error: " . $e->getMessage());
}

header('Location: index.php');
exit;
?>
