<?php
session_start();
include "../common/config.php";

header('Content-Type: application/json');

$data = [];

$sql = "
SELECT ai.id,
       COALESCE(MAX(b.bid_amount), ai.current_price) AS latest_price
FROM auction_items ai
LEFT JOIN bids b ON b.item_id = ai.id
WHERE ai.status='active'
GROUP BY ai.id
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[$row['id']] = number_format($row['latest_price'], 2, '.', '');
    }
}

echo json_encode($data);
