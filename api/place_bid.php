<?php
session_start();
include "../common/config.php";

header("Content-Type: application/json");

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    echo json_encode(["error" => "Not authorized"]);
    exit;
}

if (!isset($_POST["item_id"], $_POST["bid_amount"])) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$user_id    = (int)$_SESSION["user_id"];
$item_id    = (int)$_POST["item_id"];
$bid_amount = (float)$_POST["bid_amount"];

/* 🔹 Fetch auction item */
$stmt = $conn->prepare("
    SELECT current_price, min_increment, status 
    FROM auction_items 
    WHERE id=? AND status='active'
    LIMIT 1
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode(["error" => "Auction not active"]);
    exit;
}

/* 🔹 Fetch LAST bid (MOST IMPORTANT PART) */
$stmt = $conn->prepare("
    SELECT bidder_id 
    FROM bids 
    WHERE item_id=?
    ORDER BY bid_time DESC
    LIMIT 1
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$lastBid = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ❌ RULE 1: Same user cannot bid twice in a row */
if ($lastBid && (int)$lastBid["bidder_id"] === $user_id) {
    echo json_encode([
        "error" => "You cannot bid twice in a row. Wait for another bidder."
    ]);
    exit;
}

/* 🎯 RULE 2: First bid vs next bid */
if (!$lastBid) {
    // ✅ First bidder → allow starting/current price
    if ($bid_amount < (float)$item["current_price"]) {
        echo json_encode([
            "error" => "First bid must be at least Rs. " .
                       number_format($item["current_price"], 2)
        ]);
        exit;
    }
} else {
    // 🔁 Next bidders → min increment applies
    $min_allowed = (float)$item["current_price"] + (float)$item["min_increment"];

    if ($bid_amount < $min_allowed) {
        echo json_encode([
            "error" => "Bid must be at least Rs. " .
                       number_format($min_allowed, 2)
        ]);
        exit;
    }
}

/* 🔥 Insert bid */
$conn->begin_transaction();

$stmt = $conn->prepare("
    INSERT INTO bids (item_id, bidder_id, bid_amount)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iid", $item_id, $user_id, $bid_amount);

if (!$stmt->execute()) {
    $conn->rollback();
    echo json_encode(["error" => "Failed to place bid"]);
    exit;
}
$stmt->close();

/* 🔹 Update auction price */
$stmt = $conn->prepare("
    UPDATE auction_items 
    SET current_price=? 
    WHERE id=?
");
$stmt->bind_param("di", $bid_amount, $item_id);
$stmt->execute();
$stmt->close();

$conn->commit();

echo json_encode([
    "success"   => true,
    "new_price"=> number_format($bid_amount,2)
]);
