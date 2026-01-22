<?php
session_start();
include "../common/config.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= VALIDATE INPUT ================= */
if (
    !isset($_POST['item_id'], $_POST['message']) ||
    !is_numeric($_POST['item_id']) ||
    trim($_POST['message']) === ''
) {
    die("Invalid request.");
}

$item_id = (int) $_POST['item_id'];
$message = trim($_POST['message']);

/* ================= VERIFY ITEM OWNERSHIP & STATUS ================= */
$checkSql = "
SELECT id
FROM auction_items
WHERE id = ?
AND seller_id = ?
AND status = 'closed'
";

$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    die("Unauthorized action.");
}

/* ================= INSERT FEEDBACK ================= */
$conn->begin_transaction();

/* Create feedback thread */
$stmt = $conn->prepare("
    INSERT INTO auction_feedback (item_id, created_by)
    VALUES (?, ?)
");
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$feedback_id = $stmt->insert_id;
$stmt->close();

/* Insert first message */
$stmt = $conn->prepare("
    INSERT INTO auction_feedback_messages
    (feedback_id, sender_id, sender_role, message)
    VALUES (?, ?, 'user', ?)
");
$stmt->bind_param("iis", $feedback_id, $user_id, $message);
$stmt->execute();
$stmt->close();

$conn->commit();


/* ================= REDIRECT ================= */
header("Location: feedback_success.php");
exit();
