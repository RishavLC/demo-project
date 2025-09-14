<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

// ✅ Handle approval
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve"])) {
    $id = intval($_POST["id"]);
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    $stmt = $conn->prepare("UPDATE auction_items 
        SET status='active', start_time=?, end_time=? 
        WHERE id=?");
    $stmt->bind_param("ssi", $start_time, $end_time, $id);
    $stmt->execute();
}

// ✅ Handle rejection
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE auction_items SET status='rejected' WHERE id=$id");
}

// ✅ Fetch all auction items with seller name
$sql = "SELECT ai.*, u.username 
        FROM auction_items ai 
        JOIN users u ON ai.seller_id = u.id
        ORDER BY ai.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Auctions</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
    }
    .card {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin: 0 0 10px;
      font-size: 20px;
      color: #2c3e50;
    }
    .card p {
      margin: 5px 0;
    }
    .actions {
      margin-top: 15px;
    }
    input[type="datetime-local"] {
      padding: 6px;
      margin: 5px 0;
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button, .btn-delete {
      padding: 8px 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 6px;
    }
    button[name="approve"] {
      background: #27ae60;
      color: white;
      width: 100%;
    }
    .btn-delete {
      background: #e74c3c;
      color: white;
      text-decoration: none;
      display: block;
      text-align: center;
    }
    .winner {
      margin-top: 10px;
      padding: 8px;
      border-radius: 6px;
      background: #ecf0f1;
    }
    .winner strong {
      color: #2c3e50;
    }
  </style>
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
  <h2>Manage Auction Items</h2>
  <div class="grid">
    <?php while($row = $result->fetch_assoc()) { ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><strong>Seller:</strong> <?= htmlspecialchars($row['username']) ?></p>
      <p><strong>Start Price:</strong> $<?= $row['start_price'] ?></p>
      <p><strong>Status:</strong> <?= ucfirst($row['status']) ?></p>
      <p><strong>Ends:</strong> <?= $row['end_time'] ?></p>

      <?php
      // ✅ Check if auction ended
      if ($row['status'] == 'closed' || ($row['end_time'] && strtotime($row['end_time']) < time())) {
          // Fetch highest bid
          $bidSql = "SELECT b.bid_amount, u.username AS bidder 
                     FROM bids b 
                     JOIN users u ON b.bidder_id = u.id 
                     WHERE b.item_id = ? 
                     ORDER BY b.bid_amount DESC LIMIT 1";
          $stmt = $conn->prepare($bidSql);
          $stmt->bind_param("i", $row['id']);
          $stmt->execute();
          $winnerResult = $stmt->get_result();
          
          if ($winnerResult->num_rows > 0) {
              $winner = $winnerResult->fetch_assoc();
              echo "<div class='winner'>
                      🏆 <strong>Winner:</strong> " . htmlspecialchars($winner['bidder']) . 
                      " <br><strong>Winning Bid:</strong> $" . $winner['bid_amount'] . "
                    </div>";
          } else {
              echo "<div class='winner'>⚠️ No bids placed.</div>";
          }
      }
      ?>

      <div class="actions">
        <?php if ($row['status'] == 'pending') { ?>
        <form method="POST">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <label>Start Time:</label>
          <input type="datetime-local" name="start_time" required>
          <label>End Time:</label>
          <input type="datetime-local" name="end_time" required>
          <button type="submit" name="approve">✅ Approve</button>
        </form>
        <a href="?reject=<?= $row['id'] ?>" class="btn-delete">❌ Reject</a>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
  </div>
</div>

  <script src="assets/script.js"></script>
</body>
</html>
