<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}

include "config.php";

$user_id = $_SESSION["user_id"];
$message = "";

// Ensure item_id exists
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["item_id"])) {
    $item_id = intval($_POST["item_id"]);

    // Fetch the item info
    $stmt = $conn->prepare("SELECT id, title, category, buy_now_price, status, seller_id FROM auction_items WHERE id=? AND status='active' LIMIT 1");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();

    if (!$item) {
        $message = "<p style='color:red;font-weight:bold;'>❌ Item not available or already sold.</p>";
    } else {
        if ($item["category"] !== "Antique") {
            $message = "<p style='color:red;font-weight:bold;'>⚠ This item is not eligible for direct claim.</p>";
        } else {
            $price = floatval($item["buy_now_price"]);

            // Record as bid (for transparency)
            $stmt = $conn->prepare("INSERT INTO bids (item_id, bidder_id, bid_amount, bid_time) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iid", $item_id, $user_id, $price);
            $stmt->execute();
            $stmt->close();

            // Update item to sold
            $stmt2 = $conn->prepare("UPDATE auction_items SET status='sold', current_price=?, winner_id=?, end_time=NOW() WHERE id=?");
            $stmt2->bind_param("dii", $price, $user_id, $item_id);
            $stmt2->execute();
            $stmt2->close();

            // Insert into auction history (optional if you have that table)
            $stmt3 = $conn->prepare("INSERT INTO auctionhistory (item_id, buyer_id, final_price, sold_date) VALUES (?, ?, ?, NOW())");
            $stmt3->bind_param("iid", $item_id, $user_id, $price);
            $stmt3->execute();
            $stmt3->close();

            $message = "<p style='color:green;font-weight:bold;'>💎 Congratulations! You successfully claimed '<b>" . htmlspecialchars($item['title']) . "</b>' for Rs. " . number_format($price,2) . ".</p>";
        }
    }
} else {
    $message = "<p style='color:red;font-weight:bold;'>Invalid Request.</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buy Now Confirmation</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .confirmation-box {
            max-width: 600px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirmation-box h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .confirmation-box p {
            font-size: 1.1rem;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            background: #4a90e2;
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: #357ab7;
        }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h2>Instant Purchase Status</h2>
        <?= $message ?>
        <a href="auction_bid.php" class="back-btn">← Back to Auctions</a>
    </div>
</body>
</html>
