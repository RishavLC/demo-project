<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    die("Invalid item.");
}

$item_id = (int) $_GET['item_id'];

/* Verify item belongs to user & is closed */
$sql = "
SELECT a.id, a.title, u.username AS winner_name
FROM auction_items a
JOIN bids b ON b.item_id = a.id
JOIN users u ON u.id = b.bidder_id
WHERE a.id = ?
AND a.seller_id = ?
AND a.status = 'closed'
ORDER BY b.bid_amount DESC
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    die("Unauthorized or auction not closed.");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Feedback</title>
<style>
textarea {
    width:100%;
    padding:10px;
    min-height:120px;
}
button {
    background:#2c3e50;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
</style>
</head>
<body>

<h2>Feedback for Auction: <?= htmlspecialchars($item['title']) ?></h2>
<p><strong>Winner:</strong> <?= htmlspecialchars($item['winner_name']) ?></p>

<form method="post" action="submit_feedback.php">
    <input type="hidden" name="item_id" value="<?= $item_id ?>">

    <label>Message to Admin / Feedback about Winner</label><br><br>
    <textarea name="message" required></textarea><br><br>

    <button type="submit">Submit Feedback</button>
</form>

</body>
</html>
