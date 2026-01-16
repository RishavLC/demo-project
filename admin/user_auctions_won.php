<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
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

/* Fetch auctions won by the user */
$sql = "
SELECT ai.*
FROM auction_items ai
WHERE ai.status='closed'
AND ai.id IN (
    SELECT b.item_id
    FROM bids b
    WHERE b.bidder_id=?
    GROUP BY b.item_id
    HAVING MAX(b.bid_amount) = (
        SELECT MAX(b2.bid_amount) FROM bids b2 WHERE b2.item_id=b.item_id
    )
)
ORDER BY ai.end_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wins = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Auctions Won</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
/* .main-content { max-width:1200px; margin:30px auto; font-family:sans-serif; } */
.card { background:#fff; padding:20px; margin-bottom:20px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,.05); display:flex; gap:20px; position:relative; }
.auction-img img { width:180px; height:130px; object-fit:cover; border-radius:10px; border:2px solid #ddd; }
.auction-info { flex:1; }
.badge { padding:4px 10px; border-radius:15px; font-size:12px; color:#fff; position:absolute; top:20px; right:20px; background:#e74c3c; }
.meta { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-top:10px; font-size:14px; }
.btn { display:inline-block; padding:6px 12px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; margin-top:10px; }
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
<h2>Auctions Won by <?= htmlspecialchars($user['username']) ?></h2>

<?php if($wins->num_rows === 0): ?>
    <p>No auctions won yet.</p>
<?php endif; ?>

<?php while($w = $wins->fetch_assoc()): ?>

<?php
// Fetch one auction image
$img_url = "../assets/default-item.png";
$imgStmt = $conn->prepare("SELECT image_path FROM auction_images WHERE item_id=? LIMIT 1");
$imgStmt->bind_param("i", $w['id']);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
if($imgRes && $imgRes->num_rows > 0){
    $img = $imgRes->fetch_assoc();
    $cleanPath = preg_replace('#^\.\./#', '', $img['image_path']);
    $cleanPath = preg_replace('#^uploads/#', '', $cleanPath);
    $serverPath = __DIR__ . "/../uploads/" . $cleanPath;
    $browserPath = "../uploads/" . $cleanPath;
    if(file_exists($serverPath)){
        $img_url = $browserPath;
    }
}

// User max bid
$q = $conn->prepare("SELECT MAX(bid_amount) AS user_max FROM bids WHERE bidder_id=? AND item_id=?");
$q->bind_param("ii",$user_id,$w['id']); $q->execute();
$user_max = $q->get_result()->fetch_assoc()['user_max'] ?? 0;

// Auction highest bid
$q = $conn->prepare("SELECT MAX(bid_amount) AS highest FROM bids WHERE item_id=?");
$q->bind_param("i",$w['id']); $q->execute();
$auction_max = $q->get_result()->fetch_assoc()['highest'] ?? 0;

// Total bids count
$q = $conn->prepare("SELECT COUNT(*) AS total_bids FROM bids WHERE item_id=?");
$q->bind_param("i",$w['id']); $q->execute();
$total_bids = $q->get_result()->fetch_assoc()['total_bids'] ?? 0;

// Winner name (this user)
$winner_name = "N/A";

$winnerStmt = $conn->prepare("
    SELECT u.username
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.item_id = ?
    ORDER BY b.bid_amount DESC
    LIMIT 1
");
$winnerStmt->bind_param("i", $w['id']);
$winnerStmt->execute();
$winnerRes = $winnerStmt->get_result();

if ($winnerRes && $winnerRes->num_rows > 0) {
    $winner_name = $winnerRes->fetch_assoc()['username'];
}

?>

<div class="card">
    <div class="auction-img">
        <img src="<?= htmlspecialchars($img_url) ?>" alt="Auction Image">
        <span class="badge">CLOSED</span>
    </div>
    <div class="auction-info">
        <a style="text-decoration: none;" href="bid_history.php?item_id=<?= $w['id'] ?>"><h3><?= htmlspecialchars($w['title']) ?></h3></a>
        <p><?= htmlspecialchars($w['description']) ?></p>
        <div class="meta">
            <div><b>Your Max Bid:</b> ₹<?= $user_max ?></div>
            <div><b>Auction Highest Bid:</b> ₹<?= $auction_max ?></div>
            <div><b>Total Bids (All Users):</b> <?= $total_bids ?></div>
            <div><b>Winner:</b> <?= htmlspecialchars($winner_name) ?></div>
            <div><b>Start Time:</b> <?= $w['start_time'] ?></div>
            <div><b>End Time:</b> <?= $w['end_time'] ?>
                <a class="btn" href="bid_history.php?item_id=<?= $w['id'] ?>"  style="position: absolute; bottom: 20px; right: 20px;">View Details</a>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>

<a class="btn" href="view_user.php?id=<?= $user_id ?>">⬅ Back</a>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
