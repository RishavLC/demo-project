<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

// Validate POST
if (!isset($_POST['conversation_id'], $_POST['message']) || empty(trim($_POST['message']))) {
    die("Invalid request.");
}

$conv_id = (int)$_POST['conversation_id'];
$message = trim($_POST['message']);

// Check if user is part of the conversation
$stmt = $conn->prepare("SELECT * FROM conversations WHERE id=? AND (buyer_id=? OR seller_id=?)");
$stmt->bind_param("iii", $conv_id, $user_id, $user_id);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conv) {
    die("Unauthorized to send message in this conversation.");
}

// Insert message
$stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, message)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $conv_id, $user_id, $message);
$stmt->execute();
$stmt->close();

// Redirect back to chat view
header("Location: chat_view.php?id=".$conv_id);
exit();
