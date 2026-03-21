<?php
require_once "../../config/config.php";
// $conn      → jewelry_db  (orders, order_items, products)
// $conn_user → user_db     (customers, users)

// ============================================================
// Tham số tìm kiếm từ GET
// ============================================================
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date   = isset($_GET['to_date'])   ? trim($_GET['to_date'])   : '';
$status    = isset($_GET['status'])    ? trim($_GET['status'])    : 'All';
$searched  = isset($_GET['searched']);

// ============================================================
// Lấy toàn bộ customers JOIN users từ user_db
// customers.id = customer_id trong jewelry_db.orders
// ============================================================
$cust_map    = [];
$cust_result = $conn_user->query("
    SELECT c.id, c.full_name, c.phone, c.address, u.email
    FROM customers c
    JOIN users u ON c.user_id = u.id
");
if ($cust_result !== false) {
    while ($row = $cust_result->fetch_assoc()) {
        $cust_map[$row['id']] = $row;
    }
}

// ============================================================
// Build câu query orders từ jewelry_db
// ============================================================
$where  = [];
$params = [];
$types  = '';

if ($from_date !== '') {
    $where[]  = 'order_date >= ?';
    $params[] = $from_date;
    $types   .= 's';
}
if ($to_date !== '') {
    $where[]  = 'order_date <= ?';
    $params[] = $to_date;
    $types   .= 's';
}
if ($status !== '' && $status !== 'All') {
    $where[]  = 'status = ?';
    $params[] = $status;
    $types   .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT id, order_number, customer_id, order_date, total_amount, status
        FROM orders
        $where_sql
        ORDER BY order_date DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$raw_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================================
// Ghép thông tin customer vào từng order
// ============================================================
$orders = [];
foreach ($raw_orders as $o) {
    $cid            = $o['customer_id'];
    $o['full_name'] = $cust_map[$cid]['full_name'] ?? 'N/A';
    $o['phone']     = $cust_map[$cid]['phone']     ?? 'N/A';
    $o['address']   = $cust_map[$cid]['address']   ?? 'N/A';
    $orders[]       = $o;
}

$status_options = ['All', 'Pending', 'Processed', 'Delivered', 'Cancelled'];
$status_class   = [
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
  <title>Order Management</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #f5f5f5; color: #333; display: flex; }

    .sidebar {
      width: 220px; background-color: #8e4b00; color: #f8ce86;
      display: flex; flex-direction: column; padding: 20px;
      height: 100vh; position: fixed; top: 0; left: 0;
    }
    .logo { text-align: center; margin-bottom: 30px; }
    .logo img { width: 80px; border-radius: 50%; }
    .logo h2 { font-size: 18px; margin-top: 10px; }
    .menu a {
      display: block; padding: 12px; color: #f8ce86; text-decoration: none;
      border-radius: 8px; margin-bottom: 10px; font-weight: bold;
      transition: background 0.3s, color 0.3s;
    }
    .menu a:hover, .menu a.active { background-color: #f8ce86; color: #8e4b00; }

    main { flex: 1; padding: 25px 40px; margin-left: 220px; }
    header h1 { font-size: 24px; color: #8e4b00; margin-bottom: 20px; }

    .search-section {
      background: #fff; border-radius: 10px; padding: 20px;
      display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 25px;
    }
    .search-group { flex: 1; min-width: 150px; display: flex; flex-direction: column; }
    .search-label { font-weight: 600; color: #333; margin-bottom: 6px; }
    .search-input { height: 42px; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; width: 100%; }
    .btn-search, .btn-reset { height: 42px; padding: 0 20px; font-weight: 600; border-radius: 6px; cursor: pointer; border: none; font-size: 15px; white-space: nowrap; }
    .btn-search { background-color: #8e4b00; color: #fff; }
    .btn-reset  { background-color: #888; color: #fff; }
    .btn-search:hover { background-color: #a3670b; }
    .btn-reset:hover  { background-color: #666; }

    table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.08); }
    th { background-color: #8e4b00; color: #f8ce86; font-weight: 600; text-align: center; padding: 14px 10px; font-size: 15px; }
    td { text-align: center; padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
    tr:nth-child(even) td { background: #faf6f2; }
    tr:hover td { background: #f3ede6; }

    .btn-view { background-color: #8e4b00; color: #f8ce86; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; transition: 0.2s; display: inline-block; }
    .btn-view:hover { background-color: #a3670b; color: #fff; }

    .status { padding: 5px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; display: inline-block; }
    .status-pending   { background-color: #fff3cd; color: #856404; }
    .status-processed { background-color: #d1ecf1; color: #0c5460; }
    .status-delivered { background-color: #d4edda; color: #155724; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; }
    .pagination-btn { border: 1px solid #ddd; background: white; color: #8e4b00; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-weight: bold; }
    .pagination-btn:hover  { background-color: #f8ce86; }
    .pagination-btn.active { background-color: #8e4b00; color: #fff; border-color: #8e4b00; }
    .pagination-info { text-align: center; font-size: 14px; color: #666; margin-top: 10px; }

    .no-result { text-align: center; padding: 30px; color: #888; font-size: 15px; }
    .result-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 8px; padding: 10px 16px; margin-bottom: 15px; font-size: 14px; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="logo">
      <img src="../../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>
    <div class="menu">
      <a href="../Administration_menu.html#products">Jewelry List</a>
      <a href="../Administration_menu.html#product-manage">Product Management</a>
      <a href="../Price Manage/pricing.php">Pricing Management</a>
      <a href="../Administration_menu.html#users">Customers</a>
      <a href="order_management.php" class="active">Order Management</a>
      <a href="../Stock Manage/stocking_management.php">Stocking</a>
      <a href="../Administration_menu.html#setting">Settings</a>
    </div>
  </div>

  <main>
    <header><h1>Order Management</h1></header>

    <form method="GET" action="order_management.php">
      <input type="hidden" name="searched" value="1">
      <div class="search-section">
        <div class="search-group">
          <label class="search-label">From date</label>
          <input type="date" name="from_date" class="search-input" value="<?= htmlspecialchars($from_date) ?>">
        </div>
        <div class="search-group">
          <label class="search-label">To date</label>
          <input type="date" name="to_date" class="search-input" value="<?= htmlspecialchars($to_date) ?>">
        </div>
        <div class="search-group">
          <label class="search-label">Status</label>
          <select name="status" class="search-input">
            <?php foreach ($status_options as $opt): ?>
              <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-search">Search</button>
        <a href="order_management.php" class="btn-reset" style="display:flex;align-items:center;justify-content:center;text-decoration:none;">Reset</a>
      </div>
    </form>

    <?php if ($searched): ?>
      <div class="result-info">
        🔍 Tìm thấy <strong><?= count($orders) ?></strong> đơn hàng
        <?= $from_date ? " từ <strong>" . htmlspecialchars($from_date) . "</strong>" : '' ?>
        <?= $to_date   ? " đến <strong>" . htmlspecialchars($to_date)   . "</strong>" : '' ?>
        <?= ($status !== 'All') ? " — trạng thái: <strong>" . htmlspecialchars($status) . "</strong>" : '' ?>
      </div>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>No.</th><th>Order No.</th><th>Customer Name</th>
          <th>Address</th><th>Phone Number</th><th>Date</th>
          <th>Total Amount</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="9" class="no-result">
            <?= $searched ? '❌ Không tìm thấy đơn hàng phù hợp.' : 'Chưa có đơn hàng.' ?>
          </td></tr>
        <?php else: ?>
          <?php foreach ($orders as $i => $o): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['full_name']) ?></td>
              <td><?= htmlspecialchars($o['address']) ?></td>
              <td><?= htmlspecialchars($o['phone']) ?></td>
              <td><?= htmlspecialchars($o['order_date']) ?></td>
              <td><?= number_format($o['total_amount'], 0, '.', ',') ?> USD</td>
              <td><span class="status <?= $status_class[$o['status']] ?? '' ?>"><?= htmlspecialchars($o['status']) ?></span></td>
              <td><a href="order_detail.php?id=<?= $o['id'] ?>" class="btn-view">View</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="pagination">
      <button class="pagination-btn">&#10094;</button>
      <button class="pagination-btn active">1</button>
      <button class="pagination-btn">2</button>
      <button class="pagination-btn">3</button>
      <button class="pagination-btn">&#10095;</button>
    </div>
    <div class="pagination-info">Showing 1–<?= count($orders) ?> of <?= count($orders) ?> Orders</div>
  </main>
</body>
</html>
<?php
$conn->close();
$conn_user->close();
?>