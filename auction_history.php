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
    $where = "WHERE ai.status='approved' AND ai.start_time > NOW()";
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

    .main-content {
      padding: 20px;
    }

    h2 {
      text-align: center;
      color: #2c3e50;
      margin-bottom: 25px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
    }

    th {
      background: #4a90e2;
      color: white;
      text-transform: uppercase;
      font-size: 14px;
    }

    tr:nth-child(even) {
      background: #f2f6fc;
    }

    tr:hover {
      background: #e8f0fe;
    }

    .status {
      font-weight: bold;
      padding: 6px 10px;
      border-radius: 6px;
      display: inline-block;
      text-align: center;
      width: 90px;
    }

    .status-active { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-closed { background: #d1ecf1; color: #0c5460; }
    .status-upcoming { background: #e0f7fa; color: #006064; }
    .status-sold { background:#e8eaf6; color:#1a237e; }

    .winner-box {
      background: #ecf0f1;
      padding: 6px 8px;
      border-radius: 6px;
      display: inline-block;
      font-size: 13px;
      color: #2c3e50;
    }

    .summary {
      margin-top: 25px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 15px 20px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header">
    Admin Panel
    <div class="toggle-btn">☰</div>
  </div>
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

  <!-- ----------------------- -->
  <!-- FILTER DROPDOWN         -->
  <!-- ----------------------- -->
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
        <th>Item Title</th>
        <th>Seller</th>
        <th>Category</th>
        <th>Start Price</th>
        <th>Current / Final Price</th>
        <th>Current / Final Winner</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>

      <?php
      if ($result->num_rows > 0) {
        $count = 1;
        $total_auctions = 0;
        $closed_count = 0;
        $active_count = 0;
        $total_revenue = 0;

        while ($row = $result->fetch_assoc()) {

            $total_auctions++;
            $current_time = date('Y-m-d H:i:s');
            $auction_status = $row['status'];

            // detect upcoming
            if ($row['status'] == 'approved' && $row['start_time'] > $current_time) {
                $auction_status = 'upcoming';
            }

            $status_class = "status-" . strtolower($auction_status);

            // winner info
            $winnerQuery = $conn->prepare("SELECT b.bid_amount, u.username 
                                           FROM bids b 
                                           JOIN users u ON b.bidder_id=u.id 
                                           WHERE b.item_id=? 
                                           ORDER BY b.bid_amount DESC LIMIT 1");
            $winnerQuery->bind_param("i", $row['id']);
            $winnerQuery->execute();
            $winnerResult = $winnerQuery->get_result();

            $winner = "—";
            if ($winnerResult->num_rows > 0) {
                $w = $winnerResult->fetch_assoc();
                $winner = "<div class='winner-box'>🏆 " . htmlspecialchars($w['username']) . 
                          "<br>Rs. " . number_format($w['bid_amount'], 2) . "</div>";

                if ($row['status'] == 'closed') {
                    $total_revenue += $w['bid_amount'];
                }
            }

            if ($row['status'] == 'closed') $closed_count++;
            if ($row['status'] == 'active') $active_count++;

            echo "<tr>
                    <td>{$count}</td>
                    <td>" . htmlspecialchars($row['title']) . "</td>
                    <td>" . htmlspecialchars($row['seller_name']) . "</td>
                    <td>" . htmlspecialchars($row['category']) . "</td>
                    <td>Rs. " . number_format($row['start_price'], 2) . "</td>
                    <td>Rs. " . number_format($row['current_price'], 2) . "</td>
                    <td>{$winner}</td>
                    <td>" . ($row['start_time'] ?: '—') . "</td>
                    <td>" . ($row['end_time'] ?: '—') . "</td>
                    <td><span class='status {$status_class}'>" . ucfirst($auction_status) . "</span></td>
                  </tr>";
            $count++;
        }
      } else {
        echo "<tr><td colspan='10' style='text-align:center;'>No auction records found.</td></tr>";
      }
      ?>

    </tbody>
  </table>

  <?php if ($result->num_rows > 0) { ?>
  <div class="summary">
    <h3>📊 Auction Summary</h3>
    <p><strong>Total Auctions:</strong> <?= $total_auctions ?></p>
    <p><strong>Active Auctions:</strong> <?= $active_count ?></p>
    <p><strong>Closed Auctions:</strong> <?= $closed_count ?></p>
    <p><strong>Total Revenue Generated:</strong> Rs. <?= number_format($total_revenue, 2) ?></p>
  </div>
  <?php } ?>

</div>

</body>
</html>
