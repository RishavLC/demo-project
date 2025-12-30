<?php
session_start();

// Only allow admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

include "../common/config.php";

// Check if ID is passed
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // prevent SQL injection

    // First, prevent admin from deleting their own account (optional safety)
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own account!'); window.location='manage_users.php';</script>";
        exit();
    }

    // Prepare delete query
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('User deleted successfully'); window.location='manage_users.php';</script>";
    } else {
        echo "<script>alert('Error deleting user'); window.location='manage_users.php';</script>";
    }

    $stmt->close();
}
else {
    header("Location: manage_users.php");
    exit();
}
