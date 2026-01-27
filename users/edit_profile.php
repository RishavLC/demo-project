<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD']=="POST") {

  if (!empty($_FILES['photo']['name'])) {
    $photo = time().$_FILES['photo']['name'];
    move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/".$photo);
    $conn->query("UPDATE users SET photo='$photo' WHERE id=$user_id");
  }

  $stmt = $conn->prepare("
    UPDATE users SET email=?, phone=?, address=?, citizenship_no=?, nic_no=?
    WHERE id=?
  ");
  $stmt->bind_param(
    "sssssi",
    $_POST['email'],
    $_POST['phone'],
    $_POST['address'],
    $_POST['citizenship_no'],
    $_POST['nic_no'],
    $user_id
  );
  $stmt->execute();

  header("Location: profile.php");
  exit();
}

$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Profile</title>
<style>
body {
  font-family: Arial, sans-serif;
  background:#f4f6f9;
  margin:0;
}

.edit-container {
  max-width:800px;
  margin:40px auto;
  background:#fff;
  border-radius:10px;
  box-shadow:0 6px 15px rgba(0,0,0,0.1);
  padding:30px;
}

.edit-container h2 {
  margin-bottom:20px;
  color:#162d44;
}

.form-group {
  margin-bottom:15px;
}

.form-group label {
  font-size:13px;
  color:#555;
  display:block;
  margin-bottom:5px;
}

.form-group input {
  width:100%;
  padding:10px;
  border:1px solid #ccc;
  border-radius:6px;
}

.form-actions {
  display:flex;
  justify-content:space-between;
  margin-top:20px;
}

.form-actions button {
  background:#27ae60;
  color:#fff;
  border:none;
  padding:10px 25px;
  border-radius:6px;
  cursor:pointer;
}

.form-actions a {
  background:#7f8c8d;
  color:#fff;
  padding:10px 20px;
  text-decoration:none;
  border-radius:6px;
}
</style>
</head>
<body>

<div class="edit-container">
  <h2>Edit Profile</h2>

  <form method="post" enctype="multipart/form-data">

    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= $user['email'] ?>">
    </div>

    <div class="form-group">
      <label>Phone</label>
      <input type="text" name="phone" value="<?= $user['phone'] ?>">
    </div>

    <div class="form-group">
      <label>Address</label>
      <input type="text" name="address" value="<?= $user['address'] ?>">
    </div>

    <div class="form-group">
      <label>Citizenship No</label>
      <input type="text" name="citizenship_no" value="<?= $user['citizenship_no'] ?>">
    </div>

    <div class="form-group">
      <label>NIC No</label>
      <input type="text" name="nic_no" value="<?= $user['nic_no'] ?>">
    </div>

    <div class="form-group">
      <label>Profile Photo</label>
      <input type="file" name="photo">
    </div>

    <div class="form-actions">
      <a href="profile.php">Cancel</a>
      <button type="submit">Update Profile</button>
    </div>

  </form>
</div>

</body>
</html>
