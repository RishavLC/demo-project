<?php
include "config.php";

$auction_id = intval($_GET['auction_id']);
$sql = "SELECT MAX(bid_amount) AS highest FROM bids WHERE item_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
