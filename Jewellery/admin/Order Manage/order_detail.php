<?php
require_once "../../config/config.php";
// $conn → jewelry_db (orders, order_items, products, customers, users)

// ============================================================
// Lấy order ID
// ============================================================
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    header('Location: order_management.php');
    exit;
}

// ============================================================
// Xử lý cập nhật trạng thái (POST) → jewelry_db
// ============================================================
$update_success = false;
$update_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $allowed    = ['Pending', 'Processed', 'Delivered'];
    $new_status = trim($_POST['status']);

    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $order_id);
        $update_success = $stmt->execute();
        if (!$update_success) {
            $update_error = $conn->error;
        } else {
            // ── ĐỒNG BỘ HÓA KHO ──────────────────────────────
            require_once "../admin_sync.php";
            $res_items = $conn->query("SELECT product_id FROM order_items WHERE order_id = $order_id");
            while ($item = $res_items->fetch_assoc()) {
                syncProduct($conn, $item['product_id']);
            }
        }
        $stmt->close();
    } else {
        $update_error = 'Invalid status value.';
    }
}

// Get order info from jewelry_db
$stmt = $conn->prepare("
    SELECT id, order_number, customer_id, order_date, total_amount, status
    FROM orders
    WHERE id = ?
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<p style='padding:40px;color:red;font-family:sans-serif;'>❌ Order not found.</p>";
    $conn->close();
    exit;
}

// ============================================================
// Lấy thông tin khách hàng từ jewelry_db
// customers JOIN users để lấy đủ thông tin + email
// ============================================================
$stmt_c = $conn->prepare("
    SELECT c.full_name, c.phone, c.address, u.email
    FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt_c->bind_param('i', $order['customer_id']);
$stmt_c->execute();
$customer = $stmt_c->get_result()->fetch_assoc();
$stmt_c->close();

if (!$customer) {
    $customer = ['full_name' => 'N/A', 'email' => 'N/A', 'phone' => 'N/A', 'address' => 'N/A'];
}

// ============================================================
// Lấy danh sách sản phẩm từ jewelry_db
// ============================================================
$stmt2 = $conn->prepare("
    SELECT oi.product_id, oi.product_name, oi.quantity, oi.unit_price, oi.total_price,
           p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$stmt2->bind_param('i', $order_id);
$stmt2->execute();
$items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

$total_qty = array_sum(array_column($items, 'quantity'));
$grand_total = array_sum(array_column($items, 'total_price'));

$status_class = [
    'Pending'   => 'status-pending',
    'Processed' => 'status-processed',
    'Delivered' => 'status-delivered',
    'Cancelled' => 'status-cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Detail | Luxury Jewelry Admin</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{background:#f5f5f5;color:#333;display:flex;}

    .sidebar{width:220px;background:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;height:100vh;position:fixed;top:0;left:0;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;border-radius:50%;}
    .logo h2{font-size:18px;margin-top:10px;}
    .menu a{display:block;padding:12px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:10px;font-weight:bold;transition:.3s;}
    .menu a:hover,.menu a.active{background:#f8ce86;color:#8e4b00;}

    main{flex:1;padding:30px 40px;margin-left:220px;}
    header h1{font-size:24px;color:#8e4b00;margin-bottom:20px;}

    .card{background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.05);padding:25px;margin-bottom:25px;}
    .card-header{font-size:18px;color:#8e4b00;font-weight:bold;margin-bottom:20px;border-bottom:2px solid #f1e4e1;padding-bottom:10px;}

    .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;}
    .info-label{font-weight:600;color:#555;margin-bottom:4px;}
    .info-value{font-size:15px;}

    table{width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-top:20px;}
    th{background:#8e4b00;color:#f8ce86;padding:12px;text-align:center;}
    td{text-align:center;padding:12px;border-bottom:1px solid #eee;font-size:14px;}
    tr:nth-child(even) td{background:#faf6f2;}
    tr:hover td{background:#f3ede6;}
    .product-image{width:60px;height:60px;border-radius:6px;object-fit:cover;border:1px solid #ddd;}

    .summary-card{background:#f8f9fa;border-radius:8px;padding:15px;margin-top:20px;}
    .summary-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #ddd;}
    .summary-total{font-weight:bold;color:#8e4b00;font-size:16px;}

    .btn{display:inline-block;padding:10px 18px;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none;transition:.3s;border:none;font-size:14px;}
    .btn-primary{background:#8e4b00;color:#f8ce86;}
    .btn-secondary{background:#6c757d;color:#fff;}
    .btn-primary:hover{background:#a3670b;transform:translateY(-2px);}
    .btn-secondary:hover{background:#555;}
    .action-buttons{display:flex;justify-content:flex-end;gap:10px;margin-top:25px;}

    .status-badge{display:inline-block;padding:5px 10px;border-radius:6px;font-size:13px;font-weight:600;}
    .status-pending{background:#fff3cd;color:#856404;}
    .status-processed{background:#d1ecf1;color:#0c5460;}
    .status-delivered{background:#d4edda;color:#155724;}
    .status-cancelled{background:#f8d7da;color:#721c24;}

    .status-form{margin-top:20px;display:flex;align-items:center;gap:15px;flex-wrap:wrap;}
    .status-form select{padding:8px 12px;border:1px solid #ccc;border-radius:6px;font-size:14px;}

    .alert{padding:12px 18px;border-radius:8px;margin-bottom:20px;font-weight:600;}
    .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
    .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}

    .popup{position:fixed;top:0;left:0;width:100%;height:100%;display:none;justify-content:center;align-items:center;background:rgba(0,0,0,0.5);z-index:3000;}
    .popup-box{background:#fff;border-radius:10px;padding:25px 35px;width:400px;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,0.2);}
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
      <label for="print_order" class="btn btn-primary" style="display:inline-block;margin-top:15px;">OK</label>
    </div>
  </div>

<?php include '../sidebar_include.php'; ?>

  <main>
    <header>
      <h1>Order Detail - #<?= htmlspecialchars($order['order_number']) ?></h1>
    </header>

    <?php if ($update_success): ?>
      <div class="alert alert-success">✅ Order status has been successfully updated.</div>
    <?php elseif ($update_error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($update_error) ?></div>
    <?php endif; ?>

    <!-- CARD 1: Customer (từ user_db: customers JOIN users) -->
    <div class="card">
      <div class="card-header">Customer Information</div>
      <div class="info-grid">
        <div>
          <div class="info-label">Full Name</div>
          <div class="info-value"><?= htmlspecialchars($customer['full_name']) ?></div>
        </div>
        <div>
          <div class="info-label">Phone Number</div>
          <div class="info-value"><?= htmlspecialchars($customer['phone']) ?></div>
        </div>
        <div>
          <div class="info-label">Email</div>
          <div class="info-value"><?= htmlspecialchars($customer['email']) ?></div>
        </div>
        <div>
          <div class="info-label">Address</div>
          <div class="info-value"><?= htmlspecialchars($customer['address']) ?></div>
        </div>
      </div>
    </div>

    <!-- CARD 2: Order Info + Update Status -->
    <div class="card">
      <div class="card-header">Order Information</div>
      <div class="info-grid">
        <div>
          <div class="info-label">Order ID</div>
          <div class="info-value">#<?= htmlspecialchars($order['order_number']) ?></div>
        </div>
        <div>
          <div class="info-label">Order Date</div>
          <div class="info-value"><?= htmlspecialchars($order['order_date']) ?></div>
        </div>
        <div>
          <div class="info-label">Current Status</div>
          <div class="info-value">
            <span class="status-badge <?= $status_class[$order['status']] ?? '' ?>">
              <?= htmlspecialchars($order['status']) ?>
            </span>
          </div>
        </div>
      </div>

      <form method="POST" action="order_detail.php?id=<?= $order_id ?>" class="status-form">
        <label for="status"><strong>Update Status:</strong></label>
        <select id="status" name="status">
          <?php foreach (['Pending','Processed','Delivered'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Update</button>
      </form>
    </div>

    <!-- CARD 3: Products -->
    <div class="card">
      <div class="card-header">Product List</div>
      <table>
        <thead>
          <tr>
            <th>No.</th><th>Product ID</th><th>Image</th><th>Product Name</th>
            <th>Quantity</th><th>Unit Price</th><th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="7" style="color:#888;padding:25px;">No products found for this order.</td></tr>
          <?php else: ?>
            <?php foreach ($items as $idx => $item): ?>
              <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($item['product_id']) ?></td>
                <td>
                  <img src="../../images/<?= htmlspecialchars($item['image'] ?? ($item['product_id'] . '.jpg')) ?>"
                       class="product-image" onerror="this.style.opacity='0.2'">
                </td>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= intval($item['quantity']) ?></td>
                <td>$<?= number_format($item['unit_price'], 2) ?></td>
                <td>$<?= number_format($item['total_price'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="summary-card">
        <div class="summary-row"><span>Total Products:</span><span><?= count($items) ?></span></div>
        <div class="summary-row"><span>Total Quantity:</span><span><?= $total_qty ?></span></div>
        <div class="summary-row summary-total">
          <span>Grand Total:</span>
          <span>$<?= number_format($grand_total, 2) ?></span>
        </div>
      </div>
    </div>

    <div class="action-buttons">
      <a href="order_management.php" class="btn btn-secondary">Back</a>
      <button type="button" class="btn btn-primary"
              onclick="document.getElementById('print_order').checked = true;">
        Print Order
      </button>
    </div>
  </main>
</body>
</html>
<?php
$conn->close();
?>
