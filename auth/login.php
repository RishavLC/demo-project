<?php
session_start();
include "../common/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = md5($_POST["password"]);

    // Fetch user with role and status
    $sql = "SELECT users.id, users.username, users.status, roles.role_name
            FROM users
            JOIN roles ON users.role_id = roles.id
            WHERE users.username='$username' AND users.password='$password'";

    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Check status
        if ($user['status'] == 'banned') {
            echo "Your account is banned. Contact admin to regain access.";
            exit();
        }

        if ($user['status'] == 'suspended') {
            // Get the time the suspension started (use session if no column)
            if (!isset($_SESSION['suspended_users'])) $_SESSION['suspended_users'] = [];

            $user_id = $user['id'];
            $now = time();

            if (!isset($_SESSION['suspended_users'][$user_id])) {
                // First login attempt after suspension, set suspend start
                $_SESSION['suspended_users'][$user_id] = $now;
                echo "Your account is suspended. You can login after 7 days.";
                exit();
            } else {
                $suspend_start = $_SESSION['suspended_users'][$user_id];
                $suspend_end = $suspend_start + (7 * 24 * 60 * 60); // 7 days in seconds

                if ($now < $suspend_end) {
                    $remaining = ceil(($suspend_end - $now) / 86400); // days left
                    echo "Your account is suspended. You can login after $remaining day(s).";
                    exit();
                } else {
                    // Automatically reactivate after 7 days
                    $conn->query("UPDATE users SET status='active' WHERE id=$user_id");
                    unset($_SESSION['suspended_users'][$user_id]);
                    $user['status'] = 'active'; // allow login now
                }
            }
        }

        // Login success
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role_name"];

        if ($user["role_name"] == "admin") {
            header("Location: ../admin/dashboard_admin.php");
        } else {
            header("Location: ../users/dashboard_user.php");
        }
        exit();
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
