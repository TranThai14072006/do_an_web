<?php
header('Content-Type: application/json');

// ===== CONNECT DB =====
$conn = new mysqli("localhost", "root", "", "jewelry_db");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// ===== NHẬN DATA (JSON + POST) =====
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// fallback nếu JSON rỗng → dùng POST
if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? '';

// ===== GET CART =====
if ($action === 'get') {

    $sql = "SELECT c.product_id, c.quantity, c.size, p.name, p.image, p.price, p.profit_percent, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id";

    $result = $conn->query($sql);
    $items = [];

    while ($row = $result->fetch_assoc()) {

        $current_cost = (float)$row['price'];
        $profit = (float)$row['profit_percent'];

        // Chỉ tính từ phiếu nhập, KHÔNG cộng stock (đồng bộ với search-api.php)
        $total_qty = 0;
        $total_cost = 0;

        $res = $conn->query("SELECT quantity, unit_price FROM goods_receipt_items WHERE product_id = '{$row['product_id']}'");

        while ($r = $res->fetch_assoc()) {
            $total_cost += $r['quantity'] * $r['unit_price'];
            $total_qty += $r['quantity'];
        }

        // Nếu chưa có phiếu nhập → dùng cost_price
        $avg = $total_qty > 0 ? $total_cost / $total_qty : $current_cost;
        $sale_price = round($avg * (1 + $profit / 100), 2);

        $items[] = [
            'id' => $row['product_id'],
            'name' => $row['name'],
            'image' => $row['image'],
            'price' => $sale_price,
            'quantity' => $row['quantity'],
            'size' => $row['size'] ?? ''
        ];
    }

    echo json_encode($items);
    exit;
}

// ===== ADD =====
if ($action === 'add') {
    $id  = $data['product_id'] ?? '';
    $qty = (int)($data['quantity'] ?? 1);
    $size = $conn->real_escape_string($data['size'] ?? '');

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing product_id']);
        exit;
    }

    $check = $conn->query("SELECT * FROM cart WHERE product_id = '$id'");

    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + $qty, size = '$size' WHERE product_id = '$id'");
    } else {
        $conn->query("INSERT INTO cart (product_id, quantity, size) VALUES ('$id', $qty, '$size')");
    }

    echo json_encode(['success' => true]);
    exit;
}

// ===== UPDATE =====
if ($action === 'update') {
    $id = $data['product_id'] ?? '';
    $qty = (int)($data['quantity'] ?? 1);

    $conn->query("UPDATE cart SET quantity = $qty WHERE product_id = '$id'");

    echo json_encode(['success' => true]);
    exit;
}

// ===== REMOVE =====
if ($action === 'remove') {
    $id = $data['product_id'] ?? '';

    $conn->query("DELETE FROM cart WHERE product_id = '$id'");

    echo json_encode(['success' => true]);
    exit;
}

// ===== FALLBACK =====
echo json_encode([
    'success' => false,
    'message' => 'Invalid action',
    'received' => $data
]);