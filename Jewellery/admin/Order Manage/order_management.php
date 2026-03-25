<?php
require_once "../../config/config.php";

// ============================================================
// Tham số tìm kiếm & sort từ GET
// ============================================================
$from_date  = isset($_GET['from_date'])  ? trim($_GET['from_date'])  : '';
$to_date    = isset($_GET['to_date'])    ? trim($_GET['to_date'])    : '';
$status     = isset($_GET['status'])     ? trim($_GET['status'])     : 'All';
$sort_by    = isset($_GET['sort_by'])    ? trim($_GET['sort_by'])    : '';   // 'district' hoặc ''
$sort_dir   = isset($_GET['sort_dir'])   ? trim($_GET['sort_dir'])   : 'asc';
$searched   = isset($_GET['searched']);

// ============================================================
// Lấy customers từ user_db
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
// Query orders từ jewelry_db
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
        FROM orders $where_sql ORDER BY order_date DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$raw_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================================
// Ghép thông tin customer + tách district từ address
// Định dạng address: "123 Le Loi, Q1, HCMC"
//   → parts[0] = "123 Le Loi"  (đường)
//   → parts[1] = "Q1"           (quận/phường) ← dùng để sort
//   → parts[2] = "HCMC"         (thành phố)
// ============================================================
$orders = [];
foreach ($raw_orders as $o) {
    $cid            = $o['customer_id'];
    $address        = $cust_map[$cid]['address'] ?? 'N/A';

    // Tách phần quận/phường: lấy phần tử thứ 2 (index 1) khi split bởi dấu phẩy
    $addr_parts     = array_map('trim', explode(',', $address));
    $district       = $addr_parts[1] ?? $addr_parts[0] ?? 'N/A';

    $o['full_name'] = $cust_map[$cid]['full_name'] ?? 'N/A';
    $o['phone']     = $cust_map[$cid]['phone']     ?? 'N/A';
    $o['address']   = $address;
    $o['district']  = $district;
    $orders[]       = $o;
}

// ============================================================
// Sort theo district nếu được yêu cầu
// ============================================================
if ($sort_by === 'district') {
    usort($orders, function($a, $b) use ($sort_dir) {
        $cmp = strcmp($a['district'], $b['district']);
        return $sort_dir === 'desc' ? -$cmp : $cmp;
    });
}

// Helper: tạo URL sort — giữ nguyên tham số filter, toggle direction
function sortUrl($col, $current_sort, $current_dir, $extra = []) {
    $dir = ($current_sort === $col && $current_dir === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, ['sort_by' => $col, 'sort_dir' => $dir, 'searched' => '1'], $extra);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}

$status_options = ['All', 'Pending', 'Processed', 'Delivered', 'Cancelled'];
$status_class   = [
    'Pending'   => 'status-pending',
    'Processed' => 'status-processed',
    'Delivered' => 'status-delivered',
    'Cancelled' => 'status-cancelled',
];

// Icon sort cho header
function sortIcon($col, $current_sort, $current_dir) {
    if ($current_sort !== $col) return '<span style="opacity:.35;font-size:11px;margin-left:4px;">⇅</span>';
    return $current_dir === 'asc'
        ? '<span style="font-size:11px;margin-left:4px;">▲</span>'
        : '<span style="font-size:11px;margin-left:4px;">▼</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Management</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #f5f5f5; color: #333; display: flex; }

    .sidebar { width: 220px; background-color: #8e4b00; color: #f8ce86; display: flex; flex-direction: column; padding: 20px; height: 100vh; position: fixed; top: 0; left: 0; }
    .logo { text-align: center; margin-bottom: 30px; }
    .logo img { width: 80px; border-radius: 50%; }
    .logo h2 { font-size: 18px; margin-top: 10px; }
    .menu a { display: block; padding: 12px; color: #f8ce86; text-decoration: none; border-radius: 8px; margin-bottom: 10px; font-weight: bold; transition: background 0.3s, color 0.3s; }
    .menu a:hover, .menu a.active { background-color: #f8ce86; color: #8e4b00; }

    main { flex: 1; padding: 25px 40px; margin-left: 220px; }
    header h1 { font-size: 24px; color: #8e4b00; margin-bottom: 20px; }

    /* SEARCH */
    .search-section { background: #fff; border-radius: 10px; padding: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 25px; }
    .search-group { flex: 1; min-width: 150px; display: flex; flex-direction: column; }
    .search-label { font-weight: 600; color: #333; margin-bottom: 6px; }
    .search-input { height: 42px; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; width: 100%; }
    .btn-search, .btn-reset { height: 42px; padding: 0 20px; font-weight: 600; border-radius: 6px; cursor: pointer; border: none; font-size: 15px; white-space: nowrap; }
    .btn-search { background-color: #8e4b00; color: #fff; }
    .btn-reset  { background-color: #888; color: #fff; }
    .btn-search:hover { background-color: #a3670b; }
    .btn-reset:hover  { background-color: #666; }

    /* SORT TOOLBAR */
    .sort-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .sort-bar span { font-size: 14px; color: #666; }
    .sort-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 7px; border: 1px solid #ddd; background: #fff; color: #333; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: .2s; }
    .sort-btn:hover { border-color: #8e4b00; color: #8e4b00; }
    .sort-btn.active { background: #8e4b00; color: #f8ce86; border-color: #8e4b00; }
    .sort-btn .icon { font-size: 11px; opacity: .7; }

    /* TABLE */
    table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.08); }
    th { background-color: #8e4b00; color: #f8ce86; font-weight: 600; text-align: center; padding: 14px 10px; font-size: 15px; white-space: nowrap; }
    th.sortable { cursor: pointer; user-select: none; }
    th.sortable:hover { background-color: #a3670b; }
    th.sorted { background-color: #6d3900; }
    td { text-align: center; padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
    tr:nth-child(even) td { background: #faf6f2; }
    tr:hover td { background: #f3ede6; }

    /* District badge */
    .district-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #f8f0e6; color: #8e4b00; white-space: nowrap; }

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

    /* Highlight row khi đang sort theo district */
    <?php if ($sort_by === 'district'): ?>
    td.district-col { background-color: rgba(142,75,0,0.04) !important; }
    tr:hover td.district-col { background-color: rgba(142,75,0,0.08) !important; }
    <?php endif; ?>
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
      <a href="../Administration_menu.html#users">Customers</a>
      <a href="../Price Manage/pricing.php">Pricing Management</a>
      <a href="../Import_product/import_management.php">Import Management</a>
      <a href="order_management.php" class="active">Order Management</a>
      <a href="../Stock Manage/stocking_management.php">Stocking</a>
      <a href="../Administration_menu.html#setting">Settings</a>
    </div>
  </div>

  <main>
    <header><h1>Order Management</h1></header>

    <!-- SEARCH FORM -->
    <form method="GET" action="order_management.php">
      <input type="hidden" name="searched" value="1">
      <?php if ($sort_by): ?>
        <input type="hidden" name="sort_by"  value="<?= htmlspecialchars($sort_by) ?>">
        <input type="hidden" name="sort_dir" value="<?= htmlspecialchars($sort_dir) ?>">
      <?php endif; ?>
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
        <a href="order_management.php" class="btn-reset"
           style="display:flex;align-items:center;justify-content:center;text-decoration:none;">Reset</a>
      </div>
    </form>

    <!-- SORT TOOLBAR -->
    <div class="sort-bar">
      <span>Sort by:</span>

      <!-- Sort theo ngày (mặc định) -->
      <a href="<?= sortUrl('date', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'date' ? 'active' : '' ?>">
        Date
        <span class="icon"><?= $sort_by === 'date' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>

      <!-- Sort theo district/phường -->
      <a href="<?= sortUrl('district', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'district' ? 'active' : '' ?>">
        District / Ward
        <span class="icon"><?= $sort_by === 'district' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>

      <!-- Sort theo tổng tiền -->
      <a href="<?= sortUrl('amount', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'amount' ? 'active' : '' ?>">
        Amount
        <span class="icon"><?= $sort_by === 'amount' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>

      <?php if ($sort_by === 'district'): ?>
        <span style="font-size:13px;color:#8e4b00;font-weight:600;">
          — Đang sắp xếp theo quận/phường <?= $sort_dir === 'asc' ? '(A → Z)' : '(Z → A)' ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($searched): ?>
      <div class="result-info">
        🔍 Tìm thấy <strong><?= count($orders) ?></strong> đơn hàng
        <?= $from_date ? " từ <strong>" . htmlspecialchars($from_date) . "</strong>" : '' ?>
        <?= $to_date   ? " đến <strong>" . htmlspecialchars($to_date)   . "</strong>" : '' ?>
        <?= ($status !== 'All') ? " — trạng thái: <strong>" . htmlspecialchars($status) . "</strong>" : '' ?>
        <?= $sort_by === 'district' ? " — sắp xếp theo <strong>quận/phường</strong>" : '' ?>
      </div>
    <?php endif; ?>

    <!-- TABLE -->
    <table>
      <thead>
        <tr>
          <th>No.</th>
          <th>Order No.</th>
          <th>Customer Name</th>
          <th>Full Address</th>
          <!-- Header District có thể click để sort -->
          <th class="sortable <?= $sort_by === 'district' ? 'sorted' : '' ?>"
              onclick="window.location.href='<?= sortUrl('district', $sort_by, $sort_dir) ?>'">
            District / Ward
            <?= sortIcon('district', $sort_by, $sort_dir) ?>
          </th>
          <th>Phone</th>
          <th class="sortable <?= $sort_by === 'date' ? 'sorted' : '' ?>"
              onclick="window.location.href='<?= sortUrl('date', $sort_by, $sort_dir) ?>'">
            Date <?= sortIcon('date', $sort_by, $sort_dir) ?>
          </th>
          <th class="sortable <?= $sort_by === 'amount' ? 'sorted' : '' ?>"
              onclick="window.location.href='<?= sortUrl('amount', $sort_by, $sort_dir) ?>'">
            Total Amount <?= sortIcon('amount', $sort_by, $sort_dir) ?>
          </th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="10" class="no-result">
              <?= $searched ? '❌ Không tìm thấy đơn hàng phù hợp.' : 'Chưa có đơn hàng.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php
          // Sort amount ở PHP (orders đã được sort district ở trên nếu cần)
          if ($sort_by === 'amount') {
              usort($orders, function($a, $b) use ($sort_dir) {
                  $cmp = $a['total_amount'] <=> $b['total_amount'];
                  return $sort_dir === 'desc' ? -$cmp : $cmp;
              });
          } elseif ($sort_by === 'date') {
              usort($orders, function($a, $b) use ($sort_dir) {
                  $cmp = strcmp($a['order_date'], $b['order_date']);
                  return $sort_dir === 'desc' ? -$cmp : $cmp;
              });
          }
          ?>
          <?php
          // Dùng để group highlight khi sort district
          $prev_district = null;
          $group_colors  = ['#fffbf5', '#fff'];
          $color_idx     = 0;

          foreach ($orders as $i => $o):
            if ($sort_by === 'district' && $o['district'] !== $prev_district) {
                $color_idx    = 1 - $color_idx;
                $prev_district = $o['district'];
            }
            $row_bg = ($sort_by === 'district') ? $group_colors[$color_idx] : '';
          ?>
            <tr <?= $row_bg ? "style='background:{$row_bg}'" : '' ?>>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['full_name']) ?></td>
              <td style="text-align:left;font-size:13px;color:#555"><?= htmlspecialchars($o['address']) ?></td>
              <td class="district-col">
                <span class="district-badge"><?= htmlspecialchars($o['district']) ?></span>
              </td>
              <td><?= htmlspecialchars($o['phone']) ?></td>
              <td><?= htmlspecialchars($o['order_date']) ?></td>
              <td><?= number_format($o['total_amount'], 0, '.', ',') ?> USD</td>
              <td>
                <span class="status <?= $status_class[$o['status']] ?? '' ?>">
                  <?= htmlspecialchars($o['status']) ?>
                </span>
              </td>
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
    <div class="pagination-info">
      Showing 1–<?= count($orders) ?> of <?= count($orders) ?> Orders
    </div>
  </main>
</body>
</html>
<?php
$conn->close();
$conn_user->close();
?>