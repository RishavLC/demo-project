<?php
session_start();
include "config.php";

// Only allow admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

// Check if user ID is passed
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user data
$stmt = $conn->prepare("SELECT id, username, role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('User not found!'); window.location='manage_users.php';</script>";
    exit();
}
$user = $result->fetch_assoc();

// Fetch all roles
$roles_result = $conn->query("SELECT id, role_name FROM roles ORDER BY role_name ASC");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $role_id = intval($_POST['role_id']);

    // Optional: check if role_id exists
    $role_check = $conn->prepare("SELECT id FROM roles WHERE id = ?");
    $role_check->bind_param("i", $role_id);
    $role_check->execute();
    $role_check_result = $role_check->get_result();

    if ($role_check_result->num_rows == 0) {
        echo "<script>alert('Selected role is invalid!');</script>";
    } else {
        $updateStmt = $conn->prepare("UPDATE users SET username = ?, role_id = ? WHERE id = ?");
        $updateStmt->bind_param("sii", $username, $role_id, $user_id);

        if ($updateStmt->execute()) {
            echo "<script>alert('User updated successfully!'); window.location='manage_users.php';</script>";
        } else {
            echo "<script>alert('Error updating user!');</script>";
        }
        $updateStmt->close();
    }
    $role_check->close();
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
    <form method="POST" class="auth-form">
    <h2>Edit User</h2>
        <input placeholder="Username" type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br><br>
        <select name="role_id"  placeholder="Role" required>
            <?php while ($role = $roles_result->fetch_assoc()): ?>
                <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role['role_name']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Update User</button>
        <a href="manage_users.php" class="btn">Cancel</a>
    </form>
</div>
</body>
</html>
