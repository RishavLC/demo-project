<?php
session_start();
include "../common/config.php";

$id = $_GET["id"];
$conn->query("DELETE FROM records WHERE id=$id");

header("Location: " . ($_SESSION["role"] == "admin" ? "../admin/dashboard_admin.php" : "../users/dashboard_user.php"));
?>
