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

// Lấy danh sách sản phẩm
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';

if ($gender !== 'all') {
    $stmt = $pdo->prepare(
        "SELECT id, name, cost_price, profit_percent, stock, image, gender 
         FROM products WHERE gender = :gender ORDER BY id ASC"
    );
    $stmt->execute(['gender' => $gender]);
} else {
    $stmt = $pdo->query(
        "SELECT id, name, cost_price, profit_percent, stock, image, gender 
         FROM products ORDER BY id ASC"
    );
}

$rows = $stmt->fetchAll();

// Pre-load phiếu nhập
$receiptItems = [];
foreach ($pdo->query("SELECT product_id, quantity, unit_price FROM goods_receipt_items")->fetchAll() as $r) {
    $receiptItems[$r['product_id']][] = $r;
}

// Tính giá bán
$products = [];

foreach ($rows as $p) {
    $pid            = $p['id'];
    $current_cost   = (float)$p['cost_price'];
    $profit_percent = (float)$p['profit_percent'];

    // ❌ BỎ stock ra khỏi công thức
    $total_quantity = 0;
    $total_cost     = 0;

    // Chỉ tính từ phiếu nhập
    foreach ($receiptItems[$pid] ?? [] as $r) {
        $qty   = (int)$r['quantity'];
        $price = (float)$r['unit_price'];

        $total_quantity += $qty;
        $total_cost     += $qty * $price;
    }

    // Nếu chưa có nhập → dùng cost_price
    if ($total_quantity > 0) {
        $avg_cost = $total_cost / $total_quantity;
    } else {
        $avg_cost = $current_cost;
    }

    // Giá bán
    $sale_price = round($avg_cost * (1 + $profit_percent / 100), 2);

    $products[] = [
        'id'         => $pid,
        'name'       => $p['name'],
        'gender'     => $p['gender'],
        'sale_price' => $sale_price,
        'image'      => !empty($p['image']) ? $p['image'] : 'placeholder.png',
    ];
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);