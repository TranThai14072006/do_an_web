<?php
header('Content-Type: application/json');

// Kết nối database
$host = "localhost";
$user = "root";
$pass = "";
$db = "jewelry_db";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

// Lấy danh sách sản phẩm
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';

if ($gender !== 'all') {
    $gender_safe = $conn->real_escape_string($gender);
    $sql_products = "SELECT id, name, price AS cost_price, profit_percent, stock, image 
                     FROM products WHERE gender = '$gender_safe'";
} else {
    $sql_products = "SELECT id, name, price AS cost_price, profit_percent, stock, image 
                     FROM products";
}

$result_products = $conn->query($sql_products);

$products = [];

if ($result_products && $result_products->num_rows > 0) {
    while ($product = $result_products->fetch_assoc()) {

        $product_id = $product['id'];
        $current_stock = (int)$product['stock'];
        $current_cost = (float)$product['cost_price'];
        $profit_percent = isset($product['profit_percent']) ? (float)$product['profit_percent'] : 0;

        $total_quantity = $current_stock;
        $total_cost = $current_cost * $current_stock;

        $sql_receipt = "SELECT quantity, unit_price 
                        FROM goods_receipt_items 
                        WHERE product_id = '$product_id'";

        $result_receipt = $conn->query($sql_receipt);

        if ($result_receipt && $result_receipt->num_rows > 0) {
            while ($row = $result_receipt->fetch_assoc()) {
                $qty_new = (int)$row['quantity'];
                $price_new = (float)$row['unit_price'];

                $total_cost += $qty_new * $price_new;
                $total_quantity += $qty_new;
            }
        }

        if ($total_quantity > 0) {
            $avg_cost = $total_cost / $total_quantity;
        } else {
            $avg_cost = $current_cost;
        }

        $sale_price = round($avg_cost * (1 + $profit_percent / 100), 2);
        $image = !empty($product['image']) ? $product['image'] : 'placeholder.png';

        $products[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'sale_price' => $sale_price,
            'image' => $image
        ];
    }
}

$conn->close();
echo json_encode($products);
?>