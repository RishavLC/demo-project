<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";

/* ================= FORCE UPCOMING ONLY ================= */
$search = $_GET['search'] ?? "";
$sort   = $_GET['sort'] ?? "newest";

/* ================= WHERE ================= */
$whereParts = ["ai.status = 'upcoming'"];

if ($search != "") {
    $searchText = "%" . $conn->real_escape_string($search) . "%";
    $whereParts[] = "(ai.title LIKE '$searchText'
                      OR ai.category LIKE '$searchText'
                      OR u.username LIKE '$searchText')";
}

$whereSQL = "WHERE " . implode(" AND ", $whereParts);

/* ================= SORT ================= */
switch ($sort) {
    case 'oldest':
        $sortSQL = "ORDER BY ai.created_at ASC";
        break;
    case 'high_price':
        $sortSQL = "ORDER BY ai.start_price DESC";
        break;
    case 'low_price':
        $sortSQL = "ORDER BY ai.start_price ASC";
        break;
    case 'seller':
        $sortSQL = "ORDER BY u.username ASC";
        break;
    default:
        $sortSQL = "ORDER BY ai.created_at DESC";
}

/* ================= PAGINATION ================= */
$limit = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ================= COUNT ================= */
$countSQL = "
    SELECT COUNT(*) total
    FROM auction_items ai
    JOIN users u ON ai.seller_id = u.id
    $whereSQL
";
$totalRows = $conn->query($countSQL)->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

/* ================= MAIN QUERY ================= */
$sql = "
SELECT 
    ai.*,
    u.username AS seller_name
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
<title>Admin | Upcoming Auctions</title>
<link rel="stylesheet" href="../assets/style.css">

<style>
body { background:#f8f9fa; font-family:Poppins,sans-serif; }
.main-content { padding:20px; }

table {
    width:100%; border-collapse:collapse;
    background:white; border-radius:12px;
}
th, td { padding:12px; text-align:center; }
th { background:#f39c12; color:white; }
tr:nth-child(even) { background:#f2f6fc; }

.status {
    padding:6px 10px;
    border-radius:6px;
    background:#fff3cd;
    color:#856404;
    font-weight:bold;
}

.btn {
    padding:6px 10px;
    background:#3498db;
    color:white;
    border-radius:5px;
    text-decoration:none;
}

.pagination { text-align:center; margin-top:15px; }
.pagination a {
    padding:6px 10px;
    border:1px solid #ccc;
    margin:2px;
    border-radius:6px;
    text-decoration:none;
}
.active-page {
    background:#f39c12;
    color:white !important;
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
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>

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

<h2>⏳ Upcoming Auctions</h2>

<form method="get" style="display:flex; gap:10px; margin-bottom:15px;">
    <select name="sort">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="high_price">High Start Price</option>
        <option value="low_price">Low Start Price</option>
        <option value="seller">Seller</option>
    </select>

    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<table>
<tr>
    <th>#</th>
    <th>Image</th>
    <th>Item</th>
    <th>Seller</th>
    <th>Category</th>
    <th>Start Price</th>
    <th>Starts At</th>
    <th>Status</th>
</tr>

<?php
if ($result->num_rows) {
    $sn = $offset + 1;
    while ($row = $result->fetch_assoc()) {

        /* IMAGE */
        $imgStmt = $conn->prepare("
            SELECT image_path FROM auction_images
            WHERE item_id=? ORDER BY is_primary DESC,id ASC LIMIT 1
        ");
        $imgStmt->bind_param("i", $row['id']);
        $imgStmt->execute();
        $img = $imgStmt->get_result()->fetch_assoc();
        $imgStmt->close();

        $imgUrl = $img
            ? "../" . str_replace(['../','./'],'',$img['image_path'])
            : "../assets/no-image.png";

        echo "<tr>
            <td>{$sn}</td>
            <td><img src='$imgUrl' width='70' height='60' style='border-radius:6px;object-fit:cover'></td>
            <td>{$row['title']}</td>
            <td>{$row['seller_name']}</td>
            <td>{$row['category']}</td>
            <td>Rs {$row['start_price']}</td>
            <td>{$row['start_time']}</td>
            <td><span class='status'>Upcoming</span></td>
        </tr>";
        $sn++;
    }
} else {
    echo "<tr><td colspan='8'>No upcoming auctions found</td></tr>";
}
?>
</table>

<div class="pagination">
<?php
for ($i = 1; $i <= $totalPages; $i++) {
    $active = ($i == $page) ? "active-page" : "";
    echo "<a class='$active' href='?page=$i&search=$search&sort=$sort'>$i</a>";
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
