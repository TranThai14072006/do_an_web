<?php
require_once "../config.php";

$category = $_POST['category'];
$profit = $_POST['profit'];

$sql = "UPDATE products 
        SET profit_percent='$profit' 
        WHERE category='$category'";

$conn->query($sql);

header("Location: pricing.php");