<?php
header('Content-Type: application/json'); // Đảm bảo trả JSON

// Kết nối database
$host = "localhost";
$user = "root";
$pass = "";
$db = "shop_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

// Lấy sản phẩm và tính giá nhập bình quân
$sql_products = "SELECT p.id, p.name, p.price AS cost_price, p.profit_percent, p.stock, p.image
                 FROM products p";
$result_products = $conn->query($sql_products);

$products = [];
if ($result_products->num_rows > 0) {
    while ($product = $result_products->fetch_assoc()) {
        $current_stock = (int)$product['stock'];
        $current_cost = (float)$product['cost_price'];

        $sql_receipt = "SELECT quantity, unit_price FROM goods_receipt_items WHERE product_id = '".$product['id']."'";
        $result_receipt = $conn->query($sql_receipt);

        $total_quantity = $current_stock;
        $total_cost = $current_cost * $current_stock;

        if ($result_receipt->num_rows > 0) {
            while ($row = $result_receipt->fetch_assoc()) {
                $qty_new = (int)$row['quantity'];
                $price_new = (float)$row['unit_price'];
                $total_cost += $qty_new * $price_new;
                $total_quantity += $qty_new;
            }
        }

        $avg_cost = $total_quantity > 0 ? $total_cost / $total_quantity : 0;
        $sale_price = round($avg_cost * (1 + $product['profit_percent'] / 100), 2);

        // Nếu ảnh rỗng thì hiển thị placeholder
        if(empty($product['image'])) $product['image'] = 'placeholder.png';

        $product['sale_price'] = $sale_price;

        $products[] = $product;
    }
}

$conn->close();

// Xuất JSON
echo json_encode($products);