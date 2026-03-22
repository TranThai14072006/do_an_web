<?php
require_once "../../config/config.php";

// Lấy danh sách sản phẩm từ DB cho dropdown
$products_result = $conn->query("SELECT id, name, cost_price FROM products ORDER BY id ASC");
$products_list   = $products_result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Thông báo lỗi từ redirect
$error = $_GET['error'] ?? '';
$error_msg = match($error) {
    'missing_fields' => 'Vui lòng điền đầy đủ ngày, mã phiếu và ít nhất 1 sản phẩm.',
    'no_products'    => 'Phải chọn ít nhất 1 sản phẩm hợp lệ.',
    default          => $error ? htmlspecialchars($error) : '',
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Entry Form</title>
<style>
body { font-family: sans-serif; background: #f0f2f5; margin: 0; }
.sidebar { width: 220px; background-color: #8e4b00; color: #f8ce86; display: flex; flex-direction: column; padding: 20px; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; }
.logo { text-align: center; margin-bottom: 30px; }
.logo img { width: 80px; border-radius: 50%; }
.logo h2 { font-size: 18px; margin-top: 10px; }
.menu a { display: block; padding: 12px; color: #f8ce86; text-decoration: none; border-radius: 8px; margin-bottom: 10px; font-weight: bold; }
.menu a:hover, .menu a.active { background: #f8ce86; color: #8e4b00; }
main { margin-left: 250px; padding: 0; box-sizing: border-box; }
.top-nav { background: white; padding: 18px 22px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: 700; color: #8e4b00; font-size: 18px; }
.card { background: white; border-radius: 10px; margin: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.card-header { background: #8e4b00; color: #f8ce86; padding: 18px 22px; font-weight: 700; font-size: 17px; border-radius: 10px 10px 0 0; }
.card-body { padding: 22px; }
.form-row { display: flex; gap: 22px; margin-bottom: 22px; flex-wrap: wrap; }
.form-col { flex: 1; min-width: 180px; }
.form-label { display: block; font-weight: 600; margin-bottom: 8px; }
.form-control { width: 100%; border: 1px solid #8e4b00; border-radius: 6px; padding: 10px; font-size: 14px; box-sizing: border-box; }
.product-row { display: flex; gap: 14px; margin-bottom: 14px; background: #f8f9fa; padding: 14px; border-radius: 8px; align-items: center; flex-wrap: wrap; }
.product-select { flex: 2; min-width: 160px; }
.product-price { flex: 1; min-width: 120px; }
.product-quantity { flex: 1; min-width: 80px; }
.product-total { flex: 1; color: #8e4b00; font-weight: 700; text-align: right; padding-right: 10px; min-width: 100px; }
.product-actions { flex: 0.3; display: flex; justify-content: center; }
.btn { padding: 10px 22px; border-radius: 8px; border: none; font-weight: 600; color: white; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-secondary { background: #6c757d; }
.btn-primary { background: #8e4b00; }
.btn-danger { background: #dc3545; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 16px; border-radius: 6px; }
.btn-add-product { margin-bottom: 10px; padding: 8px 18px; }
.summary-card { background: #f8f9fa; border-radius: 8px; padding: 14px; margin-top: 28px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
.summary-row:last-child { border-bottom: none; }
.summary-total { font-weight: 700; color: #8e4b00; font-size: 18px; }
.form-actions { display: flex; justify-content: flex-end; gap: 14px; margin-top: 32px; flex-wrap: wrap; }
.input-with-usd { position: relative; }
.input-with-usd .form-control { padding-right: 50px; }
.input-usd-label { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #8e4b00; font-weight: 700; font-size: 14px; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px 18px; margin-bottom: 18px; font-weight: 600; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px 18px; margin-bottom: 18px; font-weight: 600; }
select.form-control { background: white; }
.col-label { font-size: 12px; color: #888; margin-bottom: 4px; }
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
    <a href="../Price Manage/pricing.php">Pricing Management</a>
    <a href="../Administration_menu.html#users">Customers</a>
    <a href="../Order Manage/order_management.php">Order Management</a>
    <a href="import_management.php" class="active">Import Management</a>
    <a href="../Stock Manage/stocking_management.php">Stocking</a>
  </div>
</div>

<form action="save_receipt.php" method="POST" id="entry-form">
<main>
  <div class="top-nav">Add Entry Form</div>

  <div class="card">
    <div class="card-header">Receipt Information</div>
    <div class="card-body">

      <?php if ($error_msg): ?>
        <div class="alert-error">⚠ <?= $error_msg ?></div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-col">
          <label class="form-label">Date *</label>
          <input type="date" name="date" class="form-control" required
                 value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-col">
          <label class="form-label">Order Number *</label>
          <input type="text" name="order_number" class="form-control"
                 placeholder="VD: GR.021" required>
        </div>
        <div class="form-col">
          <label class="form-label">Supplier</label>
          <input type="text" name="supplier" class="form-control"
                 placeholder="Tên nhà cung cấp">
        </div>
      </div>

      <!-- Header cột -->
      <div style="display:flex;gap:14px;padding:0 14px;margin-bottom:4px;flex-wrap:wrap;">
        <div style="flex:2;min-width:160px;" class="col-label">Product</div>
        <div style="flex:1;min-width:120px;" class="col-label">Unit Price (USD)</div>
        <div style="flex:1;min-width:80px;"  class="col-label">Quantity</div>
        <div style="flex:1;min-width:100px;" class="col-label">Total</div>
        <div style="flex:0.3;"              class="col-label"></div>
      </div>

      <div id="product-container">
        <!-- Row mẫu (sẽ bị clone) -->
        <div class="product-row product-item">
          <div class="product-select">
            <select class="form-control prod-select" name="product_code[]">
              <option value="">— Chọn sản phẩm —</option>
              <?php foreach ($products_list as $p): ?>
                <option value="<?= htmlspecialchars($p['id']) ?>"
                        data-cost="<?= $p['cost_price'] ?>">
                  <?= htmlspecialchars($p['id']) ?> — <?= htmlspecialchars($p['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="product-price input-with-usd">
            <input type="text" name="price[]" class="form-control price-input"
                   placeholder="0" oninput="formatPrice(this); calcRow(this)">
            <span class="input-usd-label">USD</span>
          </div>
          <div class="product-quantity">
            <input type="number" name="quantity[]" class="form-control qty-input"
                   value="1" min="1" oninput="calcRow(this)">
          </div>
          <div class="product-total">0 USD</div>
          <div class="product-actions">
            <button type="button" class="btn btn-danger"
                    onclick="removeRow(this)">✖</button>
          </div>
        </div>
      </div>

      <button type="button" class="btn btn-primary btn-add-product"
              onclick="addRow()">+ Add Product</button>

      <!-- Summary -->
      <div class="summary-card">
        <div class="summary-row">
          <span>Total Products:</span>
          <span id="sum-products">1</span>
        </div>
        <div class="summary-row">
          <span>Total Quantity:</span>
          <span id="sum-qty">1</span>
        </div>
        <div class="summary-row summary-total">
          <span>Grand Total:</span>
          <span id="sum-total">0 USD</span>
        </div>
      </div>

      <div class="form-actions">
        <a href="import_management.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" name="status" value="Draft"
                class="btn btn-secondary">Save Draft</button>
        <button type="submit" name="status" value="Completed"
                class="btn btn-primary">✔ Save & Complete</button>
      </div>

    </div>
  </div>
</main>
</form>

<script>
// ── Auto-fill giá nhập khi chọn sản phẩm ──────────────────
document.addEventListener('change', function(e) {
  if (e.target.classList.contains('prod-select')) {
    const cost = e.target.selectedOptions[0]?.dataset.cost || '';
    const row  = e.target.closest('.product-row');
    if (cost && parseFloat(cost) > 0) {
      const priceInput = row.querySelector('.price-input');
      priceInput.value = formatNum(Math.round(parseFloat(cost)));
      calcRow(priceInput);
    }
  }
});

// ── Format số có dấu chấm ngàn ────────────────────────────
function formatNum(n) {
  return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function formatPrice(input) {
  const raw = input.value.replace(/\D/g, '');
  input.value = raw ? formatNum(parseInt(raw)) : '';
}

// ── Tính tổng từng row ────────────────────────────────────
function calcRow(el) {
  const row   = el.closest('.product-row');
  const price = parseInt((row.querySelector('.price-input').value || '0').replace(/\./g, '')) || 0;
  const qty   = parseInt(row.querySelector('.qty-input').value) || 0;
  row.querySelector('.product-total').textContent = formatNum(price * qty) + ' USD';
  updateSummary();
}

// ── Cập nhật summary ──────────────────────────────────────
function updateSummary() {
  const rows = document.querySelectorAll('.product-item');
  let totalQty = 0, totalVal = 0, productCount = 0;

  rows.forEach(row => {
    const pid   = row.querySelector('.prod-select')?.value;
    const price = parseInt((row.querySelector('.price-input').value || '0').replace(/\./g, '')) || 0;
    const qty   = parseInt(row.querySelector('.qty-input').value) || 0;
    if (pid) {
      productCount++;
      totalQty += qty;
      totalVal += price * qty;
    }
  });

  document.getElementById('sum-products').textContent = productCount;
  document.getElementById('sum-qty').textContent      = totalQty;
  document.getElementById('sum-total').textContent    = formatNum(totalVal) + ' USD';
}

// ── Thêm row sản phẩm ─────────────────────────────────────
function addRow() {
  const first = document.querySelector('.product-row');
  const clone = first.cloneNode(true);
  clone.querySelectorAll('input').forEach(i => {
    i.value = i.type === 'number' ? 1 : '';
  });
  clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
  clone.querySelector('.product-total').textContent = '0 USD';
  document.getElementById('product-container').appendChild(clone);
  updateSummary();
}

// ── Xóa row ───────────────────────────────────────────────
function removeRow(btn) {
  const rows = document.querySelectorAll('.product-item');
  if (rows.length <= 1) {
    alert('Phải có ít nhất 1 sản phẩm!');
    return;
  }
  btn.closest('.product-row').remove();
  updateSummary();
}

// ── Làm sạch giá trước khi submit ────────────────────────
document.getElementById('entry-form').addEventListener('submit', function(e) {
  const selected = [...document.querySelectorAll('.prod-select')]
    .some(s => s.value !== '');
  if (!selected) {
    alert('Phải chọn ít nhất 1 sản phẩm!');
    e.preventDefault();
    return;
  }
  // Bỏ dấu chấm ngàn trong price trước khi gửi
  document.querySelectorAll('.price-input').forEach(i => {
    i.value = i.value.replace(/\./g, '');
  });
});
</script>
</body>
</html>