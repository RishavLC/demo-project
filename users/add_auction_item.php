<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "user") {
    header("Location: ../auth/login.php");
    exit();
}

include "../common/config.php";
$user_id = $_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title         = trim($_POST["title"]);
    $description   = trim($_POST["description"]);
    $category      = trim($_POST["category"]);
    $start_price   = floatval($_POST["start_price"]);
    $start_time    = $_POST["start_time"];
    $end_time      = $_POST["end_time"];
    $min_increment = floatval($_POST["min_increment"]);

    if ($title && $start_price > 0) {

        // ✅ DATE VALIDATION
        $start_ts = strtotime($start_time);
        $end_ts   = strtotime($end_time);
        $now_ts   = time();

        if ($start_ts < $now_ts) {
            $message = "<p style='color:red;font-weight:bold;'>❌ Start time cannot be in the past.</p>";
        }
        elseif ($end_ts <= $start_ts) {
            $message = "<p style='color:red;font-weight:bold;'>❌ End time must be after start time.</p>";
        }
        else {

            // ✅ INSERT AUCTION ITEM (ONLY ONCE)
            $stmt = $conn->prepare("
                INSERT INTO auction_items 
                (seller_id, title, description, category, start_price, current_price, start_time, end_time, min_increment, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->bind_param(
                "isssddssd",
                $user_id,
                $title,
                $description,
                $category,
                $start_price,
                $start_price,
                $start_time,
                $end_time,
                $min_increment
            );

            if ($stmt->execute()) {

                $item_id = $conn->insert_id;

                // ✅ IMAGE UPLOAD
                if (!empty($_FILES['images']['name'][0])) {

                    $upload_dir = "../uploads/auctions/";
                    $db_dir     = "uploads/auctions/"; // 👈 stored in DB

                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {

                        $file_type = mime_content_type($tmp_name);
                        if (strpos($file_type, "image") === false) continue;

                        $file_name = time() . "_" . basename($_FILES['images']['name'][$key]);
                        $target    = $upload_dir . $file_name;
                        $db_path   = $db_dir . $file_name;

                        if (move_uploaded_file($tmp_name, $target)) {

                            $is_primary = ($key == 0) ? 1 : 0;

                            $img_stmt = $conn->prepare("
                                INSERT INTO auction_images (item_id, image_path, is_primary)
                                VALUES (?, ?, ?)
                            ");
                            $img_stmt->bind_param("isi", $item_id, $db_path, $is_primary);
                            $img_stmt->execute();
                        }
                    }
                }

                $message = "<p style='color:green;font-weight:bold;'>✅ Auction item with images added successfully!</p>";
            }
            else {
                $message = "<p style='color:red;font-weight:bold;'>❌ Error: {$stmt->error}</p>";
            }
        }
    }
    else {
        $message = "<p style='color:red;font-weight:bold;'>⚠ Please fill all required fields.</p>";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Auction Item</title>
    <link rel="stylesheet" href="../assets/style.css">
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
        <h2>Add Auction Item</h2>
        <?= $message ?>

        <form method="POST" enctype="multipart/form-data">
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

            <label>Item Images</label>
            <input type="file" name="images[]" multiple accept="image/*" required>

            <label>Start Price</label>
            <input type="number" step="0.1" name="start_price" required>
            <label>Start Time</label>
            <input type="datetime-local" name="start_time" id="start_time"required>
            <label>End Time</label>
            <input type="datetime-local" name="end_time" id="end_time" required>
            <label>Minimum Increment *</label>
            <input type="number" step="0.01" name="min_increment" placeholder="e.g. 50 or 500" required>

            <button type="submit">Add Item</button>
        </form>
    </div>
</div>

<script src="../assets/script.js"></script>
</body>
</html>
