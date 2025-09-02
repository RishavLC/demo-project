<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "config.php";
$role = ucfirst($_SESSION["role"]); 
$user_id = $_SESSION["user_id"];
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $role ?> - Auction Bid</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .auction-container {
      max-width: 800px;
      margin: 20px auto;
      padding: 25px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
    }
    .auction-item {
      text-align: center;
      margin-bottom: 20px;
    }
    .auction-item img {
      width: 250px;
      border-radius: 8px;
      margin-bottom: 10px;
    }
    .current-bid {
      font-size: 20px;
      margin: 15px 0;
      color: #4a90e2;
      font-weight: bold;
    }
    .bid-form {
      display: flex;
      justify-content: center;
      gap: 10px;
    }
    .bid-form input {
      padding: 10px;
      width: 150px;
      border: 1px solid #ccc;
      border-radius: 8px;
      text-align: right;
    }
    .bid-form button {
      background: #4a90e2;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
    }
    .bid-form button:hover {
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
      <li><a href="add_record.php">➕ Add Record</a></li>
      <li><a href="auctionbid.php" class="active">💰 Auction Bid</a></li>
      <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="auction-container">
      <div class="auction-item">
        <h2>Antique Vase</h2>
        <img src="assets/sample-item.jpg" alt="Auction Item">
        <p class="current-bid">Current Bid: $150</p>
      </div>

      <form class="bid-form" method="POST" action="place_bid.php">
        <input type="hidden" name="item_id" value="1">
        <input type="number" name="bid_amount" placeholder="Enter your bid" required min="151">
        <button type="submit">Place Bid</button>
      </form>
    </div>
  </div>

  <script src="assets/script.js"></script>
</body>
</html>
