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
$sql = "SELECT * FROM auction_items"; // your table name
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $role ?> - Bidder Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .dashboard {
      display: flex;
      gap: 20px;
      padding: 20px;
    }
    .sidebar-left {
      width: 220px;
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .sidebar-left h3 {
      margin-bottom: 10px;
      font-size: 16px;
    }
    .sidebar-left input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin-bottom: 15px;
    }
    .sidebar-left ul {
      list-style: none;
      padding: 0;
    }
    .sidebar-left ul li {
      margin: 8px 0;
      cursor: pointer;
      font-size: 14px;
    }
    .items-section {
      flex: 1;
    }
    .items-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
    }
    .item-card {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 15px;
      text-align: center;
    }
    .item-card img {
      width: 150px;
      height: 120px;
      object-fit: contain;
      margin-bottom: 10px;
    }
    .item-card h4 {
      font-size: 15px;
      margin-bottom: 8px;
    }
    .item-card p {
      font-size: 13px;
      color: #444;
      margin: 3px 0;
    }
    .bid-btn {
      margin-top: 8px;
      display: inline-block;
      padding: 8px 15px;
      background: #4a90e2;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      font-size: 13px;
    }
    .bid-btn:hover {
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
    <div class="dashboard">
      
      <!-- Left filter -->
      <div class="sidebar-left">
        <h3>Search</h3>
        <input type="text" placeholder="Search items...">
        <h3>Browse Categories</h3>
        <ul>
          <li>All Categories</li>
          <li>Electronics</li>
          <li>Fashion</li>
          <li>Health & Beauty</li>
          <li>Home & Living</li>
          <li>Mobiles</li>
          <li>Other</li>
        </ul>
      </div>
      
      <!-- Right items -->
      <div class="items-section">
        <h2>Collections</h2>
        <div class="items-grid">
          <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <div class="item-card">
                <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                <h4><?= htmlspecialchars($row['title']) ?></h4>
                <p><?= $row['quantity'] ?> Qty</p>
                <p>$<?= $row['price'] ?></p>
                <a href="place_bid.php?item_id=<?= $row['id'] ?>" class="bid-btn">Place Bid</a>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p>No auction items available.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <script src="assets/script.js"></script>
</body>
</html>
