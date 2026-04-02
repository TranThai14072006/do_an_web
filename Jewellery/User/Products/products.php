<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "jewelry_db";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';

if ($gender !== 'all') {
    $gender_safe = $conn->real_escape_string($gender);
    $sql = "SELECT id, name, cost_price, profit_percent, image
            FROM products
            WHERE gender = '$gender_safe'";
} else {
    $sql = "SELECT id, name, cost_price, profit_percent, image
            FROM products";
}

$result = $conn->query($sql);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cost_price     = (float)$row['cost_price'];
        $profit_percent = (float)$row['profit_percent'];

        // Giá bán = giá nhập bình quân × (1 + tỷ lệ lợi nhuận%)
        $sale_price = round($cost_price * (1 + $profit_percent / 100), 2);

        $products[] = [
            'id'         => $row['id'],
            'name'       => $row['name'],
            'sale_price' => $sale_price,
            'image'      => !empty($row['image']) ? $row['image'] : 'placeholder.png',
        ];
    }
}

$conn->close();
echo json_encode($products);
?>