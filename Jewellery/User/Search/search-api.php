<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$db   = 'jewelry_db'; // ✅ FIX DB
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

// ✅ LẤY KEYWORD SEARCH
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT id, name, price AS cost_price, profit_percent, stock, image, gender 
        FROM products 
        WHERE name LIKE :q
        ORDER BY id ASC
    ");
    $stmt->execute(['q' => "%$q%"]);
} else {
    $stmt = $pdo->query("
        SELECT id, name, price AS cost_price, profit_percent, stock, image, gender 
        FROM products
        ORDER BY id ASC
    ");
}

$rows = $stmt->fetchAll();

// ===== TÍNH GIÁ =====
$receiptItems = [];
foreach ($pdo->query("SELECT product_id, quantity, unit_price FROM goods_receipt_items")->fetchAll() as $r) {
    $receiptItems[$r['product_id']][] = $r;
}

$products = [];
foreach ($rows as $p) {
    $pid            = $p['id'];
    $current_stock  = (int)$p['stock'];
    $current_cost   = (float)$p['cost_price'];
    $profit_percent = (float)$p['profit_percent'];

    $total_quantity = $current_stock;
    $total_cost     = $current_cost * $current_stock;

    foreach ($receiptItems[$pid] ?? [] as $r) {
        $total_cost += $r['quantity'] * $r['unit_price'];
        $total_quantity += $r['quantity'];
    }

    $avg_cost   = $total_quantity > 0 ? $total_cost / $total_quantity : $current_cost;
    $sale_price = round($avg_cost * (1 + $profit_percent / 100), 2);

    $products[] = [
        'id'         => $pid,
        'name'       => $p['name'],
        'gender'     => $p['gender'],
        'sale_price' => $sale_price,
        'image'      => $p['image'] ?: 'placeholder.png',
    ];
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);