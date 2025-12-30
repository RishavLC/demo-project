<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

include "../common/config.php";

// ✅ Auto-close expired auctions
$conn->query("UPDATE auction_items 
              SET status='closed' 
              WHERE status='active' AND end_time < NOW()");

// ✅ Auto-activate upcoming auctions once their start time arrives
$conn->query("UPDATE auction_items 
              SET status='active' 
              WHERE status='upcoming' AND start_time <= NOW()");

// ✅ Approve auction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve"])) {
    $id = intval($_POST["id"]);

    // Fetch timing (if seller already added it)
    $check = $conn->query("SELECT start_time, end_time, title, seller_id FROM auction_items WHERE id=$id")->fetch_assoc();
    $start_time = !empty($check['start_time']) ? $check['start_time'] : $_POST["start_time"];
    $end_time   = !empty($check['end_time']) ? $check['end_time'] : $_POST["end_time"];

    // ✅ Determine correct status
    $now = date("Y-m-d H:i:s");
    if ($start_time > $now) {
        $status = 'upcoming'; // future auction
    } else {
        $status = 'active'; // starts now
    }

    // ✅ Update auction record
    $stmt = $conn->prepare("UPDATE auction_items SET status=?, start_time=?, end_time=? WHERE id=?");
    $stmt->bind_param("sssi", $status, $start_time, $end_time, $id);
    $stmt->execute();

    // ✅ Notify all users except seller
    $users = $conn->query("SELECT id FROM users WHERE id != {$check['seller_id']}");
    while ($u = $users->fetch_assoc()) {
        $msg = ($status == 'upcoming') 
            ? "📢 New auction scheduled soon: " . $check['title']
            : "🔥 New auction started: " . $check['title'];
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ({$u['id']}, '$msg')");
    }

    $_SESSION['msg'] = "✅ Auction approved successfully as " . ucfirst($status) . "!";
    header("Location: manage_auctions.php");
    exit();
}

// ✅ Reject auction
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $seller = $conn->query("SELECT seller_id, title FROM auction_items WHERE id=$id")->fetch_assoc();

    $conn->query("UPDATE auction_items SET status='rejected' WHERE id=$id");

    // Notify seller
    $msg = "❌ Your auction '" . $seller['title'] . "' was rejected by the admin.";
    $conn->query("INSERT INTO notifications (user_id, message) VALUES ({$seller['seller_id']}, '$msg')");

    $_SESSION['msg'] = "🚫 Auction rejected successfully!";
    header("Location: manage_auctions.php");
    exit();
}

// ✅ Fetch all auction items
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
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body { background: #f5f6fa; }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
    }
    .card {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .card h3 { margin: 0 0 10px; font-size: 20px; color: #2c3e50; }
    .card p { margin: 5px 0; font-size: 14px; }
    .actions { margin-top: 15px; }
    input[type="datetime-local"] {
      padding: 6px; margin: 5px 0; width: 100%;
      border: 1px solid #ccc; border-radius: 6px;
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
      font-size: 14px;
    }
    .winner strong { color: #2c3e50; }

    .status-active { color: #27ae60; font-weight: bold; }
    .status-upcoming { color: #2980b9; font-weight: bold; }
    .status-pending { color: #f39c12; font-weight: bold; }
    .status-rejected { color: #e74c3c; font-weight: bold; }
    .status-closed { color: #95a5a6; font-weight: bold; }

    .alert {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      font-weight: bold;
    }
    .alert.success { background: #d4edda; color: #155724; }
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
    <li><a href="auction_history.php">📜 Auction Status</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Manage Auction Items</h2>

  <?php if(isset($_SESSION['msg'])): ?>
    <div class="alert success"><?= $_SESSION['msg'] ?></div>
    <?php unset($_SESSION['msg']); ?>
  <?php endif; ?>

  <div class="grid">
    <?php 
    if ($result->num_rows == 0) {
        echo "<p>No auctions available.</p>";
    } else {
        while($row = $result->fetch_assoc()) { 
    ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><strong>Seller:</strong> <?= htmlspecialchars($row['username']) ?></p>
      <p><strong>Start Price:</strong> Rs. <?= number_format($row['start_price'], 2) ?></p>
      <p><strong>Status:</strong> 
        <span class="status-<?= strtolower($row['status']) ?>"><?= ucfirst($row['status']) ?></span>
      </p>
      <p><strong>Starts:</strong> <?= $row['start_time'] ?: 'Not set' ?></p>
      <p><strong>Ends:</strong> <?= $row['end_time'] ?: 'Not set' ?></p>

      <?php
      // ✅ Show winner if auction closed
      if ($row['status'] == 'closed' && !empty($row['end_time']) && strtotime($row['end_time']) < time()) {
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
                      "<br><strong>Winning Bid:</strong> Rs. " . number_format($winner['bid_amount'], 2) . "
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
          <button type="submit" name="approve">✅ Approve</button>
        </form>
        <a href="?reject=<?= $row['id'] ?>" class="btn-delete">❌ Reject</a>
        <?php } ?>
      </div>
    </div>
    <?php 
        } 
      }
    ?>
  </div>
</div>

<script src="../assets/script.js"></script>
</body>
</html>
