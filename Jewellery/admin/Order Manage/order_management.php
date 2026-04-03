<?php
require_once "../../config/config.php";

// ============================================================
// Tham số tìm kiếm & sort từ GET
// ============================================================
$from_date  = isset($_GET['from_date'])  ? trim($_GET['from_date'])  : '';
$to_date    = isset($_GET['to_date'])    ? trim($_GET['to_date'])    : '';
$status     = isset($_GET['status'])     ? trim($_GET['status'])     : 'All';
$sort_by    = isset($_GET['sort_by'])    ? trim($_GET['sort_by'])    : '';
$sort_dir   = isset($_GET['sort_dir'])   ? trim($_GET['sort_dir'])   : 'asc';
$searched   = isset($_GET['searched']);
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;

// ============================================================
// Query orders + customer info — tất cả từ jewelry_db (1 DB duy nhất)
// ============================================================
$where  = [];
$params = [];
$types  = '';

if ($from_date !== '') {
    $where[]  = 'o.order_date >= ?';
    $params[] = $from_date;
    $types   .= 's';
}
if ($to_date !== '') {
    $where[]  = 'o.order_date <= ?';
    $params[] = $to_date;
    $types   .= 's';
}
if ($status !== '' && $status !== 'All') {
    $where[]  = 'o.status = ?';
    $params[] = $status;
    $types   .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.customer_id,
        o.order_date,
        o.total_amount,
        o.status,
        c.full_name,
        c.phone,
        c.address,
        u.email
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN users    u ON c.user_id = u.id
    $where_sql
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$raw_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================================
// Tách district & city từ address
// Định dạng: "123 Le Loi, Q1, HCMC"
//   parts[0] = "123 Le Loi"  → đường
//   parts[1] = "Q1"          → quận/phường
//   parts[2] = "HCMC"        → tỉnh/thành phố
// ============================================================
$orders = [];
foreach ($raw_orders as $o) {
    $addr_parts     = array_map('trim', explode(',', $o['address'] ?? ''));
    $o['district']  = $addr_parts[1] ?? 'N/A';
    $o['city']      = $addr_parts[2] ?? 'N/A';
    $orders[]       = $o;
}

// ============================================================
// Sort
// ============================================================
if ($sort_by === 'district') {
    usort($orders, function($a, $b) use ($sort_dir) {
        $cmp = strcmp($a['district'], $b['district']);
        return $sort_dir === 'desc' ? -$cmp : $cmp;
    });
} elseif ($sort_by === 'city') {
    usort($orders, function($a, $b) use ($sort_dir) {
        $cmp = strcmp($a['city'], $b['city']);
        return $sort_dir === 'desc' ? -$cmp : $cmp;
    });
} elseif ($sort_by === 'amount') {
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

// Helper: build sort URL
function sortUrl($col, $current_sort, $current_dir) {
    $dir = ($current_sort === $col && $current_dir === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, ['sort_by' => $col, 'sort_dir' => $dir, 'searched' => '1', 'page' => 1]);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}

function sortIcon($col, $current_sort, $current_dir) {
    if ($current_sort !== $col) return '<span style="opacity:.35;font-size:11px;margin-left:4px;">⇅</span>';
    return $current_dir === 'asc'
        ? '<span style="font-size:11px;margin-left:4px;">▲</span>'
        : '<span style="font-size:11px;margin-left:4px;">▼</span>';
}

$status_options = ['All', 'Pending', 'Processed', 'Delivered', 'Cancelled'];
$status_class   = [
    'Pending'   => 'status-pending',
    'Processed' => 'status-processed',
    'Delivered' => 'status-delivered',
    'Cancelled' => 'status-cancelled',
];

// Pagination — after sorting
$total_orders = count($orders);
$total_pages  = max(1, (int)ceil($total_orders / $per_page));
$page         = min($page, $total_pages);
$orders_page  = array_slice($orders, ($page - 1) * $per_page, $per_page);

// Helper: build pagination URL
function pageUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    return 'order_management.php?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Management</title>
  <link rel="stylesheet" href="../admin_function.css">
  <style>
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

    .location-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .district-badge { background: #f8f0e6; color: #8e4b00; }
    .city-badge     { background: #e8f0fe; color: #1a56cc; }

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

    .no-result  { text-align: center; padding: 30px; color: #888; font-size: 15px; }
    .result-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 8px; padding: 10px 16px; margin-bottom: 15px; font-size: 14px; }

    /* Highlight column đang sort */
    <?php if ($sort_by === 'district'): ?>
    td.district-col { background-color: rgba(142,75,0,0.04) !important; }
    tr:hover td.district-col { background-color: rgba(142,75,0,0.08) !important; }
    <?php elseif ($sort_by === 'city'): ?>
    td.city-col { background-color: rgba(26,86,204,0.04) !important; }
    tr:hover td.city-col { background-color: rgba(26,86,204,0.08) !important; }
    <?php endif; ?>
  </style>
</head>
<body>

<?php include '../sidebar_include.php'; ?>

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
      <a href="<?= sortUrl('date', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'date' ? 'active' : '' ?>">
        Date <span class="icon"><?= $sort_by === 'date' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>
      <a href="<?= sortUrl('district', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'district' ? 'active' : '' ?>">
        District <span class="icon"><?= $sort_by === 'district' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>
      <a href="<?= sortUrl('city', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'city' ? 'active' : '' ?>">
        City / Province <span class="icon"><?= $sort_by === 'city' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>
      <a href="<?= sortUrl('amount', $sort_by, $sort_dir) ?>"
         class="sort-btn <?= $sort_by === 'amount' ? 'active' : '' ?>">
        Amount <span class="icon"><?= $sort_by === 'amount' ? ($sort_dir === 'asc' ? '▲' : '▼') : '⇅' ?></span>
      </a>

      <?php if ($sort_by === 'city'): ?>
        <span style="font-size:13px;color:#1a56cc;font-weight:600;">
          — Sorting by City/Province <?= $sort_dir === 'asc' ? '(A → Z)' : '(Z → A)' ?>
        </span>
      <?php elseif ($sort_by === 'district'): ?>
        <span style="font-size:13px;color:#8e4b00;font-weight:600;">
          — Sorting by District <?= $sort_dir === 'asc' ? '(A → Z)' : '(Z → A)' ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($searched): ?>
      <div class="result-info">
        🔍 Found <strong><?= count($orders) ?></strong> order(s)
        <?= $from_date ? " from <strong>" . htmlspecialchars($from_date) . "</strong>" : '' ?>
        <?= $to_date   ? " to <strong>"   . htmlspecialchars($to_date)   . "</strong>" : '' ?>
        <?= ($status !== 'All') ? " — status: <strong>" . htmlspecialchars($status) . "</strong>" : '' ?>
        <?= $sort_by === 'city'     ? " — sorted by <strong>City/Province</strong>" : '' ?>
        <?= $sort_by === 'district' ? " — sorted by <strong>District</strong>"      : '' ?>
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
          <th class="sortable <?= $sort_by === 'district' ? 'sorted' : '' ?>"
              onclick="window.location.href='<?= sortUrl('district', $sort_by, $sort_dir) ?>'">
            District <?= sortIcon('district', $sort_by, $sort_dir) ?>
          </th>
          <th class="sortable <?= $sort_by === 'city' ? 'sorted' : '' ?>"
              onclick="window.location.href='<?= sortUrl('city', $sort_by, $sort_dir) ?>'">
            City / Province <?= sortIcon('city', $sort_by, $sort_dir) ?>
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
            <td colspan="11" class="no-result">
              <?= $searched ? '❌ No orders found.' : 'No orders yet.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php
          $prev_group   = null;
          $group_colors = ['#fffbf5', '#fff'];
          $color_idx    = 0;
          $group_key    = in_array($sort_by, ['city', 'district']) ? $sort_by : null;
          ?>
          <?php foreach ($orders as $i => $o): ?>
            <?php
            if ($group_key) {
                $current_group = $o[$group_key];
                if ($current_group !== $prev_group) {
                    $color_idx  = 1 - $color_idx;
                    $prev_group = $current_group;
                }
                $row_bg = $group_colors[$color_idx];
            } else {
                $row_bg = '';
            }
            ?>
            <tr <?= $row_bg ? "style='background:{$row_bg}'" : '' ?>>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['full_name']) ?></td>
              <td style="text-align:left;font-size:13px;color:#555"><?= htmlspecialchars($o['address'] ?? '') ?></td>
              <td class="district-col">
                <span class="location-badge district-badge"><?= htmlspecialchars($o['district']) ?></span>
              </td>
              <td class="city-col">
                <span class="location-badge city-badge"><?= htmlspecialchars($o['city']) ?></span>
              </td>
              <td><?= htmlspecialchars($o['phone'] ?? '') ?></td>
              <td><?= htmlspecialchars($o['order_date']) ?></td>
              <td>$<?= number_format($o['total_amount'], 2, '.', ',') ?></td>
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
<?php $conn->close(); ?>
