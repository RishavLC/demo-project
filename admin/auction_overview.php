<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/index.php");
    exit();
}

/* ================= CONFIG ================= */
include "../common/config.php";
// ================= HANDLE APPROVE / REJECT =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE auction_items SET status='active' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // refresh page
    exit();
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $conn->prepare("UPDATE auction_items SET status='rejected' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // refresh page
    exit();
}


$limit = 3;

/* ================= PAGINATION ================= */
$p_pending  = max(1, (int)($_GET['p_pending']  ?? 1));
$p_active   = max(1, (int)($_GET['p_active']   ?? 1));
$p_upcoming = max(1, (int)($_GET['p_upcoming'] ?? 1));
$p_history  = max(1, (int)($_GET['p_history']  ?? 1));

/* ================= SAFE FETCH FUNCTION ================= */
function fetchSection($conn, $where, $page, $limit) {
    $offset = ($page - 1) * $limit;

    $sql = "
        SELECT ai.*, u.username seller_name
        FROM auction_items ai
        JOIN users u ON u.id = ai.seller_id
        WHERE $where
        ORDER BY ai.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $result = $conn->query($sql);

    $countSql = "
        SELECT COUNT(*) total
        FROM auction_items ai
        WHERE $where
    ";
    $countRes = $conn->query($countSql);

    if (!$result || !$countRes) {
        return [false, 0];
    }

    $total = $countRes->fetch_assoc()['total'];
    return [$result, ceil($total / $limit)];
}

/* ================= FETCH DATA ================= */
[$pending,  $pendingPages]  = fetchSection($conn, "ai.status='pending'",               $p_pending,  $limit);
[$reapplied, $reappliedPages] = fetchSection($conn, "ai.status='pending_reapply'", $p_reapplied ?? 1, $limit);
[$active,   $activePages]   = fetchSection($conn, "ai.status='active'",                $p_active,   $limit);
[$upcoming, $upcomingPages] = fetchSection($conn, "ai.status='upcoming'",              $p_upcoming, $limit);
$offset = ($p_history - 1) * $limit;

/* Base condition for history */
$baseWhere = "ai.status IN ('closed','rejected')";



/* ---------------- FILTER INPUTS ---------------- */
$filter = $_GET['filter'] ?? "";
$search = $_GET['search'] ?? "";
$sort   = $_GET['sort'] ?? "newest";

/* ---------------- WHERE CLAUSE ---------------- */
$whereParts = [];

if ($filter != "") {
    $whereParts[] = "ai.status = '" . $conn->real_escape_string($filter) . "'";
}

if ($search != "") {
    $searchText = "%" . $conn->real_escape_string($search) . "%";
    $whereParts[] = "(ai.title LIKE '$searchText' 
                      OR ai.category LIKE '$searchText' 
                      OR u.username LIKE '$searchText')";
}

$whereSQL = count($whereParts) ? "WHERE " . implode(" AND ", $whereParts) : "";
/* Merge filters */
if ($whereSQL) {
    $baseWhere .= " AND " . str_replace("WHERE ", "", $whereSQL);
}
/* ---------------- SORT ---------------- */
$sortSQL = "ORDER BY ai.created_at DESC";
if ($sort == "oldest") $sortSQL = "ORDER BY ai.created_at ASC";
if ($sort == "high_price") $sortSQL = "ORDER BY ai.current_price DESC";
if ($sort == "low_price") $sortSQL = "ORDER BY ai.current_price ASC";

// sorting
$allowedSorts = ['newest','oldest','high_price','low_price','seller'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'newest';

switch($sort) {
    case 'oldest':
        $sortSQL = "ORDER BY ai.created_at ASC";
        break;
    case 'high_price':
        $sortSQL = "ORDER BY ai.current_price DESC";
        break;
    case 'low_price':
        $sortSQL = "ORDER BY ai.current_price ASC";
        break;
    case 'seller':
        $sortSQL = "ORDER BY u.username ASC";
        break;
    default:
        $sortSQL = "ORDER BY ai.created_at DESC";
}
/* MAIN QUERY */
$historySql = "
    SELECT ai.*, u.username seller_name
    FROM auction_items ai
    JOIN users u ON u.id = ai.seller_id
    WHERE $baseWhere
    $sortSQL
    LIMIT $limit OFFSET $offset
";

$history = $conn->query($historySql);

/* COUNT QUERY */
$countSql = "
    SELECT COUNT(*) total
    FROM auction_items ai
    JOIN users u ON u.id = ai.seller_id
    WHERE $baseWhere
";
$countRes = $conn->query($countSql);
$historyPages = ceil($countRes->fetch_assoc()['total'] / $limit);



/* ================= IMAGE ================= */
function getImage($conn, $id) {
    $stmt = $conn->prepare("
        SELECT image_path FROM auction_images
        WHERE item_id=? ORDER BY is_primary DESC,id ASC LIMIT 1
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $img
        ? "../".str_replace(['../','./'],'',$img['image_path'])
        : "../assets/no-image.png";
}


/* ================= TABLE RENDER ================= */
function renderTable($result, $key, $page, $pages) {
    global $conn;

    if (!$result || $result->num_rows === 0) {
        echo "<p>No records</p>";
        return;
    }

    $lastCol = ($key === 'pending') ? 'Action' : 'Bids';

echo "<table>
<tr>
  <th>#</th>
  <th>Item Image</th>
  <th>Item</th>
  <th>Seller</th>
  <th>Category</th>
  <th>Price</th>
  <th>Winner</th>
  <th>{$lastCol}</th>
</tr>";


    $sn = 1;
    while ($row = $result->fetch_assoc()) {
        $img = getImage($conn, $row['id']);

        // Determine winner
        $winner = "—"; // default

        if ($row['status'] === 'active') {
            // Leading bidder for active auction
            $stmt = $conn->prepare("
                SELECT u.username
                FROM bids b
                JOIN users u ON u.id = b.bidder_id
                WHERE b.item_id=?
                ORDER BY b.bid_amount DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $winner = $res['username'] ?? '—';
            $winner = "Leading: $winner";
            $stmt->close();
        } elseif ($row['status'] === 'closed') {
            // Final winner for closed auction
            $stmt = $conn->prepare("
                SELECT u.username
                FROM bids b
                JOIN users u ON u.id = b.bidder_id
                WHERE b.item_id=?
                ORDER BY b.bid_amount DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $winner = $res['username'] ?? '—';
            $stmt->close();
        }

        $actionBtn = '';

if ($key === 'pending' || $key === 'reapplied' ) {
    $actionBtn = "<a class='btn' href='manage_auction_item.php?id={$row['id']}'>Review</a>";
} else {
    $actionBtn = "<a class='btn' href='bid_history.php?item_id={$row['id']}'>View</a>";
}
        echo "<tr>
          <td>{$sn}</td>
          <td>
            <div class='img-wrap'>
              <img src='$img'>
              <span class='badge status-{$row['status']}'>".ucfirst($row['status'])."</span>
            </div>
          </td>
          <td>{$row['title']}</td>
          <td>{$row['seller_name']}</td>
          <td>{$row['category']}</td>
          <td>Rs {$row['current_price']}</td>
          <td style='color:#f39c12;font-weight:bold'>{$winner}</td>
          <td>{$actionBtn}</td>
        </tr>";
        $sn++;
    }

    echo "</table><div class='pagination'>";
    for ($i=1; $i<=$pages; $i++) {
        $active = ($i==$page) ? "active" : "";
        echo "<a class='$active' href='?p_$key=$i#$key'>$i</a>";
    }
    echo "</div>";
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Auction Overview</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
body{background:#f4f6f8;font-family:Poppins}
.section{background:#fff;padding:15px;border-radius:12px;margin-bottom:25px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;text-align:center}
th{background:#2f3640;color:#fff}
td:nth-child(7) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

td:last-child {
    text-align: center;
}

.btn {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 8px;
    background: #3498db;
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

/* IMAGE + STATUS OVERLAY */
.img-wrap{
    position: relative;
    width: 70px;
    height: 55px;
    margin: auto;
}
.img-wrap img{
    width: 100%;
    height: 100%;
    border-radius:6px;
    object-fit:cover;
}
.img-wrap .badge{
    position: absolute;
    top: -6px;
    right: -6px;
    font-size:10px;
    padding:4px 6px;
    border-radius:6px;
    font-weight:bold;
    box-shadow:0 2px 6px rgba(0,0,0,.2);
}

/* STATUS COLORS */
.status-pending{background:#ffeeba;color:#856404}
.status-reapplied{background:#ffeeba;color:#789880}
.status-active{background:#d4edda;color:#155724}
.status-upcoming{background:#fff3cd;color:#856404}
.status-closed,.status-rejected{background:#f8d7da;color:#721c24}

/* PAGINATION */
.pagination{text-align:center;margin-top:10px; margin-bottom: -15px;}
.pagination a{padding:5px 9px;border:1px solid #ccc;margin:2px;border-radius:5px;text-decoration:none}
.pagination .active{background:#3498db;color:#fff}

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
<h2>📦 Auction Overview</h2>

<div class="section"><h3>Pending</h3><?php renderTable($pending,'pending',$p_pending,$pendingPages); ?></div>
<div class="section"><h3>Pending-Reapplied Items</h3>
<?php renderTable($reapplied,'reapplied',$p_reapplied ?? 1,$reappliedPages); ?>
</div>
<div class="section"><h3>Active</h3><?php renderTable($active,'active',$p_active,$activePages); ?></div>
<div class="section"><h3>Upcoming</h3><?php renderTable($upcoming,'upcoming',$p_upcoming,$upcomingPages); ?></div>
<div class="section" id="history"><h3>History</h3> <form method="get" action="#history" style="margin-bottom:15px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
    <!-- Status Filter -->
    <select name="filter" style="padding:5px 10px; border-radius:6px; border:1px solid #ccc;">
        <option value="">Status</option>
        <option value="closed" <?= $filter=='closed'?'selected':'' ?>>Closed</option>
        <option value="rejected" <?= $filter=='rejected'?'selected':'' ?>>Rejected</option>
    </select>

    <!-- Sort By -->
    <select name="sort" style="padding:5px 10px; border-radius:6px; border:1px solid #ccc;">
        <option value="newest" <?= $sort=='newest'?'selected':'' ?>>Newest</option>
        <option value="oldest" <?= $sort=='oldest'?'selected':'' ?>>Oldest</option>
        <option value="high_price" <?= $sort=='high_price'?'selected':'' ?>>High Price</option>
        <option value="low_price" <?= $sort=='low_price'?'selected':'' ?>>Low Price</option>
        <option value="seller" <?= $sort=='seller'?'selected':'' ?>>Seller</option>
    </select>

    <!-- Search -->
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" 
           style="padding:5px 10px; border-radius:6px; border:1px solid #ccc; flex:1;">
    <button type="submit" style="padding:6px 12px; border-radius:6px; background:#3498db; color:white; border:none;">
        Search
    </button>
    <a href="auction_overview.php#history"
   style="padding:6px 12px; border-radius:6px; background:#95a5a6; color:white; text-decoration:none;">
   Reset
    </a>
</div>

</form>
<?php renderTable($history,'history',$p_history,$historyPages); ?></div>

</div>
<script src="../assets/script.js"></script>
<script>
    function toggleDropdown(id) {
      const menu = document.getElementById(id);
          menu.classList.toggle("show");
}
</script>
</body>
</html>
