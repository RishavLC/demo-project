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

$sql = "SELECT id, username, role FROM users 
        WHERE username LIKE ? OR role LIKE ? 
        ORDER BY $sort $order";
$stmt = $conn->prepare($sql);
if(!$stmt) die("Prepare failed: ".$conn->error);
$searchTerm = "%$search%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Users - <?= $role ?></title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    /* Nice search bar card */
    /* ===== Search Bar Card ===== */
.search-container {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 15px;
}

.search-box {
  display: flex;
  align-items: center;
  width: 300px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  overflow: hidden;
}

.search-box input {
  flex: 1;
  border: none;
  padding: 8px 12px;
  font-size: 14px;
  outline: none;
}

.search-box button {
  border: none;
  background: #4a90e2;
  color: #fff;
  padding: 8px 14px;
  cursor: pointer;
  font-weight: 500;
  transition: 0.3s;
}

.search-box button:hover {
  background: #357abd;
}

/* ===== Users Table ===== */
table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

table th, table td {
  padding: 12px 15px;
  text-align: left;
}

table th {
  background: #4a90e2;
  color: #fff;
  font-weight: 500;
  cursor: pointer;
  transition: 0.2s;
}

table th:hover {
  background: #357abd;
}

table tr:nth-child(even) {
  background: #f9f9f9;
}

table tr:hover {
  background: #eef5ff;
}

/* ===== Action Buttons ===== */
.btn {
  padding: 5px 10px;
  border-radius: 5px;
  font-size: 13px;
  font-weight: 500;
  transition: 0.3s;
}

.btn-edit { background: #ffc107; color: black; }
.btn-edit:hover { background: #e0a800; }

.btn-delete { background: #dc3545; color: white; }
.btn-delete:hover { background: #c82333; }

/* ===== Main Content ===== */
.main-content {
  margin-left: 220px;
  padding: 20px 30px;
  transition: 0.3s;
}

  </style>
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
    <li><a href="logout.php">🚪 Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h2>Manage Users</h2>

  <!-- Search bar -->
  <form method="get" class="search-card">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by username or role">
    <button type="submit">🔍</button>
  </form>

  <table>
    <tr>
      <th><a href="?search=<?= $search ?>&sort=id&order=<?= $order=='ASC'?'desc':'asc' ?>">ID</a></th>
      <th><a href="?search=<?= $search ?>&sort=username&order=<?= $order=='ASC'?'desc':'asc' ?>">Username</a></th>
      <th><a href="?search=<?= $search ?>&sort=role&order=<?= $order=='ASC'?'desc':'asc' ?>">Role</a></th>
      <th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()){ ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= ucfirst($row['role']) ?></td>
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
