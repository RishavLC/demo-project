<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

// Approve or reject action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == "approve") {
        $conn->query("UPDATE auction_items SET status='approved' WHERE id=$id");
    } elseif ($action == "reject") {
        $conn->query("UPDATE auction_items SET status='rejected' WHERE id=$id");
    }
}

// Fetch all pending auction items
$sql = "SELECT auction_items.*, users.username 
        FROM auction_items 
        JOIN users ON auction_items.seller_id = users.id
        WHERE auction_items.status='pending'";
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
  <div class="sidebar-header">
    Admin Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Pending Auction Items</h2>

  <table>
    <tr>
      <th>ID</th><th>Title</th><th>Seller</th><th>Start Price</th><th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['title']) ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= $row['start_price'] ?></td>
      <td>
        <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-edit">✅ Approve</a>
        <a href="?action=reject&id=<?= $row['id'] ?>" class="btn btn-delete">❌ Reject</a>
      </td>
    </tr>
    <?php } ?>
  </table>
</div>
</body>
</html>
