<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";
$role = ucfirst($_SESSION["role"]); 

// Fetch all records (admin sees all records)
$sql = "SELECT records.id, records.title, records.description, users.username 
        FROM records 
        JOIN users ON records.user_id = users.id";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= $role ?> Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="sidebar">
  <div class="sidebar-header">
    <?= $role ?> Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="add_record.php">➕ Add New Record</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Records</h2>

  <table>
    <tr>
      <th>ID</th><th>Title</th><th>Description</th><th>User</th><th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['title']) ?></td>
      <td><?= htmlspecialchars($row['description']) ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
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
