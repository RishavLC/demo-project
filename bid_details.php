<?php
include "config.php";
if (!isset($_GET["item_id"])) exit("Invalid request.");

$item_id = intval($_GET["item_id"]);

// Fetch auction details
$sql = "SELECT ai.*, u.username AS seller_name 
        FROM auction_items ai
        JOIN users u ON ai.seller_id = u.id
        WHERE ai.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) exit("Auction not found.");

// Fetch bid history
$sql2 = "SELECT b.bid_amount, u.username, b.created_at 
         FROM bids b 
         JOIN users u ON b.bidder_id = u.id 
         WHERE b.item_id = ?
         ORDER BY b.bid_amount DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $item_id);
$stmt2->execute();
$bids = $stmt2->get_result();
$stmt2->close();
?>

<div class="bid-history">
  <p><strong>Seller:</strong> <?= htmlspecialchars($item['seller_name']) ?></p>
  <p><strong>Description:</strong> <?= htmlspecialchars($item['description']) ?></p>
  <p><strong>Start Time:</strong> <?= htmlspecialchars($item['start_time']) ?></p>
  <p><strong>End Time:</strong> <?= htmlspecialchars($item['end_time']) ?></p>
  <h4>🧾 Bid History:</h4>
  <?php if ($bids->num_rows > 0): ?>
  <table>
    <tr><th>Username</th><th>Bid Amount</th><th>Time</th></tr>
    <?php while ($b = $bids->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($b['username']) ?></td>
      <td>Rs. <?= number_format($b['bid_amount'], 2) ?></td>
      <td><?= htmlspecialchars($b['created_at']) ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <?php else: ?>
  <p>No bids yet.</p>
  <?php endif; ?>
</div>
