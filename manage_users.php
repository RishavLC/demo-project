<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: index.php");
    exit();
}
include "config.php";

$role = ucfirst($_SESSION["role"]); 

$search = isset($_GET['search']) ? $_GET['search'] : "";
$allowed_sort = ['id','username','role'];
$allowed_order = ['ASC','DESC'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'],$allowed_sort) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']),$allowed_order) ? strtoupper($_GET['order']) : 'ASC';

$searchTerm = "%$search%";
$conditions = "(username LIKE ? OR role LIKE ?)";
$params = ["ss", $searchTerm, $searchTerm];

// handle dates
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $conditions .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[0] .= "ss";  // add 2 more strings
    $params[] = $_GET['start_date'];
    $params[] = $_GET['end_date'];
} elseif (!empty($_GET['start_date'])) {
    $conditions .= " AND DATE(created_at) >= ?";
    $params[0] .= "s";
    $params[] = $_GET['start_date'];
} elseif (!empty($_GET['end_date'])) {
    $conditions .= " AND DATE(created_at) <= ?";
    $params[0] .= "s";
    $params[] = $_GET['end_date'];
}

$sql = "SELECT id, username, role, created_at, updated_at 
        FROM users 
        WHERE $conditions 
        ORDER BY $sort $order";

$stmt = $conn->prepare($sql);
if(!$stmt) die("Prepare failed: ".$conn->error);

// use spread operator to bind dynamic params
$stmt->bind_param(...$params);
$stmt->execute();
$result = $stmt->get_result();

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
    <li><a href="add_record.php">➕ Add New Record</a></li>
    <li><a href="manage_users.php">👥 Manage Users</a></li>
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
 <div class="page-header">
    <h2>Manage Users</h2>
    <form method="GET" action="manage_users.php" class="search-form">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search user..."
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        
    <label for="start_date">From:</label>
    <input type="date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">

    <label for="end_date">To:</label>
    <input type="date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">

   
    </form>
</div>


<table>
  <tr>
    <th><a href="?search=<?= $search ?>&sort=id&order=<?= $order=='ASC'?'desc':'asc' ?>">ID</a></th>
    <th><a href="?search=<?= $search ?>&sort=username&order=<?= $order=='ASC'?'desc':'asc' ?>">Username</a></th>
    <th><a href="?search=<?= $search ?>&sort=role&order=<?= $order=='ASC'?'desc':'asc' ?>">Role</a></th>
    <th>Created At</th>
    <th>Updated At</th>
    <th>Action</th>
  </tr>

  <?php while($row = $result->fetch_assoc()){ ?>
  <tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= ucfirst($row['role']) ?></td>
    <td><?= $row['created_at'] ?></td>
    <td><?= $row['updated_at'] ? $row['updated_at'] : '-' ?></td>
    <td>
      <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-edit">✏ Edit</a>
      <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this user?');">🗑 Delete</a>
    </td>
  </tr>
  <?php } ?>
</table>


</div>
  <script src="assets/script.js"></script>

</body>
</html>
