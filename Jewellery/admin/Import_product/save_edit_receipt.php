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

    // ── Tính giá bình quân khi Completed ─────────────────
    if ($status === 'Completed') {
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = $product_ids[$i];
            if (empty($pid)) continue;

            $new_qty   = intval($quantities[$i]);
            $new_price = floatval(str_replace('.', '', $prices[$i]));

            // Stock trước khi phiếu này tính vào
            // (= tổng các phiếu Completed KHÁC trừ đơn bán)
            $cur = $conn->query("
                SELECT
                    GREATEST(0,
                        COALESCE((
                            SELECT SUM(gri.quantity)
                            FROM goods_receipt_items gri
                            JOIN goods_receipt gr ON gri.receipt_id = gr.id
                            WHERE gri.product_id = '$pid'
                              AND gr.status = 'Completed'
                              AND gr.id != $id
                        ), 0)
                        -
                        COALESCE((
                            SELECT SUM(oi.quantity)
                            FROM order_items oi
                            WHERE oi.product_id = '$pid'
                        ), 0)
                    ) AS cur_stock,
                    cost_price
                FROM products
                WHERE id = '$pid'
            ")->fetch_assoc();

            $cur_stock = floatval($cur['cur_stock']  ?? 0);
            $cur_cost  = floatval($cur['cost_price'] ?? 0);

            // ── Công thức bình quân ───────────────────────
            // (tồn * giá cũ + nhập mới * giá mới) / (tồn + nhập mới)
            $total_units = $cur_stock + $new_qty;
            $avg_cost    = $total_units > 0
                ? ($cur_stock * $cur_cost + $new_qty * $new_price) / $total_units
                : $new_price;

            // ── Cập nhật cost_price bình quân + giá bán ──
            $stmt_upd = $conn->prepare("
                UPDATE products
                SET cost_price = ROUND(?, 4),
                    price      = ROUND(? * (1 + profit_percent / 100.0), 2)
                WHERE id = ?
            ");
            $stmt_upd->bind_param('dds', $avg_cost, $avg_cost, $pid);
            $stmt_upd->execute();
            $stmt_upd->close();

            // ── Cập nhật stock ────────────────────────────
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
                WHERE p.id = '$pid'
            ");
        }
    }

    $conn->close();
    header("Location: import_management.php");
    exit();
}
?>