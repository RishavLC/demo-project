<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";

// ------------------------------
// GET FILTER INPUTS
// ------------------------------
$filter = $_GET['filter'] ?? "";
$search = $_GET['search'] ?? "";
$sort   = $_GET['sort'] ?? "newest";

// ------------------------------
// BUILD WHERE CLAUSE
// ------------------------------
$whereParts = [];

if ($filter == "active") {
    $whereParts[] = "ai.status='active'";
}
else if ($filter == "closed") {
    $whereParts[] = "ai.status='closed'";
}
else if ($filter == "pending") {
    $whereParts[] = "ai.status='pending'";
}
else if ($filter == "rejected") {
    $whereParts[] = "ai.status='rejected'";
}
else if ($filter == "sold") {
    $whereParts[] = "ai.status='sold'";
}
else if ($filter == "upcoming") {
    $whereParts[] = "(ai.status='upcoming' OR (ai.status='active' AND ai.start_time > NOW()))";
}

// SEARCH FILTER (title, category, seller)
if ($search != "") {
    $searchText = "%" . $conn->real_escape_string($search) . "%";
    $whereParts[] = "(ai.title LIKE '$searchText' OR ai.category LIKE '$searchText' OR u.username LIKE '$searchText')";
}

$whereSQL = "";
if (count($whereParts) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereParts);
}

// ------------------------------
// SORT LOGIC
// ------------------------------
$sortSQL = "ORDER BY ai.created_at DESC"; // default newest

if ($sort == "oldest") {
    $sortSQL = "ORDER BY ai.created_at ASC";
} 
else if ($sort == "high_price") {
    $sortSQL = "ORDER BY ai.current_price DESC";
}
else if ($sort == "low_price") {
    $sortSQL = "ORDER BY ai.current_price ASC";
}

// ------------------------------
// PAGINATION
// ------------------------------
$limit = 10; // items per page
$page = $_GET['page'] ?? 1;
$page = max(1, intval($page));
$offset = ($page - 1) * $limit;

// Count total
$countSQL = "
    SELECT COUNT(*) AS total
    FROM auction_items ai
    JOIN users u ON ai.seller_id = u.id
    $whereSQL
";
$countResult = $conn->query($countSQL);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// ------------------------------
// MAIN SQL QUERY
// ------------------------------
$sql = "
    SELECT ai.*, u.username AS seller_name 
    FROM auction_items ai 
    JOIN users u ON ai.seller_id = u.id
    $whereSQL
    $sortSQL
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Auction History</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body { background:#f8f9fa; font-family: 'Poppins', sans-serif; }
    .main-content { padding:20px; }
    h2 { text-align:center; color:#2c3e50; }

    .row { display:flex; justify-content:space-between; margin-bottom:15px; }
    input, select {
        padding:8px; border-radius:8px; border:1px solid #ccc; font-size:14px;
    }
    table {
        width:100%; border-collapse:collapse; background:white;
        border-radius:12px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);
    }
    th,td { padding:12px 15px; }
    th { background:#4a90e2; color:white; }
    tr:nth-child(even){ background:#f2f6fc; }
    .status { padding:6px 10px; border-radius:6px; font-weight:bold; }

    .pagination { text-align:center; margin-top:20px; }
    .pagination a {
        padding:8px 12px; background:white; margin:0 3px; border-radius:6px;
        text-decoration:none; color:#333; border:1px solid #ccc;
    }
    .active-page {
        background:#4a90e2; color:white !important; font-weight:bold;
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
    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
  <h2>📜 Auction Status</h2>

  <!-- TOP CONTROLS -->
  <form method="GET">
    <div class="row">

      <!-- SEARCH BAR -->
      <input type="text" name="search" placeholder="Search Title / Category / Seller"
             value="<?= htmlspecialchars($search) ?>" style="width:40%;">

      <!-- FILTER -->
      <select name="filter" style="width:20%;" onchange="this.form.submit()">
        <option value=""> Filter </option>
        <option value="active"   <?= ($filter=="active") ? "selected":"" ?>>Active</option>
        <option value="closed"   <?= ($filter=="closed") ? "selected":"" ?>>Closed</option>
        <option value="upcoming" <?= ($filter=="upcoming") ? "selected":"" ?>>Upcoming</option>
        <option value="pending"  <?= ($filter=="pending") ? "selected":"" ?>>Pending</option>
        <option value="sold"     <?= ($filter=="sold") ? "selected":"" ?>>Sold</option>
        <option value="rejected" <?= ($filter=="rejected") ? "selected":"" ?>>Rejected</option>
      </select>

      <!-- SORT -->
      <select name="sort" style="width:20%;" onchange="this.form.submit()">
        <option value="newest" <?= ($sort=="newest")?"selected":"" ?>>Newest First</option>
        <option value="oldest" <?= ($sort=="oldest")?"selected":"" ?>>Oldest First</option>
        <option value="high_price" <?= ($sort=="high_price")?"selected":"" ?>>Highest Price</option>
        <option value="low_price" <?= ($sort=="low_price")?"selected":"" ?>>Lowest Price</option>
      </select>

      <button style="padding:8px 14px; border:none; border-radius:6px; background:#4a90e2; color:white;">
        Search
      </button>

    </div>
  </form>

  <!-- TABLE -->
  <table>
    <tr>
      <th>#</th>
      <th>Item</th>
      <th>Seller</th>
      <th>Category</th>
      <th>Start Price</th>
      <th>Current Price</th>
      <th>Start</th>
      <th>End</th>
      <th>Status</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {
        $sn = $offset + 1;

        while ($row = $result->fetch_assoc()) {

            // UPCOMING AUTO
            if ($row['start_time'] > date('Y-m-d H:i:s') && $row['status'] == 'active') {
                $row['status'] = 'upcoming';
            }

            echo "
            <tr>
              <td>$sn</td>
              <td>{$row['title']}</td>
              <td>{$row['seller_name']}</td>
              <td>{$row['category']}</td>
              <td>Rs. {$row['start_price']}</td>
              <td>Rs. {$row['current_price']}</td>
              <td>{$row['start_time']}</td>
              <td>{$row['end_time']}</td>
              <td><span class='status status-{$row['status']}'>".ucfirst($row['status'])."</span></td>
            </tr>";
            $sn++;
        }
    } else {
        echo "<tr><td colspan='9' style='text-align:center;'>No records found.</td></tr>";
    }
    ?>
  </table>

  <!-- PAGINATION -->
  <div class="pagination">
    <?php
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $page) ? "active-page" : "";
        echo "<a class='$active' href='?page=$i&filter=$filter&search=$search&sort=$sort'>$i</a>";
    }
    ?>
  </div>

</div>
<script src="../assets/script.js"></script>
</body>
</html>
