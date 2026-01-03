<?php
session_start();
include "../common/config.php";

/* =======================
   AUTH CHECK
======================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id = intval($_GET['item_id'] ?? 0);

if ($item_id <= 0) {
    die("Invalid auction item.");
}

//    FETCH AUCTION ITEM
$stmt = $conn->prepare("SELECT * FROM auction_items WHERE id=? AND status='active'");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();
// fetch username
$user_sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();  

if (!$auction) {
    die("Auction not found or not active.");
}

/* =======================
   FETCH IMAGES
======================= */
$stmt_img = $conn->prepare("
    SELECT * FROM auction_images 
    WHERE item_id=? 
    ORDER BY is_primary DESC, uploaded_at ASC
");
$stmt_img->bind_param("i", $item_id);
$stmt_img->execute();
$img_result = $stmt_img->get_result();

$images = [];
while ($row = $img_result->fetch_assoc()) {
    // Remove any "../" or "./" from DB path
    $clean_path = str_replace(['../', './'], '', $row['image_path']);

    // Relative path from this file
    $row['full_url'] = "../" . $clean_path; // go up from /users/ to /demo/
    $images[] = $row;
}

/* =======================
   HANDLE BID SUBMISSION
======================= */
$bid_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid_amount = floatval($_POST['bid_amount']);
    $min_allowed = $auction['current_price'] + $auction['min_increment'];

    if ($bid_amount < $min_allowed) {
        $bid_message = "<p style='color:red;'>Bid must be at least $min_allowed</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO bids (item_id, bidder_id, bid_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $item_id, $user_id, $bid_amount);

        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("UPDATE auction_items SET current_price=? WHERE id=?");
            $stmt2->bind_param("di", $bid_amount, $item_id);
            $stmt2->execute();

            header("Location: auction_details.php?item_id=" . $item_id);
            exit();
        } else {
            $bid_message = "<p style='color:red;'>Bid failed.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($auction['title']) ?></title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:0; }
        .sidebar { width: 220px; background:#2c3e50; color:#fff; position:fixed; top:0; bottom:0; padding-top:20px; }
        .sidebar-header { text-align:center; font-weight:bold; margin-bottom:20px; }
        .sidebar ul { list-style:none; padding:0; }
        .sidebar ul li { padding:10px 20px; }
        .sidebar ul li a { color:#fff; text-decoration:none; display:block; }
        .sidebar ul li a.active, .sidebar ul li a:hover { background:#34495e; border-radius:4px; }
        .main-content { margin-left:240px; padding:20px; }
        .auction-wrapper { max-width:1000px; margin:auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.15); }
        .gallery { display:flex; gap:20px; margin-bottom:20px; }
        .main-image img { width:380px; border-radius:10px; }
        .thumbs img { width:80px; height:70px; object-fit:cover; margin:5px; cursor:pointer; border-radius:6px; border:2px solid #ddd; }
        .auction-info { margin-top:10px; }
        .price { font-size:22px; font-weight:bold; color:#27ae60; }
        .bid-box { margin-top:20px; padding:15px; background:#f4f6f8; border-radius:10px; }
        .bid-box input, .bid-box button { width:100%; padding:10px; margin-top:10px; }
        .bid-box button { background:#4a90e2; color:#fff; border:none; border-radius:6px; cursor:pointer; }
        .bid-box button:hover { background:#357ab7; }
    </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header">
    Welcome, <?= htmlspecialchars($username) ?>
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
    <div class="auction-wrapper">
        <h2><?= htmlspecialchars($auction['title']) ?></h2>

        <!-- GALLERY -->
        <div class="gallery">
            <div class="main-image">
                <?php if (!empty($images)): ?>
                    <img id="mainImg" src="<?= $images[0]['full_url'] ?>">
                <?php else: ?>
                    <img src="../assets/no-image.png" width="380">
                <?php endif; ?>
            </div>
            <div class="thumbs">
                <?php foreach ($images as $img): ?>
                    <img src="<?= $img['full_url'] ?>" onclick="changeImage(this.src)">
                <?php endforeach; ?>
            </div>
        </div>

        <!-- AUCTION INFO -->
        <div class="auction-info">
            <p><strong>Category:</strong> <?= htmlspecialchars($auction['category']) ?></p>
            <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($auction['description'])) ?></p>
            <p class="price">Current Price: Rs<?= number_format($auction['current_price'], 2) ?></p>
            <p>Minimum Increment: Rs<?= number_format($auction['min_increment'], 2) ?></p>
            <p>Ends At: <?= $auction['end_time'] ?></p>

            <?= $bid_message ?>

            <!-- BID FORM -->
            <form method="POST" class="bid-box">
                <label>Your Bid Amount</label>
                <input type="number" step="0.01"
                       name="bid_amount"
                       min="<?= $auction['current_price'] + $auction['min_increment'] ?>"
                       required>
                <button type="submit">Place Bid</button>
            </form>
        </div>
    </div>
</div>

<script>
function changeImage(src) {
    document.getElementById("mainImg").src = src;
}
</script>

</body>
</html>
