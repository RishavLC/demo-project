<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

include "../common/config.php";

/* ================= COUNTS ================= */

// Users
$user_count = $conn->query("SELECT COUNT(*) total FROM users")->fetch_assoc()['total'];
$active_users = $conn->query("SELECT COUNT(*) total FROM users WHERE status='active'")->fetch_assoc()['total'];
$suspended_users = $conn->query("SELECT COUNT(*) total FROM users WHERE status='suspended'")->fetch_assoc()['total'];
$banned_users = $conn->query("SELECT COUNT(*) total FROM users WHERE status='banned'")->fetch_assoc()['total'];

// Auctions
$auction_count = $conn->query("SELECT COUNT(*) total FROM auction_items")->fetch_assoc()['total'];
$active_auctions = $conn->query("SELECT COUNT(*) total FROM auction_items WHERE status='active'")->fetch_assoc()['total'];
$upcoming_auctions = $conn->query("SELECT COUNT(*) total FROM auction_items WHERE status='upcoming'")->fetch_assoc()['total'];
$closed_auctions = $conn->query("SELECT COUNT(*) total FROM auction_items WHERE status IN ('closed','sold')")->fetch_assoc()['total'];

/* ================= RECENT RECORDS ================= */
$sql = "SELECT r.id, r.title, r.description, u.username
        FROM records r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.id DESC
        LIMIT 10";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<link rel="stylesheet" href="../assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background:#f4f6f8; font-family:Arial,sans-serif; }

/* ===== DASHBOARD ===== */
.main-content { margin-left:240px; padding:30px; }

/* ===== STATS ===== */
.stats {
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:20px;
}

.card-link { text-decoration:none; }

.card {
  background:#fff;
  padding:22px;
  border-radius:14px;
  text-align:center;
  box-shadow:0 4px 8px rgba(0,0,0,.1);
  transition:.3s;
  cursor:pointer;
}

.card:hover {
  transform:translateY(-6px);
  box-shadow:0 12px 22px rgba(0,0,0,.18);
}

.card h3 { font-size:28px; margin:0; color:#2c3e50; }
.card p { margin-top:6px; color:#7f8c8d; }

/* Colors */
.success { border-left:6px solid #27ae60; }
.warning { border-left:6px solid #f39c12; }
.danger  { border-left:6px solid #e74c3c; }
.info    { border-left:6px solid #3498db; }
.dark    { border-left:6px solid #2c3e50; }

/* ===== TABLE ===== */
table {
  width:100%;
  border-collapse:collapse;
  margin-top:35px;
  background:#fff;
  border-radius:12px;
  overflow:hidden;
}

th,td {
  padding:14px;
  border-bottom:1px solid #eee;
}

th {
  background:#3498db;
  color:#fff;
  text-align:left;
}

.btn-edit {
  background:#27ae60;
  color:#fff;
  padding:6px 10px;
  border-radius:6px;
  text-decoration:none;
}

.btn-delete {
  background:#e74c3c;
  color:#fff;
  padding:6px 10px;
  border-radius:6px;
  text-decoration:none;
}

/* Chart */
#auctionChart {
  max-width:380px;
  margin:40px auto;
}
</style>
</head>

<body>

<!-- ===== SIDEBAR (UNCHANGED STRUCTURE) ===== -->
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

<!-- ===== MAIN ===== -->
<div class="main-content">

<h2>Admin Dashboard</h2>

<div class="stats">

<a href="manage_users.php" class="card-link">
<div class="card">
<h3><?= $user_count ?></h3><p>Total Users</p>
</div></a>

<a href="manage_users.php?status=active" class="card-link">
<div class="card success">
<h3><?= $active_users ?></h3><p>Active Users</p>
</div></a>

<a href="manage_users.php?status=suspended" class="card-link">
<div class="card warning">
<h3><?= $suspended_users ?></h3><p>Suspended Users</p>
</div></a>

<a href="manage_users.php?status=banned" class="card-link">
<div class="card danger">
<h3><?= $banned_users ?></h3><p>Banned Users</p>
</div></a>

<a href="manage_auctions.php" class="card-link">
<div class="card info">
<h3><?= $auction_count ?></h3><p>Total Auctions</p>
</div></a>

<a href="auctions_active.php" class="card-link">
<div class="card success">
<h3><?= $active_auctions ?></h3><p>Active Auctions</p>
</div></a>

<a href="auctions_upcoming.php" class="card-link">
<div class="card warning">
<h3><?= $upcoming_auctions ?></h3><p>Upcoming Auctions</p>
</div></a>

<a href="auction_overview.php" class="card-link">
<div class="card dark">
<h3><?= $closed_auctions ?></h3><p>Closed / Sold</p>
</div></a>

</div>

<canvas id="auctionChart"></canvas>

<h2>Recent Records</h2>

<table>
<tr>
<th>ID</th><th>Title</th><th>Description</th><th>User</th><th>Action</th>
</tr>

<?php while($row=$result->fetch_assoc()){ ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= htmlspecialchars($row['description']) ?></td>
<td><?= htmlspecialchars($row['username']) ?></td>
<td>
<a href="edit_record.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
<a href="delete_record.php?id=<?= $row['id'] ?>" class="btn-delete">Delete</a>
</td>
</tr>
<?php } ?>
</table>

</div>
<script src="../assets/script.js"></script>
<script>
new Chart(document.getElementById('auctionChart'), {
  type:'doughnut',
  data:{
    labels:['Active','Upcoming','Closed'],
    datasets:[{
      data:[<?= $active_auctions ?>,<?= $upcoming_auctions ?>,<?= $closed_auctions ?>],
      backgroundColor:['#27ae60','#f39c12','#e74c3c']
    }]
  },
  options:{ plugins:{ legend:{ position:'bottom' } } }
});

function toggleDropdown(id){
  document.getElementById(id).classList.toggle("show");
}
</script>

</body>
</html>
