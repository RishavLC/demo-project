<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/index.php");
    exit();
}

include "../common/config.php";

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: my_added_items.php");
    exit();
}

$item_id = (int)$_GET['id'];

/* ================= FETCH ITEM ================= */
$stmt = $conn->prepare("
    SELECT * FROM auction_items 
    WHERE id = ? AND seller_id = ? AND status = 'pending'
");
if (!$stmt) die($conn->error);

$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: my_added_items.php");
    exit();
}

/* ================= FETCH IMAGES ================= */
$img_stmt = $conn->prepare("
    SELECT id, image_path FROM auction_images WHERE item_id = ?
");
$img_stmt->bind_param("i", $item_id);
$img_stmt->execute();
$images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================= UPDATE ITEM ================= */
$item_updated = false;

if (isset($_POST['update_item'])) {
    // ... your update logic ...
    $title         = $_POST['title'];
    $description   = $_POST['description'];
    $category      = $_POST['category'];
    $start_price   = (float)$_POST['start_price'];
    $min_increment = (float)$_POST['min_increment'];
    $buy_now_price = !empty($_POST['buy_now_price']) ? (float)$_POST['buy_now_price'] : null;
    $start_time    = $_POST['start_time'];
    $end_time      = $_POST['end_time'];

    /* ---------- UPDATE ITEM DATA ---------- */
    $up = $conn->prepare("
        UPDATE auction_items SET
            title=?,
            description=?,
            category=?,
            start_price=?,
            min_increment=?,
            buy_now_price=?,
            start_time=?,
            end_time=?
        WHERE id=? AND seller_id=? AND status='pending'
    ");
    if (!$up) die($conn->error);

    $up->bind_param(
        "sssddsssii",
        $title,
        $description,
        $category,
        $start_price,
        $min_increment,
        $buy_now_price,
        $start_time,
        $end_time,
        $item_id,
        $user_id
    );
    $up->execute();

    /* ---------- DELETE SELECTED IMAGES ---------- */
    if (!empty($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $img_id) {

            $img_id = (int)$img_id;

            $s = $conn->prepare("
                SELECT image_path FROM auction_images
                WHERE id=? AND item_id=?
            ");
            $s->bind_param("ii", $img_id, $item_id);
            $s->execute();
            $img = $s->get_result()->fetch_assoc();

            if ($img) {
                @unlink("../" . $img['image_path']);

                $d = $conn->prepare("
                    DELETE FROM auction_images WHERE id=? AND item_id=?
                ");
                $d->bind_param("ii", $img_id, $item_id);
                $d->execute();
            }
        }
    }

    /* ---------- ADD NEW IMAGES ---------- */
    if (!empty($_FILES['images']['name'][0])) {

        $upload_dir = "../uploads/auctions/";
        $db_dir = "uploads/auctions/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {

            if (!str_contains(mime_content_type($tmp), 'image')) continue;

            $file = time() . "_" . basename($_FILES['images']['name'][$key]);
            move_uploaded_file($tmp, $upload_dir . $file);

            $image_path = $db_dir . $file; // ✅ must use variable for bind_param

            $i = $conn->prepare("
                INSERT INTO auction_images (item_id, image_path)
                VALUES (?, ?)
            ");
            $i->bind_param("is", $item_id, $image_path);
            $i->execute();
        }
    }
    $item_updated = true; // ✅ flag for JS modal
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Auction Item</title>
<style>
body { background:#f3f4f6; font-family: Arial; }
.container {
    width: 800px;
    margin: 30px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
}
h2 { text-align:center; margin-bottom:20px; }
.grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.full { grid-column: span 2; }
label { font-weight:600; margin-bottom:4px; display:block; }
input, textarea, select {
    width:100%;
    padding:8px;
    border:1px solid #ccc;
    border-radius:6px;
}
button {
    width:100%;
    padding:10px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:6px;
    margin-top:15px;
    cursor:pointer;
}
img { border-radius:6px; border:1px solid #ddd; margin-bottom:5px; }
</style>
</head>

<body>

<div class="container">

<h2>Edit Auction Item (Pending)</h2>

<?php if (!empty($item['rejection_reason'])): ?>
<p style="color:red;"><b>Rejection Reason:</b>
<?= htmlspecialchars($item['rejection_reason']) ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="grid">

<div>
<label>Title</label>
<input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
</div>

<div>
<label>Category</label>
<select name="category">
                <option value=""><?= htmlspecialchars($item['category']) ?></option>
                <option value="Electronics">Electronics</option>
                <option value="Furniture">Furniture</option>
                <option value="Vehicles">Vehicles</option>
                <option value="Fashion">Fashion</option>
                <option value="Books">Books</option>
                <option value="MusicalInstruments">Musical Instruments</option>
                <option value="Antiques">Antiques</option>
                <option value="Art">Art & Collectibles</option>
                <option value="Sports">Sports</option>
                <option value="Real Estate">Real Estate</option>
                <option value="Others">Others</option>
            </select>
</div>

<div>
<label>Start Price</label>
<input type="number" step="0.01" name="start_price" value="<?= $item['start_price'] ?>" required>
</div>

<div>
<label>Min Increment</label>
<input type="number" step="0.01" name="min_increment" value="<?= $item['min_increment'] ?>" required>
</div>

<div>
<label>Start Time</label>
<input type="datetime-local" name="start_time"
value="<?= date('Y-m-d\TH:i', strtotime($item['start_time'])) ?>">
</div>

<div>
<label>End Time</label>
<input type="datetime-local" name="end_time"
value="<?= date('Y-m-d\TH:i', strtotime($item['end_time'])) ?>">
</div>

<div class="full">
<label>Description</label>
<textarea name="description"><?= htmlspecialchars($item['description']) ?></textarea>
</div>

<div class="full">
<label>Current Images</label>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
<?php foreach ($images as $img): ?>
<label style="text-align:center;">
<img src="../<?= $img['image_path'] ?>" width="90"><br>
<input type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>"> Remove
</label>
<?php endforeach; ?>
</div>
</div>

<div class="full">
<label>Add New Images</label>
<input type="file" name="images[]" multiple>
</div>

<button type="submit" name="update_item">Update Item</button>

</div>
</form>
</div>
<!-- Modal -->
<div id="updateModal" style="
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.5);
    justify-content:center; align-items:center;
">
  <div style="
      background:white;
      padding:25px;
      border-radius:8px;
      text-align:center;
      width:300px;
  ">
    <p style="margin-bottom:20px;">✅ Item updated successfully!</p>
    <button id="okBtn" style="
        padding:8px 15px;
        background:#2563eb;
        color:white;
        border:none;
        border-radius:5px;
        cursor:pointer;
    ">OK</button>
  </div>
</div>

<script>
<?php if($item_updated): ?>
    const modal = document.getElementById('updateModal');
    const okBtn = document.getElementById('okBtn');
    modal.style.display = 'flex';

    okBtn.onclick = () => {
        window.location.href = "my_added_items.php";
    }
<?php endif; ?>
</script>

</body>
</html>
