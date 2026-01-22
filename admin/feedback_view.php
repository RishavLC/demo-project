<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request");
}

$feedback_id = (int)$_GET['id'];

/* ================= FETCH THREAD INFO (SAFE) ================= */
$sql = "
SELECT 
    f.id,
    a.title AS item_title,
    u.username AS seller_name
FROM auction_feedback f
JOIN auction_items a ON a.id = f.item_id
JOIN users u ON u.id = f.created_by
WHERE f.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$result = $stmt->get_result();
$thread = $result->fetch_assoc();
$stmt->close();

if (!$thread) {
    die("Feedback not found.");
}
?>

<h2>Feedback – <?= htmlspecialchars($thread['title']) ?></h2>
<p><strong>Seller:</strong> <?= htmlspecialchars($thread['username']) ?></p>

<hr>

<?php while ($m = $messages->fetch_assoc()): ?>
<p>
<strong><?= ucfirst($m['sender_role']) ?>:</strong>
<?= nl2br(htmlspecialchars($m['message'])) ?>
<br>
<small><?= $m['created_at'] ?></small>
</p>
<hr>
<?php endwhile; ?>

<form method="post" action="feedback_reply.php">
    <input type="hidden" name="feedback_id" value="<?= $feedback_id ?>">
    <textarea name="message" required style="width:100%;min-height:100px;"></textarea><br><br>
    <button type="submit">Reply</button>
</form>
