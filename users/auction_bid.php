<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}

include "../common/config.php";
$user_id = $_SESSION["user_id"];
$message = "";

// --- Handle Bid Submission (server-side validation)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["bid_amount"], $_POST["item_id"])) {
    $item_id = intval($_POST["item_id"]);
    $bid_amount = floatval($_POST["bid_amount"]);

    // Fetch current item info including min_increment
    $stmt = $conn->prepare("SELECT current_price, end_time, min_increment FROM auction_items WHERE id=? AND status='active' LIMIT 1");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();

    if (!$item) {
        $message = "<p style='color:red;font-weight:bold;'>❌ Item not found or not active.</p>";
    } else {
        $now = date("Y-m-d H:i:s");
        $min_inc = floatval($item['min_increment'] ?? 0.0);
        $min_allowed = floatval($item['current_price']) + $min_inc;

        if ($now > $item['end_time']) {
            $message = "<p style='color:red;font-weight:bold;'>❌ Auction has already ended.</p>";
        } elseif ($bid_amount < $min_allowed) {
            $message = "<p style='color:red;font-weight:bold;'>⚠ Bid must be at least Rs. " . number_format($min_inc,2) . " higher than the current price (minimum allowed: Rs. " . number_format($min_allowed,2) . ").</p>";
        } else {
            // Insert bid (transaction-like update)
            $stmt = $conn->prepare("INSERT INTO bids (item_id, bidder_id, bid_amount) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $item_id, $user_id, $bid_amount);
            if ($stmt->execute()) {
                // Update current price
                $stmt2 = $conn->prepare("UPDATE auction_items SET current_price=? WHERE id=?");
                $stmt2->bind_param("di", $bid_amount, $item_id);
                $stmt2->execute();
                $stmt2->close();

                $message = "<p style='color:green;font-weight:bold;'>✅ Bid placed successfully!</p>";
            } else {
                $message = "<p style='color:red;font-weight:bold;'>❌ Error placing bid: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        }
    }
}

// Fetch all active auction items (include min_increment)
$sql = "SELECT * FROM auction_items 
        WHERE status='active' AND seller_id != ? 
        AND NOW() BETWEEN IFNULL(start_time, '1970-01-01') AND IFNULL(end_time, '9999-12-31')";
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
    <script src="../assets/script.js"></script>
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
        .auction-card h3 { margin: 0 0 8px; }
        .auction-card p { margin: 6px 0; color: #333; }
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
        .bid-input button:hover{ background:#357ab7; }
        .place-bid-btn{
            margin-top:10px;
            background: #4a90e2;
            color:#fff;
            border:none;
            padding:10px;
            border-radius:8px;
            cursor:pointer;
            width:100%;
            font-weight:600;
        }
        .place-bid-btn:hover{ background: #357ab7; }
        .muted { color:#666; font-size:0.95rem; }
        .message { margin: 10px 0; }
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
    <h2>Active Auctions</h2>

    <?php if ($message): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <div class="auction-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                // ensure numeric values
                $current_price = number_format((float)$row['current_price'], 2, '.', '');
                $min_inc = number_format((float)($row['min_increment'] ?? 0), 2, '.', '');
                $next_min = number_format((float)$row['current_price'] + (float)$row['min_increment'], 2, '.', '');
            ?>
                <div class="auction-card">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($row['description']) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
                    <p><strong>Current Price:</strong> Rs. <?= $current_price ?></p>
                    <p><strong>Min Increment:</strong> Rs. <?= $min_inc ?></p>
                    <p><strong>Next Minimum Bid:</strong> Rs. <?= $next_min ?></p>
                    <p><strong>Ends At:</strong> <?= htmlspecialchars($row['end_time']) ?></p>

                    <form method="POST" class="bid-form" onsubmit="return validateBid(this);">
                        <input type="hidden" name="item_id" value="<?= intval($row['id']) ?>">

                        <div class="bid-input">
                            <button type="button" class="decrease-btn" data-increment="<?= $min_inc ?>">−</button>
                            <input type="number"
                                   step="0.01"
                                   name="bid_amount"
                                   value="<?= $next_min ?>"
                                   min="<?= $next_min ?>"
                                   data-current="<?= $current_price ?>"
                                   data-mininc="<?= $min_inc ?>"
                                   required>
                            <button type="button" class="increase-btn" data-increment="<?= $min_inc ?>">+</button>
                        </div>

                        <button type="submit" class="place-bid-btn">💰 Place Bid</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No active auctions at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function pf(v){ return parseFloat(String(v).replace(/,/g,'')) || 0; }

document.querySelectorAll('.increase-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    const inc = pf(this.dataset.increment);
    const form = this.closest('.bid-form');
    const input = form.querySelector('input[name="bid_amount"]');
    let val = pf(input.value);
    const currentPrice = pf(input.dataset.current);
    const minInc = pf(input.dataset.mininc);

    // ✅ First click sets to next minimum (current + minInc)
    if (val <= currentPrice || val < currentPrice + minInc) {
        val = currentPrice + minInc;
    } else {
        val += inc; // normal increment for further clicks
    }

    input.value = val.toFixed(2);
    input.min = (currentPrice + minInc).toFixed(2);
  });
});

document.querySelectorAll('.decrease-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    const inc = pf(this.dataset.increment);
    const form = this.closest('.bid-form');
    const input = form.querySelector('input[name="bid_amount"]');
    let val = pf(input.value);
    const minAllowed = pf(input.min);

    val -= inc;
    if (val < minAllowed) val = minAllowed;
    input.value = val.toFixed(2);
  });
});

// client-side validation before submit
function validateBid(form){
    const input = form.querySelector('input[name="bid_amount"]');
    const val = pf(input.value);
    const minAllowed = pf(input.min);
    if (val < minAllowed) {
        alert("⚠ Bid must be at least Rs. " + Number(minAllowed).toFixed(2) + " (current + min increment).");
        return false;
    }
    return true;
}
</script>
</body>
</html>
