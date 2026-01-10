<?php
session_start();

/* ======================
   ADMIN AUTH CHECK
====================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

require_once "../common/config.php";

/* ======================
   INPUT VALIDATION
====================== */
if (!isset($_POST['user_id']) || empty($_POST['reason'])) {
    die("Invalid request");
}

$user_id = (int) $_POST['user_id'];
$reason  = trim($_POST['reason']);

/* ======================
   FETCH USER
====================== */
$stmt = $conn->prepare("SELECT strike_count, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found");
}

$user = $result->fetch_assoc();
$strike_count = (int) $user['strike_count'];
$strike_count++;

/* ======================
   STRIKE LOGIC
====================== */
$action = "warning";
$new_status = $user['status'];
$suspended_until = null;

if ($strike_count == 1) {
    $action = "warning";
}
elseif ($strike_count == 2) {
    $action = "suspension";
    $new_status = "suspended";
    $suspended_until = date("Y-m-d H:i:s", strtotime("+7 days"));
}
elseif ($strike_count >= 3) {
    $action = "ban";
    $new_status = "banned";
}

/* ======================
   UPDATE USERS TABLE
====================== */
$update = $conn->prepare("
    UPDATE users 
    SET strike_count = ?, 
        status = ?, 
        suspended_until = ?
    WHERE id = ?
");
$update->bind_param(
    "issi",
    $strike_count,
    $new_status,
    $suspended_until,
    $user_id
);
$update->execute();

/* ======================
   INSERT PENALTY HISTORY
====================== */
$insert = $conn->prepare("
    INSERT INTO user_penalties (user_id, reason, action_taken, expires_at)
    VALUES (?, ?, ?, ?)
");

$insert->bind_param(
    "isss",
    $user_id,
    $reason,
    $action,
    $suspended_until
);
$insert->execute();

/* ======================
   RESPONSE
====================== */
$_SESSION['success'] = "Penalty applied successfully!";
header("Location: dashboard_admin.php");
exit;
