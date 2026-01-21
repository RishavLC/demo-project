<?php
include "../common/config.php";
header("Content-Type: application/json");

if (!isset($_GET["item_id"])) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$item_id = intval($_GET["item_id"]);

$stmt = $conn->prepare("
    SELECT current_price, min_increment
    FROM auction_items
    WHERE id=? AND status='active'
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode(["error" => "Item not active"]);
    exit;
}

echo json_encode([
    "current_price" => (float)$item["current_price"],
    "min_increment" => (float)$item["min_increment"]
]);
