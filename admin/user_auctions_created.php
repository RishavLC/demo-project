<?php
session_start();
include "../common/config.php";

/* ===== ADMIN CHECK ===== */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

/* ===== USER ID CHECK ===== */
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = (int)$_GET['user_id'];

/* ===== FETCH USER ===== */
$userStmt = $conn->prepare("SELECT username FROM users WHERE id=?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    echo "<h3>User not found</h3>";
    exit();
}

/* ===== PAGINATION SETUP ===== */
$itemsPerPage = 3;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

/* ===== FETCH AUCTIONS CREATED ===== */
$sql = "
SELECT 
    ai.*,
    (SELECT COUNT(DISTINCT bidder_id) FROM bids WHERE item_id = ai.id) AS total_bidders,
    (SELECT MAX(bid_amount) FROM bids WHERE item_id = ai.id) AS highest_bid,
    u.username AS winner_name
FROM auction_items ai
LEFT JOIN users u ON ai.winner_id = u.id
WHERE ai.seller_id = ?
ORDER BY ai.created_at DESC
LIMIT ?, ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $offset, $itemsPerPage);
$stmt->execute();
$auctions = $stmt->get_result();

/* ===== TOTAL COUNT ===== */
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM auction_items
    WHERE seller_id = ?
");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);
$countStmt->close();

?>

<!DOCTYPE html>
<html>
<head>
<title>Auctions Created</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
    /* Sidebar Header */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background: #2c3e50;
  color: #fff;
}

/* Logo wrapper */
.logo-box {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Logo image */
.logo-box img {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 6px;
}

/* Logo text */
.logo-text {
  font-size: 18px;
  font-weight: 600;
  white-space: nowrap;
}

/* Toggle button */
/* .toggle-btn {
  cursor: pointer;
  font-size: 20px;
} */

/* ================= COLLAPSED SIDEBAR ================= */

.sidebar.collapsed .logo-text {
  display: none;
}

.sidebar.collapsed .logo-box {
  justify-content: center;
  width: 100%;
}

.sidebar.collapsed .sidebar-header {
  justify-content: center;
}

.sidebar.collapsed .toggle-btn {
  position: absolute;
  bottom: 15px;
  left: 50%;
  transform: translateX(-50%);
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar ul li {
  position: relative;
}

.sidebar ul li a {
  display: block;
  padding: 12px 20px;
  text-decoration: none;
  color: #fff;
}

.dropdown-menu {
  display: none;
  background: #2f3640;
}

.dropdown-menu li a {
  padding-left: 40px;
  font-size: 14px;
}

/* Show dropdown when active */
.dropdown-menu.show {
  display: block;
}
.page-box {
    max-width:1200px;
    margin:30px auto;
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
}

.auction-card {
    display:flex;
    position: relative;
    gap:20px;
    padding:20px;
    border-bottom:1px solid #eee;
}

.auction-card:last-child {
    border-bottom:none;
}

.auction-img img {
    width:160px;
    height:120px;
    object-fit:cover;
    border-radius:10px;
    border:2px solid #ddd;
}

.auction-info {
    flex:1;
}

.badge {
    padding:4px 10px;
    border-radius:15px;
    font-size:12px;
    color:#fff;
    position: absolute;
    top: 20px;
    right: 20px;
}

.active { background:#2ecc71; }
.closed { background:#e74c3c; }
.upcoming { background:#f39c12; }
.rejected { background:#7f8c8d; }

.meta {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
    margin-top:10px;
    font-size:14px;
}

h2 { margin-bottom:10px; }
.btn { display:inline-block; padding:6px 12px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; margin-top:10px; }

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
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>

    <!-- DROPDOWN -->
    <li class="dropdown">
      <a href="javascript:void(0)" onclick="toggleDropdown()">
        📜 Auctions ▾
      </a>
      <ul class="dropdown-menu" id="auctionDropdown">
        <li><a href="auctions_active.php">🟢 Active</a></li>
        <li><a href="auctions_upcoming.php">🟡 Upcoming</a></li>
        <li><a href="auction_history.php">📜 History</a></li>
      </ul>
    </li>

    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
    <h2>Auctions Created by <?= htmlspecialchars($user['username']) ?></h2>
<div class="page-box">
<?php if ($auctions->num_rows === 0): ?>
    <p>No auctions created.</p>
<?php endif; ?>

<?php while ($a = $auctions->fetch_assoc()): ?>
<?php
$img_url = "../assets/default-item.png"; // default image

$imgStmt = $conn->prepare("
    SELECT image_path 
    FROM auction_images 
    WHERE item_id = ? 
    LIMIT 1
");
$imgStmt->bind_param("i", $a['id']);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();

if ($imgRes && $imgRes->num_rows > 0) {
    $img = $imgRes->fetch_assoc();

    // Remove any leading "../" or "uploads/" from DB value
    $cleanPath = preg_replace('#^\.\./#', '', $img['image_path']);
    $cleanPath = preg_replace('#^uploads/#', '', $cleanPath);

    // Server path for file_exists
    $serverPath = __DIR__ . "/../uploads/" . $cleanPath;
    // Browser path for <img>
    $browserPath = "../uploads/" . $cleanPath;

    if (file_exists($serverPath)) {
        $img_url = $browserPath;
    }
}
?>


<div class="auction-card">

    <div class="auction-img">
    <img src="<?= htmlspecialchars($img_url) ?>" alt="Auction Image">
    </div>


    <div class="auction-info">
        <a style="text-decoration: none; color: black;" href="bid_history.php?item_id=<?= $a['id'] ?>"><h3><?= htmlspecialchars($a['title']) ?></h3></a>
              <span class="badge <?= $a['status'] ?>">
            <?= strtoupper($a['status']) ?>
        </span>

        <p><?= htmlspecialchars($a['description']) ?></p>

        

        <div class="meta">
            <div><b>Start Price:</b> Rs <?= $a['start_price'] ?></div>
            <div>
                <b>
                <?= $a['status'] === 'active' ? 'Current Price:' : 'Highest Bid:' ?>
                </b>
                Rs <?= $a['highest_bid'] ?? $a['start_price'] ?>
            </div>
            <div><b>Total Bidders:</b> <?= $a['total_bidders'] ?></div>

            <div><b>Start Time:</b> <?= $a['start_time'] ?></div>
            <div><b>End Time:</b> <?= $a['end_time'] ?></div>
            <div>
                <b>Winner:</b>
                <?= $a['winner_name'] ?? '-' ?>
                <a class="btn" href="bid_history.php?item_id=<?= $a['id'] ?>" style="position: absolute; bottom: 20px; right: 20px;">
                    View Details
                </a>
            </div>
        </div>
    </div>

</div>

<?php endwhile; ?>
<?php if ($totalPages > 1): ?>
<div style="text-align:center; margin-top:20px;">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php if ($p == $page): ?>
            <strong style="padding:6px 12px; background:#3498db; color:#fff; border-radius:6px;">
                <?= $p ?>
            </strong>
        <?php else: ?>
            <a href="?user_id=<?= $user_id ?>&page=<?= $p ?>"
               style="padding:6px 12px; border:1px solid #ccc; border-radius:6px; text-decoration:none; color:#333;">
               <?= $p ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<br>
<a class="btn" href="view_user.php?id=<?= $user_id ?>">⬅ Back</a>

</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
