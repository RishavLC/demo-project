<?php
session_start();
include "config.php";

$id = $_GET["id"];
$conn->query("DELETE FROM records WHERE id=$id");

header("Location: " . ($_SESSION["role"] == "admin" ? "dashboard_admin.php" : "dashboard_user.php"));
?>
