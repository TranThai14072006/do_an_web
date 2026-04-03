<?php
require __DIR__ . '/Jewellery/config/config.php';
$res = $conn->query("SELECT * FROM cart");
echo "CART TABLE ROWS:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "\n";
