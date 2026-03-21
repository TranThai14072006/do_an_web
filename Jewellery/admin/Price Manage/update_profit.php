<?php
require_once "../../config/config.php";

$id = $_POST['id'];
$profit = $_POST['profit'];

$sql = "UPDATE products SET profit_percent='$profit' WHERE id='$id'";
$conn->query($sql);

header("Location: pricing.php");