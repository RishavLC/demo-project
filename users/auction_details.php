<?php
session_start();
include "../common/config.php";

$item_id = $_GET['item_id'] ?? 0;

if($item_id <= 0){
    echo "Invalid auction item.";
    exit();
}

// Fetch auction item
$stmt = $conn->prepare("SELECT * FROM auction_items WHERE id=?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$auction = $result->fetch_assoc();

if(!$auction){
    echo "Auction item not found.";
    exit();
}

// Fetch auction images
$stmt_img = $conn->prepare("SELECT * FROM auction_images WHERE item_id=? ORDER BY is_primary DESC, uploaded_at ASC");
$stmt_img->bind_param("i", $item_id);
$stmt_img->execute();
$images = $stmt_img->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($auction['title']) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .auction-images img { margin:5px; border-radius:8px; }
        .auction-container { max-width:700px; margin:auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.2);}
    </style>
</head>
<body>

<div class="auction-container">
    <h2><?= htmlspecialchars($auction['title']) ?></h2>
    <p><strong>Category:</strong> <?= htmlspecialchars($auction['category']) ?></p>
    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($auction['description'])) ?></p>
    <p><strong>Start Price:</strong> $<?= number_format($auction['start_price'],2) ?></p>
    <p><strong>Current Price:</strong> $<?= number_format($auction['current_price'],2) ?></p>
    <p><strong>Start Time:</strong> <?= $auction['start_time'] ?></p>
    <p><strong>End Time:</strong> <?= $auction['end_time'] ?></p>

    <div class="auction-images">
        <?php while($img = $images->fetch_assoc()): ?>
            <img src="../<?= $img['image_path'] ?>" width="200">
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>
