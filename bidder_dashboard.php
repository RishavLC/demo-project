<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "config.php";
$role = ucfirst($_SESSION["role"]); 
$user_id = $_SESSION["user_id"];

// Fetch all auction items
$sql = "SELECT * FROM auction_items"; // replace with your auction items table
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $role ?> - Auction Bid</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table th, table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }
    table th {
      background: #4a90e2;
      color: white;
    }
    .btn-bid {
      background: #4a90e2;
      color: #fff;
      padding: 6px 12px;
      text-decoration: none;
      border-radius: 5px;
    }
    .btn-bid:hover {
      background: #357abd;
    }
  </style>
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
      <li><a href="auctionbid.php" class="active">💰 Auction Bid</a></li>
      <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h2>Auction Items</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Item</th>
        <th>Quantity</th>
        <th>Price (Rs)</th>
        <th>Action</th>
      </tr>
      <?php if($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= $row['quantity'] ?></td>
          <td><?= $row['price'] ?></td>
          <td>
            <a href="place_bid.php?item_id=<?= $row['id'] ?>" class="btn-bid">Place Bid</a>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5">No auction items available.</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <script src="assets/script.js"></script>
</body>
</html>
