<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_GET['user_id'];

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
.card{background:#fff;padding:20px;margin-bottom:20px;border-radius:10px}
</style>
</head>

<body>
<div class="main-content">
<h2>👥 Auctions Participated</h2>

<?php while($a=$items->fetch_assoc()): ?>

<?php
/* User Bids Count */
$q=$conn->prepare("SELECT COUNT(*) c FROM bids WHERE bidder_id=? AND item_id=?");
$q->bind_param("ii",$user_id,$a['id']);$q->execute();
$user_bids=$q->get_result()->fetch_assoc()['c'];

/* User Investment */
$q=$conn->prepare("SELECT SUM(bid_amount) s FROM bids WHERE bidder_id=? AND item_id=?");
$q->bind_param("ii",$user_id,$a['id']);$q->execute();
$investment=$q->get_result()->fetch_assoc()['s'] ?? 0;

/* User Max */
$q=$conn->prepare("SELECT MAX(bid_amount) m FROM bids WHERE bidder_id=? AND item_id=?");
$q->bind_param("ii",$user_id,$a['id']);$q->execute();
$user_max=$q->get_result()->fetch_assoc()['m'];

/* Auction Max */
$q=$conn->prepare("SELECT MAX(bid_amount) m FROM bids WHERE item_id=?");
$q->bind_param("i",$a['id']);$q->execute();
$auction_max=$q->get_result()->fetch_assoc()['m'];

$result="❌ Lost";
if($a['status']=='active' && $user_max==$auction_max) $result="⏳ Leading";
if($a['status']=='closed' && $user_max==$auction_max) $result="🏆 Won";
?>

<div class="card">
<h3><?= $a['title'] ?></h3>

<table>
<tr><td>Status</td><td><?= strtoupper($a['status']) ?></td></tr>
<tr><td>User Bids</td><td><?= $user_bids ?></td></tr>
<tr><td>User Max Bid</td><td>Rs <?= $user_max ?></td></tr>
<tr><td>Total Invested</td><td>Rs <?= $investment ?></td></tr>
<tr><td>Result</td><td><?= $result ?></td></tr>
</table>
</div>

<?php endwhile; ?>

<a href="view_user.php?id=<?= $user_id ?>">⬅ Back</a>
</div>
</body>
</html>
