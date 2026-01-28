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

/* Check if a chat already exists between user and seller for this item */
$chatStmt = $conn->prepare("
    SELECT id 
    FROM item_chats
    WHERE item_id = ?
      AND (
        (buyer_id = ? AND seller_id = ?)
        OR
        (buyer_id = ? AND seller_id = ?)
      )
    LIMIT 1
");

$chatStmt->bind_param(
    "iiiii",
    $row['id'],         // current item ID
    $_SESSION['user_id'], // current user
    $row['seller_id'],    // seller of current item
    $row['seller_id'],    // swap
    $_SESSION['user_id']
);

$chatStmt->execute();
$chat = $chatStmt->get_result()->fetch_assoc();
$chatStmt->close();
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
    <!-- Logo instead of Welcome -->
    <div class="logo-box">
      <img src="../images/logo.jpeg" alt="EasyBid Logo" class="logo-img">
      <!-- <span class="logo-text">EasyBid</span> -->
       <?= htmlspecialchars($username) ?>
    </div>
    <div class="toggle-btn">☰</div>
  </div>

  <ul>
    <li><a href="../users/" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <!-- <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li> -->
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">🪙 <span>Place Bids</span></a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="feedback_list.php" data-label="Feedback list">💬 <span>My Feedback</span></a></li>
    <li><a href="../auth/logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>
<div class="main-content">
<h2>My Bidding History</h2>
<div style="margin-bottom:15px;">
  <label for="statusFilter">Filter by status: </label>
  <select id="statusFilter">
    <option value="all">All</option>
    <option value="pending">won</option>
    <option value="lost">Lost</option>
    <option value="ongoing">Ongoing</option>
    <option value="paid">Paid</option>
  </select>
</div>

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
        $status = "<span class='winner'>WON</span>";
    } else {
        $status = "<span class='loser'>Lost</span>";
    }
    $isEnded = ($row['end_time'] < date("Y-m-d H:i:s"));
    $isWinner = ($row['winner_id'] == $user_id);
// check if already paid or not
   $paid = false;
if ($isWinner && $isEnded) {
    $payCheck = $conn->prepare(
        "SELECT id 
         FROM payments 
         WHERE item_id = ? 
           AND status = 'success'
         LIMIT 1"
    );
    $payCheck->bind_param("i", $row['id']);
    $payCheck->execute();
    $payCheck->store_result();
    $paid = $payCheck->num_rows > 0;
    $payCheck->close();
}
if ($paid) {
            $voucherSql = $conn->prepare("
                SELECT voucher_no 
                FROM payments 
                WHERE item_id=? AND user_id=? AND status='success' 
                LIMIT 1
            ");
            $voucherSql->bind_param("ii", $row['id'], $user_id);
            $voucherSql->execute();
            $voucherNo = $voucherSql->get_result()->fetch_assoc()['voucher_no'] ?? '';
            $voucherSql->close();
        }
    // Determine card status for filtering
    $cardStatus = 'ongoing'; // default
    if ($isEnded && $isWinner && $paid) $cardStatus = 'paid';
    elseif ($isEnded && $isWinner && !$paid) $cardStatus = 'pending';
    elseif ($isEnded && $isWinner) $cardStatus = 'won';
    elseif ($isEnded && !$isWinner) $cardStatus = 'lost';
    elseif (!$isEnded) $cardStatus = 'ongoing';
?>

<div class="card" data-status="<?= $cardStatus ?>">
  <img src="<?= htmlspecialchars($imagePath) ?>" alt="Auction Image">
  <h3><?= htmlspecialchars($row['title']) ?></h3>
  <p><strong>Seller:</strong> <?= htmlspecialchars($row['seller']) ?></p>
  <p><strong>Your Highest Bid:</strong> <?= $row['my_highest_bid'] ? "Rs. ".$row['my_highest_bid'] : "N/A" ?></p>
  <p><strong>Winning Bid:</strong> <?= $row['winning_bid'] ? "Rs. ".$row['winning_bid'] : "N/A" ?></p>
  <p><strong>Winner:</strong> <?= htmlspecialchars($winner_name) ?></p>
  <p><strong>Status:</strong> <?= $status ?></p>
  <p><em>Ends at: <?= $row['end_time'] ?></em></p>
 <?php if ($_SESSION['user_id'] != $row['seller_id']): ?>
    <div style="margin-top:10px;">
        <a href="<?= $chat 
            ? 'chat_view.php?chat_id='.$chat['id']
            : 'start_chat.php?item_id='.$row['id'] ?>"
           style="
            display:inline-block;
            background:#34495e;
            color:white;
            padding:7px 14px;
            border-radius:5px;
            text-decoration:none;
            font-size:13px;
           ">
           <?= $chat ? '💬 View Chat' : '💬 Message Seller' ?>
        </a>
    </div>
<?php endif; ?>
<?php if ($isEnded && $isWinner): ?>
    <?php if (!$paid): ?>
        <form action="../users/payment/payment_form.php" method="POST">
          <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
          <button class="pay-btn">Pay Now</button>
        </form>
    <?php else: ?>
        <!-- Payment is done, show voucher -->
        <?php
        // Fetch the voucher number
        $voucherSql = $conn->prepare("
            SELECT voucher_no 
            FROM payments 
            WHERE item_id=? AND user_id=? AND status='success' 
            LIMIT 1
        ");
        $voucherSql->bind_param("ii", $row['id'], $user_id);
        $voucherSql->execute();
        $voucherNo = $voucherSql->get_result()->fetch_assoc()['voucher_no'] ?? '';
        $voucherSql->close();
        ?>
        <?php if ($voucherNo): ?>
            <a href="../users/payment/payment_voucher.php?v=<?= urlencode($voucherNo) ?>" 
               class="pay-btn" 
               style="background:#2ecc71;margin-top:5px;display:inline-block; text-decoration:none;">
                View Voucher
            </a>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

</div>

<?php } ?>

</div>
</div>
<script src="../assets/script.js"></script>
<script>
const filter = document.getElementById('statusFilter');
const cards = document.querySelectorAll('.grid .card');

filter.addEventListener('change', () => {
  const value = filter.value;
  cards.forEach(card => {
    if (value === 'all' || card.dataset.status === value) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
});
</script>
</body>
</html>
