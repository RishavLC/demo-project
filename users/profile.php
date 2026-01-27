<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$photo = $user['photo'] ? "../uploads/".$user['photo'] : "../assets/default-user.png";
?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<style>
body {
  font-family: Arial, sans-serif;
  background:#f4f6f9;
  margin:0;
}

.profile-container {
  max-width:900px;
  margin:40px auto;
  background:#fff;
  border-radius:10px;
  box-shadow:0 6px 15px rgba(0,0,0,0.1);
  overflow:hidden;
}

.profile-header {
  background:#162d44;
  color:#fff;
  padding:30px;
  display:flex;
  align-items:center;
  gap:20px;
}

.profile-header img {
  width:120px;
  height:120px;
  border-radius:50%;
  object-fit:cover;
  border:4px solid #fff;
}

.profile-body {
  padding:30px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}

.profile-item {
  background:#f8f9fb;
  padding:15px;
  border-radius:8px;
}

.profile-item label {
  font-size:12px;
  color:#777;
  display:block;
}

.profile-item span {
  font-size:16px;
  font-weight:600;
}

.profile-actions {
  padding:20px;
  text-align:right;
}

.profile-actions a {
  background:#3498db;
  color:#fff;
  padding:10px 20px;
  text-decoration:none;
  border-radius:6px;
}
</style>
</head>
<body>

<div class="profile-container">

  <div class="profile-header">
    <img src="<?= $photo ?>">
    <div>
      <h2><?= htmlspecialchars($user['username']) ?></h2>
      <p><?= htmlspecialchars($user['email']) ?></p>
      <p>Status: <b><?= $user['status'] ?></b></p>
    </div>
  </div>

  <div class="profile-body">
    <div class="profile-item"><label>Phone</label><span><?= $user['phone'] ?></span></div>
    <div class="profile-item"><label>Address</label><span><?= $user['address'] ?></span></div>
    <div class="profile-item"><label>Citizenship No</label><span><?= $user['citizenship_no'] ?></span></div>
    <div class="profile-item"><label>NIC No</label><span><?= $user['nic_no'] ?></span></div>
  </div>

  <div class="profile-actions">
    <a href="edit_profile.php">Edit Profile</a>
  </div>

</div>

</body>
</html>
