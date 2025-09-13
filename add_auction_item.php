<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: index.php");
    exit();
}

include "config.php";
$user_id = $_SESSION["user_id"]; // seller = logged in user
$message = "";

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $category = trim($_POST["category"]);
    $start_price = floatval($_POST["start_price"]);
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    if ($title && $start_price > 0 && $start_time && $end_time) {
        $stmt = $conn->prepare("INSERT INTO auction_items 
            (seller_id, title, description, category, start_price, current_price, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?,'closed')");
        $stmt->bind_param("isssddss", $user_id, $title, $description, $category, $start_price, $start_price, $start_time, $end_time);

        if ($stmt->execute()) {
            $message = "<p style='color:green;font-weight:bold;'>✅ Auction item added successfully!</p>";
        } else {
            $message = "<p style='color:red;font-weight:bold;'>❌ Error: " . $stmt->error . "</p>";
        }
    } else {
        $message = "<p style='color:red;font-weight:bold;'>⚠ Please fill all required fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Auction Item</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-container {
            width: 500px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.2);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 15px;
        }
        .form-container label {
            display: block;
            margin: 10px 0 5px;
        }
        .form-container input, 
        .form-container textarea, 
        .form-container select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .form-container button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            border: none;
            background: #4a90e2;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        .form-container button:hover {
            background: #357ab7;
        }
    </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header">
    User Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_user.php">🏠 Dashboard</a></li>
    <li><a href="add_record.php">➕ Add Record</a></li>
    <li><a href="add_auction_item.php">📦 Add Auction Item</a></li>
    <li><a href="auction_bid.php">💰 Place Bid</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
    <div class="form-container">
        <h2>Add Auction Item</h2>
        <?= $message ?>

        <form method="POST">
            <label>Item Title *</label>
            <input type="text" name="title" required>

            <label>Description</label>
            <textarea name="description"></textarea>

            <label>Category</label>
            <input type="text" name="category">

            <label>Start Price *</label>
            <input type="number" step="0.01" name="start_price" required>

            <label>Start Time *</label>
            <input type="datetime-local" name="start_time" required>

            <label>End Time *</label>
            <input type="datetime-local" name="end_time" required>

            <button type="submit">✅ Add Item</button>
        </form>
    </div>
</div>

<script src="assets/script.js"></script>
</body>
</html>
