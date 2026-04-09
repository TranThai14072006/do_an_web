<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/logout.php
// Chức năng: Hủy session và chuyển về trang index
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';
if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');

// Xóa toàn bộ dữ liệu session
$_SESSION = [];

// Xóa session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Chuyển về trang index thay vì login báo lỗi 404
header('Location: ' . BASE_URL . 'User/index.php');
exit();
