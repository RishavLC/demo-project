<?php
session_start();
include "../common/config.php";
if(!isset($_SESSION['role']) || $_SESSION['role']!=='admin') die("Unauthorized");

$result = $conn->query("
SELECT f.id, a.title AS item_title, u.username AS sender_name, f.status, f.created_at
FROM auction_feedback f
JOIN auction_items a ON a.id=f.item_id
JOIN users u ON u.id=f.sender_id
ORDER BY f.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Feedbacks</title>
    <link rel="stylesheet" href="../assets/style.css">
<style>
body { font-family: Arial; padding: 20px; background: #f4f6f8; }
.container { max-width: 100%; margin:auto; background:#fff; padding:20px; border-radius:8px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding:10px; border:1px solid #ddd; text-align:center; }
th { background:#2c3e50; color:white; }
.status-open { color:red; font-weight:bold; }
.status-reviewed { color:green; font-weight:bold; }
a { text-decoration:none; color:#007bff; }
</style>
</head>
<body>
    <div class="sidebar">
  <div class="sidebar-header">
    <div class="logo-box">
      <img src="../images/logo.jpeg" alt="EasyBid Logo">
      <span class="logo-text">EasyBid</span>
    </div>
    <div class="toggle-btn">☰</div>
  </div>

  <ul>
    <li><a href="../admin/">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <!-- <li><a href="manage_auctions.php">📦 Manage Auctions</a></li> -->
    <li><a href="feedback_list.php">💬 Feedback</a></li>
    <!-- DROPDOWN -->
    <li>
      <a class="caret" onclick="toggleDropdown('auctionDropdown')">
        📜 Auctions 
      </a>
      <ul class="dropdown-menu" id="auctionDropdown">
        <li><a href="auctions_active.php">🟢 Active</a></li>
        <li><a href="auctions_upcoming.php">🟡 Upcoming</a></li>
        <li><a href="auction_overview.php">📜 History</a></li>
      </ul>
    </li>

    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
<div class="container">
<h2>All Feedbacks</h2>
<table>
<tr><th>ID</th><th>Item</th><th>User</th><th>Status</th><th>Action</th></tr>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['item_title']) ?></td>
<td><?= htmlspecialchars($row['sender_name']) ?></td>
<td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
<td><a href="feedback_view.php?id=<?= $row['id'] ?>">View</a></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
