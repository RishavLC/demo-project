<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "../common/config.php";

$role = ucfirst($_SESSION["role"]);

$search = isset($_GET['search']) ? $_GET['search'] : "";
$status_filter = isset($_GET['status']) ? $_GET['status'] : "";

$allowed_sort = ['id','username','role_name','status','created_at','updated_at'];
$allowed_order = ['ASC','DESC'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'],$allowed_sort) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']),$allowed_order) ? strtoupper($_GET['order']) : 'ASC';

$searchTerm = "%$search%";
$conditions = "(users.username LIKE ? OR roles.role_name LIKE ?)";
$params = ["ss", $searchTerm, $searchTerm];

$error_message = "";

// ✅ Handle date filters
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    if ($_GET['end_date'] < $_GET['start_date']) {
        $error_message = "⚠ Invalid date range: End date cannot be earlier than Start date.";
    } else {
        $conditions .= " AND DATE(users.created_at) BETWEEN ? AND ?";
        $params[0] .= "ss";
        $params[] = $_GET['start_date'];
        $params[] = $_GET['end_date'];
    }
} elseif (!empty($_GET['start_date'])) {
    $conditions .= " AND DATE(users.created_at) >= ?";
    $params[0] .= "s";
    $params[] = $_GET['start_date'];
} elseif (!empty($_GET['end_date'])) {
    $conditions .= " AND DATE(users.created_at) <= ?";
    $params[0] .= "s";
    $params[] = $_GET['end_date'];
}

// ✅ Handle status filter
if (!empty($status_filter) && in_array($status_filter, ['active','suspended','banned'])) {
    $conditions .= " AND users.status = ?";
    $params[0] .= "s";
    $params[] = $status_filter;
}
/* ---------------- PAGINATION ---------------- */
$limit = 8;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) AS total FROM auction_items");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$allowed_sort = ['id','username','role_name','status','created_at','updated_at'];
$allowed_order = ['ASC','DESC'];

$sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'id';
$order = in_array(strtoupper($_GET['order'] ?? ''), $allowed_order) ? strtoupper($_GET['order']) : 'ASC';


$sql = "SELECT users.id, users.username, roles.role_name, users.status, users.created_at, users.updated_at 
        FROM users 
        LEFT JOIN roles ON users.role_id = roles.id
        WHERE $conditions 
        ORDER BY $sort $order LIMIT $limit OFFSET $offset";

if (empty($error_message)) {
    $stmt = $conn->prepare($sql);
    if(!$stmt) die("Prepare failed: ".$conn->error);

    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Users - <?= $role ?></title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .pagination a {
    padding: 6px 10px;
    margin: 2px;
    border: 1px solid #ccc;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
}
.active-page {
    background: #4a90e2;
    color: white !important;
}
th a{
    text-decoration: none; color: white;
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
 <div class="page-header">
    <h2>Manage Users</h2>
<form method="GET" action="manage_users.php" id="searchForm" class="search-form">
    <input type="text" name="search" placeholder="Search user..." value="<?= htmlspecialchars($search) ?>">
    
    <label for="status">Status:</label>
    <select name="status" id="status">
        <option value="">All</option>
        <option value="active" <?= $status_filter=='active'?'selected':'' ?>>Active</option>
        <option value="suspended" <?= $status_filter=='suspended'?'selected':'' ?>>Suspended</option>
        <option value="banned" <?= $status_filter=='banned'?'selected':'' ?>>Banned</option>
    </select>

    <label for="start_date">From:</label>
    <input type="date" id="start_date" name="start_date" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">

    <label for="end_date">To:</label>
    <input type="date" id="end_date" name="end_date" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">

    <button type="submit" style="background-color:red;">🔍 Filter</button>
</form>
</div>

<?php if (!empty($error_message)): ?>
    <p style="color:red; font-weight:bold;"><?= $error_message ?></p>
<?php endif; ?>

<table>
  <tr>
    <th><a href="?search=<?= $search ?>&status=<?= $status_filter ?>&sort=id&order=<?= $order=='ASC'?'desc':'asc' ?>">ID</a></th>
    <th><a href="?search=<?= $search ?>&status=<?= $status_filter ?>&sort=username&order=<?= $order=='ASC'?'desc':'asc' ?>">Username</a></th>
    <th><a href="?search=<?= $search ?>&status=<?= $status_filter ?>&sort=role_name&order=<?= $order=='ASC'?'desc':'asc' ?>">Role</a></th>
    <th><a href="?search=<?= $search ?>&status=<?= $status_filter ?>&sort=status&order=<?= $order=='ASC'?'desc':'asc' ?>">Status</a></th>
    <th><a href="?search=<?= $search ?>&status=<?= $status_filter ?>&sort=created_at&order=<?= $order=='ASC'?'desc':'asc' ?>">Created At</a></th>
    <th><a href="?search=<?= $search ?>&status=<?= $status_filter ?>&sort=updated_at&order=<?= $order=='ASC'?'desc':'asc' ?>">Updated At</a></th>
    <th>Action</th>
  </tr>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= $row['role_name'] ? ucfirst($row['role_name']) : '-' ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['created_at'] ?></td>
            <td><?= $row['updated_at'] ? $row['updated_at'] : '-' ?></td>
            <td>
                  <a href="view_user.php?id=<?= $row['id'] ?>" class="btn btn-view">
                        👁 View
                  </a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="7">No users found.</td></tr>
<?php endif; ?>
</table>
<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active-page' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
</div>
<script src="../assets/script.js"></script>
<script>
     function toggleDropdown(id) {
  const menu = document.getElementById(id);
          menu.classList.toggle("show");
}
</script>
</body>
</html>
