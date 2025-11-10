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
    $min_increment = floatval($_POST["min_increment"]);
    
    if ($title && $start_price > 0) {
    $stmt = $conn->prepare("INSERT INTO auction_items 
    (seller_id, title, description, category, start_price, current_price, min_increment, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("isssddd", $user_id, $title, $description, $category, $start_price, $start_price, $min_increment);

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
    <li><a href="my_bids.php">📜 My Bidding History</a></li>
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
            <label>Item Title</label>
            <input type="text" name="title" required>

            <label>Description</label>
            <textarea name="description"></textarea>

             <label>Category</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="Electronics">Electronics</option>
                <option value="Furniture">Furniture</option>
                <option value="Vehicles">Vehicles</option>
                <option value="Fashion">Fashion</option>
                <option value="Books">Books</option>
                <option value="MusicalInstruments">Musical Instruments</option>
                <option value="Antiques">Antiques</option>
                <option value="Art">Art & Collectibles</option>
                <option value="Sports">Sports</option>
                <option value="Real Estate">Real Estate</option>
                <option value="Others">Others</option>
            </select>

            <label>Start Price</label>
            <input type="number" step="0.1" name="start_price" required>
            <label>Minimum Increment *</label>
            <input type="number" step="0.01" name="min_increment" placeholder="e.g. 50 or 500" required>

            <button type="submit">Add Item</button>
        </form>
    </div>
</div>

<script src="assets/script.js"></script>
</body>
</html>
