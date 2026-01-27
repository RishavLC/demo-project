<?php
session_start();
include "../common/config.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$item_id = intval($_GET["item_id"] ?? 0);

if ($item_id <= 0) {
    die("Invalid auction item.");
}

/* ================= USERNAME ================= */
$stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

/* ================= AUCTION ITEM ================= */
$stmt = $conn->prepare("
    SELECT a.*, u.username AS seller
    FROM auction_items a
    JOIN users u ON a.seller_id = u.id
    WHERE a.id=? AND a.status='active'
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$auction) {
    die("Auction not found or inactive.");
}

/* ================= IMAGE ================= */
$stmt = $conn->prepare("
    SELECT image_path 
    FROM auction_images 
    WHERE item_id=? 
    ORDER BY is_primary DESC, uploaded_at ASC 
    LIMIT 1
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$image = $row ? "../" . str_replace(['../','./'],'',$row['image_path']) : "../assets/no-image.png";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($auction['title']) ?></title>
<link rel="stylesheet" href="../assets/style.css">
<style>
body{font-family:Arial;background:#f4f6f8;margin:0}
.main{padding:25px}
.wrapper{max-width:1100px;margin:auto;background:#fff;padding:25px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,.1)}

.header1 h2{margin-bottom:5px}
.meta{color:#555;display:flex;gap:15px;flex-wrap:wrap}
.gallery{
    display:flex;
    gap:25px;
    margin-top:20px;
    align-items:flex-start;   /* KEY: no forced height */
}

/* IMAGE SMALLER */
.main-image{
    width:300px;              /* smaller image */
    flex-shrink:0;
}

.main-image img{
    width:100%;
    height:220px;             /* controlled height */
    object-fit:cover;
    border-radius:10px;
}

/* DATA PANEL NATURAL */
.details-panel{
    flex:1;                   /* take remaining space */
    background:#f9f9f9;
    padding:18px;
    border-radius:10px;
}
.detail-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #ddd}
.detail-row:last-child{border-bottom:none}
.detail-row span{color:#555}

.header1 h2{
    margin:0;
}

.title-row{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap; /* mobile safe */
}

.badge{
    background:#2ecc71;
    color:#fff;
    padding:5px 14px;
    border-radius:20px;
    font-size:12px;
    height:fit-content;
    position: relative;
    left: 20px;
}

.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin:25px 0}
.card{background:#f9f9f9;padding:15px;border-radius:10px;text-align:center}
.price{font-size:24px;font-weight:bold;color:#27ae60}

.bid-box{background:#f4f6f8;padding:20px;border-radius:10px}
.bid-box input,.bid-box button{width:100%;padding:12px;margin-top:10px}
.bid-box button{background:#4a90e2;color:#fff;border:none;border-radius:6px;cursor:pointer}
.bid-box button:hover{background:#357ab7}

.countdown{background:#fff3cd;padding:12px;border-left:5px solid #f39c12;font-weight:bold;margin:15px 0}

.bid-history{margin-top:30px;background:#fff;padding:15px;border-radius:10px}
.bid-history table{width:100%;border-collapse:collapse}
.bid-history th,.bid-history td{padding:10px;border-bottom:1px solid #ddd;text-align:center}
.bid-history th{background:#2c3e50;color:#fff}
.highlight{background:#e8f8f0;font-weight:bold}
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
<div class="wrapper">

<div class="header1">
    <div class="title-row">
        <h2><?= htmlspecialchars($auction['title']) ?></h2>
        <span class="badge">Active</span>
    </div>

    <div class="meta">
        <span>📂 <?= htmlspecialchars($auction['category']) ?></span>
        <span>👤 Seller: <?= htmlspecialchars($auction['seller']) ?></span>
        <span>🆔 Item #<?= $auction['id'] ?></span>
    </div>
</div>


<div class="countdown">
    ⏳ Auction Ends In: <span id="timer">Loading...</span>
</div>

<div class="gallery">
    <div class="main-image">
        <img src="<?= $image ?>">
    </div>

    <div class="details-panel">
        <div class="detail-row"><span>Started At</span><b><?= $auction['start_time'] ?></b></div>
        <div class="detail-row"><span>Starting Bid</span><b>Rs <?= number_format($auction['start_price'],2) ?></b></div>
        <div class="detail-row"><span>Min Increment</span><b>Rs <?= number_format($auction['min_increment'],2) ?></b></div>
        <div class="detail-row"><span>Latest Bid</span><b id="currentPrice">Rs <?= number_format($auction['current_price'],2) ?></b></div>
        <div class="detail-row"><span>Ends At</span><b><?= $auction['end_time'] ?></b></div>
    </div>
</div>

<h3>Description</h3>
<p><?= nl2br(htmlspecialchars($auction['description'])) ?></p>

<form class="bid-box" onsubmit="return placeBid();">
    <h3>🚀 Place Your Bid</h3>
    <input type="number" id="bidAmount"
        min="<?= $auction['current_price'] + $auction['min_increment'] ?>"
        value="<?= $auction['current_price'] + $auction['min_increment'] ?>"
        step="0.01" required>
    <button>Place Bid</button>
</form>

<div class="bid-history">
    <h3>📜 Live Bid History (Latest 5)</h3>
    <div id="bidHistory">Loading...</div>
</div>

</div>
</div>

<script>
const ITEM_ID = <?= $item_id ?>;
const END_TIME = new Date("<?= $auction['end_time'] ?>").getTime();

/* Countdown */
setInterval(()=>{
    const diff = END_TIME - new Date().getTime();
    if(diff <= 0){
        timer.innerText = "Auction Ended";
        return;
    }
    const h = Math.floor(diff/3600000);
    const m = Math.floor((diff%3600000)/60000);
    const s = Math.floor((diff%60000)/1000);
    timer.innerText = `${h}h ${m}m ${s}s`;
},1000);

/* Place bid */
function placeBid(){
    const fd = new FormData();
    fd.append("item_id", ITEM_ID);
    fd.append("bid_amount", bidAmount.value);

    fetch("../api/place_bid.php",{method:"POST",body:fd})
    .then(r=>r.json())
    .then(d=>{
        if(d.error){ alert(d.error); return; }
        alert("🎉 Bid placed successfully!");
    });
    return false;
}

/* Live updates */
setInterval(()=>{
    fetch("../api/get_current_price.php?item_id="+ITEM_ID)
    .then(r=>r.json())
    .then(d=>{
        if(!d.error){
            currentPrice.innerText = "Rs " + parseFloat(d.current_price).toFixed(2);
        }
    });

    fetch("../api/get_bid_history.php?item_id="+ITEM_ID)
    .then(r=>r.text())
    .then(html=> bidHistory.innerHTML = html);
},3000);
</script>
<script src="../assets/script.js"></script>
</body>
</html>
