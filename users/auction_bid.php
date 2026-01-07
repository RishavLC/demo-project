<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";
$user_id = $_SESSION["user_id"];
$message = "";

// ✅ Fetch active auctions except seller’s own
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
<meta charset="utf-8" />
<title>Place Bid</title>

<link rel="stylesheet" href="../assets/style.css">

<style>
.auction-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.auction-card {
    background: #fff;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0px 3px 8px rgba(0,0,0,0.12);
}
.bid-input {
    display:flex;
    gap:8px;
    align-items:center;
    margin-top:8px;
}
.bid-input input[type="number"]{
    width:140px;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    text-align:center;
}
.bid-input button{
    background:#4a90e2;
    color:#fff;
    border:none;
    padding:8px 10px;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
}
.place-bid-btn{
    margin-top:10px;
    background:#4a90e2;
    color:#fff;
    border:none;
    padding:10px;
    border-radius:8px;
    cursor:pointer;
    width:100%;
    font-weight:600;
}
</style>
</head>

<body>

<div class="sidebar">
  <div class="sidebar-header">User Panel<div class="toggle-btn">☰</div></div>
 <ul>
    <li><a href="dashboard_user.php" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li>
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">💰 <span>Place Bids</span></a></li>
    <li><a href="auctions.php" class="active">📊 Auction Details</a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>


<div class="main-content">
<h2>Active Auctions — Place Your Bid</h2>

<div class="auction-container">

<?php while ($row = $result->fetch_assoc()):
    $item_id = $row["id"];
    $current_price = floatval($row["current_price"]);
    $min_inc = floatval($row["min_increment"]);
    $next_min = $current_price + $min_inc;
?>

<div class="auction-card">

<h3><?= htmlspecialchars($row["title"]) ?></h3>

<p><strong>Category:</strong> <?= htmlspecialchars($row["category"]) ?></p>

<p>
<strong>Current Price:</strong>
<span class="current-price" data-item-id="<?= $item_id ?>">
Rs. <?= number_format($current_price, 2) ?>
</span>
</p>

<p><strong>Minimum Increment:</strong> Rs. <?= number_format($min_inc,2) ?></p>

<p><strong>Ends At:</strong> <?= htmlspecialchars($row["end_time"]) ?></p>

<!-- AJAX BID FORM -->
<form method="POST"
      class="bid-form"
      onsubmit="return placeBid(this);">

<input type="hidden" name="item_id" value="<?= $item_id ?>">
<input type="hidden" name="bid_amount_min" value="<?= $next_min ?>">

<div class="bid-input">

<button type="button"
        class="decrease-btn"
        data-increment="<?= $min_inc ?>"
        onclick="decreaseBid(this)">−</button>

<input type="number"
       step="0.01"
       name="bid_amount"
       value="<?= number_format($next_min,2,'.','') ?>"
       min="<?= number_format($next_min,2,'.','') ?>"
       data-current="<?= number_format($current_price,2,'.','') ?>"
       data-mininc="<?= number_format($min_inc,2,'.','') ?>"
       required>

<button type="button"
        class="increase-btn"
        data-increment="<?= $min_inc ?>"
        onclick="increaseBid(this)">+</button>


</div>

<button type="submit" class="place-bid-btn" onclick="alert('Your bid placed sucessfully!!');">💰 Place Bid</button>

</form>

</div>

<?php endwhile; ?>
</div>
</div>

<script>
// ================= UTIL =================
function pf(v){ return parseFloat(String(v).replace(/,/g,'')) || 0; }

// =============== AJAX PLACE BID ===============
function placeBid(form) {

    const input = form.querySelector('input[name="bid_amount"]');
    const val = pf(input.value);
    const minAllowed = pf(input.min);

    if (val < minAllowed) {
        alert("⚠ Bid too low! Minimum: Rs. " + minAllowed.toFixed(2));
        return false;
    }

    const fd = new FormData(form);

    fetch("../api/place_bid.php", {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        if (d.error) {
            alert("❌ " + d.error);
            return;
        }

        // ✅ Update current price in this card instantly
        const card = form.closest(".auction-card");
        const priceSpan = card.querySelector(".current-price");
        if (priceSpan) {
            priceSpan.innerText = "Rs. " + pf(d.new_price).toFixed(2);
            input.min = (pf(d.new_price) + pf(input.dataset.mininc)).toFixed(2);
            input.dataset.current = pf(d.new_price).toFixed(2);
        }
    })
    .catch(err => console.error("Bid Error:", err));

    return false; // prevent default form submit
}

// =============== BUTTON ACTIONS ===============
function increaseBid(btn){
    const form = btn.closest(".bid-form");
    const input = form.querySelector('input[name="bid_amount"]');

    const inc = pf(btn.dataset.increment);
    let val = pf(input.value);

    const currentPrice = pf(input.dataset.current);
    const minInc = pf(input.dataset.mininc);

    if (val <= currentPrice || val < currentPrice + minInc) {
        val = currentPrice + minInc;
    } else {
        val += inc;
    }

    input.value = val.toFixed(2);
    input.min = (currentPrice + minInc).toFixed(2);
}

function decreaseBid(btn){
    const form = btn.closest(".bid-form");
    const input = form.querySelector('input[name="bid_amount"]');

    const inc = pf(btn.dataset.increment);
    let val = pf(input.value);

    const minAllowed = pf(input.min);
    val -= inc;
    if (val < minAllowed) val = minAllowed;

    input.value = val.toFixed(2);
}

// =============== LIVE PRICE POLLING ===============
function updatePrices() {
    fetch("../api/get_all_latest_bids.php")
    .then(res => res.json())
    .then(data => {

        document.querySelectorAll(".current-price").forEach(el => {
            const id = el.dataset.itemId;
            if (data[id]) {

                const oldPrice = pf(el.innerText.replace(/[^0-9.]/g,''));
                const newPrice = pf(data[id]);

                if (newPrice > oldPrice) {
                    el.innerText = "Rs. " + newPrice.toFixed(2);

                    // update bid input min
                    const card = el.closest(".auction-card");
                    const input = card.querySelector('input[name="bid_amount"]');
                    if (input) {
                        input.min = (newPrice + pf(input.dataset.mininc)).toFixed(2);
                        input.dataset.current = newPrice.toFixed(2);
                    }
                }
            }
        });

    })
    .catch(err => console.log("Polling error", err));
}

// Poll every 3 seconds
setInterval(updatePrices, 1000);
</script>

<script src="../assets/script.js"></script>

</body>
</html>
