<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $desc = $_POST["description"];
    $user_id = $_SESSION["user_id"];

    $sql = "INSERT INTO records (user_id, title, description) VALUES ($user_id, '$title', '$desc')";
    if ($conn->query($sql)) {
        header("Location: " . ($_SESSION["role"] == "admin" ? "dashboard_admin.php" : "dashboard_user.php"));
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Record</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<form method="POST">
    <h2>Add Record</h2>
    <input type="text" name="title" placeholder="Title" required><br><br>
    <textarea name="description" placeholder="Description"></textarea><br><br>
    <button type="submit">Save</button>
</form>
</body>
</html>
