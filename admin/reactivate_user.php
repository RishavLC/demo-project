<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

include "../common/config.php";

$user_id = intval($_GET['id']);
if(!$user_id) die("Invalid request.");

$conn->query("UPDATE users SET status='active', suspended_until=NULL WHERE id=$user_id");
header("Location: manage_users.php");
exit();
