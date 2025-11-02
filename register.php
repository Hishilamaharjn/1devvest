<?php 
session_start();
require 'db_connect.php'; // Uses $pdo

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'] ?? 'client';

    if ($username === '' || $email === '' || $password === '' || $confirm_password === '') {
        $msg = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $msg = 'Passwords do not match.';
    } else {
        // Strong password validation
        if (!preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[!@#\$%\^&\*\(\)_\+\-=\[\]{};:"\\|,.<>\/?]/', $password) ||
            strlen($password) < 6) {
            $msg = 'Password must include uppercase, lowercase, number, and special character.';
        } else {
            // Check if username/email exists
            $check = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
            $check->execute(['username' => $username, 'email' => $email]);
            if ($check->fetch()) {
                $msg = 'Username or Email already exists.';
            } else {
                // ✅ Corrected: matches your table column `profile_pic`
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (username, email, password, role, profile_pic) 
                                         VALUES (:username, :email, :password, :role, :pic)");
                $insert->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashed,
                    'role' => strtolower($role),
                    'pic' => 'uploads/default.png'
                ]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = strtolower($role);
                $_SESSION['profile_pic'] = 'uploads/default.png';

                // ✅ Redirect based on role
                if ($role === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($role === 'client') {
                    header("Location: client_dashboard.php");
                } elseif ($role === 'donor') {
                    header("Location: donor_dashboard.php");
                }
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — Crowdfunding</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<style>
:root{--blue1:#007bff;--blue2:#00aaff;--card:#fff;}
*{box-sizing:border-box;}
body{margin:0;font-family:Poppins,Arial,sans-serif;background:linear-gradient(135deg,var(--blue2),var(--blue1));height:100vh;display:flex;align-items:center;justify-content:center;}
.card{width:380px;background:var(--card);border-radius:14px;padding:30px;box-shadow:0 8px 30px rgba(0,0,0,0.12);text-align:center;}
h2{margin:0 0 18px;color:#0b3a66;}
input,select{width:100%;padding:11px;margin:8px 0;border:1px solid #ddd;border-radius:8px;font-size:14px;}
button{width:100%;background:var(--blue1);color:#fff;border:0;padding:11px;border-radius:8px;font-weight:600;margin-top:12px;cursor:pointer;transition:0.3s;}
button:hover{opacity:0.9;}
.error{color:#b90000;margin-bottom:10px;}
.link{color:#004aad;text-decoration:underline;}
.small{font-size:14px;color:#333;margin-top:12px;}
.toggle-btn{position:absolute;right:10px;top:12px;cursor:pointer;color:#007bff;}
.strength-wrap{margin-top:8px;}
.strength-bar{width:100%;height:10px;background:#eee;border-radius:6px;overflow:hidden;margin-top:6px;}
.strength-fill{height:100%;width:0%;background:linear-gradient(90deg,#f44336,#ff9800,#4caf50);transition:width 0.25s ease;}
.strength-label{margin-top:8px;font-weight:600;text-align:left;}
.char-count{font-size:13px;color:#555;margin-top:4px;text-align:right;}
</style>
</head>
<body>
<div class="card">
<h2>Register</h2>
<?php if($msg): ?><div class="error"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="post" action="" id="regForm">
    <input type="text" name="username" placeholder="Username" required maxlength="30" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    <input type="email" name="email" placeholder="Email" required maxlength="50" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

    <div style="position:relative;">
        <input type="password" id="password" name="password" placeholder="Password" required minlength="6" maxlength="20">
        <span id="togglePass" class="toggle-btn" onclick="toggle('password','togglePass')">👁️</span>
    </div>

    <div class="strength-wrap">
        <div class="strength-bar"><div id="strengthFill" class="strength-fill"></div></div>
        <div id="strengthLabel" class="strength-label">Strength: —</div>
        <div id="charCount" class="char-count">0 / 20</div>
    </div>

    <div style="position:relative;">
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required minlength="6" maxlength="20">
        <span id="toggleConfirm" class="toggle-btn" onclick="toggle('confirm_password','toggleConfirm')">👁️</span>
    </div>

    <select name="role" required>
        <option value="client" <?= (($_POST['role'] ?? '') === 'client') ? 'selected' : '' ?>>Client</option>
        <option value="donor" <?= (($_POST['role'] ?? '') === 'donor') ? 'selected' : '' ?>>Donor</option>
        <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
    </select>

    <button type="submit">Register</button>
    <p class="small">Already have an account? <a class="link" href="login.php">Login</a></p>
</form>
</div>

<script>
function toggle(fieldId, toggleId){
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(toggleId);
    if(field.type === 'password'){ field.type='text'; icon.textContent='🙈'; } 
    else { field.type='password'; icon.textContent='👁️'; }
}

// Password strength and char count
const pwd = document.getElementById('password');
const fill = document.getElementById('strengthFill');
const label = document.getElementById('strengthLabel');
const charCount = document.getElementById('charCount');

pwd.addEventListener('input', function(){
    const val = pwd.value;
    charCount.textContent = val.length + ' / 20';

    let score=0;
    if(/[a-z]/.test(val)) score++;
    if(/[A-Z]/.test(val)) score++;
    if(/[0-9]/.test(val)) score++;
    if(/[!@#\$%\^&\*\(\)_\+\-=\[\]{};:"\\|,.<>\/?]/.test(val)) score++;
    if(val.length>=6) score++;

    fill.style.width = (score/5*100)+'%';
    if(score<=2) label.textContent='Strength: Weak';
    else if(score===3) label.textContent='Strength: Medium';
    else label.textContent='Strength: Strong';
});
</script>
</body>
</html>
