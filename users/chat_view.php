<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

// Validate conversation ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid conversation.");
}

$conv_id = (int)$_GET['id'];

// Fetch conversation details and validate access
$stmt = $conn->prepare("
    SELECT * FROM conversations
    WHERE id = ? AND (buyer_id = ? OR seller_id = ?)
");
if (!$stmt) die("Prepare failed: ".$conn->error);
$stmt->bind_param("iii", $conv_id, $user_id, $user_id);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conv) die("Unauthorized");

// Determine the other party
$other_user_id = ($conv['buyer_id'] == $user_id) ? $conv['seller_id'] : $conv['buyer_id'];
$other_user_stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
$other_user_stmt->bind_param("i", $other_user_id);
$other_user_stmt->execute();
$other_user_stmt->bind_result($other_username);
$other_user_stmt->fetch();
$other_user_stmt->close();

// Fetch messages
$stmt = $conn->prepare("
    SELECT m.*, u.username 
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
if (!$stmt) die("Prepare failed: ".$conn->error);
$stmt->bind_param("i", $conv_id);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat with <?= htmlspecialchars($other_username) ?></title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; }
        .chat-container { max-width:100%; margin:auto; background:#fff; padding:20px; border-radius:10px; }
        .message { padding:10px; margin:5px 0; border-radius:8px; max-width:80%; }
        .mine { background:#60bb46; color:white; margin-left:auto; text-align:right; }
        .theirs { background:#ddd; color:#333; margin-right:auto; text-align:left; }
        .chat-header { text-align:center; font-weight:bold; margin-bottom:15px; }
        form { display:flex; gap:10px; margin-top:15px; }
        input[type=text] { flex:1; padding:10px; border-radius:6px; border:1px solid #ccc; }
        button { padding:10px 15px; border:none; border-radius:6px; background:#34495e; color:white; cursor:pointer; }
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
<div class="chat-container">
    <div class="chat-header">Chat with <?= htmlspecialchars($other_username) ?></div>

    <?php while($msg = $messages->fetch_assoc()): ?>
        <div class="message <?= ($msg['sender_id']==$user_id) ? 'mine' : 'theirs' ?>">
            <?= nl2br(htmlspecialchars($msg['message'])) ?>
            <br><small><?= $msg['username'] ?>, <?= $msg['created_at'] ?></small>
        </div>
    <?php endwhile; ?>

    <form method="post" action="chat_send.php">
        <input type="hidden" name="conversation_id" value="<?= $conv_id ?>">
        <input type="text" name="message" placeholder="Type a message..." required>
        <button type="submit">Send</button>
    </form>
</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
