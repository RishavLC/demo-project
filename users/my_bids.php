<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "../common/config.php";

$user_id = $_SESSION["user_id"];

/* Fetch username */
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

/* Fetch auctions where user has bid */
$sql = "SELECT ai.*, u.username AS seller,
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
<link rel="stylesheet" href="../assets/style.css">

<style>
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}
.card {
  background: #fff;
  padding: 15px;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 8px;
  margin-bottom: 10px;
}
.winner { color: green; font-weight: bold; }
.loser { color: red; font-weight: bold; }
.ongoing { color: orange; font-weight: bold; }
.pay-btn {
  background: #60bb46;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
}
.pay-btn:hover {
  background: #4aa637;
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
    <li><a href="../users/" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li>
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">💰 <span>Place Bids</span></a></li>
    <!-- <li><a href="auctions.php" class="active">📊 Auction Details</a></li> -->
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="../auth/logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>
<div class="main-content">
<h2>My Bidding History</h2>

<div class="grid">

<?php while ($row = $result->fetch_assoc()) {

    /* Fetch image */
    $imgSql = "SELECT image_path FROM auction_images 
               WHERE item_id = ? 
               ORDER BY is_primary DESC, id ASC LIMIT 1";
    $imgStmt = $conn->prepare($imgSql);
    $imgStmt->bind_param("i", $row['id']);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    $imgRow = $imgRes->fetch_assoc();
    $imgStmt->close();

    $imagePath = (!empty($imgRow['image_path']))
        ? "../" . ltrim($imgRow['image_path'], './')
        : "../assets/no-image.png";

    /* Winner name */
    $winner_name = "No Winner";
    if ($row['winner_id']) {
        $w = $conn->prepare("SELECT username FROM users WHERE id=?");
        $w->bind_param("i", $row['winner_id']);
        $w->execute();
        $w->bind_result($winner_name);
        $w->fetch();
        $w->close();
    }

    /* Status */
    if ($row['end_time'] > date("Y-m-d H:i:s")) {
        $status = "<span class='ongoing'>Ongoing Auction</span>";
    } elseif ($row['winner_id'] == $user_id) {
        $status = "<span class='winner'>You WON 🎉</span>";
    } else {
        $status = "<span class='loser'>You Lost ❌</span>";
    }
    $isEnded = ($row['end_time'] < date("Y-m-d H:i:s"));
    $isWinner = ($row['winner_id'] == $user_id);
// check if already paid or not
    $paid = false;
if ($isWinner && $isEnded) {
    $payCheck = $conn->prepare(
        "SELECT status FROM payments WHERE user_id=? AND item_id=? AND status='success'"
    );
    $payCheck->bind_param("ii", $user_id, $row['id']);
    $payCheck->execute();
    $payCheck->store_result();
    $paid = $payCheck->num_rows > 0;
    $payCheck->close();
}


?>

<div class="card">
  <img src="<?= htmlspecialchars($imagePath) ?>" alt="Auction Image">
  <h3><?= htmlspecialchars($row['title']) ?></h3>
  <p><strong>Seller:</strong> <?= htmlspecialchars($row['seller']) ?></p>
  <p><strong>Your Highest Bid:</strong> <?= $row['my_highest_bid'] ? "Rs. ".$row['my_highest_bid'] : "N/A" ?></p>
  <p><strong>Winning Bid:</strong> <?= $row['winning_bid'] ? "Rs. ".$row['winning_bid'] : "N/A" ?></p>
  <p><strong>Winner:</strong> <?= htmlspecialchars($winner_name) ?></p>
  <p><strong>Status:</strong> <?= $status ?></p>
  <p><em>Ends at: <?= $row['end_time'] ?></em></p>
  <?php if ($isEnded && $isWinner): ?>
    <?php if (!$paid): ?>
        <form action="../users/payment/payment_form.php" method="POST">
          <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
          <button class="pay-btn">Pay Now</button>
        </form>
    <?php else: ?>
        <p class="winner">Payment Completed ✅</p>
    <?php endif; ?>
<?php endif; ?>

</div>

<?php } ?>

</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
