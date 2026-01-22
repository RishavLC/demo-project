<?php
session_start();
include "../common/config.php";

if ($_SESSION['role'] !== 'admin') exit("Unauthorized");

$feedback_id = (int)$_POST['feedback_id'];
$message = trim($_POST['message']);
$admin_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
INSERT INTO auction_feedback_messages
(feedback_id, sender_id, sender_role, message)
VALUES (?, ?, 'admin', ?)
");
$stmt->bind_param("iis", $feedback_id, $admin_id, $message);
$stmt->execute();
$stmt->close();

/* Update status */
$conn->query("
UPDATE auction_feedback
SET status = 'replied'
WHERE id = $feedback_id
");

header("Location: feedback_view.php?id=$feedback_id");
exit();
