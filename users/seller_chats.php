<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') die("Unauthorized");
$user_id = $_SESSION['user_id'];

// Fetch all conversations where user is seller
$sql = "
    SELECT c.id, c.item_id, ai.title AS item_title, u.username AS buyer_name, c.created_at
    FROM conversations c
    JOIN auction_items ai ON ai.id = c.item_id
    JOIN users u ON u.id = c.buyer_id
    WHERE c.seller_id=?
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<h2>Messages from Buyers</h2>
<ul>
<?php while($row = $result->fetch_assoc()): ?>
    <li>
        <strong>Item:</strong> <?= htmlspecialchars($row['item_title']) ?> <br>
        <strong>Buyer:</strong> <?= htmlspecialchars($row['buyer_name']) ?> <br>
        <a href="chat_view.php?id=<?= $row['id'] ?>">💬 View / Reply</a>
    </li>
<?php endwhile; ?>
</ul>
