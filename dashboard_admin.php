<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

// Count Users
$user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

// Count Auctions
$auction_count = $conn->query("SELECT COUNT(*) AS total FROM auction_items")->fetch_assoc()['total'];

// Active Auctions
$active_auctions = $conn->query("SELECT COUNT(*) AS total FROM auction_items WHERE status='active'")->fetch_assoc()['total'];

// Closed Auctions
$closed_auctions = $conn->query("SELECT COUNT(*) AS total FROM auction_items WHERE status='closed' OR status='sold'")->fetch_assoc()['total'];

// Total Bids
$total_bids = $conn->query("SELECT COUNT(*) AS total FROM bids")->fetch_assoc()['total'];

// Fetch all records (admin sees all)
$sql = "SELECT r.id, r.title, r.description, u.username 
        FROM records r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .card {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      text-align: center;
    }
    .card h3 {
      margin: 0;
      font-size: 24px;
      color: #2c3e50;
    }
    .card p {
      margin: 5px 0 0;
      color: #7f8c8d;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 25px;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }
    th {
      background: #3498db;
      color: white;
    }
    .btn-edit, .btn-delete {
      padding: 5px 10px;
      border-radius: 6px;
      text-decoration: none;
      color: white;
    }
    .btn-edit { background: #27ae60; }
    .btn-delete { background: #e74c3c; }
    canvas {
      margin-top: 40px;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-header">
    Admin Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="auction_history.php">📜 Auction History</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Admin Dashboard</h2>
  
  <div class="stats">
    <div class="card">
      <h3><?= $user_count ?></h3>
      <p>Total Users</p>
    </div>
    <div class="card">
      <h3><?= $auction_count ?></h3>
      <p>Total Auctions</p>
    </div>
    <div class="card">
      <h3><?= $active_auctions ?></h3>
      <p>Active Auctions</p>
    </div>
    <div class="card">
      <h3><?= $closed_auctions ?></h3>
      <p>Closed Auctions</p>
    </div>
    <div class="card">
      <h3><?= $total_bids ?></h3>
      <p>Total Bids</p>
    </div>
  </div>

  <canvas id="auctionChart" width="400" height="150"></canvas>

  <h2 style="margin-top:40px;">All Records</h2>
  <table>
    <tr>
      <th>ID</th><th>Title</th><th>Description</th><th>User</th><th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['title']) ?></td>
      <td><?= htmlspecialchars($row['description']) ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td>
        <a href="edit_record.php?id=<?= $row['id'] ?>" class="btn-edit">✏ Edit</a>
        <a href="delete_record.php?id=<?= $row['id'] ?>" class="btn-delete">🗑 Delete</a>
      </td>
    </tr>
    <?php } ?>
  </table>
</div>

<script src="assets/script.js"></script>
<script>
const ctx = document.getElementById('auctionChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Active Auctions', 'Closed Auctions'],
        datasets: [{
            data: [<?= $active_auctions ?>, <?= $closed_auctions ?>],
            backgroundColor: ['#27ae60', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
</body>
</html>
