<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $desc = $_POST["description"];
    $user_id = $_SESSION["user_id"];

    $sql = "INSERT INTO records (user_id, title, description) VALUES ($user_id, '$title', '$desc')";
    if ($conn->query($sql)) {
        header("Location: " . ($_SESSION["role"] == "admin" ? "dashboard_admin.php" : "dashboard_user.php"));
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Record</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="sidebar">
  <div class="sidebar-header">
    User Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_user.php" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li>
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">💰 <span>Place Bids</span></a></li>
    <li><a href="auctions.php" class="active">📊 Auction Details</a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="../auth/logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>

<div class="main-content">
    <div class="form-container">
<form method="POST" class="auth-form">
    <h2>Add Record</h2>
    <input type="text" name="title" placeholder="Title" required><br><br>
    <textarea name="description" placeholder="Description"></textarea><br><br>
    <button type="submit">Save</button>
</form>
</div></div>
</body>
</html>
