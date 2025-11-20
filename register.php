<?php
session_start();
require 'db_connect.php';
include_once 'header.php'; // logo + nav

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'] ?? 'investor';

    $min_len = 8;
    $max_len = 20;

    if ($username && $email && $password && $confirm_password) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "Please enter a valid email address.";
        } elseif (strlen($password) < $min_len) {
            $msg = "Password must be at least $min_len characters.";
        } elseif (strlen($password) > $max_len) {
            $msg = "Password must not exceed $max_len characters.";
        } elseif ($password !== $confirm_password) {
            $msg = "Passwords do not match.";
        } else {

            // ============ NEW RULE: Max 2 accounts per email (1 Investor + 1 Client) ============
            $checkEmail = $pdo->prepare("SELECT role FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            $existingRoles = $checkEmail->fetchAll(PDO::FETCH_COLUMN);

            if (count($existingRoles) >= 2) {
                $msg = "This email has already been used. You cannot register again.";
            }
            elseif (count($existingRoles) == 1) {
                $alreadyRole = $existingRoles[0];
                if ($role === $alreadyRole) {
                    $msg = "This email is already registered as " . ucfirst($alreadyRole) . ". You cannot create another $alreadyRole account.";
                }
            }

            if (!$msg) {
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $checkUser->execute([$username]);
                if ($checkUser->rowCount() > 0) {
                    $msg = "This username is already taken.";
                }
            }

            if (!$msg) {
                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username,email,password,role,status) VALUES (?,?,?,?,?)");
                    $stmt->execute([$username, $email, $hashed, $role, 'active']);

                    header("Location: login.php");
                    exit;

                } catch (PDOException $e) {
                    $msg = "Registration failed. Try again.";
                }
            }
        }

    } else {
        $msg = "Please fill all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Register - DevVest</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">

<style>
body.auth-page { font-family: 'Poppins', sans-serif; background:#eef2f7; margin:0; padding:0; }
.form-container { max-width:400px; margin:120px auto 50px auto; padding:30px; background:#fff; border-radius:10px; box-shadow:0 0 12px rgba(0,0,0,0.1); }
.form-container h2 { text-align:center; margin-bottom:20px; color:#333; }
.form-container input, .form-container select { width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1px solid #ccc; font-size:15px; }
button { width:100%; padding:10px; border:none; border-radius:6px; background:#3B4BFF; color:#fff; font-size:16px; cursor:pointer; }
button:hover { opacity:0.9; }
.link { display:block; margin-top:15px; text-align:center; color:#2563eb; }
.error { background:#ffe5e5; padding:10px; border-radius:6px; border-left:4px solid red; text-align:center; color:#b30000; margin-bottom:15px; }
.password-wrapper { position:relative; }
.toggle-password { position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer; font-size:18px; }
</style>
</head>
<body class="auth-page">

<div class="form-container">
    <h2>Create Account</h2>

    <?php if($msg): ?>
        <div class="error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Enter a valid email (example@gmail.com)">

        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Password" required minlength="8" maxlength="20">
            <span class="toggle-password" onclick="togglePass()">👁️</span>
        </div>

        <div class="password-wrapper">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required minlength="8" maxlength="20">
            <span class="toggle-password" onclick="toggleConfirm()">👁️</span>
        </div>

        <select name="role" required>
            <option value="investor">Investor</option>
            <option value="client">Client</option>
        </select>

        <button type="submit">Register</button>
    </form>

    <a href="login.php" class="link">Already have an account? Login</a>
</div>

<script>
function togglePass() {
    let input = document.getElementById("password");
    const icon = document.querySelectorAll(".toggle-password")[0];
    input.type = input.type === "password" ? "text" : "password";
    icon.textContent = input.type === "password" ? "👁️" : "🙈";
}
function toggleConfirm() {
    let input = document.getElementById("confirm_password");
    const icon = document.querySelectorAll(".toggle-password")[1];
    input.type = input.type === "password" ? "text" : "password";
    icon.textContent = input.type === "password" ? "👁️" : "🙈";
}
</script>

<!-- Same professional footer as about.php & login.php -->
<?php include 'footer.php'; ?>

</body>
</html>