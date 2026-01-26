<?php
session_start();
include '../common/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// for username import
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
// Fetch all items the user has added
$sql = "
  SELECT ai.*, 
         (SELECT MAX(bid_amount) FROM bids WHERE bids.item_id = ai.id) AS highest_bid,
         (SELECT COUNT(*) FROM bids WHERE bids.item_id = ai.id) AS total_bids,
         (
           SELECT u.username 
           FROM bids b 
           JOIN users u ON u.id = b.bidder_id
           WHERE b.item_id = ai.id
           ORDER BY b.bid_amount DESC
           LIMIT 1
         ) AS winner_name
  FROM auction_items ai
  WHERE ai.seller_id = ?
  ORDER BY ai.created_at DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Added Items</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 0;
    }

    .container {
      margin-left: 230px;
      padding: 25px;
    }

    h2 {
      color: #333;
      margin-bottom: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      overflow: hidden;
    }

    th, td {
      padding: 12px 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }

    th {
      background: #3498db;
      color: white;
      font-weight: 600;
    }

    tr:hover {
      background: #f1f1f1;
    }

    .status-active {
      color: #27ae60;
      font-weight: bold;
    }

    .status-closed {
      color: #e74c3c;
      font-weight: bold;
    }

    .no-record {
      text-align: center;
      padding: 20px;
      font-size: 16px;
      color: #888;
    }

    .back-btn {
      display: inline-block;
      background: #3498db;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      margin-bottom: 15px;
    }

    .back-btn:hover {
      background: #2980b9;
    }
  </style>
</head>
<body>
    <div class="sidebar">
  <div class="sidebar-header">
    <!-- Logo instead of Welcome -->
    <div class="logo-box">
      <img src="../images/logo.jpeg" alt="EasyBid Logo" class="logo-img">
      <span class="logo-text">EasyBid</span>
    </div>
    <div class="toggle-btn">☰</div>
  </div>

  <ul>
    <li><a href="../users/" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <!-- <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li> -->
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">🪙 <span>Place Bids</span></a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="feedback_list.php" data-label="Feedback list">💬 <span>My Feedback</span></a></li>
    <li><a href="../auth/logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>
  <div class="container">
    <h2>My Added Auction Items</h2>

    <?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Image</th>
          <th>Item </th>
          <th>Category</th>
          <th>Starting Price</th>
          <th>Highest Bid</th>
          <th>Total Bids</th>
          <th>Winner</th>
          <th>Status</th>
          <th>Bid History</th>
          <th>Messages</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $sn = 1;
        while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td>
            <?php
            /* ===== FETCH IMAGE FOR THIS ITEM ===== */
$imgSql = "
    SELECT image_path 
    FROM auction_images 
    WHERE item_id = ? 
    ORDER BY is_primary DESC, id ASC 
    LIMIT 1
";
$imgStmt = $conn->prepare($imgSql);
$imgStmt->bind_param("i", $row['id']);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
$imgRow = $imgRes->fetch_assoc();
$imgStmt->close();

/* assign image_path into row so your existing code works */
$row['image_path'] = $imgRow['image_path'] ?? '';

if (!empty($row['image_path'])) {
    $clean_path = str_replace(['../', './'], '', $row['image_path']);
    $img_url = "../" . $clean_path;
} else {
    $img_url = "../assets/no-image.png";
}
?>
<img src="<?= $img_url ?>" 
     width="70" height="60" 
     style="object-fit:cover;border-radius:6px;">
</td>
          </td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['category']) ?></td>
          <td>Rs. <?= number_format($row['start_price'], 2) ?></td>
          <td>Rs. <?= $row['highest_bid'] ? number_format($row['highest_bid'], 2) : '-' ?></td>
          <td><?= $row['total_bids'] ?></td>
                
  <!-- Winner Column -->
  <td>
    <?php if ($row['winner_name']): ?>
      <?php if ($row['status'] === 'active'): ?>
        <span style="color:#f39c12;font-weight:bold;">
          Leading: <?= htmlspecialchars($row['winner_name']) ?>
        </span>
      <?php else: ?>
        <span style="color:#27ae60;font-weight:bold;">
          <?= htmlspecialchars($row['winner_name']) ?>
        </span>
      <?php endif; ?>
    <?php else: ?>
      —
    <?php endif; ?>
  </td>
<td class="<?= $row['status'] === 'active' ? 'status-active' : 'status-closed' ?>">
            <?= ucfirst($row['status']) ?>
          </td>
  <!-- Bid History Button -->
<td>
<?php if ($row['status'] == 'pending'): ?>
    <a href="edit_item.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
        Edit
    </a>
<?php else: ?>
    <a href="bid_history.php?item_id=<?= $row['id'] ?>" class="btn btn-info btn-sm">
      View
    </a>

<?php endif; ?>
</td>
<!-- Messages Column -->
<td>

<!-- Messages Block -->
<div class="messages-block">
    <div class="messages-list">
    <?php
    // Fetch all conversations for this item
    $convStmt = $conn->prepare("
        SELECT c.id, u.username AS buyer_name, 
               (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id = c.buyer_id) AS unread
        FROM conversations c
        JOIN users u ON c.buyer_id = u.id
        WHERE c.item_id = ?
        ORDER BY c.created_at DESC
    ");
    $convStmt->bind_param("i", $row['id']);
    $convStmt->execute();
    $convs = $convStmt->get_result();
    $convStmt->close();

    if ($convs->num_rows > 0):
        while ($c = $convs->fetch_assoc()):
            $unreadBadge = $c['unread'] > 0 ? "<span class='unread'>({$c['unread']})</span>" : "";
            echo '<a href="chat_view.php?id='.$c['id'].'" class="chat-btn">
                    '.htmlspecialchars($c['buyer_name']).$unreadBadge.'
                  </a>';
        endwhile;
    else:
        echo '<span class="no-messages">No messages yet</span>';
    endif;
    ?>
    </div>
</div>

</td>
          
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-record">No items added yet.</div>
    <?php endif; ?>
  </div>
<script src="../assets/script.js"></script>
</body>
</html>
