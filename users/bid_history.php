<?php
session_start();

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
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

/* assign image_path into item so existing code works */
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

/* ================= FETCH ALL BIDS ================= */
$bids_sql = "
    SELECT b.bid_amount, b.bid_time, u.username
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.item_id = ?
    ORDER BY b.bid_amount DESC, b.bid_time DESC
";
$stmt = $conn->prepare($bids_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$bids = $stmt->get_result();
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
</style>
</head>

<body>
   <div class="sidebar">
  <div class="sidebar-header">
    User Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_user.php" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li>
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">💰 <span>Place Bids</span></a></li>
    <li><a href="auctions.php" class="active">📊 Auction Details</a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="../auth/logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
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
    <div>Starting Price<br>Rs <?= number_format($item['start_price'], 2) ?></div>
    <div>
        Highest Bid<br>
        Rs <?= $stats['highest_bid'] ? number_format($stats['highest_bid'], 2) : "—" ?>
    </div>
    <div>
        Winner<br>
        <?= $winner ? "<span class='winner'>{$winner['username']}</span>" : "No bids yet" ?>
    </div>
</div>

<h3>All Bids</h3>

<table>
<tr>
    <th>S.N</th>
    <th>Bidder</th>
    <th>Bid Amount (Rs)</th>
    <th>Bid Time</th>
</tr>

<?php if ($bids->num_rows > 0): ?>
<?php $i = 1; while ($row = $bids->fetch_assoc()): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td>Rs <?= number_format($row['bid_amount'], 2) ?></td>
    <td><?= $row['bid_time'] ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="4" class="no-data">No bids placed for this item</td>
</tr>
<?php endif; ?>
</table>

</div>
</body>
</html>
