<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

include "../common/config.php";

if (!isset($_GET['id'])) {
    header("Location: manage_auctions.php");
    exit();
}

$id = (int)$_GET['id'];

/* FETCH AUCTION */
$stmt = $conn->prepare("
    SELECT ai.*, u.username, u.email
    FROM auction_items ai
    JOIN users u ON ai.seller_id = u.id
    WHERE ai.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: manage_auctions.php");
    exit();
}

/* FETCH IMAGES */
$images = [];
$res = $conn->query("SELECT image_path FROM auction_images WHERE item_id=$id");
while ($img = $res->fetch_assoc()) {
    $images[] = "../" . str_replace(['../','./'], '', $img['image_path']);
}

/* APPROVE */
if (isset($_POST['approve'])) {
    $status = ($item['start_time'] > date("Y-m-d H:i:s")) ? 'upcoming' : 'active';
    $stmt = $conn->prepare("
        UPDATE auction_items SET status=?, reviewed_at=NOW(), rejection_reason=NULL WHERE id=?
    ");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $_SESSION['msg'] = "Auction approved.";
    header("Location: manage_auctions.php");
    exit();
}

/* REJECT */
if (isset($_POST['reject'])) {
    $reason = trim($_POST['reason']);
    if ($reason === "") {
        $error = "Rejection reason required.";
    } else {
        $stmt = $conn->prepare("
            UPDATE auction_items SET status='rejected', rejection_reason=?, reviewed_at=NOW() WHERE id=?
        ");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();
        $_SESSION['msg'] = "Auction rejected.";
        header("Location: manage_auctions.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Auction</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
body { background:#f4f6f9; font-family:Arial,sans-serif; }

.wrapper {
  max-width:1050px;
  margin:20px auto;
}

.card {
  background:#fff;
  border-radius:10px;
  padding:16px;
  box-shadow:0 6px 16px rgba(0,0,0,.08);
}

/* MAIN ROW */
.top {
  display:flex;
  gap:16px;
}

/* IMAGES */
.images {
  flex:1;
}
.images img {
  width:100%;
  height:230px;
  object-fit:cover;
  border-radius:8px;
  margin-bottom:6px;
}

/* DETAILS */
.details {
  flex:1.3;
}

.details h3 {
  margin:0 0 6px 0;
}
.pending { background:#fff3cd; color:#856404; }
.active { background:#d4edda; color:#155724; }
.rejected { background:#f8d7da; color:#721c24; }

/* INFO ROWS */
.info p {
  margin:3px 0;
  font-size:14px;
}
/* TEXTAREA */
textarea {
  width:100%;
  min-height:60px;
  padding:8px;
  border-radius:6px;
  border:1px solid #ccc;
}

/* ACTIONS */
.actions {
  display: flex;
  gap: 10px;
  margin-top: 8px;
  flex-wrap: wrap; /* ensures responsive stacking if small screen */
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}


.approve { background:#27ae60; color:#fff; }
.reject { background:#e74c3c; color:#fff; }

.error {
  color:#e74c3c;
  font-size:13px;
  margin-bottom:6px;
}
</style>
</head>

<body><div class="sidebar">
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
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>

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
<div class="wrapper">
<div class="card">
    <h3 style="margin-top: -5px;">Manage Auction Item</h3>
<div class="top">

<!-- IMAGES -->
<div class="images">
<?php if ($images): foreach ($images as $img): ?>
  <img src="<?= $img ?>">
<?php endforeach; else: ?>
  <img src="../assets/no-image.png">
<?php endif; ?>
</div>

<!-- DETAILS -->
<div class="details">
  <h3><?= htmlspecialchars($item['title']) ?></h3>

  

  <div class="info">
    <p><strong>Seller:</strong> <?= htmlspecialchars($item['username']) ?></p>
    <p><strong>Email:</strong> <?= $item['email'] ?></p>
    <p><strong>Price:</strong> Rs. <?= number_format($item['start_price'],2) ?></p>
    <p><strong>Start:</strong> <?= date("d M Y h:i A", strtotime($item['start_time'])) ?></p>
    <p><strong>End:</strong> <?= date("d M Y h:i A", strtotime($item['end_time'])) ?></p>
    <p><strong>Status:</strong>
        <?= ucfirst($item['status']) ?>
        
<h4 style="margin-bottom:-5px;">Description</h4>
<p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
  </div>
</div>
</div>


<!-- ACTIONS -->
<div class="section">
<?php if ($item['status'] === 'pending'): ?>

<?php if(isset($error)): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
  <div class="actions">
    <button name="approve" class="btn approve">Approve</button>
    <button name="reject" class="btn reject">Reject</button>
  </div>
  <label style="margin-top:8px;"><strong>Reject reason</strong></label>
  <textarea name="reason" required></textarea>
</form>

<?php else: ?>
<p><em>Already reviewed.</em></p>
<?php if($item['rejection_reason']): ?>
<p><strong>Reason:</strong> <?= htmlspecialchars($item['rejection_reason']) ?></p>
<?php endif; ?>
<?php endif; ?>
</div>

</div>
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
