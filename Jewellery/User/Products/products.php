<?php
header('Content-Type: application/json');

// Kết nối database
$host = "localhost";
$user = "root";
$pass = "";
$db = "shop_db";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

// Lấy danh sách sản phẩm
$sql_products = "SELECT id, name, price AS cost_price, profit_percent, stock, image 
                 FROM products";
$result_products = $conn->query($sql_products);

$products = [];

if ($result_products && $result_products->num_rows > 0) {
    while ($product = $result_products->fetch_assoc()) {

        $product_id = $product['id'];
        $current_stock = (int)$product['stock'];
        $current_cost = (float)$product['cost_price'];

        // Lấy lịch sử nhập hàng
        $sql_receipt = "SELECT quantity, unit_price 
                        FROM goods_receipt_items 
                        WHERE product_id = '$product_id'";
        $result_receipt = $conn->query($sql_receipt);

        $total_quantity = $current_stock;
        $total_cost = $current_cost * $current_stock;

        if ($result_receipt && $result_receipt->num_rows > 0) {
            while ($row = $result_receipt->fetch_assoc()) {
                $qty_new = (int)$row['quantity'];
                $price_new = (float)$row['unit_price'];

                $total_cost += $qty_new * $price_new;
                $total_quantity += $qty_new;
            }
        }

        // 🔥 FIX QUAN TRỌNG: fallback nếu không có dữ liệu
        $avg_cost = $total_quantity > 0 
            ? $total_cost / $total_quantity 
            : $current_cost;

        $sale_price = round($avg_cost * (1 + $product['profit_percent'] / 100), 2);

        // Ảnh mặc định
        if (empty($product['image'])) {
            $product['image'] = 'placeholder.png';
        }

        // Gán dữ liệu trả về
        $products[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'sale_price' => $sale_price,
            'image' => $product['image']
        ];
    }
}

$conn->close();

// Xuất JSON
echo json_encode($products);
?>