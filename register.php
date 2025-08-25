<?php
include "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = md5($_POST["password"]);

    $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'user')";
    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert success'>🎉 Registration successful! 
                       <a href='index.php'>Login here</a>
                    </div>";
    } else {
        $message = "<div class='alert error'>⚠️ Error: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="assets/style.css">
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
            margin: 40%;
            background: #fff;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 300px;
            text-align: center;
        }
        form h2 {
            margin-bottom: 15px;
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
        .alert a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .alert a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Register</h2>
        <?php if ($message) echo $message; ?>
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>
