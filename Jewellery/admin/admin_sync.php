<?php
/**
 * admin_sync.php
 * Centralized logic for product synchronization (Stock & Pricing)
 */

if (!function_exists('syncProduct')) {
    /**
     * Synchronizes a single product's cost_price, selling_price, and stock level.
     * Logic:
     * - cost_price: Weighted Average of all 'Completed' Goods Receipts.
     * - stock: Total Completed Imports - Total Non-Cancelled Orders.
     * - price: cost_price * (1 + profit_percent / 100).
     */
    function syncProduct($conn, $product_id) {
        if (empty($product_id)) return false;

        // 1. Calculate Weighted Average Cost from Completed Receipts
        $stmt_cost = $conn->prepare("
            SELECT 
                SUM(gri.quantity * gri.unit_price) AS total_cost,
                SUM(gri.quantity) AS total_qty
            FROM goods_receipt_items gri
            JOIN goods_receipt gr ON gri.receipt_id = gr.id
            WHERE gri.product_id = ? AND gr.status = 'Completed'
        ");
        $stmt_cost->bind_param('s', $product_id);
        $stmt_cost->execute();
        $res_cost = $stmt_cost->get_result()->fetch_assoc();
        $stmt_cost->close();

        $avg_cost = 0;
        if ($res_cost && $res_cost['total_qty'] > 0) {
            $avg_cost = $res_cost['total_cost'] / $res_cost['total_qty'];
        }

        // 2. Calculate Total Imported Qty (Completed)
        $stmt_imp = $conn->prepare("
            SELECT SUM(gri.quantity) AS total_imported
            FROM goods_receipt_items gri
            JOIN goods_receipt gr ON gri.receipt_id = gr.id
            WHERE gri.product_id = ? AND gr.status = 'Completed'
        ");
        $stmt_imp->bind_param('s', $product_id);
        $stmt_imp->execute();
        $total_imported = intval($stmt_imp->get_result()->fetch_assoc()['total_imported'] ?? 0);
        $stmt_imp->close();

        // 3. Calculate Total Ordered Qty (NOT Cancelled)
        $stmt_ord = $conn->prepare("
            SELECT SUM(oi.quantity) AS total_ordered
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = ? AND o.status != 'Cancelled'
        ");
        $stmt_ord->bind_param('s', $product_id);
        $stmt_ord->execute();
        $total_ordered = intval($stmt_ord->get_result()->fetch_assoc()['total_ordered'] ?? 0);
        $stmt_ord->close();

        $new_stock = $total_imported - $total_ordered;

        // 4. Update Product Record
        // We round cost_price to 4 decimals for accuracy, and price to 2 decimals.
        $stmt_upd = $conn->prepare("
            UPDATE products 
            SET 
                cost_price = ROUND(?, 4),
                stock = ?,
                price = ROUND(? * (1 + profit_percent / 100.0), 2)
            WHERE id = ?
        ");
        $stmt_upd->bind_param('ddss', $avg_cost, $new_stock, $avg_cost, $product_id);
        $success = $stmt_upd->execute();
        $stmt_upd->close();

        return $success;
    }
}
?>
