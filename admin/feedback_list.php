<?php
session_start();
include "../common/config.php";

/* ================= ADMIN AUTH ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* ================= FETCH FEEDBACK ================= */
$sql = "
SELECT 
    f.id,
    f.message,
    f.created_at,
    f.status,
    u.username AS sender_name,
    a.title AS item_title
FROM auction_feedback f
JOIN users u ON u.id = f.sender_id
JOIN auction_items a ON a.id = f.item_id
ORDER BY f.created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>User Feedback</title>
<style>
table {
    width:100%;
    border-collapse:collapse;
}
th, td {
    padding:10px;
    border:1px solid #ccc;
}
th {
    background:#2c3e50;
    color:white;
}
.open { color:red; font-weight:bold; }
.reviewed { color:green; }
</style>
</head>
<body>

<h2>User Feedback / Reports</h2>

<table>
<tr>
    <th>ID</th>
    <th>Item</th>
    <th>Sender</th>
    <th>Message</th>
    <th>Status</th>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['item_title']) ?></td>
    <td><?= htmlspecialchars($row['sender_name']) ?></td>
    <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
    <td class="<?= $row['status'] ?>">
        <?= ucfirst($row['status']) ?>
    </td>
    <td><?= $row['created_at'] ?></td>
    <td>
        <?php if ($row['status'] === 'open'): ?>
            <a href="mark_reviewed.php?id=<?= $row['id'] ?>">Mark Reviewed</a>
        <?php else: ?>
                <a href="feedback_view.php?id=<?= $row['id'] ?>">View & Reply</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
