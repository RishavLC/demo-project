<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "config.php";
$role = ucfirst($_SESSION["role"]); 
$user_id = $_SESSION["user_id"];

// ✅ Fetch user’s name
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// Fetch only this user's records
$sql = "SELECT * FROM records WHERE user_id = $user_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $role ?> Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <?= $role ?> Panel
      <div class="toggle-btn">☰</div>
    </div>
    <ul>
      <li><a href="dashboard_user.php">🏠 Dashboard</a></li>
      <li><a href="add_record.php">➕ Add Record</a></li>
      <li><a href="auction_bid.php">Place bid</a></li>
      
      <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
     <h2>Welcome, <?= htmlspecialchars($username) ?> 👋</h2>
    <table>
      <tr>
        <th>ID</th><th>Title</th><th>Description</th><th>Action</th>
      </tr>
      <?php while($row = $result->fetch_assoc()) { ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td>
          <a href="edit_record.php?id=<?= $row['id'] ?>" class="btn btn-edit">✏ Edit</a>
          <a href="delete_record.php?id=<?= $row['id'] ?>" class="btn btn-delete">🗑 Delete</a>
        </td>
      </tr>
      <?php } ?>
    </table>
  </div>

  <script src="assets/script.js"></script>
</body>
</html>
