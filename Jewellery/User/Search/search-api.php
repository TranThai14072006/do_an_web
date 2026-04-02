<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$db   = 'jewelry_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// ===== LẤY KEYWORD SEARCH =====
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT id, name, cost_price, profit_percent, image, gender 
        FROM products 
        WHERE name LIKE :q
        ORDER BY id ASC
    ");
    $stmt->execute(['q' => "%$q%"]);
} else {
    $stmt = $pdo->query("
        SELECT id, name, cost_price, profit_percent, image, gender 
        FROM products
        ORDER BY id ASC
    ");
}

$rows = $stmt->fetchAll();

// ===== TÍNH GIÁ =====
$products = [];
foreach ($rows as $p) {
    $cost_price     = (float)$p['cost_price'];
    $profit_percent = (float)$p['profit_percent'];

    // Giá bán = giá nhập bình quân × (1 + tỷ lệ lợi nhuận%)
    $sale_price = round($cost_price * (1 + $profit_percent / 100), 2);

    $products[] = [
        'id'         => $p['id'],
        'name'       => $p['name'],
        'gender'     => $p['gender'],
        'sale_price' => $sale_price,
        'image'      => $p['image'] ?: 'placeholder.png',
    ];
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);