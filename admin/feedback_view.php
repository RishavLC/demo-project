<?php
session_start();
include "../common/config.php";
if(!isset($_SESSION['role']) || $_SESSION['role']!=='admin') die("Unauthorized");

$feedback_id = (int)$_GET['id'];

// fetch feedback
$stmt = $conn->prepare("
SELECT f.id, a.title AS item_title, u.username AS sender_name, f.status
FROM auction_feedback f
JOIN auction_items a ON a.id=f.item_id
JOIN users u ON u.id=f.sender_id
WHERE f.id=?
");
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$feedback) die("Feedback not found");

// fetch messages
$stmt = $conn->prepare("SELECT sender_role, message, created_at FROM auction_feedback_messages WHERE feedback_id=? ORDER BY created_at ASC");
$stmt->bind_param("i",$feedback_id);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Feedback View</title>
    <link rel="stylesheet" href="../assets/style.css">
<style>
body { font-family: Arial; padding: 20px; background:#f4f6f8; }
.container { max-width:100%; margin:auto; background:#fff; padding:20px; border-radius:8px; }
.msg { padding:10px; margin-bottom:10px; border-radius:6px; }
.user { background:#e8f0ff; text-align:right; }
.admin { background:#e9f7ef; text-align:left; }
.time { font-size:12px;color:#666; }
textarea { width:100%; min-height:80px; padding:10px; }
button { margin-top:10px; padding:8px 16px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer; }
button:hover{background:#0056b3;}
</style>
</head>
<body>
     <div class="sidebar">
  <div class="sidebar-header">
    <div class="logo-box">
      <img src="../images/logo.jpeg" alt="EasyBid Logo">
      <span class="logo-text">EasyBid</span>
    </div>
    <div class="toggle-btn">☰</div>
  </div>

  <ul>
    <li><a href="../admin/">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <!-- <li><a href="manage_auctions.php">📦 Manage Auctions</a></li> -->
    <li><a href="feedback_list.php">💬 Feedback</a></li>
    <!-- DROPDOWN -->
    <li>
      <a class="caret" onclick="toggleDropdown('auctionDropdown')">
        📜 Auctions 
      </a>
      <ul class="dropdown-menu" id="auctionDropdown">
        <li><a href="auctions_active.php">🟢 Active</a></li>
        <li><a href="auctions_upcoming.php">🟡 Upcoming</a></li>
        <li><a href="auction_overview.php">📜 History</a></li>
      </ul>
    </li>

    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
<div class="container">
<h2>Feedback – <?= htmlspecialchars($feedback['item_title']) ?></h2>
<p>User: <?= htmlspecialchars($feedback['sender_name']) ?> | Status: <?= ucfirst($feedback['status']) ?></p>
<hr>
<?php while($m=$messages->fetch_assoc()): ?>
<div class="msg <?= $m['sender_role'] ?>">
<strong><?= ucfirst($m['sender_role']) ?>:</strong><br>
<?= nl2br(htmlspecialchars($m['message'])) ?>
<div class="time"><?= $m['created_at'] ?></div>
</div>
<?php endwhile; ?>
<hr>
<form method="post" action="feedback_reply.php">
<input type="hidden" name="feedback_id" value="<?= $feedback_id ?>">
<textarea name="message" required placeholder="Write your reply..."></textarea><br>
<button type="submit">Send Reply</button>
</form>
</div>
</div>
<script src="../assets/script.js"></script>
<script>
    
function toggleDropdown(id){
  document.getElementById(id).classList.toggle("show");
}
</script>
</body>
</html>
