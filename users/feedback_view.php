<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') die("Unauthorized");

$user_id = $_SESSION['user_id'];
$feedback_id = (int)$_GET['id'];

// Fetch feedback info
$stmt = $conn->prepare("SELECT f.id, f.status, a.title AS item_title FROM auction_feedback f JOIN auction_items a ON a.id=f.item_id WHERE f.id=? AND f.sender_id=?");
$stmt->bind_param("ii", $feedback_id, $user_id);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$feedback) die("Feedback not found");

// Fetch messages
$stmt = $conn->prepare("SELECT sender_role, message, created_at FROM auction_feedback_messages WHERE feedback_id=? ORDER BY created_at ASC");
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Feedback Conversation</title>
<style>
body { font-family: Arial; padding: 20px; background: #f4f6f8; }
.container { max-width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
.msg { padding: 10px; margin-bottom: 10px; border-radius: 6px; }
.user { background: #e8f0ff; text-align: right; }
.admin { background: #e9f7ef; text-align: left; }
.time { font-size: 12px; color: #666; }
textarea { width: 100%; min-height: 80px; padding: 10px; }
button { margin-top: 10px; padding: 8px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #0056b3; }
</style>
</head>
<body>
<div class="container">
<h2>Feedback – <?= htmlspecialchars($feedback['item_title']) ?></h2>
<p>Status: <?= ucfirst($feedback['status']) ?></p>
<hr>

<?php while($m=$messages->fetch_assoc()): ?>
<div class="msg <?= $m['sender_role'] ?>">
<strong><?= ucfirst($m['sender_role']) ?>:</strong><br>
<?= nl2br(htmlspecialchars($m['message'])) ?>
<div class="time"><?= $m['created_at'] ?></div>
</div>
<?php endwhile; ?>

<hr>
<form method="post" action="feedback_reply.php">
<input type="hidden" name="feedback_id" value="<?= $feedback_id ?>">
<textarea name="message" required placeholder="Write a reply..."></textarea><br>
<button type="submit">Send Reply</button>
</form>
</div>
</body>
</html>
