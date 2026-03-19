<?php
require __DIR__ . "/../../config/config.php";

$id = (int)$_GET['id'];

// Lấy phiếu
$form = $conn->query("SELECT * FROM goods_receipt WHERE id=$id")->fetch_assoc();

// Lấy sản phẩm
$details = $conn->query("
SELECT * FROM goods_receipt_items WHERE receipt_id=$id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Goods Receipt</title>
<style>
body { font-family: "Segoe UI"; background:#fafafa; }
.container {
  width: 80%; max-width: 700px;
  background:#fff; margin:50px auto;
  padding:30px; border-radius:10px;
}
.form-group { margin-bottom:15px; }
label { font-weight:bold; }
input, select { width:100%; padding:8px; }
.btn { background:#d4af37; color:#fff; padding:10px 20px; border:none; }
</style>
</head>

<body>
<div class="container">
<h2>Edit Goods Receipt</h2>

<form action="save_edit_receipt.php" method="POST">

<input type="hidden" name="id" value="<?= $form['id'] ?>">

<!-- ORDER -->
<div class="form-group">
<label>Order Number:</label>
<input type="text" name="order_number" value="<?= $form['order_number'] ?>">
</div>

<!-- DATE -->
<div class="form-group">
<label>Date:</label>
<input type="date" name="date" value="<?= $form['entry_date'] ?>">
</div>

<!-- STATUS -->
<div class="form-group">
<label>Status:</label>
<select name="status">
  <option value="Draft" <?= $form['status']=='Draft'?'selected':'' ?>>Draft</option>
  <option value="Completed" <?= $form['status']=='Completed'?'selected':'' ?>>Completed</option>
</select>
</div>

<hr>

<h3>Products</h3>

<?php if ($details->num_rows == 0): ?>
  <p>Chưa có sản phẩm</p>
<?php endif; ?>

<?php while($item = $details->fetch_assoc()): ?>
  
  <!-- Giữ ID item để update -->
  <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">

  <div class="form-group">
    <label>Product ID:</label>
    <input type="text" name="product_code[]" value="<?= $item['product_id'] ?>">
  </div>

  <div class="form-group">
    <label>Unit Price:</label>
    <input type="number" name="price[]" value="<?= $item['unit_price'] ?>">
  </div>

  <div class="form-group">
    <label>Quantity:</label>
    <input type="number" name="quantity[]" value="<?= $item['quantity'] ?>">
  </div>

  <hr>

<?php endwhile; ?>

<div class="form-actions">
  <button type="submit" class="btn">Save Changes</button>
  <a href="import_management.php" class="btn">Cancel</a>
</div>

</form>
</div>
</body>
</html>