<?php
session_start();

/* Redirect if already logged in */
if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: /demo-project/admin/");
        exit();
    }
    if ($_SESSION["role"] === "user") {
        header("Location: /demo-project/users/");
        exit();
    }
}
$error = $_GET["error"] ?? null;
$suspendedUntil = isset($_GET["suspended_until"]) ? (int)$_GET["suspended_until"] : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .error-box {
            background: #ffe6e6;
            color: #b30000;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
   
<form method="POST" action="login_process.php" class="auth-form">
     <?php if ($error === "banned"): ?>
    <div class="error-box">
        🚫 Your account is permanently banned.
    </div>
<?php endif; ?>

<?php if ($suspendedUntil): ?>
    <div class="error-box">
        ⏳ Your account is suspended.
        <br>
        Time remaining: <strong id="countdown"></strong>
    </div>
<?php endif; ?>
    <h2>Login</h2>
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
        <p>If you don't have account? <a href="register.php">Register here</a></p>
</form>

<?php if ($suspendedUntil): ?>
<script>
    const suspendEnd = <?= $suspendedUntil ?> * 1000;

    function updateCountdown() {
        const diff = suspendEnd - Date.now();

        if (diff <= 0) {
            document.getElementById("countdown").innerHTML =
                "✅ Suspension expired. Please refresh.";
            return;
        }

        const d = Math.floor(diff / (1000 * 60 * 60 * 24));
        const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
        const m = Math.floor((diff / (1000 * 60)) % 60);
        const s = Math.floor((diff / 1000) % 60);

        document.getElementById("countdown").innerHTML =
            `${d}d ${h}h ${m}m ${s}s`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
</script>
<?php endif; ?>

</body>
</html>
