<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

$result = $conn->prepare("
SELECT f.id, a.title AS item_title, f.status, f.created_at
FROM auction_feedback f
JOIN auction_items a ON a.id = f.item_id
WHERE f.sender_id = ?
ORDER BY f.created_at DESC
");
$result->bind_param("i", $user_id);
$result->execute();
$feedbacks = $result->get_result();
$result->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Feedbacks</title>
<style>
body { font-family: Arial; padding: 20px; background: #f4f6f8; }
.container { max-width: 100%; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
th { background: #2c3e50; color: white; }
.status-open { color: red; font-weight: bold; }
.status-reviewed { color: green; font-weight: bold; }
a { text-decoration: none; color: #007bff; }
</style>
<link rel="stylesheet" href="../assets/style.css">

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
<div class="main-content">

<div class="container">
<h2>My Feedbacks</h2>
<table>
<tr>
    <th>ID</th>
    <th>Item</th>
    <th>Status</th>
    <th>Created</th>
    <th>Action</th>
</tr>
<?php while($row = $feedbacks->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['item_title']) ?></td>
    <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
    <td><?= $row['created_at'] ?></td>
    <td><a href="feedback_view.php?id=<?= $row['id'] ?>">View</a></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
