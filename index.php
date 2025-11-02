<?php
include "config.php"; // Your DB connection
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM auction_items WHERE status='active'";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

    /* footer {
      background: #003366;
      color: #fff;
      text-align: center;
      padding: 15px 0;
      margin-top: 50px;
    } */
    /* ================== FOOTER STYLES ================== */
.site-footer {
  background: #0b3d91; /* Deep blue theme */
  color: #fff;
  padding: 50px 20px 10px;
  font-family: 'Segoe UI', sans-serif;
}

.footer-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.footer-col h3 {
  color: #fff;
  font-size: 20px;
  margin-bottom: 15px;
  border-bottom: 2px solid #1e90ff;
  display: inline-block;
  padding-bottom: 5px;
}

.footer-col p,
.footer-col li,
.footer-col a {
  color: #dcdcdc;
  font-size: 14px;
  line-height: 1.7;
  text-decoration: none;
}

.footer-col ul {
  list-style: none;
  padding: 0;
}

.footer-col ul li {
  margin-bottom: 10px;
}

.footer-col a:hover {
  color: #1e90ff;
}

/* Social Icons */
.social-links a {
  color: white;
  margin-right: 10px;
  font-size: 18px;
  transition: 0.3s;
}

.social-links a:hover {
  color: #1e90ff;
}

/* Newsletter */
.newsletter-form {
  display: flex;
  flex-direction: column;
}

.newsletter-form input {
  padding: 10px;
  border-radius: 5px;
  border: none;
  margin-bottom: 10px;
  outline: none;
}

.newsletter-form button {
  padding: 10px;
  border: none;
  border-radius: 5px;
  background: #1e90ff;
  color: white;
  cursor: pointer;
  transition: 0.3s;
}

.newsletter-form button:hover {
  background: #0077cc;
}

/* Bottom Footer */
.footer-bottom {
  text-align: center;
  margin-top: 40px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  padding-top: 15px;
  font-size: 13px;
  color: #bbb;
}

  </style>
</head>
<body>

<header>
  <div class="logo">EasyBid</div>
  <nav>
    <a href="#">Home</a>
    <a href="aboutus.html">About Us</a>
    <a href="#auctions">Auctions</a>
    <a href="#">Contact Us</a>
  </nav>
  <form class="search-bar" method="GET">
    <select name="category">
      <option value="">All Categories</option>
      <option value="Electronics">Electronics</option>
      <option value="Furnitures">Furniture</option>
      <option value="Vehicles">Vehicles</option>
    </select>
    <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
  </form>
  <nav>
    <a href="login.php">Login</a>
  </nav>
</header>

<section class="hero">
  <h1>Welcome to EasyBid</h1>
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
        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
        <p>Starting Price: $<?php echo number_format($row['start_price'], 2); ?></p>
        <a href="login.php">Bid Now</a>
      </div>
    <?php } } else { ?>
      <p>No active auctions found.</p>
    <?php } ?>
  </div>
</section>

  <!-- ================== FOOTER SECTION ================== -->
<footer class="site-footer">
  <div class="footer-container">

    <!-- About Us -->
    <div class="footer-col">
      <h3>About AuctionEase</h3>
      <p>
        AuctionEase is a secure and user-friendly online auction platform 
        where buyers and sellers connect easily. We believe in transparency, 
        fair bidding, and great deals every day.
      </p>
    </div>

    <!-- Quick Links -->
    <div class="footer-col">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="aboutus.php">About Us</a></li>
        <li><a href="contactus.php">Contact</a></li>
        <li><a href="login.php">Login</a></li>
      </ul>
    </div>

    <!-- Contact Info -->
    <div class="footer-col">
      <h3>Contact Us</h3>
      <p><i class="fas fa-map-marker-alt"></i> Kathmandu, Nepal</p>
      <p><i class="fas fa-phone"></i> +977-9812345678</p>
      <p><i class="fas fa-envelope"></i> support@easybid.com</p>

      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
      </div>
    </div>

    <!-- Newsletter -->
    <div class="footer-col">
      <h3>Stay Updated</h3>
      <p>Subscribe to our newsletter to get the latest auction alerts.</p>
      <form class="newsletter-form">
        <input type="email" placeholder="Enter your email" required>
        <button type="submit">Subscribe</button>
      </form>
    </div>

  </div>

  <div class="footer-bottom">
    <p>© <?php echo date('Y'); ?> Easybid. All Rights Reserved</p>
  </div>

</footer>

</body>
</html>
