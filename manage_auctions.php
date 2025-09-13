<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

// ✅ Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve"])) {
    $id = intval($_POST["id"]);
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    $stmt = $conn->prepare("UPDATE auction_items 
        SET status='active', start_time=?, end_time=? 
        WHERE id=?");
    $stmt->bind_param("ssi", $start_time, $end_time, $id);
    $stmt->execute();
}

if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE auction_items SET status='rejected' WHERE id=$id");
}


// ✅ Fetch all items
$sql = "SELECT ai.*, u.username FROM auction_items ai 
        JOIN users u ON ai.seller_id = u.id
        ORDER BY ai.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Auctions</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="sidebar">
  <div class="sidebar-header">Admin Panel</div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Manage Auction Items</h2>
  <table>
    <tr>
      <th>ID</th><th>Title</th><th>Seller</th><th>Status</th><th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['title']) ?></td>
  <td><?= htmlspecialchars($row['username']) ?></td>
  <td><?= $row['start_price'] ?></td>
  <td>
    <form method="POST">
      <input type="hidden" name="id" value="<?= $row['id'] ?>">
      <label>Start Time:</label>
      <input type="datetime-local" name="start_time" required>
      <label>End Time:</label>
      <input type="datetime-local" name="end_time" required>
      <button type="submit" name="approve">✅ Approve</button>
    </form>
    <a href="?reject=<?= $row['id'] ?>" class="btn btn-delete">❌ Reject</a>
  </td>
</tr>
    <?php } ?>
  </table>
</div>
</body>
</html>
