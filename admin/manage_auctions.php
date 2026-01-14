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
/* ---------------- PAGINATION ---------------- */
$limit = 3;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) AS total FROM auction_items");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);


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
ORDER BY ai.created_at DESC
LIMIT $limit OFFSET $offset
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
.status-active { color:#27ae60; font-weight:bold; }
.status-upcoming { color:#2980b9; font-weight:bold; }
.status-pending { color:#f39c12; font-weight:bold; }
.status-rejected { color:#e74c3c; font-weight:bold; }
.status-closed { color:#95a5a6; font-weight:bold; }

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

.pagination { text-align:center; margin-top:15px; }
.pagination a {
    padding:6px 10px; margin:2px;
    border:1px solid #ccc; border-radius:6px;
    text-decoration:none; color:#333;
}
.active-page {
    background:#4a90e2; color:white !important;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-header">Admin Panel</div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php" class="active">📦 Manage Auctions</a></li>
    <li><a href="auction_history.php">📜 Auction History</a></li>
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
// IMAGE FIX (IMPORTANT)
if (!empty($row['image_path'])) {
    $clean = str_replace(['../','./'], '', $row['image_path']);
    $img = "../" . $clean;
} else {
    $img = "../assets/no-image.png";
}
?>

<div class="card">

  <!-- IMAGE -->
  <img src="<?= $img ?>" alt="Auction Image">

  <div class="card-content">
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    <p><strong>Seller:</strong> <?= htmlspecialchars($row['username']) ?></p>
    <p><strong>Start Price:</strong> Rs. <?= number_format($row['start_price'],2) ?></p>
    <p><strong>Status:</strong>
      <span class="status-<?= strtolower($row['status']) ?>">
        <?= ucfirst($row['status']) ?>
      </span>
    </p>

    <?php if ($row['status'] == 'pending'): ?>
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
<div class="pagination">
<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active-page' : '' ?>">
        <?= $i ?>
    </a>
<?php endfor; ?>
</div>

</div>

</body>
</html>
