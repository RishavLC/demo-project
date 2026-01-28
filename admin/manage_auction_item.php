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
$stmt->close();

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
    // Determine status
    $now = date("Y-m-d H:i:s");
    $status = ($item['start_time'] > $now) ? 'upcoming' : 'active';

    // Update status and keep start/end times
    $stmt = $conn->prepare("
        UPDATE auction_items 
        SET `status`=?, rejection_reason=NULL
        WHERE id=?
    ");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['msg'] = "Auction approved.";
    header("Location: auctions_active.php");
    exit();
}

/* REJECT */
if (isset($_POST['reject'])) {
    $reason = trim($_POST['reason']);

    if ($reason === "") {
        $error = "Rejection reason required.";
    } else {
        $stmt = $conn->prepare("
            UPDATE auction_items 
            SET `status`='rejected', rejection_reason=? 
            WHERE id=?
        ");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['msg'] = "Auction rejected.";
        header("Location: auction_overview.php");
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

/* TOP */
.top {
  display:flex;
  gap:16px;
  align-items:flex-start;
}

/* IMAGES */
.images { flex:1; }
.images img {
  width:100%;
  height:220px;
  object-fit:cover;
  border-radius:8px;
  margin-bottom:6px;
}

/* DETAILS */
.details { flex:1.4; }

.details h3 {
  margin:0 0 6px;
}

.info p {
  margin:4px 0;
  font-size:14px;
}

/* DESCRIPTION */
.description {
  margin-top:12px;
  font-size:14px;
}

/* ACTIONS */
.actions {
  display:flex;
  gap:10px;
  margin-top:10px;
}

textarea {
  width:100%;
  min-height:60px;
  padding:8px;
  border-radius:6px;
  border:1px solid #ccc;
}

.btn {
  padding:8px 18px;
  border:none;
  border-radius:6px;
  cursor:pointer;
  font-weight:600;
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

<h3>Manage Auction Item</h3>

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
    <p><strong>Email:</strong> <?= htmlspecialchars($item['email']) ?></p>
    <p><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></p>
    <p><strong>Start Price:</strong> Rs. <?= number_format($item['start_price'],2) ?></p>
    <p><strong>Start:</strong> <?= date("d M Y h:i A", strtotime($item['start_time'])) ?></p>
    <p><strong>End:</strong> <?= date("d M Y h:i A", strtotime($item['end_time'])) ?></p>
    <p><strong>Status:</strong> <?= ucfirst($item['status']) ?></p>
  </div>

  <div class="description">
    <strong>Description:</strong><br>
    <?= nl2br(htmlspecialchars($item['description'])) ?>
  </div>
</div>

</div>

<!-- ACTIONS -->
<?php if (in_array($item['status'], ['pending', 'pending_reapply'])): ?>

<?php if(isset($error)): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>
<?php if($item['status'] === 'pending_reapply'): ?>
<p style="color:#ff6f00;"><strong>⚠ Reapplied item pending review</strong></p>
<?php endif; ?>

<div class="actions">
  <form method="POST" style="display:inline;">
    <button name="approve" class="btn approve">Approve</button>
  </form>
  <button class="btn reject" onclick="openRejectModal()">Reject</button>
</div>

<!-- REJECTION MODAL -->
<div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; width:400px; position:relative;">
    <h3>Reject Auction</h3>
    <form method="POST">
      <div>
        <p><strong>Select a reason:</strong></p>
        <label><input type="radio" name="reason" value="Invalid item" required> Invalid item</label><br>
        <label><input type="radio" name="reason" value="Prohibited item"> Prohibited item</label><br>
        <label><input type="radio" name="reason" value="Suspicious activity"> Suspicious activity</label><br>
        <label><input type="radio" name="reason" value="Other"> Pricing</label>
      </div>

      <div style="margin-top:15px; display:flex; justify-content:space-between;">
        <button type="submit" name="reject" class="btn reject">Submit</button>
        <button type="button" class="btn" onclick="closeRejectModal()">Cancel</button>
      </div>
    </form>

    <button onclick="closeRejectModal()" style="position:absolute; top:8px; right:12px; background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
  </div>
</div>

<script>
function openRejectModal() {
  document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
  document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php else: ?>
<p><em>Already reviewed.</em></p>
<?php if($item['rejection_reason']): ?>
<p><strong>Reason:</strong> <?= htmlspecialchars($item['rejection_reason']) ?></p>
<?php endif; ?>
<?php endif; ?>


</div>
</div>
</div>

<script src="../assets/script.js"></script>
</body>
</html>
