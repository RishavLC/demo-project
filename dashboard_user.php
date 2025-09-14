<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "config.php";

$user_id = $_SESSION["user_id"];

/* 🔹 1. Close expired auctions automatically */
$conn->query("UPDATE auction_items 
              SET status='closed' 
              WHERE status='active' AND end_time <= NOW()");

/* 🔹 Fetch user’s name */
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

/* 🔹 2. Fetch Active Auctions (others only, not expired) */
$active_sql = "SELECT ai.*, u.username AS seller 
               FROM auction_items ai
               JOIN users u ON ai.seller_id = u.id
               WHERE ai.status='active' 
                 AND ai.seller_id != ? 
                 AND ai.end_time > NOW()
               ORDER BY ai.end_time ASC";
$stmt = $conn->prepare($active_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_result = $stmt->get_result();
$stmt->close();

/* 🔹 3. Fetch Closed Auctions (with winners) */
$closed_sql = "SELECT ai.*, u.username AS seller,
               (SELECT b.bidder_id FROM bids b 
                WHERE b.item_id = ai.id 
                ORDER BY b.bid_amount DESC LIMIT 1) AS winner_id,
               (SELECT b.bid_amount FROM bids b 
                WHERE b.item_id = ai.id 
                ORDER BY b.bid_amount DESC LIMIT 1) AS winning_bid
               FROM auction_items ai
               JOIN users u ON ai.seller_id = u.id
               WHERE ai.status='closed'
               ORDER BY ai.end_time DESC";
$closed_result = $conn->query($closed_sql);

if(!$closed_result){
    die("SQL Error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>User Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }
    .card {
      background: #fff;
      padding: 18px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin: 0 0 8px;
      font-size: 18px;
      color: #2c3e50;
    }
    .card p {
      margin: 5px 0;
    }
    .btn {
      display: inline-block;
      padding: 8px 14px;
      margin-top: 8px;
      border-radius: 6px;
      text-decoration: none;
      color: white;
      background: #3498db;
    }
    .btn-disabled {
      background: #7f8c8d;
      cursor: not-allowed;
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
    <li><a href="dashboard_user.php">🏠 Dashboard</a></li>
    <li><a href="add_record.php">➕ Add Record</a></li> 
    <li><a href="add_auction_item.php">📦 Add Auctions Items</a></li>
    <li><a href="auction_bid.php">💰 Place Bids</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Active Auctions</h2>
  <div class="grid">
    <?php while($row = $active_result->fetch_assoc()) { ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><strong>Seller:</strong> <?= htmlspecialchars($row['seller']) ?></p>
      <p><strong>Start Price:</strong> $<?= $row['start_price'] ?></p>
      <p><strong>Ends At:</strong> <?= $row['end_time'] ?></p>
      <a href="auction_bid.php?auction_id=<?= $row['id'] ?>" class="btn">Place Bid</a>
    </div>
    <?php } ?>
  </div>

  <h2 style="margin-top:40px;">Closed Auctions</h2>
  <div class="grid">
    <?php while($row = $closed_result->fetch_assoc()) { 
        $winner_name = "No bids";
        if ($row['winner_id']) {
            $wstmt = $conn->prepare("SELECT username FROM users WHERE id=?");
            $wstmt->bind_param("i", $row['winner_id']);
            $wstmt->execute();
            $wstmt->bind_result($winner_name);
            $wstmt->fetch();
            $wstmt->close();
        }
    ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><strong>Seller:</strong> <?= htmlspecialchars($row['seller']) ?></p>
      <p><strong>Final Price:</strong> <?= $row['winning_bid'] ? "$".$row['winning_bid'] : "N/A" ?></p>
      <p><strong>Winner:</strong> <?= htmlspecialchars($winner_name) ?></p>
      <p><em>Closed on <?= $row['end_time'] ?></em></p>
    </div>
    <?php } ?>
  </div>
</div>

<script src="assets/script.js"></script>
</body>
</html>
