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

// Fetch all auctions the user participated in
$sql = "
SELECT DISTINCT ai.*
FROM bids b
JOIN auction_items ai ON ai.id=b.item_id
WHERE b.bidder_id=?
ORDER BY ai.end_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Auctions Participated</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
/* .main-content { max-width:1200px; margin:30px auto; font-family:sans-serif; } */
.card { background:#fff; padding:20px; margin-bottom:20px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,.05); display:flex; gap:20px; position:relative; }
.auction-img img { width:180px; height:130px; object-fit:cover; border-radius:10px; border:2px solid #ddd; }
.auction-info { flex:1; }
.badge { padding:4px 10px; border-radius:15px; font-size:12px; color:#fff; position:absolute; top:20px; right:20px; }
.active { background:#2ecc71; }
.closed { background:#e74c3c; }
.upcoming { background:#f39c12; }
.rejected { background:#7f8c8d; }
.meta { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-top:10px; font-size:14px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
th { background:#f4f4f4; }
.btn { display:inline-block; padding:6px 12px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; margin-top:10px; }
</style>
</head>

<body>
  <div class="sidebar">
  <div class="sidebar-header">
    <div class="logo-box">
      <img src="../images/logo.jpeg">
      <span class="logo-text">EasyBid</span>
    </div>
  </div>

  <ul>
    <li><a href="../admin/">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <!-- <li><a href="manage_auctions.php">📦 Manage Auctions</a></li> -->
    <li><a href="feedback_list.php">💬 Feedback</a></li>

    <li>
      <a class="caret" onclick="toggleDropdown('auctionDrop')">📜 Auctions</a>
      <ul class="dropdown-menu" id="auctionDrop">
        <li><a href="auctions_active.php">🟢 Active</a></li>
        <li><a href="auctions_upcoming.php">🟡 Upcoming</a></li>
        <li><a href="auction_overview.php">📕 History</a></li>
      </ul>
    </li>

    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
<h2>Auctions Participated by <?= htmlspecialchars($user['username']) ?></h2>

<?php while($a = $items->fetch_assoc()): ?>

<?php
// Fetch one auction image
$img_url = "../assets/default-item.png";
$imgStmt = $conn->prepare("SELECT image_path FROM auction_images WHERE item_id=? LIMIT 1");
$imgStmt->bind_param("i", $a['id']);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();
if ($imgRes && $imgRes->num_rows > 0) {
    $img = $imgRes->fetch_assoc();
    $cleanPath = preg_replace('#^\.\./#', '', $img['image_path']);
    $cleanPath = preg_replace('#^uploads/#', '', $cleanPath);
    $serverPath = __DIR__ . "/../uploads/" . $cleanPath;
    $browserPath = "../uploads/" . $cleanPath;
    if (file_exists($serverPath)) {
        $img_url = $browserPath;
    }
}

// User bids info
$q = $conn->prepare("SELECT COUNT(*) c, SUM(bid_amount) s, MAX(bid_amount) m FROM bids WHERE bidder_id=? AND item_id=?");
$q->bind_param("ii", $user_id, $a['id']); $q->execute();
$userStats = $q->get_result()->fetch_assoc();
$user_bids = $userStats['c'];
$user_max = $userStats['m'] ?? 0;

// Auction max bid
$q = $conn->prepare("SELECT MAX(bid_amount) m FROM bids WHERE item_id=?");
$q->bind_param("i",$a['id']); $q->execute();
$auction_max = $q->get_result()->fetch_assoc()['m'] ?? 0;

// Winner info
$q = $conn->prepare("SELECT u.username FROM auction_items ai LEFT JOIN users u ON ai.winner_id=u.id WHERE ai.id=?");
$q->bind_param("i", $a['id']); $q->execute();
$winner_name = $q->get_result()->fetch_assoc()['username'] ?? '-';
// Total number of bids on this auction by all users
$q = $conn->prepare("SELECT COUNT(*) AS total_bids FROM bids WHERE item_id=?");
$q->bind_param("i", $a['id']);
$q->execute();
$total_bids = $q->get_result()->fetch_assoc()['total_bids'] ?? 0;


// Winner or leading bidder
if($a['status'] === 'closed') {
    // Actual winner
    $q = $conn->prepare("
        SELECT u.username 
        FROM auction_items ai 
        LEFT JOIN users u ON ai.winner_id = u.id 
        WHERE ai.id=?
    ");
    $q->bind_param("i", $a['id']);
    $q->execute();
    $winner_name = $q->get_result()->fetch_assoc()['username'] ?? '-';
} else {
    // Leading bidder for active/upcoming
    $q = $conn->prepare("
        SELECT u.username 
        FROM bids b 
        LEFT JOIN users u ON b.bidder_id = u.id 
        WHERE b.item_id=? 
        ORDER BY b.bid_amount DESC, b.bid_time ASC 
        LIMIT 1
    ");
    $q->bind_param("i", $a['id']);
    $q->execute();
    $winner_name = $q->get_result()->fetch_assoc()['username'] ?? '-';
}
// Determine user result
if($a['status'] === 'closed') {
    $result = ($user_max == $auction_max) ? "🏆 Won" : "❌ Lost";
} else {
    $result = "-"; // No result for active/upcoming auctions
}

?>

<div class="card">
    <div class="auction-img">
        <img src="<?= htmlspecialchars($img_url) ?>" alt="Auction Image">
        <span class="badge <?= $a['status'] ?>"><?= strtoupper($a['status']) ?></span>
    </div>
    <div class="auction-info">
        <a style="text-decoration: none;" href="bid_history.php?item_id=<?= $a['id'] ?>"><h3><?= htmlspecialchars($a['title']) ?></h3></a>
              <p><?= htmlspecialchars($a['description']) ?></p>
        <div class="meta">
            <div><b>User Bids:</b> <?= $user_bids ?></div>
            <div><b>User Max Bid:</b> Rs <?= $user_max ?></div>
            <div><b>Total Bids (By All Users):</b> <?= $total_bids ?></div>
            <?php
            if ($a['status'] === 'active') {
    $bid_label = "Latest Bid";
} elseif ($a['status'] === 'closed') {
    $bid_label = "Winning Bid";
} else {
    $bid_label = "Highest Bid";
}
 ?><div>
    <b><?= $bid_label ?>:</b>
    Rs <?= $auction_max ?? $a['start_price'] ?>
</div>

            <div>
                <b>Winner:</b>
                <?php
                    if ($a['status'] === 'active') {
                        echo "Currently Leading Winner  " . htmlspecialchars($winner_name);
                    } elseif ($a['status'] === 'closed') {
                        echo "" . htmlspecialchars($winner_name);
                    } else {
                        echo "-";
                     }
                ?>
            </div>
            <div><b>Result:</b> <?= $result ?></div>
            <div><b>Start Time:</b> <?= $a['start_time'] ?></div>
            <div><b>End Time:</b> <?= $a['end_time'] ?>
                <a class="btn" href="bid_history.php?item_id=<?= $a['id'] ?>" style="position: absolute; bottom: 20px; right: 20px;">
                    View Details
                </a>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>

<a class="btn" href="view_user.php?id=<?= $user_id ?>">⬅ Back</a>
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
