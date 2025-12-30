<?php
session_start();
include "../common/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = md5($_POST["password"]);

    // Join users with roles to fetch role_name
    $sql = "SELECT users.id, users.username, roles.role_name 
            FROM users
            JOIN roles ON users.role_id = roles.id
            WHERE users.username='$username' AND users.password='$password'";
    
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role_name"]; // now stores "admin" or "user"

        if ($user["role_name"] == "admin") {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_user.php");
        }
    } else {
        echo "Invalid username or password!";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>    
    <form method="POST" class="auth-form">
        <h2>Login</h2>
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
        <p>If you don't have account? <a href="register.php">Register here</a></p>
    </form>
   
</body>
</html>
