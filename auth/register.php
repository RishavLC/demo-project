<?php
include "../common/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = md5($_POST["password"]);

    // ✅ Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert error'>Invalid email format.</div>";
    } else {
        // ✅ Check if username or email already exists
        $check = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "<div class='alert error'>Username or Email already exists!</div>";
        } else {
            // ✅ Get role_id for 'user'
            $roleResult = $conn->query("SELECT id FROM roles WHERE role_name = 'user' LIMIT 1");
            $roleRow = $roleResult->fetch_assoc();
            $role_id = $roleRow['id'];

            // ✅ Insert new user
            $sql = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
            $sql->bind_param("sssi", $username, $email, $password, $role_id);

            if ($sql->execute()) {
                // ✅ Get created_at
                $user_id = $conn->insert_id;
                $result = $conn->query("SELECT created_at FROM users WHERE id = $user_id");
                $row = $result->fetch_assoc();
                $created_at = $row['created_at'];

                // ✅ Success message
                echo "<div style='
                        margin: 20px auto; 
                        width: 420px; 
                        padding: 15px; 
                        text-align: center; 
                        background: #e6ffed; 
                        border: 1px solid #28a745; 
                        border-radius: 8px; 
                        font-family: Arial, sans-serif; 
                        color: #155724;'>
                        🎉 Registration successful on <b>$created_at</b>! <br><br>
                        <a href='login.php' style='
                            display: inline-block; 
                            padding: 8px 15px; 
                            margin-top: 10px; 
                            background: #28a745; 
                            color: #fff; 
                            text-decoration: none; 
                            border-radius: 5px;'>Login here</a>
                      </div>";
                exit();
            } else {
                $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background: #fff;
            padding: 25px 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 320px;
            text-align: center;
        }
        form h2 {
            margin-bottom: 20px;
            color: #333;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        form button {
            background: #4CAF50;
            border: none;
            padding: 10px;
            width: 100%;
            color: #fff;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        form button:hover {
            background: #45a049;
        }
        .alert {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<form method="POST">
    <h2>Register</h2>
    <?= $message ?>
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Register</button>
</form>
</body>
</html>
<!-- user registration can can e done easily by email , username and password but role will be defaultly be normal user 
 and only admin can change roles -->