<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/logout.php
// Chức năng: Hủy session và chuyển về trang login
// ═══════════════════════════════════════════════════════════

session_start();

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

// Chuyển về trang login
header('Location: /do_an_web/Jewellery/User/users/login.php');
exit();
