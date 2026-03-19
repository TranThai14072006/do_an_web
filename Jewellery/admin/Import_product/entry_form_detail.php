<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Entry Form Detail | Luxury Jewelry Admin</title>
  <style>
    /* ====== RESET ====== */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }

    body {
      background-color: #f5f5f5;
      color: #333;
      display: flex;
    }

    /* ====== SIDEBAR ====== */
    .sidebar {
      width: 220px;
      background-color: #8e4b00;
      color: #f8ce86;
      display: flex;
      flex-direction: column;
      padding: 20px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
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
      transition: background 0.3s, color 0.3s;
    }

    .menu a:hover,
    .menu a.active {
      background-color: #f8ce86;
      color: #8e4b00;
    }

    /* ====== MAIN ====== */
    main {
      flex: 1;
      padding: 30px 40px;
      margin-left: 220px;
    }

    header h1 {
      font-size: 24px;
      color: #8e4b00;
      margin-bottom: 20px;
    }

    /* ====== CARD ====== */
    .card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 25px;
      margin-bottom: 25px;
    }

    .card-header {
      font-weight: bold;
      font-size: 18px;
      color: #8e4b00;
      margin-bottom: 20px;
      border-bottom: 2px solid #f1e4e1;
      padding-bottom: 10px;
    }

    /* ====== INFO GRID ====== */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }

    .info-item {
      margin-bottom: 10px;
    }

    .info-label {
      font-weight: 600;
      color: #555;
      margin-bottom: 4px;
    }

    .info-value {
      font-size: 15px;
    }

    /* ====== TABLE ====== */
    table {
      width: 100%;
      border-collapse: collapse;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      margin-top: 20px;
    }

    th {
      background-color: #8e4b00;
      color: #f8ce86;
      font-weight: 600;
      text-align: center;
      padding: 12px 10px;
      font-size: 15px;
    }

    td {
      text-align: center;
      padding: 12px 10px;
      border-bottom: 1px solid #eee;
      font-size: 15px;
    }

    tr:nth-child(even) td { background: #faf6f2; }
    tr:hover td { background: #f3ede6; }

    .text-right { text-align: right; }
    .text-center { text-align: center; }

    .product-image {
      width: 60px;
      height: 60px;
      border-radius: 6px;
      object-fit: cover;
      border: 1px solid #ddd;
    }

    /* ====== SUMMARY ====== */
    .summary-card {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-top: 20px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #ddd;
    }

    .summary-total {
      font-weight: bold;
      color: #8e4b00;
      font-size: 16px;
    }

    .summary-card {
  background: #fff8f0;
  border-radius: 10px;
  padding: 20px;
  margin-top: 20px;
  border: 1px solid #f1e4e1;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
  font-size: 15px;
}

.summary-row:last-child {
  border-bottom: none;
}

.summary-total {
  font-weight: bold;
  font-size: 18px;
  color: #8e4b00;
}

    /* ====== BUTTONS ====== */
    .btn {
      display: inline-block;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: 0.3s;
    }

    .btn-primary {
      background-color: #8e4b00;
      color: #f8ce86;
      border: none;
    }

    .btn-secondary {
      background-color: #6c757d;
      color: white;
      border: none;
    }

    .btn-primary:hover {
      background-color: #a3670b;
      transform: translateY(-2px);
    }

    .btn-secondary:hover {
      background-color: #555;
    }

    .action-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 25px;
    }

    /* ====== STATUS BADGE ====== */
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      text-transform: capitalize;
    }

    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-processed { background-color: #d1ecf1; color: #0c5460; }
    .status-delivered { background-color: #d4edda; color: #155724; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }

    /* ====== STATUS FORM ====== */
    .status-form {
      margin-top: 20px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .status-form label {
      font-weight: 600;
      color: #555;
    }

    .status-form select {
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    .popup{
      position:fixed;top:0;left:0;width:100%;height:100%;
      display:none;justify-content:center;align-items:center;
      background:rgba(0,0,0,0.5);z-index:3000;
    }
    .popup-box{
      background:#fff;border-radius:10px;padding:25px 35px;width:400px;
      text-align:center;box-shadow:0 5px 15px rgba(0,0,0,0.2);
    }
    #resetPopup:checked ~ .popup-update{display:flex;}
    #print_order:checked ~ .popup-print{display:flex;}
    .overlay{position:absolute;width:100%;height:100%;top:0;left:0;}

  </style>
</head>
<body>


  <input type="checkbox" id="print_order" hidden>
  <div class="popup popup-print">
    <label for="print_order" class="overlay"></label>
    <div class="popup-box">
      <h3>🖨 Print Order</h3>
      <p>Order is printing, please wait...</p>
      <label for="print_order" class="btn btn-primary">OK</label>
    </div>
  </div>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo">
      <img src="../../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>

    <div class="menu">
      <a href="../Administration_menu.html#products">Jewelry List</a>
      <a href="../Administration_menu.html#product-manage">Product Management</a>
      <a href="../Price Manage/pricing.html">Pricing Management</a>
      <a href="import_management.php" class="active">Import Management</a>
      <a href="../Order Manage/order_management.html">Order Management</a>
      <a href="../Stock Manage/stocking_management.html">Stocking</a>
      <a href="../Administration_menu.html#setting">Settings</a>
    </div>
  </div>

  <!-- MAIN -->
  <main>
    <header><h1>Purchase Order - PO.001</h1></header>

    <div class="card">
      <div class="card-header">Order Information</div>
      <div class="info-grid">
        <div class="info-item"><div class="info-label">ENTRY FORM ID</div><div class="info-value">#IMP036</div></div>
        <div class="info-item"><div class="info-label">Date</div><div class="info-value">01-10-2025</div></div>
      </div>

    <div class="card">
      <div class="card-header">Product List</div>
      <table>
        <thead>
          <tr>
            <th>No.</th>
            <th>Product ID</th>
            <th>Image</th>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Unit Price (USD)</th>
            <th>Total (USD)</th>
          </tr>
        </thead>
        <?php
        include __DIR__ . "/../../config/config.php";
        $id = $_GET['id'];

        $form = $conn->query("SELECT * FROM goods_receipt WHERE id=$id")->fetch_assoc();
        ?>

        <h1>Purchase Order - <?= $form['order_number'] ?></h1>
        <?php
        $details = $conn->query("
        SELECT d.*, p.image
        FROM goods_receipt_items d
        LEFT JOIN products p ON d.product_id = p.id
        WHERE d.receipt_id = $id
        ");

        $i=1;
        ?>

        <tbody>
        <?php while($row = $details->fetch_assoc()): ?>
        <tr>
        <td><?= $i++ ?></td>
        <td><?= $row['product_id'] ?></td>
        <td><img src="../../images/<?= $row['image'] ?>" class="product-image"></td>
        <td><?= $row['product_name'] ?></td>
        <td><?= $row['quantity'] ?></td>
        <td><?= $row['unit_price'] ?></td>
        <td><?= $row['total_price'] ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>

        <div class="summary-card">
        <div class="summary-row">
            <span>Total Quantity</span>
            <span><?= $form['total_quantity'] ?></span>
        </div>

        <div class="summary-row">
            <span>Total Value</span>
            <span><?= number_format($form['total_value'], 2) ?> USD</span>
        </div>

        <div class="summary-row summary-total">
            <span>Grand Total</span>
            <span><?= number_format($form['total_value'], 2) ?> USD</span>
        </div>
        </div>
    </div>

    <div class="action-buttons">
      <a href="import_management.php" class="btn btn-secondary">Back</a>
      <button type="button" class="btn btn-primary"
         onclick="document.getElementById('print_order').checked = true;">
        Print Order
      </button>
    </div>
  </main>
</body>
</html>

