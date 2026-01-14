<?php
session_start();
include "../common/config.php";

/* ================= ADMIN CHECK ================= */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

/* ================= USER ID CHECK ================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = (int)$_GET['id'];

/* ================= FETCH USER ================= */
$stmt = $conn->prepare("
    SELECT users.*, roles.role_name 
    FROM users 
    LEFT JOIN roles ON users.role_id = roles.id
    WHERE users.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<h3>User not found</h3>";
    exit();
}
$user = $result->fetch_assoc();

/* ================= HANDLE ACTIONS ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'];

    switch ($action) {
        case 'suspend_7':
            $stmt = $conn->prepare("
                UPDATE users 
                SET status='suspended', suspended_at=DATE_ADD(NOW(), INTERVAL 7 DAY) 
                WHERE id=?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;

        case 'suspend_15':
            $stmt = $conn->prepare("
                UPDATE users 
                SET status='suspended', suspended_at=DATE_ADD(NOW(), INTERVAL 15 DAY) 
                WHERE id=?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;

        case 'unsuspend':
            $stmt = $conn->prepare("
                UPDATE users 
                SET status='active', suspended_at=NULL 
                WHERE id=?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;

        case 'ban':
            $stmt = $conn->prepare("
                UPDATE users 
                SET status='banned', suspended_at=NULL 
                WHERE id=?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;

        case 'unban':
            $stmt = $conn->prepare("
                UPDATE users 
                SET status='active' 
                WHERE id=?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
    }

    header("Location: view_user.php?id=".$user_id);
    exit();
}

/* ================= STATUS BADGE ================= */
function statusBadge($user) {
    if ($user['status'] === 'banned') {
        return "<span class='badge red'>BANNED</span>";
    }

    if ($user['status'] === 'suspended') {
        return "<span class='badge orange'>SUSPENDED<br>Until: {$user['suspended_at']}</span>";
    }

    return "<span class='badge green'>ACTIVE</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>User Management</title>
  <link rel="stylesheet" href="../assets/style.css">
<style>
body { background:#f4f6f9; font-family:Arial; }
/* Sidebar Header */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background: #2c3e50;
  color: #fff;
}

/* Logo wrapper */
.logo-box {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Logo image */
.logo-box img {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 6px;
}

/* Logo text */
.logo-text {
  font-size: 18px;
  font-weight: 600;
  white-space: nowrap;
}

/* Toggle button */
/* .toggle-btn {
  cursor: pointer;
  font-size: 20px;
} */

/* ================= COLLAPSED SIDEBAR ================= */

.sidebar.collapsed .logo-text {
  display: none;
}

.sidebar.collapsed .logo-box {
  justify-content: center;
  width: 100%;
}

.sidebar.collapsed .sidebar-header {
  justify-content: center;
}

.sidebar.collapsed .toggle-btn {
  position: absolute;
  bottom: 15px;
  left: 50%;
  transform: translateX(-50%);
}



.user-card {
    max-width: 950px;
    margin: 30px auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.profile {
    display: flex;
    gap: 25px;
    align-items: center;
}
.profile img {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #ddd;
}
.badge {
    display:inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    color:#fff;
    margin-top:6px;
}
.green { background:#2ecc71; }
.orange { background:#f39c12; }
.red { background:#e74c3c; }

table {
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
td {
    padding:10px;
    border-bottom:1px solid #eee;
}

.actions button {
    padding:10px 15px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    margin:6px;
}
.safe { background:#2ecc71;color:#fff; }
.warn { background:#f39c12;color:#fff; }
.danger { background:#e74c3c;color:#fff; }

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
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="auction_history.php">📜 Auction History</a></li>
    <li><a href="../auth/logout.php">🚪 Logout</a></li>
  </ul>
</div>
<div class="main-content">
<div class="user-card">

<h2>User's Profile</h2><br>

<div class="profile">
    <?php
    $userImage = (!empty($user['photo']) && file_exists("../uploads/assets".$user['photo']))
    ? "../uploads/".$user['photo']
    : "../assets/default-user.png";
    ?>
    
<img src="<?= $userImage ?>" class="user-photo">
    <div>
        <h3><?= htmlspecialchars($user['username']) ?></h3>
        <p><b>Role:</b> <?= ucfirst($user['role_name']) ?></p>
        <p><?= statusBadge($user) ?></p>

    </div>
</div>

<table>
<tr><td><b>User Name</b></td><td><?= $user['username'] ?? '-' ?></td></tr>
<tr><td><b>Email</b></td><td><?= $user['email'] ?></td></tr>
<tr><td><b>Phone</b></td><td><?= $user['phone'] ?? '-' ?></td></tr>
<tr><td><b>Address</b></td><td><?= htmlspecialchars($user['address'] ?? '-') ?></td></tr>
<tr><td><b>Citizenship No</b></td><td><?= htmlspecialchars($user['citizenship_no'] ?? '-') ?></td></tr>
<tr><td><b>National ID No</b></td><td><?= htmlspecialchars($user['nic_no'] ?? '-') ?></td></tr>
<tr><td><b>Created At</b></td><td><?= $user['created_at'] ?></td></tr>
<tr><td><b>Updated At</b></td><td><?= $user['updated_at'] ?: '-' ?></td></tr>
<tr><td><b>Suspended Until</b></td><td><?= $user['suspended_at'] ?: '-' ?></td></tr>
</table>

<hr>

<form method="POST">
<h3>🛠 Admin Controls</h3>
<div class="actions">

<?php if ($user['status'] === 'active'): ?>
    <!-- Active user -->
    <button class="warn" name="action" value="suspend_7">Suspend 7 Days</button>
    <button class="warn" name="action" value="suspend_15">Suspend 15 Days</button>
    <button class="danger" name="action" value="ban">Ban User</button>

<?php elseif ($user['status'] === 'suspended'): ?>
    <!-- Suspended user -->
    <button class="safe" name="action" value="unsuspend">Unsuspend User</button>
    <button class="danger" name="action" value="ban">Ban Permanently</button>

<?php elseif ($user['status'] === 'banned'): ?>
    <!-- Banned user -->
    <button class="safe" name="action" value="unban">Unban User</button>

<?php endif; ?>

</div>

</form>

<br>
<a href="manage_users.php">⬅ Back</a>
</div>
</div>
<script src="../assets/script.js"></script>
</body>
</html>
