<?php
date_default_timezone_set('Asia/Kathmandu');
session_start();
include "../common/config.php";

/* Block role switching */
if (isset($_SESSION["role"])) {
    header("Location: /demo-project/");
    exit();
}

/* Validate request */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$username = trim($_POST["username"]);
$password = md5($_POST["password"]);

/* Fetch user */
$sql = "SELECT users.id, users.status, users.suspended_at, roles.role_name
        FROM users
        JOIN roles ON users.role_id = roles.id
        WHERE users.username=? AND users.password=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

/* ❌ INVALID LOGIN */
if (!$user) {
    header("Location: login.php?error=invalid");
    exit();
}

/* 🚫 BANNED */
if ($user["status"] === "banned") {
    header("Location: login.php?error=banned");
    exit();
}

/* ⏳ SUSPENDED */
if ($user["status"] === "suspended") {

    $suspendEnd = strtotime($user["suspended_at"]);

    if (time() < $suspendEnd) {
        header("Location: login.php?suspended_until=" . $suspendEnd);
        exit();
    }

    /* Auto-reactivate */
    $conn->query("
        UPDATE users
        SET status='active', suspended_at=NULL
        WHERE id=" . (int)$user["id"]
    );
}

/* ✅ LOGIN SUCCESS */
$_SESSION["user_id"] = $user["id"];
$_SESSION["role"] = $user["role_name"];

if ($user["role_name"] === "admin") {
    header("Location: /demo-project/admin/");
} else {
    header("Location: /demo-project/users/");
}
exit();
