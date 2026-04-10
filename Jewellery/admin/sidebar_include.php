<?php
// sidebar_include.php - Centralized navigation for Luxury Jewelry Admin
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Determine base path based on directory depth
$base_path  = ($current_dir === 'admin') ? '' : '../';
$image_path = ($current_dir === 'admin') ? '../images/' : '../../images/';

function isActive($page, $dir = 'admin') {
    global $current_page, $current_dir;
    if ($dir === 'admin' && $current_dir === 'admin' && $current_page === $page) return 'active';
    if ($dir !== 'admin' && $current_dir === $dir   && $current_page === $page) return 'active';
    return '';
}
?>
<!-- FontAwesome injected once by sidebar_include.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="sidebar">
    <div class="logo">
        <a href="<?php echo $base_path; ?>admin_detail.php" style="text-decoration: none; color: inherit; display: block;">
            <img src="<?php echo $image_path; ?>Admin_login.jpg" alt="Admin Logo">
            <h2>Luxury Jewelry</h2>
        </a>
    </div>
    <div class="menu">
        <a href="<?php echo $base_path; ?>Administration_menu.php" class="<?php echo isActive('Administration_menu.php'); ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo $base_path; ?>product_management.php" class="<?php echo isActive('product_management.php'); ?>">
            <i class="fas fa-boxes"></i> Products
        </a>
        <a href="<?php echo $base_path; ?>Price Manage/pricing.php" class="<?php echo isActive('pricing.php', 'Price Manage'); ?>">
            <i class="fas fa-tags"></i> Pricing
        </a>
        <a href="<?php echo $base_path; ?>customer_management.php" class="<?php echo isActive('customer_management.php'); ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        <a href="<?php echo $base_path; ?>Order Manage/order_management.php" class="<?php echo isActive('order_management.php', 'Order Manage'); ?>">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a href="<?php echo $base_path; ?>Import_product/import_management.php" class="<?php echo isActive('import_management.php', 'Import_product'); ?>">
            <i class="fas fa-file-import"></i> Import
        </a>
        <a href="<?php echo $base_path; ?>Stock Manage/stocking_management.php" class="<?php echo isActive('stocking_management.php', 'Stock Manage'); ?>">
            <i class="fas fa-warehouse"></i> Stock
        </a>
        <a href="<?php echo $base_path; ?>admin_login.php?logout=1" class="logout-link" onclick="return confirm('Are you sure you want to logout?')">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
