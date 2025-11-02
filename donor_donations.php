<?php
session_start();
require 'db_connect.php'; // PDO connection

// ✅ Ensure donor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$donor_name = $_SESSION['username'] ?? 'Donor';
$date = date("l, F d, Y");

// ✅ Fetch all donations made by this donor with project details
$stmt = $pdo->prepare("
    SELECT 
        d.id,
        d.amount,
        d.created_at,
        d.message,
        p.title AS project_title,
        p.goal,
        p.collected
    FROM donations d
    JOIN projects p ON d.project_id = p.id
    WHERE d.donor_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Donations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f6fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 15px;
            padding: 25px 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background: #4a4ae8;
            color: white;
        }
        tr:nth-child(even) {
            background: #f8f8ff;
        }
        tr:hover {
            background: #f0f0ff;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 15px;
            background: #4a4ae8;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
        }
        .back-btn:hover {
            background: #3333cc;
        }
        .empty {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="donor_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    <h2>My Donations</h2>

    <?php if (count($donations) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Project Title</th>
                <th>Amount (Rs.)</th>
                <th>Message</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($donations as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['project_title']) ?></td>
                <td>Rs. <?= number_format($d['amount'], 2) ?></td>
                <td><?= htmlspecialchars($d['message'] ?: '-') ?></td>
                <td><?= date("F d, Y", strtotime($d['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="empty">You haven’t made any donations yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
