<?php
session_start();

// Only allow admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

include "config.php";

// Check if ID is passed
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch user data
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('User not found!'); window.location='manage_users.php';</script>";
    exit();
}
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);

    $updateStmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $username, $role, $id);

    if ($updateStmt->execute()) {
        echo "<script>alert('User updated successfully!'); window.location='manage_users.php';</script>";
    } else {
        echo "<script>alert('Error updating user!'); window.location='manage_users.php';</script>";
    }
    $updateStmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="main-content">
    <h2>Edit User</h2>
    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br><br>

        <label>Role:</label>
        <select name="role" required>
            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
            <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
        </select><br><br>

        <button type="submit">Update User</button>
        <a href="manage_users.php">Cancel</a>
    </form>
</div>
</body>
</html>
