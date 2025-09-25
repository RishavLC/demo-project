<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

include "config.php";

$user_id = $_SESSION["user_id"];

// Fetch all notifications for the user
$sql = "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Notifications</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      padding: 20px;
    }
    .container {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
    h2 {
      margin-bottom: 20px;
    }
    .notification {
      padding: 12px;
      border-bottom: 1px solid #ddd;
    }
    .notification.unread {
      background: #f0f8ff;
      font-weight: bold;
    }
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 16px;
      background: #3498db;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>My Notifications</h2>
    <?php if ($result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) { ?>
        <div class="notification <?= $row['is_read'] ? '' : 'unread' ?>">
          <?= htmlspecialchars($row['message']) ?>
          <br><small><?= $row['created_at'] ?></small>
        </div>
    <?php } 
      } else { ?>
      <p>No notifications yet.</p>
    <?php } ?>
    
    <a href="mark_notifications.php" class="back-btn">Mark All as Read</a>
    <a href="dashboard_user.php" class="back-btn">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
