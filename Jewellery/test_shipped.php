<?php
require 'c:/xampp/htdocs/do_an_web/Jewellery/config/config.php';

$res = $conn->query("SELECT id, user_id FROM customers");
$count = 0;
while($r = $res->fetch_assoc()) {
    $cid = $r['id'];
    $uid = $r['user_id'];
    
    // Find product
    $pres = $conn->query("SELECT * FROM products LIMIT 1");
    $prod = $pres->fetch_assoc();
    if (!$prod) {
        $conn->query("INSERT INTO products (id, name, price, stock) VALUES ('PTEST', 'Test Item', 100, 10)");
        $pid = 'PTEST';
        $pname = 'Test Item';
        $price = 100;
    } else {
        $pid = $prod['id'];
        $pname = $conn->real_escape_string($prod['name']);
        $price = $prod['price'];
    }
    
    $num = 'TEST-' . time() . '-' . $cid . '-' . rand(1, 1000);
    $conn->query("INSERT INTO orders (order_number, customer_id, order_date, total_amount, status) VALUES ('$num', $cid, NOW(), $price, 'Shipped')");
    $oid = $conn->insert_id;
    
    if($oid) {
        $conn->query("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price, size) VALUES ($oid, '$pid', '$pname', 1, $price, $price, 'M')");
        echo "Created for user $uid (Customer $cid)\n";
        $count++;
    } else {
        echo "Failed to create order for $cid: " . $conn->error . "\n";
    }
}
echo "Total created: $count\n";
