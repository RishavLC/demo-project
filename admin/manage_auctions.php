<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}

include "../common/config.php";

/* =======================
   AUTO STATUS UPDATES
======================= */
$conn->query("UPDATE auction_items 
              SET status='closed' 
              WHERE status='active' AND end_time < NOW()");

$conn->query("UPDATE auction_items 
              SET status='active' 
              WHERE status='upcoming' AND start_time <= NOW()");

/* =======================
   APPROVE AUCTION
======================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve"])) {
    $id = intval($_POST["id"]);

    $check = $conn->query("SELECT start_time, end_time, title, seller_id 
                           FROM auction_items WHERE id=$id")->fetch_assoc();

    $start_time = $check['start_time'];
    $end_time   = $check['end_time'];

    $now = date("Y-m-d H:i:s");
    $status = ($start_time > $now) ? 'upcoming' : 'active';

    $stmt = $conn->prepare("UPDATE auction_items 
                            SET status=?, start_time=?, end_time=? 
                            WHERE id=?");
    $stmt->bind_param("sssi", $status, $start_time, $end_time, $id);
    $stmt->execute();

    $_SESSION['msg'] = "✅ Auction approved successfully!";
    header("Location: manage_auctions.php");
    exit();
}

/* =======================
   REJECT AUCTION
======================= */
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE auction_items SET status='rejected' WHERE id=$id");
    $_SESSION['msg'] = "🚫 Auction rejected!";
    header("Location: manage_auctions.php");
    exit();
}

/* =======================
   FETCH AUCTIONS + IMAGE
======================= */
$sql = "
SELECT ai.*, u.username,
       (SELECT image_path 
        FROM auction_images 
        WHERE item_id = ai.id 
        ORDER BY is_primary DESC, uploaded_at ASC 
        LIMIT 1) AS image_path
FROM auction_items ai
JOIN users u ON ai.seller_id = u.id
WHERE ai.status IN ('pending', 'pending_reapply')
ORDER BY ai.created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Auctions</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
body { background:#f5f6fa; }
.grid {
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
  gap:20px;
}
.card {
  background:#fff;
  border-radius:12px;
  box-shadow:0 4px 8px rgba(0,0,0,.1);
  overflow:hidden;
}
.card img {
  width:100%;
  height:300px;
  object-fit:cover;
}
.card-content { padding:15px; }

button, .btn-delete {
  width:100%;
  padding:8px;
  margin-top:6px;
  border:none;
  border-radius:6px;
  cursor:pointer;
}
button { background:#27ae60; color:#fff; }
.btn-delete {
  background:#e74c3c;
  color:#fff;
  text-decoration:none;
  display:block;
  text-align:center;
}
.alert.success {
  background:#d4edda;
  padding:10px;
  border-radius:6px;
  margin-bottom:15px;
}

/* Status badge */
.status-badge {
  padding:3px 8px;
  color:white;
  border-radius:6px;
  font-weight:bold;
}

/* Dropdown */
.sidebar ul li > a.caret::after { content: "▾"; float: right; }
.sidebar ul li { position: relative; }
.dropdown-menu { display:none; position:absolute; top:100%; left:25px; width:220px; background:#3a5064; margin:0; padding:0; border-radius:6px; z-index:1000; }
.dropdown-menu li a { padding:10px 20px; padding-left:35px; font-size:14px; color:#0a4554; white-space:nowrap; }
.dropdown-menu li a:hover { background:#223345; margin-left:-10px; }
.dropdown-menu.show { display:block; }
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
    <li>
      <a class="caret" onclick="toggleDropdown('auctionDropdown')">📜 Auctions</a>
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
<h2>Manage Auction Items</h2>

<?php if(isset($_SESSION['msg'])): ?>
  <div class="alert success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<div class="grid">
<?php while($row = $result->fetch_assoc()): ?>
<?php
$img = !empty($row['image_path']) ? "../" . str_replace(['../','./'],'',$row['image_path']) : "../assets/no-image.png";

// Status badge colors
$badge_color = match($row['status']) {
    'pending' => '#ffc107',
    'pending_reapply' => '#ff6f00', // dark orange
    'active' => '#28a745',
    'rejected' => '#dc3545',
    'closed' => '#95a5a6',
    default => '#6c757d',
};
?>

<div class="card">
  <img src="<?= $img ?>" alt="Auction Image">
  <div class="card-content">
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    <p><strong>Seller:</strong> <?= htmlspecialchars($row['username']) ?></p>
    <p><strong>Start Price:</strong> Rs. <?= number_format($row['start_price'],2) ?></p>
    <p><strong>Start Date:</strong> <?= date("d M Y, h:i A", strtotime($row['start_time'])) ?></p>
    <p><strong>End Date:</strong> <?= date("d M Y, h:i A", strtotime($row['end_time'])) ?></p>
    <p><strong>Status:</strong> 
       <span class="status-badge" style="background:<?= $badge_color ?>;">
           <?= ucfirst($row['status']) ?>
       </span>
    </p>

    <?php if(in_array($row['status'], ['pending','pending_reapply'])): ?>
      <form method="POST">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <button name="approve">✅ Approve</button>
      </form>
      <a href="?reject=<?= $row['id'] ?>" class="btn-delete">❌ Reject</a>
    <?php endif; ?>
  </div>
</div>
<?php endwhile; ?>
</div>
</div>

<script src="../assets/script.js"></script>
<script>
function toggleDropdown(id) {
  document.getElementById(id).classList.toggle("show");
}
</script>
</body>
</html>
