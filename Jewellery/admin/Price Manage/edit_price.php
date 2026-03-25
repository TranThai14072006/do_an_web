<?php
require_once "../../config/config.php";

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
    header('Location: pricing.php');
    exit;
}

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: pricing.php?tab=tab1');
    exit;
}

// Lấy lịch sử nhập theo lô cho sản phẩm này
$stmt2 = $conn->prepare("
    SELECT gr.order_number, gr.entry_date, gr.supplier,
           gri.quantity, gri.unit_price, gri.total_price
    FROM goods_receipt_items gri
    JOIN goods_receipt gr ON gri.receipt_id = gr.id
    WHERE gri.product_id = ? AND gr.status = 'Completed'
    ORDER BY gr.entry_date DESC
");
$stmt2->bind_param('s', $id);
$stmt2->execute();
$lot_history = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Xử lý lưu
$save_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cost   = floatval($_POST['cost_price']     ?? 0);
    $profit = intval($_POST['profit_percent']   ?? 0);
    $price  = round($cost * (1 + $profit / 100.0), 2);

    $stmt3 = $conn->prepare("UPDATE products SET cost_price = ?, profit_percent = ?, price = ? WHERE id = ?");
    $stmt3->bind_param('diis', $cost, $profit, $price, $id);
    $stmt3->execute();
    $stmt3->close();

    // Reload lại dữ liệu
    $stmt4 = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt4->bind_param('s', $id);
    $stmt4->execute();
    $product = $stmt4->get_result()->fetch_assoc();
    $stmt4->close();
    $save_success = true;
}

$sell_price = $product['cost_price'] * (1 + $product['profit_percent'] / 100.0);
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Edit Price</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:#f3f3f3;color:#333;display:flex;}
.sidebar{width:220px;background:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;height:100vh;position:fixed;left:0;top:0;}
.logo{text-align:center;margin-bottom:30px;}
.logo img{width:80px;border-radius:50%;}
.logo h2{font-size:18px;margin-top:10px;}
.menu a{display:block;padding:12px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:10px;font-weight:bold;transition:0.3s;}
.menu a:hover,.menu a.active{background:#f8ce86;color:#8e4b00;}
main{margin-left:220px;flex:1;padding:25px;}
h1{color:#8e4b00;margin-bottom:20px;font-size:22px;}
.card{background:#fff;border-radius:10px;box-shadow:0 3px 8px rgba(0,0,0,0.1);padding:25px;margin-bottom:20px;max-width:1100px;}
.card h3{color:#8e4b00;margin-bottom:15px;font-size:16px;}
.form-layout{display:flex;flex-wrap:wrap;gap:30px;}
.form-column{flex:2;min-width:380px;display:flex;flex-direction:column;}
.form-group{display:flex;flex-direction:column;margin-bottom:15px;}
.form-group label{font-weight:600;margin-bottom:6px;font-size:14px;}
input,textarea{padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:14px;width:100%;}
input:focus{border-color:#8e4b00;outline:none;}
input[readonly]{background:#f8f9fa;color:#666;}
textarea{resize:vertical;min-height:80px;}
.image-column{flex:1;min-width:240px;display:flex;flex-direction:column;align-items:center;}
.image-preview{width:200px;height:200px;border:2px solid #ddd;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fafafa;}
.image-preview img{max-width:100%;max-height:100%;object-fit:contain;}
.form-actions{display:flex;justify-content:center;gap:20px;margin-top:24px;padding-top:18px;border-top:1px solid #eee;}
.btn{text-decoration:none;padding:10px 22px;border-radius:7px;font-weight:600;cursor:pointer;border:none;font-size:14px;transition:.2s;}
.btn-secondary{background:#ccc;color:#333;}
.btn-secondary:hover{background:#b3b3b3;}
.btn-primary{background:#8e4b00;color:#f8ce86;}
.btn-primary:hover{background:#a3670b;transform:translateY(-1px);}
.price-preview{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px 18px;margin-top:8px;font-size:15px;}
.price-preview strong{color:#8e4b00;font-size:18px;}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-weight:600;}
/* Lot history table */
.lot-table{width:100%;border-collapse:collapse;margin-top:10px;}
.lot-table th{background:#8e4b00;color:#f8ce86;padding:10px 12px;text-align:center;font-size:13px;}
.lot-table td{padding:9px 12px;text-align:center;border-bottom:1px solid #f0e2d0;font-size:13px;}
.lot-table tr:hover td{background:#f9f2e7;}
.no-data{color:#888;text-align:center;padding:20px;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo">
    <img src="../../images/Admin_login.jpg" alt="Admin">
    <h2>Luxury Jewelry Admin</h2>
  </div>
  <div class="menu">
    <a href="../Administration_menu.html#products">Jewelry List</a>
    <a href="../Administration_menu.html#product-manage">Product Management</a>
    <a href="../Administration_menu.html#users">Customers</a>
    <a href="pricing.php" class="active">Pricing Management</a>
    <a href="../Import_product/import_management.php">Import Management</a>
    <a href="../Order Manage/order_management.php">Order Management</a>
    <a href="../Stock Manage/stocking_management.php">Stocking Management</a>
  </div>
</div>

<main>
  <h1>Edit Price — <?= htmlspecialchars($product['id']) ?></h1>

  <?php if ($save_success): ?>
    <div class="alert-success">✅ Đã lưu thành công! Giá bán mới: <strong>$<?= number_format($sell_price, 2) ?></strong></div>
  <?php endif; ?>

  <!-- FORM -->
  <div class="card">
    <h3>Product Information</h3>
    <form method="POST">
      <div class="form-layout">
        <div class="form-column">

          <div class="form-group">
            <label>Product Code</label>
            <input type="text" value="<?= htmlspecialchars($product['id']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Product Name</label>
            <input type="text" value="<?= htmlspecialchars($product['name']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Category</label>
            <input type="text" value="<?= htmlspecialchars($product['category']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" value="<?= intval($product['stock']) ?>" readonly>
          </div>

          <div class="form-group">
            <label>Avg Cost Price (USD) <span style="color:#888;font-weight:normal;font-size:12px">— tính từ giá bình quân</span></label>
            <input type="number" step="0.01" name="cost_price"
                   id="cost_input" value="<?= $product['cost_price'] ?>"
                   oninput="calcPreview()">
          </div>

          <div class="form-group">
            <label>Profit % <span style="color:#888;font-weight:normal;font-size:12px">— thay đổi sẽ cập nhật giá bán</span></label>
            <input type="number" step="1" min="0" max="999" name="profit_percent"
                   id="profit_input" value="<?= $product['profit_percent'] ?>"
                   oninput="calcPreview()">
          </div>

          <!-- Preview giá bán realtime -->
          <div class="price-preview">
            Selling Price preview: <strong id="price_preview">$<?= number_format($sell_price, 2) ?></strong>
            <br><small style="color:#888">= Cost × (1 + Profit%)</small>
          </div>

        </div>

        <div class="image-column">
          <label style="font-weight:600;margin-bottom:10px;">Product Image</label>
          <div class="image-preview">
            <img src="../../images/<?= htmlspecialchars($product['image']) ?>"
                 onerror="this.style.opacity='.2'" alt="<?= htmlspecialchars($product['name']) ?>">
          </div>
        </div>
      </div>

      <div class="form-actions">
        <a href="pricing.php?tab=tab1" class="btn btn-secondary">Back</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>

  <!-- LOT HISTORY -->
  <div class="card">
    <h3>Import Lot History — giá vốn theo từng lần nhập</h3>
    <?php if (empty($lot_history)): ?>
      <p class="no-data">Chưa có lô nhập nào cho sản phẩm này.</p>
    <?php else: ?>
      <table class="lot-table">
        <thead>
          <tr>
            <th>Receipt No.</th>
            <th>Date</th>
            <th>Supplier</th>
            <th>Qty</th>
            <th>Lot Unit Cost (USD)</th>
            <th>Lot Total (USD)</th>
            <th>Lot Sell Price* (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lot_history as $lot):
            $lot_sell = $lot['unit_price'] * (1 + $product['profit_percent'] / 100.0);
          ?>
          <tr>
            <td><?= htmlspecialchars($lot['order_number']) ?></td>
            <td><?= htmlspecialchars($lot['entry_date']) ?></td>
            <td><?= htmlspecialchars($lot['supplier'] ?? '—') ?></td>
            <td><?= intval($lot['quantity']) ?></td>
            <td><strong><?= number_format($lot['unit_price'], 2) ?></strong></td>
            <td><?= number_format($lot['total_price'], 2) ?></td>
            <td>$<?= number_format($lot_sell, 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p style="font-size:12px;color:#888;margin-top:10px;">* Lot Sell Price = giá bán nếu tính theo giá vốn lô đó × % lợi nhuận hiện tại</p>
    <?php endif; ?>
  </div>
</main>

<script>
function calcPreview() {
  const cost   = parseFloat(document.getElementById('cost_input').value)   || 0;
  const profit = parseFloat(document.getElementById('profit_input').value) || 0;
  const sell   = cost * (1 + profit / 100);
  document.getElementById('price_preview').textContent = '$' + sell.toFixed(2);
}
</script>
</body>
</html>