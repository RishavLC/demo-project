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

/* ===== Auction Created ===== */
$q_created = $conn->prepare("
    SELECT COUNT(*) AS total_created
    FROM auction_items
    WHERE seller_id = ?
");
$q_created->bind_param("i", $user_id);
$q_created->execute();
$created = $q_created->get_result()->fetch_assoc()['total_created'];

/* ===== Auctions Participated ===== */
$q_participated = $conn->prepare("
    SELECT COUNT(DISTINCT item_id) AS total_participated
    FROM bids
    WHERE bidder_id = ?
");

if (!$q_participated) {
    die("Participated query error: " . $conn->error);
}

$q_participated->bind_param("i", $user_id);
$q_participated->execute();

$participated = $q_participated
    ->get_result()
    ->fetch_assoc()['total_participated'];

/* ===== Auctions Won ===== */
$q_won = $conn->prepare("
    SELECT COUNT(*) AS total_won
    FROM auction_items
    WHERE winner_id = ?
");

$q_won->bind_param("i", $user_id);
$q_won->execute();
$won = $q_won->get_result()->fetch_assoc()['total_won'];

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
.stat-card {
  flex: 1;
  padding: 25px;
  border-radius: 12px;
  color: #fff;
  text-align: center;
  text-decoration: none;
  box-shadow: 0 8px 20px rgba(0,0,0,.15);
  transition: transform .2s ease, box-shadow .2s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0,0,0,.25);
}

.green { background:#2ecc71; }
.orange { background:#f39c12; }
.red { background:#e74c3c; }

.stat-card h2 {
  font-size: 36px;
  margin: 0;
}

.stat-card p {
  margin-top: 8px;
  font-size: 15px;
  opacity: .95;
}


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

.user-card {
    position: relative;
}

.status-corner {
    position: absolute;
    top: 20px;
    right: 20px;
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
        <div class="status-corner">
            <?= statusBadge($user) ?>
        </div>


    </div>
</div>
<h3>📊 Auction Activity Summary</h3>

<div style="display:flex; gap:20px; margin:20px 0;">

  <a href="user_auctions_created.php?user_id=<?= $user_id ?>" class="stat-card green">
    <h2><?= $created ?></h2>
    <p>Auctions Created</p>
  </a>

  <a href="user_auctions_participated.php?user_id=<?= $user_id ?>" class="stat-card orange">
    <h2><?= $participated ?></h2>
    <p>Participated</p>
  </a>

  <a href="user_auctions_won.php?user_id=<?= $user_id ?>" class="stat-card red">
    <h2><?= $won ?></h2>
    <p>Auctions Won</p>
  </a>

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
<form method="POST" id="adminActionForm">
<h3>🛠 Admin Controls</h3>

<div class="actions">

<?php if ($user['status'] === 'active'): ?>
    <button type="button" class="warn"
        onclick="confirmAction('suspend_7','Do you want to Suspend user for 7 days?')">
        Suspend 7 Days
    </button>

    <button type="button" class="warn"
        onclick="confirmAction('suspend_15','Are you sure want to Suspend user for 15 days?')">
        Suspend 15 Days
    </button>

    <button type="button" class="danger"
        onclick="confirmAction('ban','Are you sure you want to BAN this user permanently?')">
        Ban User
    </button>

<?php elseif ($user['status'] === 'suspended'): ?>
    <button type="button" class="safe"
        onclick="confirmAction('unsuspend',' Do you want to Unsuspend this user?')">
        Unrestrict User
    </button>

    <button type="button" class="danger"
        onclick="confirmAction('ban','Are you sure want to Ban this user permanently?')">
        Ban Permanently
    </button>

<?php elseif ($user['status'] === 'banned'): ?>
    <button type="button" class="safe"
        onclick="confirmAction('unban','Do you want to Unban this user?')">
        Unban User
    </button>
<?php endif; ?>

</div>

<!-- Hidden input MUST be inside the form -->
<input type="hidden" name="action" id="actionInput">

</form>


<br>
<a class="btn" href="manage_users.php">⬅ Back</a>
</div>
</div>
<!-- CONFIRM MODAL -->
<div id="confirmModal" style="
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,.6);
    justify-content:center;
    align-items:center;
    z-index:9999;
">
  <div style="
      background:#fff;
      padding:25px;
      border-radius:12px;
      width:380px;
      text-align:center;
      box-shadow:0 15px 40px rgba(0,0,0,.3);
  ">
    <!-- <h3>confirm</h3> -->
    <p id="confirmText" style="margin:15px 0;color:#555;"></p>

    <div style="display:flex;gap:15px;justify-content:center;">
      <button class="safe" onclick="submitConfirmed()">Confirm</button>
      <button class="danger" onclick="closeModal()">Cancel</button>
    </div>
  </div>
</div>

<script src="../assets/script.js"></script>
<script>
    function toggleDropdown(id) {
        const menu = document.getElementById(id);
          menu.classList.toggle("show");
}

let selectedAction = null;

function confirmAction(action, message) {
    selectedAction = action;
    document.getElementById("confirmText").innerText = message;
    document.getElementById("confirmModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("confirmModal").style.display = "none";
    selectedAction = null;
}

function submitConfirmed() {
    if (!selectedAction) return;

    document.getElementById("actionInput").value = selectedAction;
    document.getElementById("adminActionForm").submit();
}

</script>

</body>
</html>
