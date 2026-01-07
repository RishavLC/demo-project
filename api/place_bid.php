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

$user_id = $_SESSION["user_id"];
$item_id = intval($_POST["item_id"]);
$bid_amount = floatval($_POST["bid_amount"]);

/* 🔹 Fetch current price */
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

$min_allowed = floatval($item["current_price"]) + floatval($item["min_increment"]);

if ($bid_amount < $min_allowed) {
    echo json_encode(["error" => "Bid must be at least Rs. " . number_format($min_allowed,2)]);
    exit;
}

/* 🔥 Insert bid */
$stmt = $conn->prepare("INSERT INTO bids (item_id, bidder_id, bid_amount) VALUES (?, ?, ?)");
$stmt->bind_param("iid", $item_id, $user_id, $bid_amount);

if ($stmt->execute()) {

    $stmt2 = $conn->prepare("UPDATE auction_items SET current_price=? WHERE id=?");
    $stmt2->bind_param("di", $bid_amount, $item_id);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode(["success"=>true,"new_price"=>$bid_amount]);

} else {
    echo json_encode(["error"=>$stmt->error]);
}

$stmt->close();
