<?php
session_start();
include "../common/config.php";
// username
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
/* =======================
   AUTH CHECK
======================= */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$item_id = intval($_GET["item_id"] ?? 0);

if ($item_id <= 0) {
    die("Invalid auction item.");
}

/* =======================
   FETCH AUCTION ITEM
======================= */
$stmt = $conn->prepare("
    SELECT * FROM auction_items
    WHERE id=? AND status='active'
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$auction) {
    die("Auction not found or inactive.");
}

/* =======================
   FETCH USERNAME
======================= */
$stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

/* =======================
   FETCH IMAGES
======================= */
$stmt = $conn->prepare("
    SELECT * FROM auction_images
    WHERE item_id=?
    ORDER BY is_primary DESC, uploaded_at ASC
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$res = $stmt->get_result();

$images = [];
while ($row = $res->fetch_assoc()) {
    $clean = str_replace(['../','./'],'',$row['image_path']);
    $row['full_url'] = "../".$clean;
    $images[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($auction["title"]) ?></title>

<link rel="stylesheet" href="../assets/style.css">

<style>
body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; }
.sidebar { width:220px; background:#2c3e50; color:#fff; position:fixed; top:0; bottom:0; padding-top:20px; }
.sidebar-header { text-align:center; font-weight:bold; margin-bottom:20px; }
.sidebar ul { list-style:none; padding:0; }
.sidebar ul li { padding:10px 20px; }
.sidebar ul li a { color:#fff; text-decoration:none; display:block; }
.sidebar ul li a:hover { background:#34495e; border-radius:6px; }
.main-content { margin-left:240px; padding:20px; }

.auction-wrapper {
    max-width:1000px;
    margin:auto;
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,.15);
}

.gallery { display:flex; gap:20px; }
.main-image img { width:380px; border-radius:10px; }
.thumbs img {
    width:80px; height:70px;
    object-fit:cover;
    cursor:pointer;
    border-radius:6px;
    margin:5px;
    border:2px solid #ddd;
}

.price { font-size:22px; font-weight:bold; color:#27ae60; }

.bid-box {
    margin-top:20px;
    background:#f4f6f8;
    padding:15px;
    border-radius:10px;
}
.bid-box input, .bid-box button {
    width:100%;
    padding:10px;
    margin-top:10px;
}
.bid-box button {
    background:#4a90e2;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.bid-box button:hover { background:#357ab7; }
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

<!-- MAIN -->
<div class="main-content">
<div class="auction-wrapper">

<h2><?= htmlspecialchars($auction["title"]) ?></h2>

<!-- GALLERY -->
<div class="gallery">
    <div class="main-image">
        <?php if ($images): ?>
            <img id="mainImg" src="<?= $images[0]["full_url"] ?>">
        <?php else: ?>
            <img src="../assets/no-image.png">
        <?php endif; ?>
    </div>
    <div class="thumbs">
        <?php foreach ($images as $img): ?>
            <img src="<?= $img["full_url"] ?>" onclick="changeImage(this.src)">
        <?php endforeach; ?>
    </div>
</div>

<hr>

<p><strong>Category:</strong> <?= htmlspecialchars($auction["category"]) ?></p>
<p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($auction["description"])) ?></p>

<p class="price">
Current Price:
<span id="currentPrice" data-price="<?= $auction["current_price"] ?>">
Rs <?= number_format($auction["current_price"],) ?>
</span>
</p>

<p><strong>Minimum Increment:</strong> Rs <?= number_format($auction["min_increment"],2) ?></p>
<p><strong>Ends At:</strong> <?= htmlspecialchars($auction["end_time"]) ?></p>

<!-- BID FORM -->
<form class="bid-box" onsubmit="return placeBid();">
<label>Your Bid</label>
<input type="number"
       id="bidAmount"
       step="0.01"
       min="<?= $auction["current_price"] + $auction["min_increment"] ?>"
       value="<?= $auction["current_price"] + $auction["min_increment"] ?>"
       required>

<button>Place Bid</button>
</form>

</div>
</div>
<script src="../assets/script.js"></script>

<script>
const ITEM_ID = <?= $item_id ?>;
const MIN_INC = <?= $auction["min_increment"] ?>;

function pf(v){ return parseFloat(v)||0; }

function changeImage(src){
    document.getElementById("mainImg").src = src;
}

/* PLACE BID */
function placeBid(){
    const bid = document.getElementById("bidAmount").value;
    const fd = new FormData();
    fd.append("item_id", ITEM_ID);
    fd.append("bid_amount", bid);

    fetch("../api/place_bid.php", {
        method:"POST",
        body:fd
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.error){
            alert(d.error);
            return;
        }
        updatePrice(d.new_price);
         alert("🎉 Bid Successfully Placed!");
    });
    return false;
}

/* UPDATE PRICE */
function updatePrice(price){
    document.getElementById("currentPrice").innerText =
        "Rs " + pf(price).toFixed(2);

    const nextMin = pf(price) + pf(MIN_INC);
    const bidInput = document.getElementById("bidAmount");
    bidInput.min = nextMin.toFixed(2);
    bidInput.value = nextMin.toFixed(2);
}

/* AUTO REFRESH PRICE */
setInterval(()=>{
    fetch("../api/get_current_price.php?item_id="+ITEM_ID)
    .then(r=>r.json())
    .then(d=>{
        if(!d.error){
            updatePrice(d.current_price);
        }
    });
},3000);
</script>
</body>
</html>
