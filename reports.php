<?php
include('db_connect.php');

// Fetch project summary
$projects = $conn->query("SELECT id, name, description, created_at FROM projects");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">Project Reports</h2>

    <table class="table table-striped table-bordered">
        <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Project Name</th>
            <th>Description</th>
            <th>Created At</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $projects->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="text-end">
        <a href="generate_report.php" class="btn btn-success">Download PDF</a>
        <a href="dashboard.php" class="btn btn-secondary">← Back</a>
    </div>
</div>
</body>
</html>
