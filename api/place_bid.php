<?php
session_start();
include "../common/config.php";

header("Content-Type: application/json");

// ✅ Check login
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    echo json_encode(["error" => "Not authorized"]);
    exit;
}

// ✅ Accept for ALL items
if (!isset($_POST["item_id"], $_POST["bid_amount"])) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$user_id = $_SESSION["user_id"];
$item_id = intval($_POST["item_id"]);
$bid_amount = floatval($_POST["bid_amount"]);

// fetch price + increment
$stmt = $conn->prepare("
SELECT current_price, end_time, min_increment
FROM auction_items
WHERE id=? AND status='active'
LIMIT 1
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode(["error" => "Item not active"]);
    exit;
}

// validate amount
$min_allowed = $item["current_price"] + $item["min_increment"];

if ($bid_amount < $min_allowed) {
    echo json_encode(["error" => "Bid must be at least Rs. {$min_allowed}"]);
    exit;
}

// ✅ Insert bid
$stmt = $conn->prepare("INSERT INTO bids (item_id, bidder_id, bid_amount) VALUES (?, ?, ?)");
$stmt->bind_param("iid", $item_id, $user_id, $bid_amount);
$stmt->execute();
$stmt->close();

// ✅ Update price
$up = $conn->prepare("UPDATE auction_items SET current_price=? WHERE id=?");
$up->bind_param("di", $bid_amount, $item_id);
$up->execute();
$up->close();

echo json_encode(["success" => true, "new_price" => $bid_amount]);
