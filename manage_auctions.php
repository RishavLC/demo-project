<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

// ✅ Handle approval/rejection
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE auction_items SET status='approved' WHERE id=$id");
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE auction_items SET status='rejected' WHERE id=$id");
}

// ✅ Fetch all items
$sql = "SELECT ai.*, u.username FROM auction_items ai 
        JOIN users u ON ai.seller_id = u.id
        ORDER BY ai.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Auctions</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="sidebar">
  <div class="sidebar-header">Admin Panel</div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Manage Auction Items</h2>
  <table>
    <tr>
      <th>ID</th><th>Title</th><th>Seller</th><th>Status</th><th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['title']) ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= ucfirst($row['status']) ?></td>
      <td>
        <?php if ($row['status'] == 'pending') { ?>
          <a href="?approve=<?= $row['id'] ?>" class="btn btn-approve">✅ Approve</a>
          <a href="?reject=<?= $row['id'] ?>" class="btn btn-reject">❌ Reject</a>
        <?php } else { ?>
          <?= ucfirst($row['status']) ?>
        <?php } ?>
      </td>
    </tr>
    <?php } ?>
  </table>
</div>
</body>
</html>
