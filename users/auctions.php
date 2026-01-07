<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}
include "../common/config.php";

// Fetch all ongoing auctions
$sql = "
    SELECT ai.*, 
           (SELECT MAX(bid_amount) FROM bids WHERE bids.item_id = ai.id) AS highest_bid,
           (SELECT u.username 
              FROM users u 
              JOIN bids b ON b.bidder_id = u.id 
             WHERE b.item_id = ai.id 
             ORDER BY b.bid_amount DESC LIMIT 1) AS highest_bidder,
           (SELECT COUNT(*) FROM bids WHERE bids.item_id = ai.id) AS total_bids
    FROM auction_items ai
    WHERE ai.status = 'active'
      AND NOW() BETWEEN ai.start_time AND ai.end_time
    ORDER BY ai.start_time DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title> Detailed Auction View</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; }
    .main-content { padding: 20px; }
    h2 { color: #333; margin-bottom: 15px; }
    .auction-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
    }
    .auction-card {
        background: #fff;
        padding: 18px;
        border-radius: 12px;
        box-shadow: 0px 3px 8px rgba(0,0,0,0.12);
    }
    .auction-card h3 { margin: 0 0 8px; color: #222; }
    .auction-card p { margin: 6px 0; color: #444; }
    .more-btn {
        margin-top: 10px;
        background: #4a90e2;
        color: #fff;
        border: none;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }
    .more-btn:hover { background: #357ab7; }
    .details {
        display: none;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid #ddd;
        font-size: 0.95rem;
        color: #555;
    }
    .bid-history {
        margin-top: 10px;
        padding: 8px;
        background: #f1f1f1;
        border-radius: 8px;
    }
    .bid-history table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .bid-history th, .bid-history td {
        border-bottom: 1px solid #ccc;
        padding: 6px;
        text-align: left;
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
<h2>🔍 Ongoing Auctions</h2>

<div class="auction-grid">
<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $item_id = $row['id'];
        $highest_bid = $row['highest_bid'] ? number_format($row['highest_bid'], 2) : 'No bids yet';
        $highest_bidder = $row['highest_bidder'] ?: '—';
        $total_bids = $row['total_bids'] ?? 0;

        echo "<div class='auction-card'>
                <h3>" . htmlspecialchars($row['title']) . "</h3>
                <p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>
                    <strong>Current Price:</strong>
                    <span class='current-price' data-item-id='" . $item_id . "'>
                    Rs. " . number_format($row['current_price'], 2, '.', '') . "
                    </span>
                </p>
                <p><strong>Highest Bid:</strong> Rs. {$highest_bid}</p>
                <p><strong>Highest Bidder:</strong> {$highest_bidder}</p>
                <p><strong>Total Bids:</strong> {$total_bids}</p>
                <p><strong>Ends At:</strong> " . htmlspecialchars($row['end_time']) . "</p>

                <div class='details' id='details-{$item_id}'></div>
              </div>";
    }
} else {
    echo "<p>No ongoing auctions right now.</p>";
}
?>
</div>
</div>
<script>
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
