<?php    
session_start();
require 'db_connect.php';

// ensure only client
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = 'client';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $upload_dir = __DIR__ . "/uploads/";
    $db_path_prefix = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_tmp = $_FILES['profile_pic']['tmp_name'];
    $file_name = basename($_FILES['profile_pic']['name']);
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];

    if (in_array($ext, $allowed)) {
        $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
        $target_path = $upload_dir . $new_filename;
        $db_file_path = $db_path_prefix . $new_filename;

        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old_pic = $stmt->fetchColumn();

        if ($old_pic && $old_pic !== 'uploads/default.png' && file_exists(__DIR__ . "/" . $old_pic)) {
            unlink(__DIR__ . "/" . $old_pic);
        }

        if (move_uploaded_file($file_tmp, $target_path)) {
            $update = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $update->execute([$db_file_path, $user_id]);
            $_SESSION['profile_pic'] = $db_file_path;
            $_SESSION['success'] = "✅ Profile picture updated successfully!";
        } else {
            $_SESSION['error'] = "⚠️ Failed to upload image.";
        }
    } else {
        $_SESSION['error'] = "❌ Only JPG, JPEG, PNG, or GIF allowed.";
    }

    header("Location: client_profile.php");
    exit;
}

// fetch data
try {
    $stmt = $pdo->prepare("SELECT username, email, country, phone, education, profile_pic, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $username = $user['username'] ?? 'User';
    $email = $user['email'] ?? '';
    $country = $user['country'] ?? '';
    $phone = $user['phone'] ?? '';
    $education = $user['education'] ?? '';
    $created_at = $user['created_at'] ?? '';
    $profile_pic = (!empty($user['profile_pic']) && file_exists(__DIR__ . "/" . $user['profile_pic']))
        ? $user['profile_pic']
        : "uploads/default.png";
    $_SESSION['profile_pic'] = $profile_pic;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Client Profile | Crowdfunding</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
<style>
/* same CSS (identical to admin_profile) */
:root{ --primary:#3b82f6; --secondary:#2563eb; --bg:#f3f4f6; --text-dark:#1e293b; --text-light:#64748b; --white:#ffffff; }
*{box-sizing:border-box;margin:0;padding:0}
body{ font-family:'Poppins',sans-serif; background:var(--bg); display:flex; min-height:100vh; overflow-x:hidden; }
.sidebar{ width:250px; background:linear-gradient(180deg,#3b82f6,#2563eb); color:white; display:flex; flex-direction:column; justify-content:space-between; padding:25px 20px; position:fixed; top:0; left:0; height:100vh; box-shadow:6px 0 15px rgba(0,0,0,0.1); border-radius:0 20px 20px 0; z-index:100; }
.sidebar .logo{ font-size:20px; font-weight:700; text-align:center; margin-bottom:40px; }
.sidebar a{ color:rgba(255,255,255,0.9); text-decoration:none; display:flex; align-items:center; gap:12px; padding:12px 15px; border-radius:12px; margin-bottom:10px; transition:all .3s ease; font-size:15px; }
.sidebar a i{width:25px;text-align:center}
.sidebar a:hover, .sidebar a.active{ background:rgba(255,255,255,0.25); transform:translateX(5px); }
.main{ margin-left:270px; padding:50px; width:calc(100% - 270px); display:flex; justify-content:center; }
.profile-container{ background:var(--white); border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.08); padding:50px 60px; width:100%; max-width:850px; }
.profile-header{text-align:center;margin-bottom:35px}
.profile-header img{ width:130px;height:130px;border-radius:50%; border:4px solid var(--primary);object-fit:cover; box-shadow:0 6px 25px rgba(37,99,235,0.3);margin-bottom:20px; }
.profile-header h2{margin-top:8px;color:var(--primary);font-size:24px}
.profile-header p{color:var(--text-light);margin-top:4px;font-size:15px}
.profile-info{ background:#f9fafb;padding:25px 35px;border-radius:12px;margin-bottom:25px; }
.profile-info h4{color:var(--text-dark);margin-bottom:20px;font-weight:600}
.profile-info .row{display:grid;grid-template-columns:1fr 1fr;gap:15px 30px}
.profile-info label{font-weight:500;color:var(--text-dark);font-size:14px}
.profile-info span{color:var(--text-light);font-size:14px}
.update-section{ background:#f9fafb;padding:25px 35px;border-radius:12px;text-align:center }
.update-section h5{margin-bottom:15px;color:var(--text-dark);font-weight:600}
input[type="file"]{padding:8px;background:#fff;border:1px solid #ccc;border-radius:10px;width:100%}
.btn{background:var(--primary);color:white;padding:10px 25px;border-radius:8px;border:none;cursor:pointer;font-weight:500;margin-top:15px;transition:.25s}
.btn:hover{background:var(--secondary);transform:translateY(-2px)}
.success{color:green;font-weight:500;margin-bottom:10px}
.error{color:red;font-weight:500;margin-bottom:10px}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div>
    <div class="logo"><i class="fa-solid fa-globe"></i> Crowdfunding</div>

    <a href="client_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="create_project.php"><i class="fa-solid fa-circle-plus"></i> Create Project</a>
    <a href="client_project.php"><i class="fa-solid fa-folder"></i> My Projects</a>
    <a href="client_donations.php"><i class="fa-solid fa-hand-holding-dollar"></i> My Donations</a>

    <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
  </div>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Main -->
<div class="main">
  <div class="profile-container">
    <div class="profile-header">
      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture">
      <h2>@<?= htmlspecialchars($username) ?></h2>
      <p><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($email) ?></p>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
      <p class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php elseif(isset($_SESSION['error'])): ?>
      <p class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <div class="profile-info">
      <h4>Profile Details</h4>
      <div class="row">
        <div><label>Country:</label><br><span><?= htmlspecialchars($country ?: 'N/A') ?></span></div>
        <div><label>Phone:</label><br><span><?= htmlspecialchars($phone ?: 'N/A') ?></span></div>
        <div><label>Education:</label><br><span><?= htmlspecialchars($education ?: 'N/A') ?></span></div>
        <div><label>Joined:</label><br><span><?= $created_at ? date("F d, Y", strtotime($created_at)) : 'N/A' ?></span></div>
        <div><label>Role:</label><br><span><?= ucfirst($role) ?></span></div>
      </div>

      <form action="edit_profile.php" method="get" style="margin-top:18px;">
        <button class="btn" type="submit"><i class="fa-solid fa-pen"></i> Edit Info</button>
      </form>
    </div>

    <div class="update-section">
      <h5>Update Profile Picture</h5>
      <form action="client_profile.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="profile_pic" accept="image/*" required>
        <button type="submit" class="btn"><i class="fa-solid fa-upload"></i> Upload</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
