<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit("Invalid ID");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("
    UPDATE auction_feedback
    SET status = 'reviewed'
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: feedback_list.php");
exit();
