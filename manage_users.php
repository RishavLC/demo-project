<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

$role = ucfirst($_SESSION["role"]); 

$search = isset($_GET['search']) ? $_GET['search'] : "";
$allowed_sort = ['id','username','role_name','created_at','updated_at'];
$allowed_order = ['ASC','DESC'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'],$allowed_sort) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']),$allowed_order) ? strtoupper($_GET['order']) : 'ASC';

$searchTerm = "%$search%";
$conditions = "(users.username LIKE ? OR roles.role_name LIKE ?)";
$params = ["ss", $searchTerm, $searchTerm];

$error_message = ""; // store error message for invalid dates

// ✅ Handle dates safely
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    if ($_GET['end_date'] < $_GET['start_date']) {
        // invalid range
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

$sql = "SELECT users.id, users.username, roles.role_name, users.created_at, users.updated_at 
        FROM users 
        LEFT JOIN roles ON users.role_id = roles.id
        WHERE $conditions 
        ORDER BY $sort $order";

// ✅ Only run query if no error
if (empty($error_message)) {
    $stmt = $conn->prepare($sql);
    if(!$stmt) die("Prepare failed: ".$conn->error);

    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false; // skip query
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Users - <?= $role ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="sidebar">
  <div class="sidebar-header">
    <?= $role ?> Panel
    <div class="toggle-btn">☰</div>
  </div>
  <ul>
    <li><a href="dashboard_admin.php">🏠 Dashboard</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="manage_auctions.php">📦 Manage Auctions</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
 <div class="page-header">
    <h2>Manage Users</h2>
<form method="GET" action="manage_users.php" id="searchForm" class="search-form">
    <input type="text" name="search" placeholder="Search user..."
           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
    
    <label for="start_date">From:</label>
    <input type="date" id="start_date" name="start_date" 
           value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">

    <label for="end_date">To:</label>
    <input type="date" id="end_date" name="end_date" 
           value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">

    <button type="submit" style="background-color:red;">🔍 Filter</button>
</form>
</div>

<table>
  <tr>
    <th style="background-color:4a90e2;"><a style="color: white; text-decoration: none;" href="?search=<?= $search ?>&sort=id&order=<?= $order=='ASC'?'desc':'asc' ?>">ID</a></th>
    <th style="background-color:4a90e2;"><a style="color: white; text-decoration: none;" href="?search=<?= $search ?>&sort=username&order=<?= $order=='ASC'?'desc':'asc' ?>">Username</a></th>
    <th style="background-color:4a90e2;"><a style="color: white; text-decoration: none;" href="?search=<?= $search ?>&sort=role_name&order=<?= $order=='ASC'?'desc':'asc' ?>">Role</a></th>
    <th style="background-color:4a90e2;"><a style="color: white; text-decoration: none;" href="?search=<?= $search ?>&sort=created_at&order=<?= $order=='ASC'?'desc':'asc' ?>">Created At</a></th>
    <th style="background-color:4a90e2;"><a style="color: white; text-decoration: none;" href="?search=<?= $search ?>&sort=updated_at&order=<?= $order=='ASC'?'desc':'asc' ?>">Updated At</a></th>
    <th>Action</th>
  </tr>
<?php if (!empty($error_message)): ?>
    <p style="color:red; font-weight:bold;"><?= $error_message ?></p>
<?php elseif ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= $row['role_name'] ? ucfirst($row['role_name']) : '-' ?></td>
    <td><?= $row['created_at'] ?></td>
    <td><?= $row['updated_at'] ? $row['updated_at'] : '-' ?></td>
    <td>
      <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-edit">✏ Edit</a>
      <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this user?');">🗑 Delete</a>
    </td>
  </tr>
    <?php endwhile; ?>
<?php else: ?>
    <p>No users found.</p>
 <?php endif; ?>
</table>

</div>
<script src="assets/script.js"></script>

</body>
</html>
