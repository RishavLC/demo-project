<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "config.php";

$user_id = $_SESSION["user_id"];

// ✅ Fetch username
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// ✅ Fetch all auctions where this user placed a bid
$sql = "SELECT ai.*, u.username AS seller,
        (SELECT MAX(bid_amount) FROM bids WHERE item_id = ai.id) AS highest_bid,
        (SELECT bidder_id FROM bids WHERE item_id = ai.id ORDER BY bid_amount DESC LIMIT 1) AS winner_id,
        (SELECT bid_amount FROM bids WHERE item_id = ai.id ORDER BY bid_amount DESC LIMIT 1) AS winning_bid,
        (SELECT MAX(bid_amount) FROM bids WHERE item_id = ai.id AND bidder_id = ?) AS my_highest_bid
        FROM auction_items ai
        JOIN users u ON ai.seller_id = u.id
        WHERE ai.id IN (SELECT DISTINCT item_id FROM bids WHERE bidder_id = ?)
        ORDER BY ai.end_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Bidding History</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }
    .card {
      background: #fff;
      padding: 18px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .card h3 { margin: 0 0 8px; font-size: 18px; color: #2c3e50; }
    .card p { margin: 5px 0; }
    .winner { color: green; font-weight: bold; }
    .loser { color: red; font-weight: bold; }
    .ongoing { color: orange; font-weight: bold; }
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
    <li><a href="my_bids.php">📜 My Bidding History</a></li>
    <li><a href="add_record.php">➕ Add Record</a></li> 
    <li><a href="add_auction_item.php">📦 Add Auctions Items</a></li>
    <li><a href="auction_bid.php">💰 Place Bids</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>My Bidding History</h2>
  <div class="grid">
    <?php while($row = $result->fetch_assoc()) {
        $status = "";
        $winner_name = "No Winner";

        // Find winner name if exists
        if ($row['winner_id']) {
            $wstmt = $conn->prepare("SELECT username FROM users WHERE id=?");
            $wstmt->bind_param("i", $row['winner_id']);
            $wstmt->execute();
            $wstmt->bind_result($winner_name);
            $wstmt->fetch();
            $wstmt->close();
        }

        // Decide status for logged-in user
        if ($row['end_time'] > date("Y-m-d H:i:s")) {
            $status = "<span class='ongoing'>Ongoing Auction</span>";
        } else {
            if ($row['winner_id'] == $user_id) {
                $status = "<span class='winner'>You WON 🎉</span>";
            } else {
                $status = "<span class='loser'>You Lost ❌</span>";
            }
        }
    ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><strong>Seller:</strong> <?= htmlspecialchars($row['seller']) ?></p>
      <p><strong>Your Highest Bid:</strong> <?= $row['my_highest_bid'] ? "$".$row['my_highest_bid'] : "N/A" ?></p>
      <p><strong>Winning Bid:</strong> <?= $row['winning_bid'] ? "$".$row['winning_bid'] : "N/A" ?></p>
      <p><strong>Winner:</strong> <?= htmlspecialchars($winner_name) ?></p>
      <p><strong>Status:</strong> <?= $status ?></p>
      <p><em>Auction Ended: <?= $row['end_time'] ?></em></p>
    </div>
    <?php } ?>
  </div>
</div>
</body>
</html>
