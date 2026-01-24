<?php
session_start();
include "../common/config.php";

$item_id = intval($_GET['item_id'] ?? 0);

if ($item_id <= 0) {
    exit("Invalid item");
}

/* Fetch latest 5 bids */
$stmt = $conn->prepare("
    SELECT b.bid_amount, b.bid_time, u.username
    FROM bids b
    JOIN users u ON b.bidder_id = u.id
    WHERE b.item_id = ?
    ORDER BY b.bid_time DESC
    LIMIT 5
");

$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No bids yet.</p>";
    exit;
}

echo "<table>";
echo "<tr>
        <th>User</th>
        <th>Amount</th>
        <th>Time</th>
      </tr>";

$first = true;

while ($row = $result->fetch_assoc()) {

    if ($first) {
        echo "<tr class='highlight'>
                <td>{$row['username']} <span style='color:#27ae60;font-size:12px'>(Latest Bid)</span></td>
                <td><b>Rs " . number_format($row['bid_amount'],2) . "</b></td>
                <td>{$row['bid_time']}</td>
              </tr>";
        $first = false;
    } else {
        echo "<tr>
                <td>{$row['username']}</td>
                <td>Rs " . number_format($row['bid_amount'],2) . "</td>
                <td>{$row['bid_time']}</td>
              </tr>";
    }
}

echo "</table>";

$stmt->close();
