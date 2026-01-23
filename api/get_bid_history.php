<?php
include "../common/config.php";

$item_id = intval($_GET['item_id'] ?? 0);
$page = intval($_GET['page'] ?? 1);
$bidsPerPage = 5;
$offset = ($page - 1) * $bidsPerPage;

if ($item_id <= 0) {
    echo "<p>Invalid item.</p>";
    exit();
}

// Fetch total bids for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) AS total_bids FROM bids WHERE item_id = ?");
$countStmt->bind_param("i", $item_id);
$countStmt->execute();
$totalBids = $countStmt->get_result()->fetch_assoc()['total_bids'];
$countStmt->close();
$totalPages = ceil($totalBids / $bidsPerPage);

// Fetch bids for current page
$stmt = $conn->prepare("
    SELECT b.bid_amount, b.bid_time, u.username
    FROM bids b
    JOIN users u ON b.bidder_id = u.id
    WHERE b.item_id = ?
    ORDER BY b.bid_time DESC
    LIMIT ?, ?
");
$stmt->bind_param("iii", $item_id, $offset, $bidsPerPage);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No bids yet.</p>";
} else {
    echo "<table style='width:100%; border-collapse:collapse;'>
            <tr style='background:#f0f0f0;'>
                <th>#</th>
                <th>User</th>
                <th>Bid Amount</th>
                <th>Time</th>
            </tr>";
    $count = $offset + 1;
    while($row = $result->fetch_assoc()){
        echo "<tr>
                <td>{$count}</td>
                <td>{$row['username']}</td>
                <td>Rs ".number_format($row['bid_amount'],2)."</td>
                <td>".date("d M Y H:i", strtotime($row['bid_time']))."</td>
              </tr>";
        $count++;
    }
    echo "</table>";

    // Pagination links
    if ($totalPages > 1) {
        echo "<div style='text-align:center; margin-top:10px;'>";
        for ($p = 1; $p <= $totalPages; $p++) {
            $active = $p == $page ? "font-weight:bold; color:#4a90e2;" : "";
            echo "<a href='#' style='margin:0 5px; $active' onclick='loadBids($p)'>$p</a>";
        }
        echo "</div>";
    }
}
$stmt->close();
?>
