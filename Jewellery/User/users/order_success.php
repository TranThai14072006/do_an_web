<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "User/Login.php"); exit(); }

define('BASE_URL', '/do_an_web/Jewellery/');
define('IMG_URL', BASE_URL . 'images/');
$link_home = BASE_URL . 'User/index.php';
$link_history = BASE_URL . 'User/users/History.php';
$link_search = BASE_URL . 'User/users/search.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Order Successfully | Swanky Jewelry</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="search.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="order_success.css">
</head>
<body>
  <header class="header-container">
    <div class="search-bar">
      <div class="left"><a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a></div>
      <div class="center"><a href="<?= $link_home ?>"><img src="<?= IMG_URL ?>36-logo.png" class="header-logo"></a><div class="search-box"><input type="text" id="search-input" placeholder="Search products..."><button onclick="doSearch()"><i class="fas fa-search"></i></button></div></div>
      <div class="right"><a href="Jewelry-cart.html" class="icon-link"><i class="fas fa-shopping-cart"></i></a><a href="profile.html" class="icon-link"><i class="fas fa-user"></i></a></div>
    </div>
  </header>
  <div class="success-container">
    <div class="success-box"><div class="icon-check">✓</div><h1>Order Successfully!</h1><p>Thank you for shopping with <strong>ThirtySix Jewelry</strong><br>Your order has been placed successfully and is being processed.<br>We'll deliver it to you as soon as possible.</p><div class="buttons"><a href="<?= $link_home ?>" class="btn home">Back to Home</a><a href="<?= $link_history ?>" class="btn order">View My Orders</a></div></div>
  </div>
  <script> function doSearch() { const keyword = document.getElementById('search-input').value.trim(); if (keyword !== '') window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword); } </script>
</body>
</html>