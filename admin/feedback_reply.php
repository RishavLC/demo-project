<?php
session_start();
include "../common/config.php";
if(!isset($_SESSION['role']) || $_SESSION['role']!=='admin') die("Unauthorized");

if($_SERVER['REQUEST_METHOD']==='POST'){
    $feedback_id = (int)$_POST['feedback_id'];
    $message = trim($_POST['message']);
    $admin_id = $_SESSION['user_id']; // admin ID

    if($message==='') die("Message cannot be empty");

    // insert message
    $stmt = $conn->prepare("INSERT INTO auction_feedback_messages (feedback_id, sender_role, sender_id, message) VALUES (?, 'admin', ?, ?)");
    $stmt->bind_param("iis",$feedback_id, $admin_id, $message);
    $stmt->execute();
    $stmt->close();

    // mark feedback as reviewed
    $stmt = $conn->prepare("UPDATE auction_feedback SET status='reviewed' WHERE id=?");
    $stmt->bind_param("i",$feedback_id);
    $stmt->execute();
    $stmt->close();

    header("Location: feedback_view.php?id=".$feedback_id);
    exit();
}
