<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['user_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversation_id = (int)$_POST['conversation_id'];
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];

    if ($message !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $conversation_id, $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: chat_view.php?id=".$conversation_id);
    exit();
}
