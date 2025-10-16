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

/* 🔹 2. Fetch Active Auctions (others only, not expired) by sorting */
$sort_by = $_GET['sort_by'] ?? 'end_time';

if ($sort_by === 'highest_bid') {
    $order_clause = "ORDER BY (SELECT MAX(bid_amount) FROM bids WHERE item_id = ai.id) DESC";
} elseif ($sort_by === 'lowest_bid') {
    $order_clause = "ORDER BY (SELECT COALESCE(MIN(bid_amount), ai.start_price) FROM bids WHERE item_id = ai.id) ASC";
} else {
    $order_clause = "ORDER BY ai.end_time ASC";
}
// --- Pagination Setup ---
$records_per_page = 5; // number of auctions per page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$active_sql = "
  SELECT ai.*, u.username AS seller,
         (SELECT MAX(bid_amount) FROM bids WHERE item_id = ai.id) AS highest_bid
  FROM auction_items ai
  JOIN users u ON ai.seller_id = u.id
  WHERE ai.status='active' 
    AND ai.seller_id != ? 
    AND ai.end_time > NOW()
  $order_clause
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($active_sql);
$stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$stmt->execute();
$active_result = $stmt->get_result();
$stmt->close();

//counting active auction item if more than five then pagination link is appeared
$count_sql = "
  SELECT COUNT(*) AS total
  FROM auction_items ai
  WHERE ai.status='active' 
    AND ai.seller_id != ? 
    AND ai.end_time > NOW()
";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);
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
// unread notification count
$sql = "SELECT COUNT(*) AS unread FROM notifications WHERE user_id=? AND is_read=0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$unread_count = $result['unread'];

// summary cards 1. Participated Auctions
$sql = "SELECT COUNT(DISTINCT item_id) AS participated 
        FROM bids WHERE bidder_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$participated = $stmt->get_result()->fetch_assoc()['participated'];
$stmt->close();

// 2. Auctions Won
$sql = "SELECT COUNT(*) AS won
        FROM auction_items ai
        WHERE ai.status='closed'
        AND ai.id IN (
            SELECT b.item_id 
            FROM bids b
            WHERE b.bidder_id=? 
            GROUP BY b.item_id 
            HAVING MAX(b.bid_amount) = (
                SELECT MAX(b2.bid_amount) FROM bids b2 WHERE b2.item_id=b.item_id
            )
        )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$won = $stmt->get_result()->fetch_assoc()['won'];
$stmt->close();

// 3. Total Investment
$sql = "SELECT SUM(bid_amount) AS total_investment
        FROM bids b
        WHERE b.bidder_id=? 
        AND b.bid_amount = (
            SELECT MAX(b2.bid_amount) 
            FROM bids b2 
            WHERE b2.item_id=b.item_id
        )
        AND b.item_id IN (
            SELECT id FROM auction_items WHERE status='closed'
        )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_investment = $stmt->get_result()->fetch_assoc()['total_investment'] ?? 0;
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
  <title>User Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
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
    <li><a href="logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>

<div class="main-content">
<!-- Header -->
<div class="header">
  <h2>Welcome, <?= htmlspecialchars($username) ?></h2>

  <!-- Notification Bell -->
  <div class="notification-wrapper">
    <div class="notification-bell" onclick="toggleDropdown()">
      🔔
      <?php if ($unread_count > 0) { ?>
        <span class="badge"><?= $unread_count ?></span>
      <?php } ?>
    </div>

<div id="notificationDropdown" class="notification-dropdown">
  <?php
  $noti_sql = "SELECT * FROM notifications 
               WHERE user_id=? 
               ORDER BY created_at DESC 
               LIMIT 5";
  $stmt = $conn->prepare($noti_sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $noti_result = $stmt->get_result();

  if ($noti_result->num_rows > 0) {
    while ($n = $noti_result->fetch_assoc()) {
      echo "<p>".htmlspecialchars($n['message'])."</p>";
    }
  } else {
    echo "<p>No notifications</p>";
  }
  ?>
  <a href="notifications.php" class="view-all">View All</a>
  <a href="mark_notifications.php" class="mark-read">Mark All as Read</a>
</div>
</div>
</div>

<!-- Summary cards -->
<div class="summary-cards">
  <div class="summary-card">
    <h3><?= $participated ?></h3>
    <p>Participated Auctions</p>
  </div>
  <div class="summary-card">
    <h3><?= $won ?></h3>
    <p>Auctions Won</p>
  </div>
  <div class="summary-card">
    <h3>$<?= number_format($total_investment, 2) ?></h3>
    <p>Total Investment</p>
  </div>
</div>


<h2>Active Auctions</h2>
<div class="sort-filter">
  <form method="GET" style="display:flex; align-items:center; gap:10px;">
    <label for="sort_by"><strong>Sort By:</strong></label>
    <select name="sort_by" id="sort_by" onchange="this.form.submit()">
      <option value="end_time" <?= (!isset($_GET['sort_by']) || $_GET['sort_by'] == 'end_time') ? 'selected' : '' ?>>Ending Soon</option>
      <option value="highest_bid" <?= (isset($_GET['sort_by']) && $_GET['sort_by'] == 'highest_bid') ? 'selected' : '' ?>>Highest Bid</option>
      <option value="lowest_bid" <?= (isset($_GET['sort_by']) && $_GET['sort_by'] == 'lowest_bid') ? 'selected' : '' ?>>Lowest Bid</option>
    </select>
  </form>
</div>
<table class="auction-table">
  <tr>
    <th>SN</th>
    <th>Auction Item</th>
    <th>Starting Price</th>
    <th>Current Bid</th>
    <th>End Date</th>
    <th>Action</th>
  </tr>
  <?php 
  $sn = 1;
  while($row = $active_result->fetch_assoc()) { ?>
    <tr>
      <td><?= $sn++ ?></td>
      <td><?= htmlspecialchars($row['title']) ?></td>
      <td>$<?= $row['start_price'] ?></td>
      <td>$<?= ($row['highest_bid'] ?? 0) ?></td>
      <td><?= $row['end_time'] ?></td>
      <td>
        <button class="btn" 
                onclick="openAuctionModal(
                  '<?= $row['title'] ?>',
                  '<?= $row['description'] ?>',
                  '<?= $row['seller'] ?>',
                  '<?= $row['start_price'] ?>',
                  '<?= $row['end_time'] ?>',
                  '<?= $row['id'] ?>'
                )">Bid</button>
      </td>
    </tr>
  <?php } ?>
</table>
<!-- pagination block -->
<div class="pagination">
  <?php if ($current_page > 1): ?>
    <a href="?page=<?= $current_page - 1 ?>&sort_by=<?= $sort_by ?>">&laquo; Prev</a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?= $i ?>&sort_by=<?= $sort_by ?>" class="<?= ($i == $current_page) ? 'active' : '' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if ($current_page < $total_pages): ?>
    <a href="?page=<?= $current_page + 1 ?>&sort_by=<?= $sort_by ?>">Next &raquo;</a>
  <?php endif; ?>
</div>

<!-- Auction Details Modal -->
<div id="auctionModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAuctionModal()">&times;</span>
    <h2 id="modalTitle"></h2>
    <p><strong>Description:</strong> <span id="modalDescription"></span></p>
    <p><strong>Seller:</strong> <span id="modalSeller"></span></p>
    <p><strong>Starting Price:</strong> $<span id="modalPrice"></span></p>
    <p><strong>Highest Bid:</strong> $<span id="modalHighest"></span></p>
    <p><strong>Ends At:</strong> <span id="modalEnd"></span></p>
    <a id="bidLink" href="#" class="btn">Place Bid</a>
  </div>
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
