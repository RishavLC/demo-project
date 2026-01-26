<?php
session_start();
include "../common/config.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= VALIDATE ITEM ID ================= */
if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    die("Invalid request");
}
$item_id = (int)$_GET['item_id'];

/* ================= FETCH REJECTED ITEM ================= */
$stmt = $conn->prepare("
    SELECT *
    FROM auction_items
    WHERE id=? AND seller_id=? AND status='rejected'
");
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    die("Item not found or not eligible for reapply");
}

/* ================= HANDLE FORM SUBMIT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title         = trim($_POST['title']);
    $description   = trim($_POST['description']);
    $category      = trim($_POST['category']);
    $start_price   = (float)$_POST['start_price'];
    $min_increment = (float)$_POST['min_increment'];
    $buy_now_price = !empty($_POST['buy_now_price']) ? (float)$_POST['buy_now_price'] : null;

    /* ✅ Convert datetime-local → MySQL DATETIME */
    $start_time = date("Y-m-d H:i:s", strtotime($_POST['start_time']));
    $end_time   = date("Y-m-d H:i:s", strtotime($_POST['end_time']));

    /* ========= VALIDATION ========= */
    if (
        empty($title) ||
        empty($category) ||
        $start_price <= 0 ||
        strtotime($end_time) <= strtotime($start_time)
    ) {
        $error = "Please check all fields. End time must be after start time.";
    } else {

        /* ========= REAPPLY (DO NOT ACTIVATE HERE) ========= */
        $update = $conn->prepare("
            UPDATE auction_items
            SET
                title=?,
                description=?,
                category=?,
                start_price=?,
                min_increment=?,
                buy_now_price=?,
                start_time=?,
                end_time=?,
                status='pending_reapply',
                rejection_reason=NULL
            WHERE id=? AND seller_id=?
        ");

        $update->bind_param(
    "sssdddsiii",
    $title,
    $description,
    $category,
    $start_price,
    $min_increment,
    $buy_now_price,
    $start_time,
    $end_time,
    $item_id,
    $user_id
);


        if ($update->execute()) {
            header("Location: my_added_items.php?reapplied=1");
            exit();
        } else {
            $error = "Failed to reapply item.";
        }

        $update->close();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Reapply Auction Item</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { font-family: Arial; background:#f4f6f8; }
        .box {
            max-width:100%;
            margin:40px auto;
            background:#fff;
            padding:25px;
            border-radius:8px;
            box-shadow:0 10px 30px rgba(0,0,0,.1);
        }
        input, textarea, select {
            width:100%;
            padding:10px;
            margin-top:6px;
            margin-bottom:15px;
        }
        button {
            padding:12px 20px;
            background:#3498db;
            color:#fff;
            border:none;
            border-radius:5px;
            cursor:pointer;
        }
        .error { color:red; margin-bottom:15px; }
    </style>
</head>

<body>
<div class="sidebar">
  <div class="sidebar-header">
    <!-- Logo instead of Welcome -->
    <div class="logo-box">
      <img src="../images/logo.jpeg" alt="EasyBid Logo" class="logo-img">
      <span class="logo-text">EasyBid</span>
    </div>
    <div class="toggle-btn">☰</div>
  </div>

  <ul>
    <li><a href="../users/" data-label="Dashboard">🏠 <span>Dashboard</span></a></li>
    <!-- <li><a href="add_record.php" data-label="Add Record">➕ <span>Add Record</span></a></li> -->
    <li><a href="add_auction_item.php" data-label="Add Auction Items">📦 <span>Add Auction Items</span></a></li>
    <li><a href="auction_bid.php" data-label="Place Bids">🪙 <span>Place Bids</span></a></li>
    <li><a href="my_added_items.php" data-label="My Added Items">📦 <span>My Added Items</span></a></li>
    <li><a href="my_bids.php" data-label="My Bidding History">📜 <span>My Bidding History</span></a></li>
    <li><a href="feedback_list.php" data-label="Feedback list">💬 <span>My Feedback</span></a></li>
    <li><a href="../auth/logout.php" data-label="Logout">🚪 <span>Logout</span></a></li>
  </ul>
</div>
<div class="main-content">

<div class="box">
    <h2>🔁 Reapply Auction Item</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>

        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars($item['description']) ?></textarea>

        <label>Category</label>
        <input type="text" name="category" value="<?= htmlspecialchars($item['category']) ?>" required>

        <label>Start Price</label>
        <input type="number" step="0.01" name="start_price" value="<?= $item['start_price'] ?>" required>

        <label>Minimum Increment</label>
        <input type="number" step="0.01" name="min_increment" value="<?= $item['min_increment'] ?>" required>

        <label>Start Time</label>
        <input type="datetime-local" name="start_time"
               value="<?= date('Y-m-d\TH:i', strtotime($item['start_time'])) ?>" required>

        <label>End Time</label>
        <input type="datetime-local" name="end_time"
               value="<?= date('Y-m-d\TH:i', strtotime($item['end_time'])) ?>" required>

        <button type="submit">Reapply Item</button>
    </form>
</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
