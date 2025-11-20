<?php
session_start();
require '../db_connect.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['investment_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$investment_id = intval($_POST['investment_id']);
$status = trim($_POST['status']);

if (!in_array($status, ['approved', 'pending', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update status
    $stmt = $pdo->prepare("UPDATE investor_projects SET status = ? WHERE id = ?");
    $stmt->execute([$status, $investment_id]);

    // If rejected → mark amount as refunded (optional column) and notify
    if ($status === 'rejected') {
        // Optional: Add a refunded_at timestamp
        $pdo->prepare("UPDATE investor_projects SET refunded_at = NOW() WHERE id = ?")->execute([$investment_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Update failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>