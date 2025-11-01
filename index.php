<?php
include "config.php"; // Your DB connection
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM items WHERE status='active'";
if ($category != '') {
    $sql .= " AND category LIKE '%" . $conn->real_escape_string($category) . "%'";
}
if ($search != '') {
    $sql .= " AND name LIKE '%" . $conn->real_escape_string($search) . "%'";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BlueBid Auction</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #f4f7fc;
    }
    header {
      background-color: #003366;
      color: #fff;
      padding: 15px 50px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: 1px;
    }
    nav {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    nav a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      padding: 8px 12px;
      transition: 0.3s;
    }
    nav a:hover {
      background: #0056b3;
      border-radius: 6px;
    }
    .search-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border-radius: 8px;
      padding: 5px 10px;
    }
    .search-bar input, .search-bar select {
      border: none;
      outline: none;
      padding: 6px;
      font-size: 14px;
    }
    .search-bar button {
      background: #007bff;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 5px;
      cursor: pointer;
    }
    .search-bar button:hover {
      background: #0056b3;
    }

    /* Hero Section */
    .hero {
      background: linear-gradient(rgba(0,0,50,0.5), rgba(0,0,50,0.5)), url('images/banner.jpg') center/cover no-repeat;
      color: white;
      text-align: center;
      padding: 140px 20px;
    }
    .hero h1 {
      font-size: 50px;
      font-weight: 700;
    }
    .hero p {
      font-size: 18px;
      margin: 10px 0 20px;
    }
    .hero button {
      padding: 10px 20px;
      background: #007bff;
      border: none;
      border-radius: 6px;
      color: white;
      font-weight: 500;
      cursor: pointer;
    }
    .hero button:hover {
      background: #0056b3;
    }

    /* Active Auctions */
    .auction-section {
      text-align: center;
      padding: 50px 20px;
    }
    .auction-section h2 {
      color: #003366;
      font-size: 32px;
      margin-bottom: 30px;
    }
    .auction-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      padding: 0 50px;
    }
    .auction-card {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }
    .auction-card:hover {
      transform: translateY(-5px);
    }
    .auction-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    .auction-card h3 {
      margin: 10px 0;
      font-size: 18px;
      color: #003366;
    }
    .auction-card p {
      font-size: 14px;
      color: #666;
      padding: 0 10px;
    }
    .auction-card a {
      display: inline-block;
      margin: 10px 0 15px;
      padding: 8px 15px;
      background: #007bff;
      color: white;
      border-radius: 6px;
      text-decoration: none;
    }
    .auction-card a:hover {
      background: #0056b3;
    }

    footer {
      background: #003366;
      color: #fff;
      text-align: center;
      padding: 15px 0;
      margin-top: 50px;
    }
  </style>
</head>
<body>

<header>
  <div class="logo">BlueBid</div>
  <form class="search-bar" method="GET">
    <select name="category">
      <option value="">All Categories</option>
      <option value="electronics">Electronics</option>
      <option value="furniture">Furniture</option>
      <option value="fashion">Fashion</option>
      <option value="art">Art</option>
      <option value="vehicles">Vehicles</option>
    </select>
    <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
  </form>
  <nav>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </nav>
</header>

<section class="hero">
  <h1>Welcome to BlueBid</h1>
  <p>Bid smart. Win big. Trusted online auction platform.</p>
  <button onclick="window.location='#auctions'">View Active Auctions</button>
</section>

<section class="auction-section" id="auctions">
  <h2>Active Auctions</h2>
  <div class="auction-grid">
    <?php if ($result->num_rows > 0) { 
      while($row = $result->fetch_assoc()) { ?>
      <div class="auction-card">
        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Item Image">
        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
        <p>Starting Price: $<?php echo number_format($row['starting_price'], 2); ?></p>
        <a href="login.php">Bid Now</a>
      </div>
    <?php } } else { ?>
      <p>No active auctions found.</p>
    <?php } ?>
  </div>
</section>

<footer>
  © <?php echo date("Y"); ?> BlueBid Auction | All Rights Reserved
</footer>

</body>
</html>
