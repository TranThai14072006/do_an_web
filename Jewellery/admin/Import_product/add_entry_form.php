<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Entry Form</title>

<style>
/* GIỮ NGUYÊN TOÀN BỘ CSS CỦA BẠN */
body {
  font-family: sans-serif;
  background: #f0f2f5;
  margin: 0;
}
.sidebar {
  width: 220px;
  background-color: #8e4b00;
  color: #f8ce86;
  display: flex;
  flex-direction: column;
  padding: 20px;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
}

.logo {
  text-align: center;
  margin-bottom: 30px;
}
.logo img {
  width: 80px;
  border-radius: 50%;
}
.logo h2 {
  font-size: 18px;
  margin-top: 10px;
}

.menu a {
  display: block;
  padding: 12px;
  color: #f8ce86;
  text-decoration: none;
  border-radius: 8px;
  margin-bottom: 10px;
  font-weight: bold;
}
.menu a:hover {
  background: #f8ce86;
  color: #8e4b00;
}
.menu a.active {
  background: #f8ce86;
  color: #8e4b00;
}

/* MAIN WRAPPER */
main {
  margin-left: 250px;
  padding: 0;
  box-sizing: border-box;
}

/* TOP HEADER NAV */
.top-nav {
  background: white;
  padding: 18px 22px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  font-weight: 700;
  color: #8e4b00;
  font-size: 18px;
}

/* CARD */
.card {
  background: white;
  border-radius: 10px;
  margin: 22px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.card-header {
  background: linear-gradient(195deg, #8e4b00, #8e4b00);
  color: #f8ce86;
  padding: 18px 22px;
  font-weight: 700;
  font-size: 17px;
}

.card-body {
  padding: 22px;
}

/* FORM */
.form-row {
  display: flex;
  gap: 22px;
  margin-bottom: 22px;
}
.form-col {
  flex: 1;
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
}

/* Form input */
.form-control {
  width: 100%;
  border: 1px solid #8e4b00;
  border-radius: 6px;
  padding: 10px;
  font-size: 14px;
}
.form-control-with-margin {
  margin-bottom: 18px;
}

/* PRODUCT ROW */
.product-row {
  display: flex;
  gap: 14px;
  margin-bottom: 24px;
  background: #f8f9fa;
  padding: 14px;
  border-radius: 8px;
  align-items: center;
}
.product-select { flex: 2; }
.product-price { flex: 1; }
.product-quantity { flex: 1; }
.product-total {
  flex: 1;
  color: #8e4b00;
  font-weight: 700;
  text-align: right;
  padding-right: 10px;
}
.product-actions {
  flex: 0.5;
  display: flex;
  justify-content: center;
}

.btn {
  padding: 10px 22px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  color: white;
  cursor: pointer;
}
.btn-secondary { background: #6c757d; }
.btn-primary { background: #8e4b00; }

.product-actions .btn-secondary {
  width: 36px;
  height: 36px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  border-radius: 6px;
  margin: 0;
}

/* Nút thêm sản phẩm */
.btn-add-product {
  margin-bottom: 10px;
  padding: 8px 18px;
}

/* SUMMARY */
.summary-card {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 14px;
  margin-top: 28px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #dee2e6;
}
.summary-row:last-child {
  border-bottom: none;
}
.summary-total {
  font-weight: 700;
  color: #8e4b00;
  font-size: 18px;
}

/* ACTION BUTTONS */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 14px;
  margin-top: 32px;
}

/* Định dạng số USD */
.input-with-usd {
  display: flex;
  align-items: center;
  position: relative;
}
.input-with-usd .form-control {
  padding-right: 50px;
}
.input-usd-label {
  position: absolute;
  right: 10px;
  color: #8e4b00;
  font-weight: 700;
  font-size: 14px;
}
</style>
</head>

<body>

<!-- ✅ THÊM FORM -->
<form action="save_receipt.php" method="POST">

<div class="sidebar">
  <div class="logo">
    <img src="../../images/Admin_login.jpg">
    <h2>Luxury Jewelry Admin</h2>
  </div>
</div>

<main>
  <div class="top-nav">Add Entry Form</div>

  <div class="card">
    <div class="card-header">Information</div>
    <div class="card-body">

      <div class="form-row">
        <div class="form-col">
          <label class="form-label">Date *</label>

          <!-- ✅ THÊM id + name -->
          <input type="date" id="dateInput" name="date" class="form-control form-control-with-margin" required>

        </div>

        <div class="form-col">
          <label class="form-label">Order Number</label>

          <!-- ✅ THÊM name -->
          <input type="text" id="orderInput" name="order_number"
            class="form-control form-control-with-margin" required>
        </div>
      </div>

      <div class="form-label">Product list</div>

      <!-- ✅ THÊM id container -->
      <div id="product-container">

      <div class="product-row product-item">

        <div class="product-select">
          <!-- ✅ THÊM name -->
          <select class="form-control product-select" name="product_code[]">
            <option value="">Select Product</option>
            <option value="R001">R001 - Kane Moissanite Ring</option>
            <option value="R002">R002 - Winston Anchor Ring</option>
          </select>
        </div>

        <div class="product-price input-with-usd">
          <!-- ✅ THÊM name + class -->
          <input type="text" name="price[]" class="form-control price-input" oninput="formatPrice(this)">
          <span class="input-usd-label">USD</span>
        </div>

        <div class="product-quantity">
          <!-- ✅ THÊM name + class -->
          <input type="number" name="quantity[]" class="form-control qty-input" value="1" min="1">
        </div>

        <div class="product-total">0 USD</div>

        <div class="product-actions">
          <button type="button" class="btn btn-secondary"
            onclick="this.closest('.product-row').remove()">✖</button>
        </div>

      </div>

      </div>

      <button type="button" class="btn btn-primary btn-add-product">Add Product</button>

      <div class="form-actions">
        <a href="import_management.html" class="btn btn-secondary">Cancel</a>

        <!-- ✅ SỬA BUTTON -->
        <button type="submit" name="status" value="Draft" class="btn">Save Draft</button>

        <button type="submit" name="status" value="Completed" class="btn btn-primary">Save</button>
      </div>

    </div>
  </div>
</main>

</form>

<script>
// GIỮ NGUYÊN CODE CỦA BẠN + CHỈ SỬA NHẸ

const orderInput = document.getElementById('orderInput');
orderInput.addEventListener('input', () => {
  orderInput.value = orderInput.value.replace(/[^0-9]/g,''); 
  orderInput.value = orderInput.value ? 'ORD.' + orderInput.value : '';
});

// format price
function formatPrice(input) {
  let value = input.value.replace(/\D/g, '');
  input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// ✅ SỬA ADD PRODUCT
document.querySelector(".btn-add-product").addEventListener("click", function(){
  const firstRow = document.querySelector(".product-row");
  const clone = firstRow.cloneNode(true);

  clone.querySelectorAll('input').forEach(input => {
    input.value = input.type === 'number' ? 1 : '';
  });

  clone.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

  document.getElementById("product-container").appendChild(clone);
});

document.querySelector("form").addEventListener("submit", function(){
  document.querySelectorAll(".price-input").forEach(input => {
    input.value = input.value.replace(/\./g, '');
  });
});

document.querySelector("form").addEventListener("submit", function(e){
  const products = document.querySelectorAll(".product-item");
  let valid = false;

  products.forEach(p => {
    if(p.querySelector(".product-select").value !== ""){
      valid = true;
    }
  });

  if(!valid){
    alert("Phải chọn ít nhất 1 sản phẩm!");
    e.preventDefault();
  }
});

document.addEventListener("input", function(){
  document.querySelectorAll(".product-row").forEach(row=>{
    let price = row.querySelector(".price-input").value.replace(/\./g,'') || 0;
    let qty = row.querySelector(".qty-input").value || 0;
    let total = price * qty;
    row.querySelector(".product-total").innerText = total.toLocaleString() + " USD";
  });
});

</script>

</body>
</html>