<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') die("Unauthorized");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = (int)$_POST['feedback_id'];
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];

    if ($message==='') die("Message cannot be empty");

    // insert message
    $stmt = $conn->prepare("INSERT INTO auction_feedback_messages (feedback_id, sender_role, sender_id, message) VALUES (?, 'user', ?, ?)");
    $stmt->bind_param("iis", $feedback_id, $user_id, $message);
    $stmt->execute();
    $stmt->close();

    header("Location: feedback_view.php?id=".$feedback_id);
    exit();
}
