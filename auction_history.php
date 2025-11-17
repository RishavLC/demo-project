<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

include "config.php";

// ------------------------------
// APPLY FILTER IF SELECTED
// ------------------------------
$filter = isset($_GET['filter']) ? $_GET['filter'] : "";
$where = "";

if ($filter == "active") {
    $where = "WHERE ai.status='active'";
}
else if ($filter == "closed") {
    $where = "WHERE ai.status='closed'";
}
else if ($filter == "pending") {
    $where = "WHERE ai.status='pending'";
}
else if ($filter == "rejected") {
    $where = "WHERE ai.status='rejected'";
}
else if ($filter == "sold") {
    $where = "WHERE ai.status='sold'";
}
else if ($filter == "upcoming") {
    // UPCOMING = status upcoming OR active but start_time > now
    $where = "WHERE (ai.status='upcoming' OR (ai.status='active' AND ai.start_time > NOW()))";
}


// ------------------------------
// MAIN SQL QUERY WITH FILTER
// ------------------------------
$sql = "SELECT ai.*, u.username AS seller_name 
        FROM auction_items ai 
        JOIN users u ON ai.seller_id = u.id 
        $where
        ORDER BY ai.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Auction History</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .main-content { padding: 20px; }
    h2 { text-align: center; margin-bottom: 25px; color: #2c3e50; }
    table { width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    th, td { padding: 12px 15px; }
    th { background:#4a90e2; color:white; text-transform:uppercase; }
    tr:nth-child(even){ background:#f2f6fc; }
    tr:hover { background:#e8f0fe; }

    .status { font-weight:bold; padding:6px 10px; border-radius:6px; display:inline-block; }
    .status-active { background:#d4edda; color:#155724; }
    .status-pending { background:#fff3cd; color:#856404; }
    .status-rejected { background:#f8d7da; color:#721c24; }
    .status-closed { background:#d1ecf1; color:#0c5460; }
    .status-upcoming { background:#e0f7fa; color:#006064; }
    .status-sold { background:#e8eaf6; color:#1a237e; }

    .winner-box {
      padding:5px 8px;
      background:#ecf0f1;
      border-radius:6px;
      font-size:13px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header">Admin Panel<div class="toggle-btn">☰</div></div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="auction_history.php">📜 Auction Status</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>🧾 All Auction Status</h2>

  <!-- FILTER AREA -->
  <form method="GET" style="margin-bottom: 15px; text-align:right;">
    <select name="filter" onchange="this.form.submit()"
      style="padding:8px; border-radius:8px; font-size:14px;">
        <option value="">🔍 Filter Auctions</option>
        <option value="active"   <?= ($filter=="active") ? "selected":"" ?>>Active</option>
        <option value="closed"   <?= ($filter=="closed") ? "selected":"" ?>>Closed</option>
        <option value="upcoming" <?= ($filter=="upcoming") ? "selected":"" ?>>Upcoming</option>
        <option value="pending"  <?= ($filter=="pending") ? "selected":"" ?>>Pending</option>
        <option value="sold"     <?= ($filter=="sold") ? "selected":"" ?>>Sold</option>
        <option value="rejected" <?= ($filter=="rejected") ? "selected":"" ?>>Rejected</option>
    </select>
  </form>

  <table>
    <thead>
    <tr>
      <th>#</th>
      <th>Item</th>
      <th>Seller</th>
      <th>Category</th>
      <th>Start Price</th>
      <th>Current Price</th>
      <th>Winner</th>
      <th>Start</th>
      <th>End</th>
      <th>Status</th>
    </tr>
    </thead>

    <tbody>
    <?php
    if ($result->num_rows > 0) {
        $count = 1;

        while ($row = $result->fetch_assoc()) {

            // AUTO UPDATE UPCOMING DETECTION
            $current_time = date('Y-m-d H:i:s');

            if ($row['start_time'] > $current_time && $row['status'] == 'active') {
                $row['status'] = 'upcoming';
            }

            $status_class = "status-" . strtolower($row['status']);

            // Winner fetch
            $winnerQuery = $conn->prepare("
                SELECT b.bid_amount, u.username
                FROM bids b 
                JOIN users u ON b.bidder_id = u.id
                WHERE b.item_id = ?
                ORDER BY b.bid_amount DESC
                LIMIT 1
            ");
            $winnerQuery->bind_param("i", $row['id']);
            $winnerQuery->execute();
            $winnerResult = $winnerQuery->get_result();

            $winner = "—";
            if ($winnerResult->num_rows > 0) {
                $w = $winnerResult->fetch_assoc();
                $winner = "<div class='winner-box'>🏆 " . $w['username'] .
                          "<br>Rs. " . number_format($w['bid_amount'], 2) . "</div>";
            }

            echo "
            <tr>
                <td>{$count}</td>
                <td>{$row['title']}</td>
                <td>{$row['seller_name']}</td>
                <td>{$row['category']}</td>
                <td>Rs. " . number_format($row['start_price']) . "</td>
                <td>Rs. " . number_format($row['current_price']) . "</td>
                <td>$winner</td>
                <td>{$row['start_time']}</td>
                <td>{$row['end_time']}</td>
                <td><span class='status {$status_class}'>" . ucfirst($row['status']) . "</span></td>
            </tr>";
            $count++;
        }
    } else {
        echo "<tr><td colspan='10' style='text-align:center;'>No auction records found.</td></tr>";
    }
    ?>
    </tbody>
  </table>
</div>

</body>
</html>
