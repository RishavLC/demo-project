<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

include "config.php";

$user_id = $_SESSION["user_id"];

// ✅ Mark all as read
$sql = "UPDATE notifications SET is_read=1 WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Redirect back to dashboard
header("Location: dashboard_user.php");
exit();
?>
