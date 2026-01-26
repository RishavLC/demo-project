<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";

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

/* ---------------- SORT ---------------- */
$sortSQL = "ORDER BY ai.created_at DESC";
if ($sort == "oldest") $sortSQL = "ORDER BY ai.created_at ASC";
if ($sort == "high_price") $sortSQL = "ORDER BY ai.current_price DESC";
if ($sort == "low_price") $sortSQL = "ORDER BY ai.current_price ASC";

/* ---------------- PAGINATION ---------------- */
$limit = 5;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ---------------- COUNT ---------------- */
$countSQL = "
    SELECT COUNT(*) AS total
    FROM auction_items ai
    JOIN users u ON ai.seller_id = u.id
    $whereSQL
";
$totalRows = $conn->query($countSQL)->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

/* ---------------- MAIN QUERY ---------------- */
$sql = "
    SELECT 
        ai.*,
        u.username AS seller_name,

        (SELECT COUNT(*) FROM bids WHERE bids.item_id = ai.id) AS total_bids,

        (SELECT u2.username
         FROM bids b2
         JOIN users u2 ON u2.id = b2.bidder_id
         WHERE b2.item_id = ai.id
         ORDER BY b2.bid_amount DESC
         LIMIT 1) AS winner_name

    FROM auction_items ai
    JOIN users u ON ai.seller_id = u.id
    $whereSQL
    $sortSQL
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

/* ---------------- SORT ---------------- */
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

?>

<!DOCTYPE html>
<html>
<head>
<title>Admin | Auction History</title>
<link rel="stylesheet" href="../assets/style.css">

<style>
body { background:#f8f9fa; font-family:Poppins,sans-serif; }
.main-content { padding:20px; }
table {
    width:100%; border-collapse:collapse; background:white;
    border-radius:12px; overflow:hidden;
}
th, td { padding:12px; text-align:center; }
th { background:#4a90e2; color:white; }
tr:nth-child(even) { background:#f2f6fc; }

.status { padding:6px 10px; border-radius:6px; font-weight:bold; }
.status-active { background:#d4edda; color:#155724; }
.status-closed { background:#f8d7da; color:#721c24; }
.status-rejected { background:#f8d7da; color:#721c24; }

.btn {
    padding:6px 10px;
    background:#3498db;
    color:white;
    border-radius:5px;
    text-decoration:none;
    font-size:14px;
}

.pagination { text-align:center; margin-top:15px; }
.pagination a {
    padding:6px 10px; margin:2px;
    border:1px solid #ccc; border-radius:6px;
    text-decoration:none; color:#333;
}
.active-page {
    background:#4a90e2; color:white !important;
}
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

<h2>Auction History </h2>
<form method="get" style="margin-bottom:15px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
    <!-- Status Filter -->
    <select name="filter" style="padding:5px 10px; border-radius:6px; border:1px solid #ccc;">
        <option value="">All Status</option>
        <option value="active" <?= $filter=='active'?'selected':'' ?>>Active</option>
        <option value="closed" <?= $filter=='closed'?'selected':'' ?>>Closed</option>
        <option value="rejected" <?= $filter=='rejected'?'selected':'' ?>>Rejected</option>
        <option value="upcoming" <?= $filter=='upcoming'?'selected':'' ?>>Upcoming</option>
        <option value="pending" <?= $filter=='pending'?'selected':'' ?>>Pending</option>
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
</div>

</form>

<table>
<tr>
    <th>S.N</th>
    <th>Image</th>
    <th>Item</th>
    <th>Seller</th>
    <th>Category</th>
    <th>Start Price</th>
    <th>Winning Price</th>
    <th>Winner</th>
    <th>Status</th>
    <th>Bid History</th>
</tr>

<?php
if ($result->num_rows > 0) {
    $sn = $offset + 1;
    
    while ($row = $result->fetch_assoc()) {

        $winner = $row['winner_name'] ?? "—";

        echo "
        <tr>
            <td>{$sn}</td>"?>
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
            <?php echo "
            <td>{$row['title']}</td>
            <td>{$row['seller_name']}</td>
            <td>{$row['category']}</td>
            <td>Rs. {$row['start_price']}</td>
            <td>Rs. {$row['current_price']}</td>"?>
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
  <?php echo "
            <td>
                <span class='status status-{$row['status']}'>
                    ".ucfirst($row['status'])."
                </span>
            </td>
            <td>
                <a class='btn' href='bid_history.php?item_id={$row['id']}'>
                    View
                </a>
            </td>
        </tr>";
        $sn++;
    }
} else {
    echo "<tr><td colspan='9'>No records found</td></tr>";
}
?>
</table>

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
<script>
     function toggleDropdown(id) {
  const menu = document.getElementById(id);
          menu.classList.toggle("show");
}
</script>
</body>
</html>
