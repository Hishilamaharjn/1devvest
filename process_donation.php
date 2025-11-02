<?php
session_start();
require 'db_connect.php'; // ✅ PDO connection

// ✅ Only donors can donate
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$donor_id = $_SESSION['user_id'];
$donor_name = $_SESSION['username'] ?? 'Anonymous';

// ✅ Check if form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $project_id    = $_POST['project_id'];
    $amount        = $_POST['amount'];
    $message       = $_POST['message'] ?? '';
    $anonymous     = isset($_POST['anonymous']) ? 1 : 0;
    $full_name     = $_POST['full_name'] ?? null;
    $email         = $_POST['email'] ?? null;
    $phone         = $_POST['phone'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;

    // ✅ Fetch project name
    $stmt = $pdo->prepare("SELECT project_name, client_id FROM projects WHERE id = :project_id");
    $stmt->execute([':project_id' => $project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        $project_name = $project['project_name'];
        $client_id = $project['client_id'];

        // ✅ Insert donation record (with optional fields)
        $insert = $pdo->prepare("
            INSERT INTO donations 
            (project_id, donor_id, donor_name, project_name, amount, message, anonymous, full_name, email, phone, payment_method)
            VALUES 
            (:project_id, :donor_id, :donor_name, :project_name, :amount, :message, :anonymous, :full_name, :email, :phone, :payment_method)
        ");

        $insert->execute([
            ':project_id' => $project_id,
            ':donor_id' => $donor_id,
            ':donor_name' => $donor_name,
            ':project_name' => $project_name,
            ':amount' => $amount,
            ':message' => $message,
            ':anonymous' => $anonymous,
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone,
            ':payment_method' => $payment_method
        ]);

        // ✅ Update project's collected amount
        $update = $pdo->prepare("UPDATE projects SET collected = collected + :amount WHERE id = :project_id");
        $update->execute([':amount' => $amount, ':project_id' => $project_id]);

        $_SESSION['success'] = "Donation successful!";
        header("Location: donor_dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid project selected.";
        header("Location: donor_dashboard.php");
        exit;
    }
} else {
    header("Location: donor_dashboard.php");
    exit;
}
?>
