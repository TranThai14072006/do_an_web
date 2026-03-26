<?php
require_once "../../config/config.php";

// ============================================================
// Nhận dữ liệu từ form
// ============================================================
$date         = isset($_POST['date'])         ? trim($_POST['date'])         : '';
$order_number = isset($_POST['order_number']) ? trim($_POST['order_number']) : '';
$supplier     = isset($_POST['supplier'])     ? trim($_POST['supplier'])     : '';
$status       = isset($_POST['status'])       ? trim($_POST['status'])       : 'Draft';

$product_codes = $_POST['product_code'] ?? [];
$prices        = $_POST['price']        ?? [];
$quantities    = $_POST['quantity']     ?? [];

// ── Validate ──────────────────────────────────────────────
if ($date === '' || $order_number === '' || empty($product_codes)) {
    header('Location: add_entry_form.php?error=missing_fields');
    exit;
}

$allowed_status = ['Draft', 'Completed'];
if (!in_array($status, $allowed_status)) {
    $status = 'Draft';
}

// ============================================================
// Build items + tính tổng
// ============================================================
$total_quantity = 0;
$total_value    = 0;
$items          = [];

for ($i = 0; $i < count($product_codes); $i++) {
    $pid = trim($product_codes[$i] ?? '');
    if ($pid === '') continue;

    $price = floatval(str_replace(['.', ','], ['', '.'], $prices[$i] ?? '0'));
    $qty   = intval($quantities[$i] ?? 1);
    if ($qty <= 0) $qty = 1;

    $stmt_p = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $stmt_p->bind_param('s', $pid);
    $stmt_p->execute();
    $prod = $stmt_p->get_result()->fetch_assoc();
    $stmt_p->close();

    $pname = $prod['name'] ?? $pid;

    $items[] = [
        'product_id'   => $pid,
        'product_name' => $pname,
        'quantity'     => $qty,
        'unit_price'   => $price,
        'total_price'  => $price * $qty,
    ];

    $total_quantity += $qty;
    $total_value    += $price * $qty;
}

if (empty($items)) {
    header('Location: add_entry_form.php?error=no_products');
    exit;
}

// ============================================================
// BƯỚC 1: INSERT goods_receipt
// ============================================================
$stmt1 = $conn->prepare("
    INSERT INTO goods_receipt
        (order_number, entry_date, supplier, total_quantity, total_value, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt1->bind_param('sssdds',
    $order_number,
    $date,
    $supplier,
    $total_quantity,
    $total_value,
    $status
);

if (!$stmt1->execute()) {
    $err = $conn->error;
    $stmt1->close();
    header("Location: add_entry_form.php?error=" . urlencode($err));
    exit;
}

$receipt_id = $conn->insert_id;
$stmt1->close();

// ============================================================
// BƯỚC 2: INSERT goods_receipt_items
// ============================================================
$stmt2 = $conn->prepare("
    INSERT INTO goods_receipt_items
        (receipt_id, product_id, product_name, quantity, unit_price, total_price)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($items as $item) {
    $stmt2->bind_param(
        'issids',
        $receipt_id,
        $item['product_id'],
        $item['product_name'],
        $item['quantity'],
        $item['unit_price'],
        $item['total_price']
    );

    if (!$stmt2->execute()) {
        error_log("save_receipt: failed item {$item['product_id']}: " . $conn->error);
    }
}
$stmt2->close();

// ============================================================
// BƯỚC 3: Nếu Completed → tính giá bình quân + cập nhật stock
// ============================================================
if ($status === 'Completed') {
    foreach ($items as $item) {
        $pid       = $item['product_id'];
        $new_qty   = $item['quantity'];
        $new_price = $item['unit_price'];

        // ── Lấy stock và cost_price hiện tại ──────────────
        // Stock hiện tại đã bao gồm phiếu vừa insert ở BƯỚC 2,
        // nên ta trừ đi new_qty để lấy stock TRƯỚC khi nhập
        $stmt_cur = $conn->prepare(
            "SELECT stock, cost_price FROM products WHERE id = ?"
        );
        $stmt_cur->bind_param('s', $pid);
        $stmt_cur->execute();
        $cur = $stmt_cur->get_result()->fetch_assoc();
        $stmt_cur->close();

        // stock trong DB chưa được cập nhật tại thời điểm này
        // → dùng trực tiếp làm "tồn trước khi nhập"
        $cur_stock = max(0, floatval($cur['stock']      ?? 0));
        $cur_cost  = floatval($cur['cost_price'] ?? 0);

        // ── Công thức bình quân ───────────────────────────
        // (tồn * giá cũ + nhập mới * giá mới) / (tồn + nhập mới)
        $total_units = $cur_stock + $new_qty;
        $avg_cost    = $total_units > 0
            ? ($cur_stock * $cur_cost + $new_qty * $new_price) / $total_units
            : $new_price;

        // ── Cập nhật cost_price bình quân + tính lại giá bán ──
        $stmt_upd = $conn->prepare("
            UPDATE products
            SET cost_price = ROUND(?, 4),
                price      = ROUND(? * (1 + profit_percent / 100.0), 2)
            WHERE id = ?
        ");
        $stmt_upd->bind_param('dds', $avg_cost, $avg_cost, $pid);
        $stmt_upd->execute();
        $stmt_upd->close();

        // ── Cập nhật stock ────────────────────────────────
        $conn->query("
            UPDATE products p
            SET p.stock = (
                COALESCE((
                    SELECT SUM(gri.quantity)
                    FROM goods_receipt_items gri
                    JOIN goods_receipt gr ON gri.receipt_id = gr.id
                    WHERE gri.product_id = p.id
                      AND gr.status = 'Completed'
                ), 0)
                -
                COALESCE((
                    SELECT SUM(oi.quantity)
                    FROM order_items oi
                    WHERE oi.product_id = p.id
                ), 0)
            )
            WHERE p.id = '{$pid}'
        ");
    }
}

$conn->close();

// ============================================================
// Redirect
// ============================================================
header("Location: import_management.php?success=1&receipt_id={$receipt_id}");
exit;
?>