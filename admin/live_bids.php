<?php
session_start();

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";

/* ================= VALIDATE ITEM ID ================= */
if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    echo "<h3 style='color:red;text-align:center'>Invalid Item Selected</h3>";
    exit();
}

$item_id = (int) $_GET['item_id'];

/* ================= FETCH ITEM DETAILS ================= */
$item_sql = "SELECT * FROM auction_items WHERE id = ?";
$stmt = $conn->prepare($item_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo "<h3 style='color:red;text-align:center'>Item not found</h3>";
    exit();
}

/* ===== FETCH IMAGE FOR THIS ITEM (PRIMARY IMAGE) ===== */
$imgSql = "
    SELECT image_path 
    FROM auction_images 
    WHERE item_id = ? 
    ORDER BY is_primary DESC, id ASC 
    LIMIT 1
";
$imgStmt = $conn->prepare($imgSql);
$imgStmt->bind_param("i", $item_id);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
$imgRow = $imgRes->fetch_assoc();
$imgStmt->close();

$item['image'] = $imgRow['image_path'] ?? '';

/* ================= FETCH BID STATS ================= */
$stats_sql = "
    SELECT 
        COUNT(*) AS total_bids,
        MAX(bid_amount) AS highest_bid
    FROM bids
    WHERE item_id = ?
";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ================= FETCH WINNER ================= */
$winner_sql = "
    SELECT u.username, b.bid_amount
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.item_id = ?
    ORDER BY b.bid_amount DESC
    LIMIT 1
";
$stmt = $conn->prepare($winner_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$winner = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ================= PAGINATION SETUP ================= */
$bidsPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $bidsPerPage;

/* ================= FETCH BIDS WITH LIMIT ================= */
$bids_sql = "
    SELECT b.bid_amount, b.bid_time, u.username
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.item_id = ?
    ORDER BY b.bid_amount DESC, b.bid_time DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($bids_sql);
$stmt->bind_param("iii", $item_id, $offset, $bidsPerPage);
$stmt->execute();
$bids = $stmt->get_result();
$stmt->close();

/* ================= GET TOTAL BIDS FOR PAGINATION ================= */
$totalBids = $stats['total_bids'];
$totalPages = ceil($totalBids / $bidsPerPage);


/* ================= USER FILTER ================= */
$searchUser = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';

/* ================= FETCH BIDS WITH FILTER AND PAGINATION ================= */
$bids_sql = "
    SELECT b.bid_amount, b.bid_time, u.username
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.item_id = ?
";

$params = [$item_id];
$types = "i";

if (!empty($searchUser)) {
    $bids_sql .= " AND u.username LIKE ?";
    $params[] = "%$searchUser%";
    $types .= "s";
}

$bids_sql .= " ORDER BY b.bid_amount DESC, b.bid_time DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $bidsPerPage;
$types .= "ii";

$stmt = $conn->prepare($bids_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bids = $stmt->get_result();

/* ================= GET TOTAL BIDS FOR PAGINATION (WITH FILTER) ================= */
$count_sql = "
    SELECT COUNT(*) AS total_bids
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.item_id = ?
";

$count_params = [$item_id];
$count_types = "i";

if (!empty($searchUser)) {
    $count_sql .= " AND u.username LIKE ?";
    $count_params[] = "%$searchUser%";
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$totalBids = $count_stmt->get_result()->fetch_assoc()['total_bids'];
$count_stmt->close();

$totalPages = ceil($totalBids / $bidsPerPage);

$stmt = $conn->prepare("
    SELECT a.*, u.username AS seller
    FROM auction_items a
    JOIN users u ON a.seller_id = u.id
    WHERE a.id=? AND a.status='active'
");

if (!$stmt) {
    die("SQL Prepare Failed: " . $conn->error);
}

$stmt->bind_param("i", $item_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bid History | EasyBid</title>
    <link rel="stylesheet" href="../assets/style.css">
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    padding: 20px;
}
.container {
    max-width: 1000px;
    margin: auto;
    background: #fff;
    padding: 25px;
    border-radius: 8px;
}
h2 {
    color: #8b0000;
}

.item-header {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.item-image-box {
    width: 220px;
    height: 140px;
    border-radius: 8px;
    overflow: hidden;
    background: #eee;
    border: 1px solid #ddd;
}

.item-image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.info-box {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 20px 0;
}
.info-box div {
    flex: 1;
    background: #f9f9f9;
    padding: 15px;
    text-align: center;
    border-radius: 6px;
    font-weight: bold;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
th {
    background: #2c3e50;
    color: white;
}
.no-data {
    text-align: center;
    padding: 20px;
    color: #888;
}
.winner {
    color: green;
}
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background: #2c3e50;
  color: #fff;
}

/* Logo wrapper */
.logo-box {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Logo image */
.logo-box img {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 6px;
}

/* Logo text */
.logo-text {
  font-size: 18px;
  font-weight: 600;
  white-space: nowrap;
}
.pagination { text-align:center; margin-top:15px; }
.pagination a {
    padding:6px 10px; margin:2px;
    border:1px solid #ccc; border-radius:6px;
    text-decoration:none; color:#333;
}
.active-page {
    background:#4a90e2; color:white !important;
}
</style>
</head>

<body>
    <div class="sidebar">
  <div class="sidebar-header">
    <div class="logo-box">
      <img src="../images/logo.jpeg" alt="EasyBid Logo">
      <span class="logo-text">EasyBid</span>
    </div>
    <div class="toggle-btn">☰</div>
  </div>

  <ul>
    <li><a href="../admin/">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>

    <!-- DROPDOWN -->
    <li>
      <a class="caret" onclick="toggleDropdown('auctionDropdown')">
        📜 Auctions 
      </a>
      <ul class="dropdown-menu" id="auctionDropdown">
        <li><a href="auctions_active.php">🟢 Active</a></li>
        <li><a href="auctions_upcoming.php">🟡 Upcoming</a></li>
        <li><a href="auction_overview.php">📜 History</a></li>
      </ul>
    </li>

    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
<!-- <div class="container"> -->
<h2>Bid History – <?= htmlspecialchars($item['title']) ?></h2>

<div class="item-header">

    <!-- IMAGE BOX -->
    <div class="item-image-box">
        <?php
        $imagePath = "../assets/no-image.png"; 
        if (!empty($item['image'])) {
            $clean_path = str_replace(['../', './'], '', $item['image']);
            $fullPath = "../" . $clean_path;
            if (file_exists($fullPath)) $imagePath = $fullPath;
        }
        ?>
        <img src="<?= $imagePath ?>" 
             style="width:220px; height:140px; object-fit:cover; border-radius:8px;">
</div>



    <!-- DETAILS -->
    <div class="item-details">
        <p><strong>Description:</strong> <?= htmlspecialchars($item['description']) ?></p>
        <p><strong>Status:</strong> <?= ucfirst($item['status']) ?></p>
        <p><strong>Start Time:</strong> <?= $item['start_time'] ?></p>
        <p><strong>End Time:</strong> <?= $item['end_time'] ?></p>
    </div>

</div>


<div class="info-box">
    <div>Total Bids<br><?= $stats['total_bids'] ?></div>
    <div>Starting Bid<br>Rs <?= number_format($item['start_price'], 2) ?></div>
   <?php
if ($item['status'] === 'active') {
    $bid_label = "Current Highest Bid";
} elseif ($item['status'] === 'closed') {
    $bid_label = "Winning Bid";
} else {
    $bid_label = "Highest Bid";
}

$display_bid = $stats['highest_bid'] 
    ? $stats['highest_bid'] 
    : $item['start_price'];
?>

<div>
    <b><?= $bid_label ?>:</b><br>
    Rs <?= number_format($display_bid, 2) ?>
</div>

    <div>
        <?php
$winner_name = '-';

if ($item['status'] === 'closed') {
    // FINAL WINNER
    $q = $conn->prepare("
        SELECT u.username
        FROM auction_items ai
        LEFT JOIN users u ON ai.winner_id = u.id
        WHERE ai.id = ?
    ");
    $q->bind_param("i", $item_id);
    $q->execute();
    $winner_name = $q->get_result()->fetch_assoc()['username'] ?? '-';

} elseif ($item['status'] === 'active') {
    // CURRENT LEADING BIDDER
    $q = $conn->prepare("
        SELECT u.username
        FROM bids b
        JOIN users u ON b.bidder_id = u.id
        WHERE b.item_id = ?
        ORDER BY b.bid_amount DESC, b.bid_time ASC
        LIMIT 1
    ");
    $q->bind_param("i", $item_id);
    $q->execute();
    $winner_name = $q->get_result()->fetch_assoc()['username'] ?? '-';
}
?>

<div>
    <b>
        <?= $item['status'] === 'active' ? 'Leading Bidder' : 'Winner' ?>:
    </b>
    <?= htmlspecialchars($winner_name) ?>
</div>

    </div>
    </div>

<h3>All Bids</h3>
<!-- User Filter Form -->
<div style="margin-bottom:15px; display:flex; justify-content:flex-end; gap:10px; align-items:center;">
    <form method="get" style="display:flex; gap:5px; align-items:center;">
        <input type="hidden" name="item_id" value="<?= $item_id ?>">
        <input 
            type="text" 
            name="search_user" 
            placeholder="Filter by username" 
            value="<?= htmlspecialchars($searchUser) ?>" 
            style="padding:5px 10px; border-radius:5px; border:1px solid #ccc; min-width:180px;"
        >
        <button type="submit" style="padding:5px 12px; border:none; background:#2c3e50; color:#fff; border-radius:5px; cursor:pointer;">Filter</button>
        <?php if($searchUser): ?>
            <a href="?item_id=<?= $item_id ?>" style="padding:5px 12px; background:#888; color:#fff; border-radius:5px; text-decoration:none;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="bid-history">
    <h3>📜 Live Bid History</h3>
    <div id="bidHistory">Loading...</div>
</div>  

</div>
<script src="../assets/script.js"></script>
<script>
    function toggleDropdown(id) {
        const menu = document.getElementById(id);
          menu.classList.toggle("show");
}

const ITEM_ID = <?= $item_id ?>;        // Make sure JS knows the current item
const bidHistory = document.getElementById("bidHistory");  // Element to update

setInterval(()=>{
    fetch("../api/get_current_price.php?item_id="+ITEM_ID)
    .then(r=>r.json())
    .then(d=>{
        if(!d.error){
            currentPrice.innerText = "Rs " + parseFloat(d.current_price).toFixed(2);
        }
    });
function loadBids(page = 1) {
    fetch("../api/get_bid_history.php?item_id=" + ITEM_ID + "&page=" + page + "&t=" + new Date().getTime())
        .then(r => r.text())
        .then(html => bidHistory.innerHTML = html);
}

},1000);
</script>
</body>
</html>
