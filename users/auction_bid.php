<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";
$user_id = $_SESSION["user_id"];

// Fetch active auctions except seller’s own
$sql = "SELECT * FROM auction_items 
        WHERE status='active' 
        AND seller_id != ?
        AND NOW() BETWEEN start_time AND end_time
        ORDER BY end_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Place Bid</title>

<link rel="stylesheet" href="../assets/style.css">

<style>
.auction-container {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:20px;
    margin-top:20px;
}
.auction-card {
    background:#fff;
    padding:18px;
    border-radius:12px;
    box-shadow:0 3px 8px rgba(0,0,0,0.12);
}
.gallery img { cursor:pointer; }
.main-image img {
    width:100%;
    height:220px;
    object-fit:cover;
    border-radius:8px;
}
.thumbs {
    display:flex;
    gap:6px;
    margin-top:8px;
}
.thumbs img {
    width:60px;
    height:60px;
    object-fit:cover;
    border-radius:6px;
}
.bid-input {
    display:flex;
    gap:8px;
    margin-top:8px;
}
.place-bid-btn {
    margin-top:10px;
    width:100%;
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
<h2>Active Auctions — Place Your Bid</h2>

<div class="auction-container">

<?php while ($row = $result->fetch_assoc()):
    $item_id = $row["id"];
    $current_price = (float)$row["current_price"];
    $min_inc = (float)$row["min_increment"];
    $next_min = $current_price + $min_inc;

    // ✅ FETCH IMAGES FOR THIS ITEM
    $stmt_img = $conn->prepare("
        SELECT * FROM auction_images 
        WHERE item_id=? 
        ORDER BY is_primary DESC, uploaded_at ASC
    ");
    $stmt_img->bind_param("i", $item_id);
    $stmt_img->execute();
    $img_result = $stmt_img->get_result();

    $images = [];
    while ($img = $img_result->fetch_assoc()) {
        $clean = str_replace(['../','./'],'',$img['image_path']);
        $img['full_url'] = "../".$clean;
        $images[] = $img;
    }
    $stmt_img->close();
?>

<div class="auction-card">

<h3><?= htmlspecialchars($row["title"]) ?></h3>

<!-- IMAGE GALLERY -->
<div class="gallery">
    <div class="main-image">
        <?php if (!empty($images)): ?>
            <img id="mainImg<?= $item_id ?>" src="<?= $images[0]['full_url'] ?>">
        <?php else: ?>
            <img src="../assets/no-image.png">
        <?php endif; ?>
    </div>

    <div class="thumbs">
        <?php foreach ($images as $img): ?>
            <img src="<?= $img['full_url'] ?>"
                 onclick="changeImage('<?= $img['full_url'] ?>', <?= $item_id ?>)">
        <?php endforeach; ?>
    </div>
</div>

<p><strong>Category:</strong> <?= htmlspecialchars($row["category"]) ?></p>

<p>
<strong>Current Price:</strong>
<span class="current-price" data-item-id="<?= $item_id ?>">
Rs. <?= number_format($current_price,2) ?>
</span>
</p>

<p><strong>Minimum Increment:</strong> Rs. <?= number_format($min_inc,2) ?></p>
<p><strong>Ends At:</strong> <?= htmlspecialchars($row["end_time"]) ?></p>

<form class="bid-form" onsubmit="return placeBid(this);">
<input type="hidden" name="item_id" value="<?= $item_id ?>">

<div class="bid-input">
<button type="button" onclick="decreaseBid(this)">−</button>

<input type="number"
       name="bid_amount"
       value="<?= number_format($next_min,2,'.','') ?>"
       min="<?= number_format($next_min,2,'.','') ?>"
       data-current="<?= number_format($current_price,2,'.','') ?>"
       data-mininc="<?= number_format($min_inc,2,'.','') ?>"
       step="0.01"
       required>

<button type="button" onclick="increaseBid(this)">+</button>
</div>

<button class="place-bid-btn">Place Bid</button>
</form>

</div>

<?php endwhile; ?>

</div>
</div>

<script>
function pf(v){ return parseFloat(v)||0; }

function changeImage(src,id){
    document.getElementById("mainImg"+id).src = src;
}

function increaseBid(btn){
    const i = btn.parentElement.querySelector("input");
    i.value = (pf(i.value)+pf(i.dataset.mininc)).toFixed(2);
}

function decreaseBid(btn){
    const i = btn.parentElement.querySelector("input");
    let v = pf(i.value)-pf(i.dataset.mininc);
    if(v < pf(i.min)) v = pf(i.min);
    i.value = v.toFixed(2);
}

function placeBid(form){
    const fd = new FormData(form);
    fetch("../api/place_bid.php",{method:"POST",body:fd})
    .then(r=>r.json())
    .then(d=>{
        if(d.error){ alert(d.error); return; }
        form.closest(".auction-card")
            .querySelector(".current-price")
            .innerText = "Rs. "+pf(d.new_price).toFixed(2);
    });
    return false;
}
</script>

<script src="../assets/script.js"></script>
</body>
</html>
