<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Entry Form Management</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #f5f5f5; color: #333; display: flex; }

    /* SIDEBAR */
    .sidebar { width: 220px; background-color: #8e4b00; color: #f8ce86; display: flex; flex-direction: column; padding: 20px; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; }
    .logo { text-align: center; margin-bottom: 30px; }
    .logo img { width: 80px; border-radius: 50%; }
    .logo h2 { font-size: 18px; margin-top: 10px; }
    .menu a { display: block; padding: 12px; color: #f8ce86; text-decoration: none; border-radius: 8px; margin-bottom: 10px; font-weight: bold; transition: background 0.3s, color 0.3s; }
    .menu a:hover, .menu a.active { background-color: #f8ce86; color: #8e4b00; }

    /* MAIN */
    main { flex: 1; padding: 25px 40px; margin-left: 220px; }
    header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    header h1 { font-size: 24px; color: #8e4b00; }

    /* BUTTONS */
    .btn {
      background: #8e4b00; color: #f8ce86; text-decoration: none;
      padding: 10px 20px; border-radius: 8px; font-weight: bold;
      transition: 0.3s; border: none; cursor: pointer; display: inline-block;
    }
    .btn:hover { background: #a3670b; transform: translateY(-2px); }
    .btn-sm { padding: 6px 14px; font-size: 13px; }
    .btn-outline { background: transparent; border: 2px solid #8e4b00; color: #8e4b00; }
    .btn-outline:hover { background: #8e4b00; color: #f8ce86; }

    /* SEARCH SECTION */
    .search-section {
      background: #fff; border-radius: 10px; padding: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 25px;
    }
    .search-row { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
    .search-group { display: flex; flex-direction: column; min-width: 160px; }
    .search-group.wide { flex: 2; }
    .search-group.narrow { flex: 1; }
    .search-label { font-weight: 600; color: #555; margin-bottom: 6px; font-size: 13px; }
    .search-input, .search-select {
      padding: 9px 12px; border: 1px solid #ccc; border-radius: 6px;
      font-size: 14px; background: white; height: 40px;
    }
    .search-input:focus, .search-select:focus { border-color: #8e4b00; outline: none; }
    .search-actions { display: flex; gap: 8px; padding-bottom: 1px; }
    .btn-search { background: #8e4b00; color: white; border: none; border-radius: 6px; padding: 0 18px; height: 40px; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-search:hover { background: #a3670b; }
    .btn-reset { background: #888; color: white; border: none; border-radius: 6px; padding: 0 14px; height: 40px; font-weight: 600; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; }
    .btn-reset:hover { background: #666; }

    /* RESULT SUMMARY */
    .result-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .result-count { font-size: 14px; color: #666; }
    .result-count strong { color: #8e4b00; }

    /* TABLE */
    table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.08); }
    th { background-color: #8e4b00; color: #f8ce86; font-weight: 600; text-align: center; padding: 14px 10px; font-size: 15px; }
    td { text-align: center; padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 15px; }
    tr:nth-child(even) td { background: #faf6f2; }
    tr:hover td { background: #f3ede6; }
    .no-results td { color: #888; font-style: italic; padding: 30px; }

    /* STATUS BADGE */
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .badge-draft { background: #fff3cd; color: #856404; }
    .badge-completed { background: #d4edda; color: #155724; }

    /* TABLE LINKS */
    .tbl-link { color: #8e4b00; text-decoration: none; font-weight: 600; padding: 4px 8px; border-radius: 4px; }
    .tbl-link:hover { background: #f8ce86; }
    .tbl-sep { color: #ccc; margin: 0 2px; }

    /* PAGINATION */
    .pagination-wrap { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; flex-wrap: wrap; gap: 10px; }
    .pagination { display: flex; gap: 6px; }
    .pagination-btn { border: 1px solid #ddd; background: white; color: #8e4b00; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-weight: bold; font-size: 14px; }
    .pagination-btn:hover { background-color: #f8ce86; }
    .pagination-btn.active { background-color: #8e4b00; color: #fff; border-color: #8e4b00; }
    .pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .pagination-info { font-size: 14px; color: #666; }

    /* SUCCESS alert */
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-success button { background: none; border: none; font-size: 18px; cursor: pointer; color: #155724; margin-left: auto; }
  </style>
</head>
<body>

<?php
require_once "../../config/config.php";

// ── Nhận params tìm kiếm ──────────────────────────────────
$search_order  = trim($_GET['order']  ?? '');
$search_from   = trim($_GET['from']   ?? '');
$search_to     = trim($_GET['to']     ?? '');
$search_status = trim($_GET['status'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 10;

// ── Build query ───────────────────────────────────────────
$where_clauses = ['1'];
$params        = [];
$types         = '';

if ($search_order !== '') {
    $where_clauses[] = 'order_number LIKE ?';
    $params[]        = '%' . $search_order . '%';
    $types          .= 's';
}
if ($search_from !== '') {
    $where_clauses[] = 'entry_date >= ?';
    $params[]        = $search_from;
    $types          .= 's';
}
if ($search_to !== '') {
    $where_clauses[] = 'entry_date <= ?';
    $params[]        = $search_to;
    $types          .= 's';
}
if (in_array($search_status, ['Draft', 'Completed'])) {
    $where_clauses[] = 'status = ?';
    $params[]        = $search_status;
    $types          .= 's';
}

$where_sql = implode(' AND ', $where_clauses);

// Count total
$count_sql = "SELECT COUNT(*) AS total FROM goods_receipt WHERE $where_sql";
if ($params) {
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = max(1, (int)ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Fetch page
$data_sql = "SELECT * FROM goods_receipt WHERE $where_sql ORDER BY id DESC LIMIT $per_page OFFSET $offset";
if ($params) {
    $stmt_data = $conn->prepare($data_sql);
    $stmt_data->bind_param($types, ...$params);
    $stmt_data->execute();
    $result = $stmt_data->get_result();
} else {
    $result = $conn->query($data_sql);
}

$rows = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$is_searching = ($search_order !== '' || $search_from !== '' || $search_to !== '' || $search_status !== '');

// Build query string for pagination links (keep search params)
function build_url($page, $params = []) {
    $p = array_merge($_GET, ['page' => $page]);
    return '?' . http_build_query($p);
}
?>

<!-- SIDEBAR -->
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
    <a href="../Order Manage/order_management.php">Order Management</a>
    <a href="import_management.php" class="active">Import Management</a>
    <a href="../Stock Manage/stocking_management.php">Stocking</a>
  </div>
</div>

<!-- MAIN -->
<main>
  <header>
    <h1>Entry Form Management</h1>
    <a href="add_entry_form.php" class="btn">+ New Entry Form</a>
  </header>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert-success" id="success-alert">
      ✅ Phiếu nhập #<?= (int)$_GET['receipt_id'] ?> đã được lưu thành công!
      <button onclick="document.getElementById('success-alert').remove()">×</button>
    </div>
  <?php endif; ?>

  <!-- SEARCH -->
  <div class="search-section">
    <form method="GET" action="import_management.php" id="search-form">
      <div class="search-row">
        <div class="search-group wide">
          <label class="search-label">Order Number</label>
          <input type="text" name="order" class="search-input"
                 placeholder="Tìm theo mã phiếu..."
                 value="<?= htmlspecialchars($search_order) ?>">
        </div>
        <div class="search-group narrow">
          <label class="search-label">From Date</label>
          <input type="date" name="from" class="search-input"
                 value="<?= htmlspecialchars($search_from) ?>">
        </div>
        <div class="search-group narrow">
          <label class="search-label">To Date</label>
          <input type="date" name="to" class="search-input"
                 value="<?= htmlspecialchars($search_to) ?>">
        </div>
        <div class="search-group narrow">
          <label class="search-label">Status</label>
          <select name="status" class="search-select">
            <option value="">— All —</option>
            <option value="Draft"     <?= $search_status === 'Draft'     ? 'selected' : '' ?>>Draft</option>
            <option value="Completed" <?= $search_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
          </select>
        </div>
        <div class="search-actions">
          <button type="submit" class="btn-search">🔍 Search</button>
          <a href="import_management.php" class="btn-reset">↺ Reset</a>
        </div>
      </div>
    </form>
  </div>

  <!-- RESULT COUNT -->
  <div class="result-info">
    <span class="result-count">
      <?php if ($is_searching): ?>
        Found <strong><?= $total_rows ?></strong> result<?= $total_rows != 1 ? 's' : '' ?>
        <?php if ($search_order): ?> for "<strong><?= htmlspecialchars($search_order) ?></strong>"<?php endif; ?>
      <?php else: ?>
        Total: <strong><?= $total_rows ?></strong> entry form<?= $total_rows != 1 ? 's' : '' ?>
      <?php endif; ?>
    </span>
    <span class="result-count">Page <?= $page ?> / <?= $total_pages ?></span>
  </div>

  <!-- TABLE -->
  <table>
    <thead>
      <tr>
        <th>No.</th>
        <th>Order No.</th>
        <th>Date</th>
        <th>Supplier</th>
        <th>Quantity</th>
        <th>Total Value</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr class="no-results"><td colspan="8">
          <?= $is_searching ? 'Không tìm thấy phiếu nhập nào phù hợp.' : 'Chưa có phiếu nhập nào.' ?>
        </td></tr>
      <?php else: ?>
        <?php
        $i = ($page - 1) * $per_page + 1;
        foreach ($rows as $row):
          $badge = $row['status'] === 'Completed' ? 'badge-completed' : 'badge-draft';
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
          <td><?= htmlspecialchars($row['entry_date']) ?></td>
          <td><?= htmlspecialchars($row['supplier'] ?? '—') ?></td>
          <td><?= intval($row['total_quantity']) ?></td>
          <td><?= number_format($row['total_value'], 2) ?> USD</td>
          <td><span class="badge <?= $badge ?>"><?= $row['status'] ?></span></td>
          <td>
            <a href="entry_form_detail.php?id=<?= $row['id'] ?>" class="tbl-link">View</a>
            <span class="tbl-sep">|</span>
            <?php if ($row['status'] === 'Draft'): ?>
              <a href="edit_entry_form.php?id=<?= $row['id'] ?>" class="tbl-link">Edit</a>
            <?php else: ?>
              <span style="color:#ccc;font-size:13px;" title="Phiếu đã hoàn thành, không thể sửa">🔒 Locked</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination-wrap">
    <span class="pagination-info">
      Showing <?= ($page-1)*$per_page + 1 ?>–<?= min($page*$per_page, $total_rows) ?> of <?= $total_rows ?>
    </span>
    <div class="pagination">
      <button class="pagination-btn" onclick="goPage(<?= $page-1 ?>)" <?= $page<=1 ? 'disabled' : '' ?>>&#10094;</button>
      <?php
      $start = max(1, $page - 2);
      $end   = min($total_pages, $page + 2);
      if ($start > 1): ?><button class="pagination-btn" onclick="goPage(1)">1</button><?php if ($start > 2): ?><span style="padding:0 4px;color:#999">…</span><?php endif; endif;
      for ($p = $start; $p <= $end; $p++): ?>
        <button class="pagination-btn <?= $p === $page ? 'active' : '' ?>" onclick="goPage(<?= $p ?>)"><?= $p ?></button>
      <?php endfor;
      if ($end < $total_pages): ?><?php if ($end < $total_pages-1): ?><span style="padding:0 4px;color:#999">…</span><?php endif; ?><button class="pagination-btn" onclick="goPage(<?= $total_pages ?>"><?= $total_pages ?></button><?php endif; ?>
      <button class="pagination-btn" onclick="goPage(<?= $page+1 ?>)" <?= $page>=$total_pages ? 'disabled' : '' ?>>&#10095;</button>
    </div>
  </div>
  <?php else: ?>
  <div class="pagination-info" style="text-align:center;margin-top:16px;color:#666;font-size:14px;">
    Showing all <?= $total_rows ?> entry form<?= $total_rows != 1 ? 's' : '' ?>
  </div>
  <?php endif; ?>

</main>

<script>
function goPage(p) {
  const form = document.getElementById('search-form');
  const input = document.createElement('input');
  input.type = 'hidden'; input.name = 'page'; input.value = p;
  form.appendChild(input);
  form.submit();
}
</script>
</body>
</html>