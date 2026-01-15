<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_GET['user_id'];

/* Dynamic winner logic */
$sql = "
SELECT ai.*
FROM auction_items ai
WHERE ai.status='closed'
AND ai.id IN (
    SELECT b.item_id
    FROM bids b
    WHERE b.bidder_id=?
    GROUP BY b.item_id
    HAVING MAX(b.bid_amount)=(
        SELECT MAX(b2.bid_amount) FROM bids b2 WHERE b2.item_id=b.item_id
    )
)
";
$stmt=$conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$wins=$stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Auctions Won</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
.card{background:#fff;padding:20px;border-radius:10px;margin-bottom:15px}
</style>
</head>

<body>
<div class="main-content">
<h2>🏆 Auctions Won</h2>

<?php while($w=$wins->fetch_assoc()): ?>
<div class="card">
<h3><?= $w['title'] ?></h3>
<p>Final Price: Rs <?= $w['current_price'] ?></p>
<p>Ended: <?= $w['end_time'] ?></p>
</div>
<?php endwhile; ?>

<a href="view_user.php?id=<?= $user_id ?>">⬅ Back</a>
</div>
</body>
</html>
