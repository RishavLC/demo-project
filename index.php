<?php
include "config.php"; 
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

//for register form
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = md5($_POST["password"]);

    // ✅ Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert error'>Invalid email format.</div>";
    } else {
        // ✅ Check if username or email already exists
        $check = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "<div class='alert error'>Username or Email already exists!</div>";
        } else {
            // ✅ Get role_id for 'user'
            $roleResult = $conn->query("SELECT id FROM roles WHERE role_name = 'user' LIMIT 1");
            $roleRow = $roleResult->fetch_assoc();
            $role_id = $roleRow['id'];

            // ✅ Insert new user
            $sql = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
            $sql->bind_param("sssi", $username, $email, $password, $role_id);

            if ($sql->execute()) {
                // ✅ Get created_at
                $user_id = $conn->insert_id;
                $result = $conn->query("SELECT created_at FROM users WHERE id = $user_id");
                $row = $result->fetch_assoc();
                $created_at = $row['created_at'];

                // ✅ Success message
                echo "<div style='
                        margin: 20px auto; 
                        width: 420px; 
                        padding: 15px; 
                        text-align: center; 
                        background: #e6ffed; 
                        border: 1px solid #28a745; 
                        border-radius: 8px; 
                        font-family: Arial, sans-serif; 
                        color: #155724;'>
                        🎉 Registration successful on <b>$created_at</b>! <br><br>
                        <a href='index.php' style='
                            display: inline-block; 
                            padding: 8px 15px; 
                            margin-top: 10px; 
                            background: #28a745; 
                            color: #fff; 
                            text-decoration: none; 
                            border-radius: 5px;'>Login here</a>
                      </div>";
                exit();
            } else {
                $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>EasyBid</title>
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
      position: sticky;/* relative for header to while scrolling */
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
  position: relative;
  width: 100%;
  height: 90vh; /* Full screen height */
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #fff;
  font-family: 'Poppins', sans-serif;
}

/* Background Image */
.hero img {
  position: absolute;
  width: 100%;
  height: 100%;
  object-fit: cover; /* Ensures full coverage without stretching */
  top: 0;
  left: 0;
  z-index: -2;
  filter: brightness(70%); /* Darkens image for better text visibility */
  transition: transform 8s ease-in-out;
}

/* Subtle zoom animation */
.hero:hover img {
  transform: scale(1.05);
}

/* Overlay effect */
.hero::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 64, 128, 0.35); /* soft blue overlay */
  z-index: -1;
}

/* Make hero-content stack vertically */
.hero-content {
  display: flex;
  flex-direction: column; /* stacks h1, p, button vertically */
  align-items: center;    /* center horizontally */
  justify-content: center;/* center vertically */
  z-index: 1;
}
/* Heading */
.hero h1 {
  font-size: 3rem;
  margin-bottom: 10px;
  font-weight: 700;
  text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
}

/* Paragraph */
.hero p {
  font-size: 1.2rem;
  margin-bottom: 25px;
  line-height: 1.6;
  color: #f0f8ff;
}

/* Button */
.hero button {
  background: #007bff;
  color: white;
  border: none;
  padding: 12px 28px;
  border-radius: 30px;
  font-size: 1rem;
  cursor: pointer;
  transition: 0.3s;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.hero button:hover {
  background: #0056b3;
  transform: scale(1.05);
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
  <div class="content">
  <img src="front.jpg" alt="">
  <h1>Welcome to EasyBid</h1>
  <p>Bid smart. Win big. Trusted online auction platform.</p>
  <button onclick="window.location='#auctions'">View Active Auctions</button>
  </div>
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

  <!--  FOOTER SECTION  -->
<footer class="site-footer">
  <div class="footer-container">

    <!-- About Us -->
    <div class="footer-col">
      <h3>About AuctionEase</h3>
      <p>
        EasyBid is a secure and user-friendly online auction platform 
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
      <form method="POST">
          <h2>Register</h2>
          <?= $message ?>
          <input type="text" name="username" placeholder="Username" required>
          <input type="email" name="email" placeholder="Email" required>
          <input type="password" name="password" placeholder="Password" required><br>
          <button type="submit">Register</button>
      </form>
    </div>

  </div>

  <div class="footer-bottom">
    <p>© <?php echo date('Y'); ?> Easybid. All Rights Reserved</p>
  </div>

</footer>

</body>
</html>
