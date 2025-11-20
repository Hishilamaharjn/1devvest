<?php
session_start();
require 'db_connect.php';
include 'header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username_email = trim($_POST['username_email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username_email && $password) {

        // Try login by username first
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If not found → try by email (supports Investor + Client with same email)
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$username_email]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($users) === 0) {
                $msg = "User not found.";
            } else {
                foreach ($users as $u) {
                    if (password_verify($password, $u['password'])) {
                        $user = $u;
                        break;
                    }
                }
                if (!$user) $msg = "Invalid password.";
            }
        } else {
            if (!password_verify($password, $user['password'])) {
                $msg = "Invalid password.";
                $user = false;
            }
        }

        // Successful login
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            if ($user['role'] === 'admin') {
                header("Location: admin/admin_dashboard.php");
            } elseif ($user['role'] === 'client') {
                header("Location: client/client_dashboard.php");
            } else {
                header("Location: investor/investor_dashboard.php");
            }
            exit;
        }
    } else {
        $msg = "Please enter username/email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - DevVest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        html, body {margin:0;padding:0;min-height:100vh;display:flex;flex-direction:column;background:#eef2f7;font-family:'Poppins',sans-serif;}
        .form-container {
            max-width:450px;margin:100px auto 60px;padding:40px 35px;background:#fff;border-radius:12px;
            box-shadow:0 0 15px rgba(0,0,0,0.12);flex:1 0 auto;
        }
        .form-container h2 {text-align:center;margin-bottom:25px;color:#333;font-size:28px;}
        .form-container input {width:100%;padding:12px;margin-bottom:18px;border-radius:6px;border:1px solid #ccc;font-size:16px;}
        button {width:100%;padding:12px;background:#3B4BFF;color:#fff;border:none;border-radius:6px;font-size:17px;cursor:pointer;}
        button:hover {opacity:0.9;}
        .link {display:block;margin-top:15px;text-align:center;color:#2563eb;}
        .password-wrapper {position:relative;}
        .toggle-password {
            position:absolute;right:10px;top:50%;transform:translateY(-50%);
            cursor:pointer;font-size:20px;user-select:none;
        }
        .error-msg {text-align:center;color:red;margin-bottom:15px;font-weight:500;}
    </style>
</head>
<body>

<div class="form-container">
    <h2>Login</h2>

    <?php if(!empty($msg)): ?>
        <p class="error-msg"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username_email" placeholder="Username or Email" required>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <button type="submit">Login</button>
    </form>

    <a href="register.php" class="link">Don't have an account? Register</a>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById("password");
    const icon = document.querySelector(".toggle-password");
    if (pass.type === "password") {
        pass.type = "text";
        icon.textContent = "🙈";
    } else {
        pass.type = "password";
        icon.textContent = "👁️";
    }
}
</script>

<!-- Same footer as about.php – now connected via footer.php -->
<?php include 'footer.php'; ?>

</body>
</html>