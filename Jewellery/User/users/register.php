<?php
session_start();
require_once "../../config/config.php";

if (isset($_SESSION['user_id'])) {
    header("Location:../index.php");
    exit();
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirmPassword'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    // Validate
    if ($fullname === '' || $email === '' || $password === '' || $phone === '' || $address === '') {
        $error = "Please fill in all required fields!";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
        $error = "Invalid phone number (must start with 0 and have 10-11 digits)!";
    } else {
        // Check email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists!";
            $stmt->close();
        } else {

            // ✅ mã hóa password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $username = $email; // use email as username

            $conn->begin_transaction();

            try {
                // Insert into users
                $stmt = $conn->prepare("INSERT INTO users(username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("sss", $username, $email, $hash);
                $stmt->execute();
                $user_id = $stmt->insert_id;
                $stmt->close();

                // Insert into customers
                $stmt = $conn->prepare("INSERT INTO customers(user_id, full_name, phone, address) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $fullname, $phone, $address);
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                $_SESSION['user_id']  = $user_id;
                $_SESSION['username'] = $fullname;
                header("Location: login.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed. Please try again.";
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | 36 Jewelry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* ==========================================================
       REGISTER PAGE WITH HEADER - LUXURY GOLD + GLASS EFFECT
       ========================================================== */

    /* ===== VARIABLES ===== */
    :root {
      --primary: #b8860b;
      --primary-hover: #996600;
      --bg: #ffffff;
      --muted: #666666;
      --dark: #111111;
      --accent: #f9f9f9;
      --radius: 10px;
      --transition: 0.3s;
      --max-width: 1400px;
    }

    /* ===== FONT & RESET ===== */
    @font-face {
      font-family: 'Hijrnotes';
      src: url('../../fonts/fonts/hijrnotes/Hijrnotes_PERSONAL_USE_ONLY.ttf') format('truetype');
    }

    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* ===== HEADER STYLES ===== */
    .header-container {
      width: 100%;
      position: fixed;
      top: 0;
      z-index: 1000;
      background-color: var(--bg);
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .search-bar {
      width: 100%;
      max-width: var(--max-width);
      margin: 0 auto;
      background: var(--bg);
      border-top: 1px solid rgba(0,0,0,0.03);
      border-bottom: 1px solid rgba(0,0,0,0.04);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }

    .search-bar .left,
    .search-bar .center,
    .search-bar .right {
      display: flex;
      align-items: center;
    }

    .search-bar .left { 
      width: auto; 
      justify-content: flex-start;
    }

    .search-bar .right { 
      justify-content: flex-end; 
      width: auto; 
    }

    .search-bar .center { 
      flex: 1 1 auto;
      justify-content: center;
      gap: 30px;
      position: relative;
    }

    .home-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 10px 15px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--dark);
      font-weight: 600;
      font-size: 16px;
      transition: background-color 0.2s;
    }

    .home-btn i {
      margin-right: 5px;
      font-size: 18px;
    }

    .home-btn:hover {
      background: var(--accent);
      color: var(--primary);
    }

    .search-bar .center a {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10;
    }

    .search-bar .center .header-logo {
      height: 55px;
      max-width: 180px;
      object-fit: contain;
      transition: all 0.25s ease;
    }

    .search-box {
      flex: 0 1 450px;
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--accent);
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid rgba(0,0,0,0.06);
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);
      height: 50px;
      opacity: 1;
    }

    .search-box input {
      flex: 1;
      border: 0;
      outline: 0;
      background: transparent;
      padding: 2px 6px;
      font-size: 15px;
      color: var(--dark);
      min-width: 100px;
    }

    .search-box button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 0;
      background: var(--primary);
      color: #fff;
      padding: 8px 12px;
      border-radius: 999px;
      cursor: pointer;
      font-size: 14px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .icons { 
      display: flex; 
      gap: 10px; 
      align-items: center; 
    }

    .icon-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--dark);
      transition: all 0.18s;
      font-size: 18px;
    }

    .icon-link:hover {
      background: rgba(186,134,11,0.08);
      color: var(--primary);
    }

    /* ===== BODY ===== */
    body {
      font-family: 'Cormorant Garamond', serif;
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), 
                  url('../../images/User_login2.jpg') no-repeat center center/cover;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding-top: 100px;
      padding-bottom: 60px;
      animation: fadeIn 1.2s ease-in-out;
    }

    /* ===== BACK BUTTON ===== */
    .back-btn {
      position: fixed;
      top: 90px;
      left: 25px;
      background: rgba(255, 215, 0, 0.1);
      color: #f8ce86;
      border: 1px solid rgba(255, 215, 0, 0.5);
      border-radius: 50px;
      padding: 8px 18px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      backdrop-filter: blur(6px);
      text-decoration: none;
      z-index: 1000;
    }

    .back-btn:hover {
      background: rgba(255, 215, 0, 0.3);
      color: #fff;
      box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    }

    /* ===== REGISTER CONTAINER ===== */
    .register-container {
      background: rgba(255, 255, 255, 0.07);
      backdrop-filter: blur(12px);
      padding: 50px 40px;
      border-radius: 20px;
      width: 450px;
      text-align: center;
      box-shadow: 0 0 30px rgba(255, 215, 0, 0.15);
      border: 1px solid rgba(255, 215, 0, 0.2);
      animation: slideUp 1s ease-out;
      transition: all 0.4s ease;
    }

    .register-container:hover {
      transform: scale(1.02);
      box-shadow: 0 0 40px rgba(255, 215, 0, 0.25);
    }

    /* ===== TITLE ===== */
    .register-title {
      color: #f8ce86;
      font-family: 'Hijrnotes', cursive;
      font-size: 38px;
      margin-bottom: 30px;
      letter-spacing: 1px;
      animation: glowGold 2s infinite alternate;
    }

    /* ===== INPUTS ===== */
    .input-group {
      margin-bottom: 20px;
      text-align: left;
      position: relative;
    }

    .input-group label {
      display: block;
      color: #f8ce86;
      font-size: 16px;
      margin-bottom: 6px;
    }

    .input-group input,
    .input-group textarea {
      width: 100%;
      padding: 12px;
      font-size: 15px;
      color: #fff;
      border: 1px solid rgba(255, 215, 0, 0.4);
      border-radius: 10px !important;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(8px);
      outline: none;
      transition: all 0.4s ease;
      font-family: inherit;
      resize: vertical;
    }

    .input-group input:focus,
    .input-group textarea:focus {
      background: rgba(255, 255, 255, 0.2);
      border-color: #f8ce86;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
      transform: scale(1.02);
    }

    .input-group input::placeholder,
    .input-group textarea::placeholder {
      color: #b6a988;
    }

    /* ===== BUTTON ===== */
    .btn-register {
      width: 100%;
      padding: 14px 0;
      background: linear-gradient(135deg, #fbe9a6 0%, #f8ce86 35%, #d4af37 70%, #8e4b00 100%);
      color: #2b1a00;
      border: none;
      border-radius: 50px;
      font-weight: 700;
      font-size: 18px;
      cursor: pointer;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
      letter-spacing: 0.5px;
      box-shadow: 0 3px 10px rgba(255, 215, 0, 0.3), inset 0 0 8px rgba(255, 255, 255, 0.2);
      animation: goldGlow 3s infinite ease-in-out;
    }

    .btn-register::before {
      content: "";
      position: absolute;
      top: 0;
      left: -75%;
      width: 50%;
      height: 100%;
      background: linear-gradient(120deg, rgba(255,255,255,0.7), transparent);
      transform: skewX(-25deg);
      transition: 0.6s ease;
    }

    .btn-register:hover::before {
      left: 130%;
    }

    .btn-register:hover {
      transform: translateY(-2px) scale(1.04);
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.6), 0 0 35px rgba(255, 215, 0, 0.4);
      background: linear-gradient(135deg, #fff5c2 0%, #fce596 30%, #f5c048 80%, #dba309 100%);
      color: #5a3500;
    }

    .btn-register:active {
      transform: scale(0.98);
      box-shadow: inset 0 0 8px rgba(0,0,0,0.3);
    }

    /* ===== EXTRA LINKS ===== */
    .extra-links { 
      margin-top: 20px; 
      color: white;
    }

    .extra-links a {
      color: #f8ce86;
      text-decoration: none;
      margin: 0 10px;
      font-size: 15px;
      transition: color 0.3s;
    }

    .extra-links a:hover { 
      color: #fff; 
      text-shadow: 0 0 10px #f8ce86; 
    }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeIn { 
      from { opacity:0; transform:translateY(20px); } 
      to { opacity:1; transform:translateY(0); } 
    }

    @keyframes slideUp { 
      from { transform:translateY(30px); opacity:0; } 
      to { transform:translateY(0); opacity:1; } 
    }

    @keyframes glowGold { 
      from { text-shadow:0 0 10px #f8ce86,0 0 20px #8e4b00; } 
      to { text-shadow:0 0 20px #fff8dc,0 0 35px #f8ce86; } 
    }

    @keyframes goldGlow {
      0%, 100% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.3), 0 0 20px rgba(255, 215, 0, 0.2); }
      50% { box-shadow: 0 0 25px rgba(255, 215, 0, 0.6), 0 0 50px rgba(255, 215, 0, 0.4); }
    }

    /* ===== RESPONSIVE DESIGN ===== */
    @media (max-width: 992px) {
      .search-bar {
        flex-direction: column;
        align-items: center;
        gap: 10px;
      }
      .search-bar .left,
      .search-bar .right,
      .search-bar .center {
        flex: unset;
      }
    }

    @media (max-width: 768px) {
      body {
        padding-top: 140px;
      }
      .back-btn {
        top: 110px;
      }
      .search-box {
        flex: 0 1 300px;
      }
    }

    @media (max-width: 480px) {
      .register-container {
        width: 90%;
        padding: 40px 30px;
      }
      .register-title {
        font-size: 32px;
      }
      .search-box {
        flex: 0 1 250px;
      }
      body {
        padding-top: 160px;
      }
      .back-btn {
        top: 120px;
        left: 15px;
        padding: 6px 14px;
        font-size: 14px;
      }
    }
  </style>
</head>

<body>

<header class="header-container">
  <div class="search-bar">

    <div class="left">
      <a href="../index.php" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>

    <div class="center">
      <a href="../index.php">
        <img src="../../images/36-logo.png" alt="Logo" class="header-logo">
      </a>

      <div class="search-box">
        <input type="text" id="searchInput">
        <button id="searchBtn"><i class="fas fa-search"></i></button>
      </div>
    </div>

    <div class="right">
      <div class="icons">
        <a href="Jewelry-cart.php" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
        <a href="login.php" class="icon-link"><i class="fas fa-user"></i></a>
      </div>
    </div>

  </div>
</header>

<a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>

<div class="register-container">
  <h2 class="register-title">Create Account</h2>

  <?php if (isset($error)): ?>
    <p style="color:#ffb347; text-align:center; margin-bottom:15px; background:rgba(0,0,0,0.5); padding:8px; border-radius:8px;">
      <?= htmlspecialchars($error) ?>
    </p>
  <?php endif; ?>

  <form method="POST" class="register-form">
    <div class="input-group">
      <label>Full Name</label>
      <input type="text" name="fullname" required>
    </div>

    <div class="input-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>

    <div class="input-group">
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phone"
             placeholder="Enter your phone number"
             value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
    </div>

    <div class="input-group">
      <label for="address">Delivery Address</label>
      <textarea id="address" name="address" rows="2"
                placeholder="Enter your address (house number, street, ward, district, city)"
                required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
    </div>

    <div class="input-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password"
             placeholder="Enter your password" required>
    </div>

    <div class="input-group">
      <label>Confirm Password</label>
      <input type="password" name="confirmPassword" required>
    </div>

    <button type="submit" class="btn-register">Register</button>
  </form>

  <div class="extra-links">
    Already have an account? <a href="login.php">Login</a>
  </div>

</div>

<script>
  // Search
  document.getElementById("searchBtn").onclick = function() {
    let key = document.getElementById("searchInput").value;
    if (key.trim()) alert("Searching: " + key);
  };
</script>

</body>
</html>