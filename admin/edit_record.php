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
    $record_id = $_POST["record_id"];  // hidden input in form

    $stmt = $conn->prepare("UPDATE records SET title = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $title, $desc, $record_id, $_SESSION["user_id"]);

    if ($stmt->execute()) {
        header("Location: " . ($_SESSION["role"] == "admin" ? "dashboard_admin.php" : "dashboard_user.php"));
        exit();
    } else {
        echo "Error updating record.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Record</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<form method="POST" class="auth-form">
    <h2>Update</h2>
    <input type="hidden" name="record_id" value="<?= $_GET['id'] ?>">
    <input type="text" name="title" placeholder="Title" required><br><br>
    <textarea name="description" placeholder="Description"></textarea><br><br>
    <button type="submit">Update</button>
</form>

</body>
</html>
