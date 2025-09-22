<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}

include "config.php";
$user_id = $_SESSION["user_id"];
$message = "";

// ✅ Handle Bid Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["bid_amount"], $_POST["item_id"])) {
    $item_id = intval($_POST["item_id"]);
    $bid_amount = floatval($_POST["bid_amount"]);

    // Fetch current item info
    $stmt = $conn->prepare("SELECT current_price, end_time FROM auction_items WHERE id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        $now = date("Y-m-d H:i:s");
        if ($now > $item['end_time']) {
            $message = "<p style='color:red;font-weight:bold;'>❌ Auction has already ended.</p>";
        } elseif ($bid_amount <= $item['current_price']) {
            $message = "<p style='color:red;font-weight:bold;'>⚠ Bid must be higher than current price.</p>";
        } else {
            // Insert bid
            $stmt = $conn->prepare("INSERT INTO bids (item_id, bidder_id, bid_amount) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $item_id, $user_id, $bid_amount);
            if ($stmt->execute()) {
                // Update current price
                $stmt2 = $conn->prepare("UPDATE auction_items SET current_price=? WHERE id=?");
                $stmt2->bind_param("di", $bid_amount, $item_id);
                $stmt2->execute();

                $message = "<p style='color:green;font-weight:bold;'>✅ Bid placed successfully!</p>";
            }
        }
    }
}
// // Notify seller
// $seller_id = $item['seller_id'];
// $msg = "New bid placed on your item: " . $item['title'];
// $conn->query("INSERT INTO notifications (user_id, message) VALUES ($seller_id, '$msg')");

// // Notify other bidders
// $other_bidders = $conn->query("SELECT DISTINCT bidder_id FROM bids WHERE item_id={$item_id} AND bidder_id != $user_id");
// while ($ob = $other_bidders->fetch_assoc()) {
//     $msg = "Another user placed a bid on item: " . $item['title'];
//     $conn->query("INSERT INTO notifications (user_id, message) VALUES ({$ob['bidder_id']}, '$msg')");
// }

// ✅ Fetch all active auction items
$sql = "SELECT * FROM auction_items 
        WHERE status='active' AND seller_id != $user_id 
        AND NOW() BETWEEN start_time AND end_time";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Place Bid</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .auction-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .auction-card {
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.15);
        }
        .auction-card h3 {
            margin: 0 0 10px;
        }
        .auction-card p {
            margin: 5px 0;
        }
        .auction-card form {
            margin-top: 10px;
        }
        .auction-card input {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .auction-card button {
            width: 100%;
            padding: 8px;
            background: #4a90e2;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .auction-card button:hover {
            background: #357ab7;
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
    <li><a href="dashboard_user.php">🏠 Dashboard</a></li>
    <li><a href="my_bids.php">📜 My Bidding History</a></li>
    <li><a href="add_record.php">➕ Add Record</a></li>
    <li><a href="add_auction_item.php">📦 Add Auction Item</a></li>
    <li><a href="auction_bid.php">💰 Place Bid</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
    <h2>Active Auctions</h2>
    <?= $message ?>

    <div class="auction-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="auction-card">
                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <p><b>Category:</b> <?= htmlspecialchars($row['category']) ?></p>
                    <p><b>Current Price:</b> $<?= number_format($row['current_price'], 2) ?></p>
                    <p><b>Ends At:</b> <?= $row['end_time'] ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                        <input type="number" step="0.01" name="bid_amount" placeholder="Enter your bid" required>
                        <button type="submit">💰 Place Bid</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No active auctions at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<script src="assets/script.js"></script>
</body>
</html>
