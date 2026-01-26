<?php
session_start();
include "../common/config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

// Validate item
if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    die("Invalid item.");
}

$item_id = (int)$_GET['item_id'];

// Fetch item & seller
$stmt = $conn->prepare("SELECT * FROM auction_items WHERE id=?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    die("Item not found.");
}

$seller_id = $item['seller_id'];

// Prevent messaging self
if ($seller_id == $user_id) {
    die("You cannot message yourself.");
}

// Check if a conversation already exists
$stmt = $conn->prepare("
    SELECT id FROM conversations 
    WHERE item_id=? AND 
          ((buyer_id=? AND seller_id=?) OR (buyer_id=? AND seller_id=?))
    LIMIT 1
");
$stmt->bind_param("iiiii", $item_id, $user_id, $seller_id, $seller_id, $user_id);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($conv) {
    // Conversation exists, redirect to chat
    header("Location: chat_view.php?id=".$conv['id']);
    exit();
}

// No conversation exists → create a new one
$stmt = $conn->prepare("
    INSERT INTO conversations (item_id, buyer_id, seller_id) 
    VALUES (?, ?, ?)
");
$stmt->bind_param("iii", $item_id, $user_id, $seller_id);
$stmt->execute();
$new_conv_id = $stmt->insert_id;
$stmt->close();

// Redirect to chat view
header("Location: chat_view.php?id=".$new_conv_id);
exit();
