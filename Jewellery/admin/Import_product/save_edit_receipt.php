<?php
include __DIR__ . "/../../config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id           = $_POST['id'];
    $date         = $_POST['date'];
    $order_number = $_POST['order_number'];
    $status       = $_POST['status'];

    $product_ids = $_POST['product_code'];
    $prices      = $_POST['price'];
    $quantities  = $_POST['quantity'];

    // ── UPDATE receipt header ─────────────────────────────
    $stmt = $conn->prepare("
        UPDATE goods_receipt
        SET entry_date = ?, order_number = ?, status = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $date, $order_number, $status, $id);
    $stmt->execute();
    $stmt->close();

    // ── XÓA items cũ ─────────────────────────────────────
    $conn->query("DELETE FROM goods_receipt_items WHERE receipt_id = $id");

    $total_qty   = 0;
    $total_value = 0;

    // ── INSERT lại items ──────────────────────────────────
    $stmt_item = $conn->prepare("
        INSERT INTO goods_receipt_items
            (receipt_id, product_id, product_name, quantity, unit_price, total_price)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    for ($i = 0; $i < count($product_ids); $i++) {
        $pid   = $product_ids[$i];
        $price = floatval(str_replace('.', '', $prices[$i]));
        $qty   = intval($quantities[$i]);

        if (empty($pid)) continue;

        $result = $conn->query("SELECT name FROM products WHERE id = '$pid'");
        $row    = $result->fetch_assoc();
        $name   = $row ? $row['name'] : 'Unknown';

        $total = $price * $qty;

        $stmt_item->bind_param("issidd", $id, $pid, $name, $qty, $price, $total);
        $stmt_item->execute();

        $total_qty   += $qty;
        $total_value += $total;
    }
    $stmt_item->close();

    // ── UPDATE tổng phiếu ─────────────────────────────────
    $conn->query("
        UPDATE goods_receipt
        SET total_quantity = $total_qty,
            total_value    = $total_value
        WHERE id = $id
    ");

    require_once "../admin_sync.php";
    // ── Đồng bộ hóa sản phẩm ─────────────────
    foreach (array_unique($product_ids) as $pid) {
        if (!empty($pid)) {
            syncProduct($conn, $pid);
        }
    }

    $conn->close();
    header("Location: import_management.php");
    exit();
}
?>