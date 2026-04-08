<?php
require_once "../../config/config.php";
header('Content-Type: application/json');

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') { echo json_encode(['lots'=>[]]); exit; }

// Lấy profit_percent & avg cost hiện tại của sản phẩm
$s = $conn->prepare("SELECT cost_price, profit_percent FROM products WHERE id=?");
$s->bind_param('s', $id);
$s->execute();
$prod = $s->get_result()->fetch_assoc();
$s->close();

if (!$prod) { echo json_encode(['lots'=>[]]); exit; }

$profit  = $prod['profit_percent'];
$avg_cost = $prod['cost_price'];

// Lấy lịch sử lô nhập
$stmt = $conn->prepare("
    SELECT gr.order_number AS receipt_no,
           gr.entry_date,
           gri.product_name,
           gri.quantity,
           gri.unit_price AS lot_cost
    FROM goods_receipt_items gri
    JOIN goods_receipt gr ON gri.receipt_id = gr.id
    WHERE gri.product_id = ? AND gr.status = 'Completed'
    ORDER BY gr.entry_date DESC
");
$stmt->bind_param('s', $id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$lots = [];
foreach ($rows as $r) {
    $new_sell = round($r['lot_cost'] * (1 + $profit / 100.0), 2);
    $lots[] = [
        'receipt_no'     => $r['receipt_no'],
        'entry_date'     => $r['entry_date'],
        'product_name'   => $r['product_name'],
        'quantity'       => (int)$r['quantity'],
        'lot_cost'       => number_format($r['lot_cost'], 2),
        'avg_cost'       => number_format($avg_cost, 2),
        'new_sell_price' => number_format($new_sell, 2),
        'profit_percent' => $profit,
    ];
}

echo json_encode(['lots' => $lots]);