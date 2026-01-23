<?php
session_start();
include "../common/config.php";

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
   FETCH USERNAME
======================= */
$stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

/* =======================
   FETCH AUCTION ITEM
======================= */
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

/* =======================
   FETCH IMAGES
======================= */
$stmt = $conn->prepare("
    SELECT * FROM auction_images
    WHERE item_id=?
    ORDER BY is_primary DESC, uploaded_at ASC
");
$stmt->bind_param("i",$item_id);
$stmt->execute();
$res = $stmt->get_result();
$images = [];

while ($row = $res->fetch_assoc()) {
    $row["full_url"] = "../".str_replace(['../','./'],'',$row["image_path"]);
    $images[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($auction["title"]) ?></title>

<style>
body { font-family: Arial; background:#f4f6f8; margin:0; }
.main { margin-left:240px; padding:20px; }
.wrapper {
    max-width:1100px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,.1);
}

.badge {
    background:#2ecc71;
    color:white;
    padding:6px 14px;
    border-radius:20px;
    font-size:12px;
}

.header h2 { margin-bottom:8px; }
.meta { display:flex; gap:15px; color:#555; }

.gallery { display:flex; gap:20px; margin-top:20px; }
.main-image img { width:400px; border-radius:10px; }
.thumbs img {
    width:90px; height:70px;
    object-fit:cover;
    cursor:pointer;
    border-radius:6px;
    border:2px solid #ddd;
    margin-bottom:6px;
}

.countdown {
    background:#fff3cd;
    padding:12px;
    border-left:5px solid #f39c12;
    margin:15px 0;
    font-weight:bold;
}

.grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:15px;
    margin:20px 0;
}

.card {
    background:#f9f9f9;
    padding:15px;
    border-radius:10px;
    text-align:center;
}

.card.highlight {
    background:#e8f8f0;
    border:2px solid #2ecc71;
}

.price { font-size:22px; color:#27ae60; font-weight:bold; }

.bid-box {
    background:#f4f6f8;
    padding:20px;
    border-radius:10px;
}

.bid-box input, .bid-box button {
    width:100%;
    padding:12px;
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

.bid-history {
    margin-top:30px;
    background:#fff;
    padding:15px;
    border-radius:10px;
}

.winner {
    background:#e8f8f0;
    padding:20px;
    border-radius:10px;
    text-align:center;
    margin-top:20px;
}

.pay-btn {
    display:inline-block;
    padding:10px 20px;
    background:#27ae60;
    color:white;
    text-decoration:none;
    border-radius:6px;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="main">
<div class="wrapper">

<div class="header">
    <h2><?= htmlspecialchars($auction["title"]) ?></h2>
    <div class="meta">
        <span class="badge">ACTIVE</span>
        <span>📂 <?= htmlspecialchars($auction["category"]) ?></span>
        <span>👤 Seller: <?= htmlspecialchars($auction["seller"]) ?></span>
        <span>🆔 Item #<?= $auction["id"] ?></span>
    </div>
</div>

<div class="countdown">
    ⏳ Auction Ends In: <span id="timer">Loading...</span>
</div>

<div class="gallery">
    <div class="main-image">
        <img id="mainImg" src="<?= $images ? $images[0]['full_url'] : '../assets/no-image.png' ?>">
    </div>
    <div class="thumbs">
        <?php foreach($images as $img): ?>
            <img src="<?= $img["full_url"] ?>" onclick="changeImage(this.src)">
        <?php endforeach; ?>
    </div>
</div>

<div class="grid">
    <div class="card highlight">
        <h4>💰 Current Price</h4>
        <p class="price" id="currentPrice">Rs <?= number_format($auction["current_price"],2) ?></p>
    </div>

    <div class="card">
        <h4>📈 Min Increment</h4>
        <p>Rs <?= number_format($auction["min_increment"],2) ?></p>
    </div>

    <div class="card">
        <h4>⏱ Ends At</h4>
        <p><?= htmlspecialchars($auction["end_time"]) ?></p>
    </div>
</div>

<h3>📄 Description</h3>
<p><?= nl2br(htmlspecialchars($auction["description"])) ?></p>

<form class="bid-box" onsubmit="return placeBid();">
    <h3>🚀 Place Your Bid</h3>
    <input type="number" id="bidAmount"
        min="<?= $auction["current_price"] + $auction["min_increment"] ?>"
        value="<?= $auction["current_price"] + $auction["min_increment"] ?>"
        step="0.01" required>
    <button>Place Bid</button>
</form>

<div class="bid-history">
    <h3>📜 Live Bid History</h3>
    <div id="bidHistory">Loading...</div>
</div>

</div>
</div>

<script>
const ITEM_ID = <?= $item_id ?>;
const END_TIME = new Date("<?= $auction['end_time'] ?>").getTime();
const MIN_INC = <?= $auction['min_increment'] ?>;

function changeImage(src){
    document.getElementById("mainImg").src = src;
}

function placeBid(){
    const fd = new FormData();
    fd.append("item_id", ITEM_ID);
    fd.append("bid_amount", bidAmount.value);

    fetch("../api/place_bid.php",{ method:"POST", body:fd })
    .then(r=>r.json())
    .then(d=>{
        if(d.error){ alert(d.error); return; }
        alert("🎉 Bid placed successfully!");
    });
    return false;
}

setInterval(()=>{
    const diff = END_TIME - new Date().getTime();
    if(diff <= 0){
        document.getElementById("timer").innerText = "Auction Ended";
        return;
    }
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    document.getElementById("timer").innerText = `${h}h ${m}m ${s}s`;
},1000);

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
    .then(d=> bidHistory.innerHTML = d);
},3000);
</script>

</body>
</html>
