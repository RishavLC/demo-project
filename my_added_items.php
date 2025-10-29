<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all items the user has added
$sql = "
  SELECT ai.*, 
         (SELECT MAX(bid_amount) FROM bids WHERE bids.item_id = ai.id) AS highest_bid,
         (SELECT COUNT(*) FROM bids WHERE bids.item_id = ai.id) AS total_bids
  FROM auction_items ai
  WHERE ai.seller_id = ?
  ORDER BY ai.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Added Items</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 0;
    }

    .container {
      margin-left: 230px;
      padding: 25px;
    }

    h2 {
      color: #333;
      margin-bottom: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      overflow: hidden;
    }

    th, td {
      padding: 12px 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }

    th {
      background: #3498db;
      color: white;
      font-weight: 600;
    }

    tr:hover {
      background: #f1f1f1;
    }

    .status-active {
      color: #27ae60;
      font-weight: bold;
    }

    .status-closed {
      color: #e74c3c;
      font-weight: bold;
    }

    .no-record {
      text-align: center;
      padding: 20px;
      font-size: 16px;
      color: #888;
    }

    .back-btn {
      display: inline-block;
      background: #3498db;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      margin-bottom: 15px;
    }

    .back-btn:hover {
      background: #2980b9;
    }
  </style>
</head>
<body>
    <div class="sidebar">
  <div class="sidebar-header">
    Welcome, <?= htmlspecialchars($username) ?>
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_user.php" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li>
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">💰 <span>Place Bids</span></a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>
  <div class="container">
    <h2>My Added Auction Items</h2>

    <?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Item Title</th>
          <th>Category</th>
          <th>Starting Price</th>
          <th>Highest Bid</th>
          <th>Total Bids</th>
          <th>Status</th>
          <th>End Date</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $sn = 1;
        while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['category']) ?></td>
          <td>$<?= number_format($row['start_price'], 2) ?></td>
          <td>$<?= $row['highest_bid'] ? number_format($row['highest_bid'], 2) : '-' ?></td>
          <td><?= $row['total_bids'] ?></td>
          <td class="<?= $row['status'] === 'active' ? 'status-active' : 'status-closed' ?>">
            <?= ucfirst($row['status']) ?>
          </td>
          <td><?= htmlspecialchars($row['end_time']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-record">No items added yet.</div>
    <?php endif; ?>
  </div>
</body>
</html>
