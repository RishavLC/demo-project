<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $message = trim($_POST['message']);

    if ($message === '') {
        die("Message cannot be empty");
    }

    // Insert into auction_feedback
    $stmt = $conn->prepare("INSERT INTO auction_feedback (item_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $item_id, $user_id, $message);
    $stmt->execute();
    $feedback_id = $stmt->insert_id;
    $stmt->close();

    // Insert first message in auction_feedback_messages
    $stmt = $conn->prepare("INSERT INTO auction_feedback_messages (feedback_id, sender_id, sender_role, message) VALUES (?, ?, 'user', ?)");
    $stmt->bind_param("iis", $feedback_id, $user_id, $message);
    $stmt->execute();
    $stmt->close();

    header("Location: feedback_view.php?id=".$feedback_id);
    exit();

}

// Simple feedback form
?>
<!DOCTYPE html>
<html>
<head>
<title>Submit Feedback</title>
<style>
body { font-family: Arial; padding: 20px; background: #f4f6f8; }
.container { max-width: 500px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
textarea { width: 100%; min-height: 100px; padding: 10px; }
button { margin-top: 10px; padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #0056b3; }
</style>
</head>
<body>
<div class="container">
<h2>Submit Feedback</h2>
<form method="post">
    <input type="hidden" name="item_id" value="<?= isset($_GET['item_id']) ? (int)$_GET['item_id'] : 1 ?>">
    <textarea name="message" placeholder="Write your feedback..." required></textarea><br>
    <button type="submit">Submit</button>
</form>
</div>
</body>
</html>
