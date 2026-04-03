<?php
session_start();
require_once "../../config/config.php";

// ============================================================
// AJAX endpoint
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    $type        = $_GET['type']       ?? '';
    $date_from   = $_GET['date_from']  ?? '';
    $date_to     = $_GET['date_to']    ?? '';
    $date_single = $_GET['date']       ?? '';
    $product     = $_GET['product']    ?? '';

    if ($date_single !== '') {
        $date_from = $date_to = $date_single;
    }

    $rows = [];

    if ($type === 'import') {
        $where  = ["gr.status = 'Completed'"];
        $params = []; $types = '';
        if ($date_from !== '') { $where[] = 'gr.entry_date >= ?'; $params[] = $date_from; $types .= 's'; }
        if ($date_to   !== '') { $where[] = 'gr.entry_date <= ?'; $params[] = $date_to;   $types .= 's'; }
        if ($product   !== '') { $where[] = 'gri.product_name LIKE ?'; $params[] = '%'.$product.'%'; $types .= 's'; }

        $sql = "SELECT gr.order_number, gr.entry_date, gr.supplier, gr.status, gr.created_at,
                       gri.product_id, gri.product_name, gri.quantity, gri.unit_price, gri.total_price
                FROM goods_receipt gr
                JOIN goods_receipt_items gri ON gr.id = gri.receipt_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY gr.entry_date DESC, gr.order_number ASC";

        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } elseif ($type === 'export') {
        $where  = ['1=1'];
        $params = []; $types = '';
        if ($date_from !== '') { $where[] = 'o.order_date >= ?'; $params[] = $date_from; $types .= 's'; }
        if ($date_to   !== '') { $where[] = 'o.order_date <= ?'; $params[] = $date_to;   $types .= 's'; }
        if ($product   !== '') { $where[] = 'oi.product_name LIKE ?'; $params[] = '%'.$product.'%'; $types .= 's'; }

        $sql = "SELECT o.order_number, o.order_date AS entry_date, c.full_name AS supplier,
                       o.status, o.created_at, oi.product_id, oi.product_name,
                       oi.quantity, oi.unit_price, oi.total_price
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY o.order_date DESC, o.order_number ASC";

        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    echo json_encode(['success' => true, 'type' => $type, 'rows' => $rows]);
    $conn->close();
    exit;
}

// ============================================================
// Sync stock
// ============================================================
$conn->query("
    UPDATE products p SET p.stock = (
        COALESCE((SELECT SUM(gri.quantity) FROM goods_receipt_items gri
                  JOIN goods_receipt gr ON gri.receipt_id = gr.id
                  WHERE gri.product_id = p.id AND gr.status = 'Completed'), 0)
        - COALESCE((SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.product_id = p.id), 0)
    )
");

$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'stock-lookup';

// ============================================================
// TAB 1: Stock Lookup
// ============================================================
$search_name     = trim($_GET['search_name']     ?? '');
$search_category = trim($_GET['search_category'] ?? 'All');
$search_date     = trim($_GET['search_date']      ?? '');

$where1 = []; $params1 = []; $types1 = '';
if ($search_name !== '')         { $where1[] = 'name LIKE ?';  $params1[] = '%'.$search_name.'%'; $types1 .= 's'; }
if ($search_category !== 'All') { $where1[] = 'category = ?'; $params1[] = $search_category;      $types1 .= 's'; }
$where1_sql = $where1 ? 'WHERE ' . implode(' AND ', $where1) : '';

$stmt1 = $conn->prepare("SELECT id, name, image, category, stock FROM products $where1_sql ORDER BY id ASC");
if ($params1) $stmt1->bind_param($types1, ...$params1);
$stmt1->execute();
$base_products = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

$products = [];
foreach ($base_products as $p) {
    if ($search_date !== '') {
        $s_in = $conn->prepare("SELECT COALESCE(SUM(gri.quantity),0) AS qty FROM goods_receipt_items gri
                                 JOIN goods_receipt gr ON gri.receipt_id=gr.id
                                 WHERE gri.product_id=? AND gr.status='Completed' AND gr.entry_date<=?");
        $s_in->bind_param('ss', $p['id'], $search_date);
        $s_in->execute();
        $in_qty = (int)$s_in->get_result()->fetch_assoc()['qty'];
        $s_in->close();

        $s_out = $conn->prepare("SELECT COALESCE(SUM(oi.quantity),0) AS qty FROM order_items oi
                                  JOIN orders o ON oi.order_id=o.id
                                  WHERE oi.product_id=? AND o.order_date<=?");
        $s_out->bind_param('ss', $p['id'], $search_date);
        $s_out->execute();
        $out_qty = (int)$s_out->get_result()->fetch_assoc()['qty'];
        $s_out->close();

        $p['stock']      = max(0, $in_qty - $out_qty);
        $p['as_of_date'] = true;
        $p['in_qty']     = $in_qty;
        $p['out_qty']    = $out_qty;
    } else {
        $p['as_of_date'] = false;
    }
    $products[] = $p;
}

// ============================================================
// TAB 2: Low Stock Alert
// ============================================================
if (isset($_POST['set_threshold']) && isset($_POST['threshold'])) {
    $t = intval($_POST['threshold']);
    if ($t >= 0) $_SESSION['low_stock_threshold'] = $t;
    header("Location: stocking_management.php?tab=stock-alert");
    exit;
}
$min_stock = isset($_SESSION['low_stock_threshold']) ? (int)$_SESSION['low_stock_threshold'] : 10;

$stmt_low = $conn->prepare("SELECT id, name, image, category, stock FROM products WHERE stock <= ? ORDER BY stock ASC");
$stmt_low->bind_param('i', $min_stock);
$stmt_low->execute();
$low_products = $stmt_low->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_low->close();

// ============================================================
// TAB 3: Import - Export - Balance
// ============================================================
$report_product = trim($_GET['report_product'] ?? '');
$report_from    = trim($_GET['report_from']    ?? '');
$report_to      = trim($_GET['report_to']      ?? '');

$imp_where = ["gr.status = 'Completed'"]; $imp_params = []; $imp_types = '';
if ($report_from    !== '') { $imp_where[] = 'gr.entry_date >= ?'; $imp_params[] = $report_from;           $imp_types .= 's'; }
if ($report_to      !== '') { $imp_where[] = 'gr.entry_date <= ?'; $imp_params[] = $report_to;             $imp_types .= 's'; }
if ($report_product !== '') { $imp_where[] = 'gri.product_name LIKE ?'; $imp_params[] = '%'.$report_product.'%'; $imp_types .= 's'; }

$stmt_imp = $conn->prepare("SELECT gr.entry_date AS report_date, SUM(gri.quantity) AS import_qty
    FROM goods_receipt gr JOIN goods_receipt_items gri ON gr.id=gri.receipt_id
    WHERE ".implode(' AND ',$imp_where)." GROUP BY gr.entry_date ORDER BY gr.entry_date DESC");
if ($imp_params) $stmt_imp->bind_param($imp_types, ...$imp_params);
$stmt_imp->execute();
$import_by_date = $stmt_imp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_imp->close();

$exp_where = ['1=1']; $exp_params = []; $exp_types = '';
if ($report_from    !== '') { $exp_where[] = 'o.order_date >= ?'; $exp_params[] = $report_from;           $exp_types .= 's'; }
if ($report_to      !== '') { $exp_where[] = 'o.order_date <= ?'; $exp_params[] = $report_to;             $exp_types .= 's'; }
if ($report_product !== '') { $exp_where[] = 'oi.product_name LIKE ?'; $exp_params[] = '%'.$report_product.'%'; $exp_types .= 's'; }

$stmt_exp = $conn->prepare("SELECT o.order_date AS report_date, SUM(oi.quantity) AS export_qty
    FROM orders o JOIN order_items oi ON o.id=oi.order_id
    WHERE ".implode(' AND ',$exp_where)." GROUP BY o.order_date ORDER BY o.order_date DESC");
if ($exp_params) $stmt_exp->bind_param($exp_types, ...$exp_params);
$stmt_exp->execute();
$export_by_date = $stmt_exp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_exp->close();

$date_map = [];
foreach ($import_by_date as $r) { $date_map[$r['report_date']]['import'] = (int)$r['import_qty']; $date_map[$r['report_date']]['export'] ??= 0; }
foreach ($export_by_date as $r) { $date_map[$r['report_date']]['export'] = (int)$r['export_qty']; $date_map[$r['report_date']]['import'] ??= 0; }
krsort($date_map);

$stock_sql = "SELECT COALESCE(SUM(stock),0) AS total_stock FROM products" . ($report_product !== '' ? " WHERE name LIKE ?" : "");
$stmt_stock = $conn->prepare($stock_sql);
if ($report_product !== '') { $sl = '%'.$report_product.'%'; $stmt_stock->bind_param('s', $sl); }
$stmt_stock->execute();
$current_stock = (int)$stmt_stock->get_result()->fetch_assoc()['total_stock'];
$stmt_stock->close();

$report_rows  = [];
$total_import = 0;
$total_export = 0;
foreach ($date_map as $date => $vals) {
    $report_rows[] = ['date' => $date, 'import' => $vals['import'], 'export' => $vals['export']];
    $total_import += $vals['import'];
    $total_export += $vals['export'];
}

// ============================================================
// Stock summary (shared — modal + stat cards)
// ============================================================
$total_stock   = 0;
$stock_details = [];
$stock_all = $conn->query("SELECT id, name, category, stock FROM products ORDER BY category, name");
while ($row = $stock_all->fetch_assoc()) {
    $total_stock   += max(0, (int)$row['stock']);
    $stock_details[] = $row;
}
$count_in  = count(array_filter($stock_details, fn($r) => (int)$r['stock'] >= 5));
$count_low = count(array_filter($stock_details, fn($r) => (int)$r['stock'] > 0 && (int)$r['stock'] < 5));
$count_out = count(array_filter($stock_details, fn($r) => (int)$r['stock'] <= 0));

function stockStatus($qty, $threshold = 10) {
    if ($qty <= 0)          return ['label' => 'Out of Stock', 'class' => 'status-danger'];
    if ($qty <= $threshold) return ['label' => 'Low Stock',    'class' => 'status-warning'];
    return                         ['label' => 'In Stock',     'class' => 'status-normal'];
}

$cat_list = $conn->query("SELECT DISTINCT category FROM products ORDER BY category")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stocking Management</title>
  <link rel="stylesheet" href="../admin_function.css">
  <style>
    /* PAGE-SPECIFIC: main override for this layout */
    main { overflow-x: hidden; padding: 25px; }
    h1 { color:#8e4b00; margin-bottom:20px; font-size:24px; }

    /* TABS */
.tabs { display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #ddd; }
.tab { padding:10px 18px; font-weight:600; color:#555; cursor:pointer; border-bottom:3px solid transparent; transition:all .3s; white-space:nowrap; }
.tab.active { color:#8e4b00; border-bottom-color:#8e4b00; }
.tab-content { display:none; }
.tab-content.active { display:block; }

/* FORMS */
.search-form { background:#fff; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,.08); padding:20px; margin-bottom:20px; display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end; }
.form-group { display:flex; flex-direction:column; flex:1; min-width:160px; }
label { font-weight:bold; color:#333; margin-bottom:6px; font-size:14px; }
input, select { padding:10px; border:1px solid #ccc; border-radius:6px; font-size:14px; outline:none; transition:border-color .2s; }
input:focus, select:focus { border-color:#8e4b00; }

/* BUTTONS */
.btn { display:inline-block; background:#8e4b00; color:#f8ce86; border:none; border-radius:6px; padding:10px 16px; font-weight:bold; cursor:pointer; transition:.3s; text-decoration:none; font-size:14px; }
.btn:hover { background:#a3670b; }
.btn.secondary { background:#ccc; color:#333; }
.btn.secondary:hover { background:#aaa; }

/* TABLE */
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.08); }
th, td { padding:13px 14px; text-align:center; font-size:14px; border-bottom:1px solid #f0e2d0; }
th { background:#8e4b00; color:#f8ce86; font-weight:600; }
tr:hover td { background:#fdf5ec; }
table img { width:56px; height:56px; object-fit:cover; border-radius:6px; }

/* DRILL LINKS */
.drill-link { display:inline-flex; align-items:center; gap:5px; background:none; border:none; cursor:pointer; font-size:15px; font-weight:700; padding:4px 10px; border-radius:20px; transition:all .2s; text-decoration:none; }
.drill-link.import-link { color:#8e4b00; background:#fdf0e0; border:1px solid #e8c98a; }
.drill-link.import-link:hover { background:#8e4b00; color:#f8ce86; border-color:#8e4b00; }
.drill-link.export-link { color:#5a2d00; background:#f5e6d0; border:1px solid #c9915a; }
.drill-link.export-link:hover { background:#5a2d00; color:#f8ce86; border-color:#5a2d00; }
.drill-link.stock-link { color:#8e4b00; background:#fff8ee; border:1px solid #e8c98a; }
.drill-link.stock-link:hover { background:#8e4b00; color:#f8ce86; border-color:#8e4b00; }
.drill-link.zero { color:#bbb; background:#f5f5f5; border:1px solid #e0e0e0; cursor:default; pointer-events:none; }
.drill-icon { font-size:11px; opacity:.7; }

/* STATUS BADGES */
.status-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:13px; font-weight:600; }
.status-normal  { background:#fdf0e0; color:#8e4b00; }
.status-warning { background:#fff3e0; color:#f57c00; }
.status-danger  { background:#ffebee; color:#c62828; }

/* THRESHOLD BOX */
.threshold-box { background:#fff; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,.08); padding:18px 20px; margin-bottom:20px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.threshold-box .label { font-weight:600; color:#333; font-size:14px; }
.threshold-box input[type=number] { width:90px; padding:8px 10px; border:2px solid #8e4b00; border-radius:6px; font-size:15px; font-weight:600; text-align:center; color:#8e4b00; }
.threshold-badge { display:inline-block; background:#fdf0e0; color:#8e4b00; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:600; border:1px solid #e8c98a; }

/* MISC */
.date-info-box { background:#fdf5ec; border:1px solid #e8c98a; border-radius:8px; padding:10px 16px; margin-bottom:16px; font-size:14px; color:#5a2d00; display:flex; align-items:center; gap:8px; }
.alert-section { background:#fdf5ec; border-left:4px solid #8e4b00; padding:1rem; margin-top:1.5rem; border-radius:0 6px 6px 0; }
.alert-title { font-weight:600; color:#8e4b00; margin-bottom:.5rem; }
.stock-note { font-size:12px; color:#aaa; display:block; }
.no-data { text-align:center; padding:30px; color:#aaa; }
.stock-as-of { font-weight:bold; color:#8e4b00; }

/* STAT CARDS */
.stat-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px; margin-bottom:20px; }
.stat-card { background:#fff; border-radius:10px; padding:16px 14px; display:flex; align-items:center; gap:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); transition:transform .2s, box-shadow .2s; }
.stat-card.clickable { cursor:pointer; }
.stat-card.clickable:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(142,75,0,.2); }
.stat-icon { width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; letter-spacing:0; }
/* Total — màu chính */
.si-gold   { background:#fdf0e0; color:#8e4b00; border:2px solid #e8c98a; }
/* In Stock — xanh lá */
.si-green  { background:#e8f5e9; color:#2e7d32; border:2px solid #a5d6a7; }
/* Low Stock — cam */
.si-orange { background:#fff3e0; color:#e65100; border:2px solid #ffcc80; }
/* Out of Stock — đỏ */
.si-red    { background:#ffebee; color:#c62828; border:2px solid #ef9a9a; }
.stat-info h3 { font-size:22px; font-weight:700; color:#333; line-height:1; }
.stat-info p  { font-size:12px; color:#888; margin-top:3px; }
.stat-info small { font-size:11px; color:#8e4b00; }

/* MODAL OVERLAY */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.open { display:flex; }
.modal { background:#fff; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:900px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; animation:modalIn .25s ease; }
@keyframes modalIn { from { opacity:0; transform:translateY(20px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }

/* Modal headers — tất cả dùng màu nâu chính */
.modal-header { padding:18px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #f0e2d0; flex-shrink:0; }
.modal-header.import-header { background:#8e4b00; color:#f8ce86; }
.modal-header.export-header { background:#5a2d00; color:#f8ce86; }
.modal-header.stock-header  { background:#8e4b00; color:#f8ce86; }
.modal-header h3 { font-size:18px; font-weight:700; }
.modal-header .meta { font-size:13px; opacity:.85; margin-top:3px; }
.modal-close { background:rgba(248,206,134,.2); border:none; color:#f8ce86; font-size:20px; width:34px; height:34px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .2s; flex-shrink:0; }
.modal-close:hover { background:rgba(248,206,134,.4); }
.modal-body { overflow-y:auto; flex:1; padding:0; }

/* Summary bars */
.modal-summary { display:flex; background:#fafafa; border-bottom:1px solid #f0e2d0; flex-shrink:0; }
.modal-summary .sum-item { flex:1; padding:12px 20px; text-align:center; border-right:1px solid #f0e2d0; font-size:13px; color:#888; }
.modal-summary .sum-item:last-child { border-right:none; }
.modal-summary .sum-item strong { display:block; font-size:20px; color:#8e4b00; margin-bottom:2px; }
.stock-summary { display:flex; background:#fafafa; border-bottom:1px solid #f0e2d0; flex-shrink:0; }
.stock-summary .s-item { flex:1; padding:10px 14px; text-align:center; border-right:1px solid #f0e2d0; font-size:12px; color:#888; }
.stock-summary .s-item:last-child { border-right:none; }
.stock-summary .s-item strong { display:block; font-size:18px; }

/* Drill-down table */
.modal-table { width:100%; border-collapse:collapse; }
.modal-table th { background:#fdf5ec; color:#8e4b00; font-weight:600; font-size:13px; padding:10px 14px; text-align:left; border-bottom:2px solid #f0e2d0; position:sticky; top:0; }
.modal-table td { padding:12px 14px; font-size:14px; border-bottom:1px solid #fdf0e0; vertical-align:middle; }
.modal-table tr:hover td { background:#fdf5ec; }
.modal-table .order-num { font-weight:700; color:#8e4b00; font-family:monospace; font-size:13px; }
.modal-table .product-chip { display:inline-block; background:#fdf0e0; color:#8e4b00; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
.modal-table .price { color:#5a2d00; font-weight:600; }
.modal-table .status-pill { display:inline-block; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }
.pill-completed { background:#fdf0e0; color:#8e4b00; }
.pill-draft     { background:#fff3e0; color:#a3670b; }
.pill-delivered { background:#f0e6d3; color:#6b3500; }
.pill-pending   { background:#fdf6ec; color:#c8860a; }
.pill-processed { background:#faebd7; color:#8e4b00; }
.pill-cancelled { background:#ffebee; color:#c62828; }
.group-row td { background:#fdf0e0; font-weight:700; color:#8e4b00; font-size:13px; padding:8px 14px; border-bottom:1px solid #e8c98a; }

/* Stock detail table */
.stock-modal-table { width:100%; border-collapse:collapse; }
.stock-modal-table th { background:#fdf5ec; color:#8e4b00; font-size:12px; font-weight:700; padding:10px 14px; text-align:left; border-bottom:2px solid #f0e2d0; position:sticky; top:0; }
.stock-modal-table td { padding:11px 14px; font-size:14px; border-bottom:1px solid #fdf0e0; vertical-align:middle; }
.stock-modal-table tr:hover td { background:#fdf5ec; }

/* Qty badges */
.qty-badge { display:inline-block; padding:3px 14px; border-radius:20px; font-size:13px; font-weight:700; color:#fff; }
.qty-green  { background:#43a047; }
.qty-orange { background:#fb8c00; }
.qty-red    { background:#e53935; }

/* Legend */
.stock-legend { display:flex; gap:14px; flex-wrap:wrap; padding:12px 16px; border-top:1px solid #f0e2d0; font-size:12px; color:#888; background:#fafafa; }
.stock-legend span { display:flex; align-items:center; gap:5px; }
.leg-dot { width:9px; height:9px; border-radius:50%; display:inline-block; }

/* Loading / empty */
.modal-loading { text-align:center; padding:50px; color:#aaa; font-size:15px; }
.modal-loading .spinner { width:36px; height:36px; border:3px solid #f0e2d0; border-top-color:#8e4b00; border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 14px; }
@keyframes spin { to { transform:rotate(360deg); } }
.modal-empty { text-align:center; padding:50px; color:#aaa; font-size:15px; }
  </style>
</head>
<body>

<?php include '../sidebar_include.php'; ?>

<main>
  <h1>Stocking Management</h1>

  <div class="tabs">
    <div class="tab <?= $active_tab==='stock-lookup' ?'active':'' ?>" data-tab="stock-lookup">Stock Lookup</div>
    <div class="tab <?= $active_tab==='stock-alert'  ?'active':'' ?>" data-tab="stock-alert">Low Stock Alert</div>
    <div class="tab <?= $active_tab==='stock-report' ?'active':'' ?>" data-tab="stock-report">Import - Export - Balance</div>
  </div>

  <!-- ====== TAB 1: Stock Lookup ====== -->
  <div class="tab-content <?= $active_tab==='stock-lookup'?'active':'' ?>" id="stock-lookup">

    <form method="GET" action="stocking_management.php" class="search-form">
      <input type="hidden" name="tab" value="stock-lookup">
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="search_name" placeholder="Enter product name..." value="<?= htmlspecialchars($search_name) ?>">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="search_category">
          <option value="All">All</option>
          <?php foreach ($cat_list as $c): ?>
            <option value="<?= htmlspecialchars($c['category']) ?>" <?= $search_category===$c['category']?'selected':'' ?>>
              <?= htmlspecialchars($c['category']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>As of Date <span style="font-weight:normal;color:#888;font-size:12px">— leave blank for current</span></label>
        <input type="date" name="search_date" value="<?= htmlspecialchars($search_date) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <button type="submit" class="btn">Search</button>
      <a href="stocking_management.php?tab=stock-lookup" class="btn secondary">Reset</a>
    </form>

    <?php if ($search_date !== ''): ?>
      <div class="date-info-box">
        Showing stock as of <strong><?= htmlspecialchars($search_date) ?></strong>
        <a href="stocking_management.php?tab=stock-lookup" style="margin-left:auto;color:#185fa5;font-size:13px;">View current stock</a>
      </div>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>Product Code</th>
          <th>Product Name</th>
          <th>Image</th>
          <th>Category</th>
          <th><?= $search_date !== '' ? 'Stock at '.htmlspecialchars($search_date) : 'Current Stock' ?></th>
          <?php if ($search_date !== ''): ?>
            <th>Total Imported</th>
            <th>Total Sold</th>
          <?php endif; ?>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="<?= $search_date !== '' ? 8 : 6 ?>" class="no-data">No products found.</td></tr>
        <?php else: foreach ($products as $p): $st = stockStatus($p['stock'], $min_stock); ?>
          <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td style="text-align:left"><?= htmlspecialchars($p['name']) ?></td>
            <td><img src="../../images/<?= htmlspecialchars($p['image']) ?>" onerror="this.style.opacity='.2'"></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td><span class="<?= $search_date !== '' ? 'stock-as-of' : '' ?>"><?= intval($p['stock']) ?></span></td>
            <?php if ($search_date !== ''): ?>
              <td style="color:#2e7d32"><?= $p['in_qty']  ?? 0 ?></td>
              <td style="color:#c62828"><?= $p['out_qty'] ?? 0 ?></td>
            <?php endif; ?>
            <td><span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div class="alert-section" style="margin-top:16px;">
      <div class="alert-title">Note</div>
      <?php if ($search_date !== ''): ?>
        Stock at <strong><?= htmlspecialchars($search_date) ?></strong>
        = Total imported (Completed, entry date up to selected date) minus Total sold (order date up to selected date).
      <?php else: ?>
        Current stock = Total imported (Completed) minus Total sold.
        Alert threshold: <strong><?= $min_stock ?></strong> — adjust in the Low Stock Alert tab.
      <?php endif; ?>
    </div>
  </div>

  <!-- ====== TAB 2: Low Stock Alert ====== -->
  <div class="tab-content <?= $active_tab==='stock-alert'?'active':'' ?>" id="stock-alert">

    <form method="POST" action="stocking_management.php?tab=stock-alert" class="threshold-box">
      <input type="hidden" name="set_threshold" value="1">
      <span class="label">Low stock alert threshold:</span>
      <input type="number" name="threshold" value="<?= $min_stock ?>" min="0" max="9999">
      <span style="font-size:14px;color:#666">items</span>
      <button type="submit" class="btn" style="padding:8px 18px;">Apply</button>
      <span class="threshold-badge">Current threshold: at most <?= $min_stock ?> items = Low Stock</span>
    </form>

    <div class="alert-section" style="margin-bottom:16px;">
      <div class="alert-title">Low Stock Warning</div>
      <?php if (empty($low_products)): ?>
        All products are above the threshold of <?= $min_stock ?>.
      <?php else: ?>
        <strong><?= count($low_products) ?></strong> product(s) have stock at or below <?= $min_stock ?> and need restocking.
      <?php endif; ?>
    </div>

    <table>
      <thead>
        <tr>
          <th>Product Code</th><th>Product Name</th><th>Image</th>
          <th>Category</th><th>Current Stock</th><th>Threshold</th><th>Need to Restock</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($low_products)): ?>
          <tr><td colspan="8" class="no-data">All products are sufficiently stocked.</td></tr>
        <?php else: foreach ($low_products as $p): $st = stockStatus($p['stock'], $min_stock); $need = max(0, $min_stock - $p['stock'] + 1); ?>
          <tr>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td style="text-align:left"><?= htmlspecialchars($p['name']) ?></td>
            <td><img src="../../images/<?= htmlspecialchars($p['image']) ?>" onerror="this.style.opacity='.2'"></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td><strong style="color:<?= $p['stock'] <= 0 ? '#c62828' : '#f57c00' ?>"><?= intval($p['stock']) ?></strong></td>
            <td><?= $min_stock ?></td>
            <td><span style="color:#c62828;font-weight:600">+<?= $need ?></span></td>
            <td><span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ====== TAB 3: Import - Export - Balance ====== -->
  <div class="tab-content <?= $active_tab==='stock-report'?'active':'' ?>" id="stock-report">

    <!-- Stat cards -->
    <div class="stat-grid">
      <div class="stat-card clickable" onclick="openStockModal()">
        <div class="stat-icon si-gold">BOX</div>
        <div class="stat-info">
          <h3><?= number_format($total_stock) ?></h3>
          <p>Current Stock</p>
          <small>Click to view details</small>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">IN</div>
        <div class="stat-info">
          <h3><?= $count_in ?></h3>
          <p>In Stock (5 or more)</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">LOW</div>
        <div class="stat-info">
          <h3><?= $count_low ?></h3>
          <p>Low Stock (1 to 4)</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-red">OUT</div>
        <div class="stat-info">
          <h3><?= $count_out ?></h3>
          <p>Out of Stock</p>
        </div>
      </div>
    </div>

    <form method="GET" action="stocking_management.php" class="search-form">
      <input type="hidden" name="tab" value="stock-report">
      <div class="form-group">
        <label>Product</label>
        <input type="text" name="report_product" placeholder="Enter product name..." value="<?= htmlspecialchars($report_product) ?>">
      </div>
      <div class="form-group">
        <label>From</label>
        <input type="date" name="report_from" value="<?= htmlspecialchars($report_from) ?>">
      </div>
      <div class="form-group">
        <label>To</label>
        <input type="date" name="report_to" value="<?= htmlspecialchars($report_to) ?>">
      </div>
      <button type="submit" class="btn">Search</button>
      <a href="stocking_management.php?tab=stock-report" class="btn secondary">Reset</a>
    </form>

    <p style="font-size:13px;color:#888;margin-bottom:12px;">
      Click on the <span style="color:#8e4b00;font-weight:600">Import</span> or
      <span style="color:#5a2d00;font-weight:600">Export</span> numbers to view order details for that day.
      Click on <span style="color:#4a6741;font-weight:600">Current Stock</span> to view stock breakdown by product.
    </p>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Import Qty</th>
          <th>Export Qty</th>
          <th>Balance</th>
          <th>Current Stock</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($report_rows)): ?>
          <tr><td colspan="5" class="no-data">No report data found.</td></tr>
        <?php else: foreach ($report_rows as $r):
          $balance = $r['import'] - $r['export'];
        ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars($r['date']) ?></td>
            <td>
              <?php if ($r['import'] > 0): ?>
                <button class="drill-link import-link"
                        onclick="openModal('import','<?= $r['date'] ?>','<?= $r['date'] ?>','<?= htmlspecialchars(addslashes($report_product)) ?>','<?= $r['date'] ?>')">
                  <?= $r['import'] ?> <span class="drill-icon">v</span>
                </button>
              <?php else: ?>
                <span class="drill-link zero">0</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['export'] > 0): ?>
                <button class="drill-link export-link"
                        onclick="openModal('export','<?= $r['date'] ?>','<?= $r['date'] ?>','<?= htmlspecialchars(addslashes($report_product)) ?>','<?= $r['date'] ?>')">
                  <?= $r['export'] ?> <span class="drill-icon">v</span>
                </button>
              <?php else: ?>
                <span class="drill-link zero">0</span>
              <?php endif; ?>
            </td>
            <td>
              <span style="font-weight:700;color:<?= $balance >= 0 ? '#2e7d32' : '#c62828' ?>">
                <?= $balance >= 0 ? '+' : '' ?><?= $balance ?>
              </span>
            </td>
            <td>
              <button class="drill-link" onclick="openStockModal()"
                      style="color:#4a6741;background:#f0f7ee;border:1px solid #a5c8a0;font-size:15px;font-weight:700;">
                <?= $current_stock ?> <span class="drill-icon">v</span>
              </button>
              <span class="stock-note">actual stock</span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if (!empty($report_rows)): ?>
    <div class="alert-section" style="margin-top:16px;">
      <div class="alert-title">Summary (filtered period)</div>
      <p>Total Imported: <strong><?= $total_import ?></strong> units
        <?php if ($total_import > 0): ?>
          &nbsp;
          <button class="drill-link import-link" style="font-size:13px;"
                  onclick="openModal('import','<?= htmlspecialchars($report_from) ?>','<?= htmlspecialchars($report_to) ?>','<?= htmlspecialchars(addslashes($report_product)) ?>','')">
            View all import orders <span class="drill-icon">v</span>
          </button>
        <?php endif; ?>
      </p>
      <p style="margin-top:8px;">Total Exported: <strong><?= $total_export ?></strong> units
        <?php if ($total_export > 0): ?>
          &nbsp;
          <button class="drill-link export-link" style="font-size:13px;"
                  onclick="openModal('export','<?= htmlspecialchars($report_from) ?>','<?= htmlspecialchars($report_to) ?>','<?= htmlspecialchars(addslashes($report_product)) ?>','')">
            View all export orders <span class="drill-icon">v</span>
          </button>
        <?php endif; ?>
      </p>
      <p style="margin-top:8px;">
        Current Stock:
        <button class="drill-link" onclick="openStockModal()"
                style="color:#4a6741;background:#f0f7ee;border:1px solid #a5c8a0;font-size:14px;font-weight:700;">
          <strong><?= $current_stock ?></strong> units <span class="drill-icon">v</span>
        </button>
      </p>
    </div>
    <?php endif; ?>
  </div>

</main>

<!-- ====== MODAL: Current Stock Detail ====== -->
<div class="modal-overlay" id="stockModal" onclick="closeStockOutside(event)">
  <div class="modal">
    <div class="modal-header stock-header">
      <div>
        <h3>Current Stock Detail</h3>
        <div class="meta">
          Total: <?= number_format($total_stock) ?> units across <?= count($stock_details) ?> products
        </div>
      </div>
      <button class="modal-close" onclick="closeStockModal()">X</button>
    </div>

    <div class="stock-summary">
      <div class="s-item">
        <strong style="color:#43a047"><?= $count_in ?></strong>In Stock (5+)
      </div>
      <div class="s-item">
        <strong style="color:#fb8c00"><?= $count_low ?></strong>Low Stock (1-4)
      </div>
      <div class="s-item">
        <strong style="color:#e53935"><?= $count_out ?></strong>Out of Stock
      </div>
      <div class="s-item">
        <strong style="color:#4a6741"><?= number_format($total_stock) ?></strong>Total Units
      </div>
    </div>

    <div class="modal-body">
      <table class="stock-modal-table">
        <thead>
          <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th style="text-align:center">Stock Qty</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stock_details as $item):
            $qty = (int)$item['stock'];
            if ($qty <= 0)    { $qc = 'qty-red';    $sl = 'Out of Stock'; $sc = 'status-danger'; }
            elseif ($qty < 5) { $qc = 'qty-orange'; $sl = 'Low Stock';    $sc = 'status-warning'; }
            else              { $qc = 'qty-green';  $sl = 'In Stock';     $sc = 'status-normal'; }
          ?>
          <tr>
            <td>
              <code style="background:#f5f0e8;padding:2px 8px;border-radius:6px;color:#8e4b00;font-size:12px">
                <?= htmlspecialchars($item['id']) ?>
              </code>
            </td>
            <td style="font-weight:600;text-align:left"><?= htmlspecialchars($item['name']) ?></td>
            <td>
              <span style="background:#f0e6d3;color:#6b3500;padding:2px 8px;border-radius:10px;font-size:12px">
                <?= htmlspecialchars($item['category'] ?? '-') ?>
              </span>
            </td>
            <td style="text-align:center">
              <span class="qty-badge <?= $qc ?>"><?= $qty ?></span>
            </td>
            <td><span class="status-badge <?= $sc ?>"><?= $sl ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="stock-legend">
      <span><span class="leg-dot" style="background:#43a047"></span> 5 or more: In Stock</span>
      <span><span class="leg-dot" style="background:#fb8c00"></span> 1 to 4: Low Stock</span>
      <span><span class="leg-dot" style="background:#e53935"></span> 0: Out of Stock</span>
    </div>
  </div>
</div>

<!-- ====== MODAL: Drill-down Import / Export ====== -->
<div class="modal-overlay" id="drillModal" onclick="closeModalOutside(event)">
  <div class="modal" id="modalBox">
    <div class="modal-header" id="modalHeader">
      <div>
        <h3 id="modalTitle">Order Details</h3>
        <div class="meta" id="modalMeta"></div>
      </div>
      <button class="modal-close" onclick="closeModal()">X</button>
    </div>

    <div class="modal-summary" id="modalSummary" style="display:none">
      <div class="sum-item"><strong id="sumOrders">-</strong>Orders</div>
      <div class="sum-item"><strong id="sumQty">-</strong>Total Qty</div>
      <div class="sum-item"><strong id="sumValue">-</strong>Total Value</div>
    </div>

    <div class="modal-body" id="modalBody">
      <div class="modal-loading">
        <div class="spinner"></div>
        Loading data...
      </div>
    </div>
  </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab.dataset.tab);
    window.location.href = url.toString();
  });
});

// ============================================================
// Drill-down modal (Import / Export)
// ============================================================
function openModal(type, dateFrom, dateTo, product, dateSingle) {
  const overlay = document.getElementById('drillModal');
  const header  = document.getElementById('modalHeader');
  const title   = document.getElementById('modalTitle');
  const meta    = document.getElementById('modalMeta');
  const body    = document.getElementById('modalBody');
  const summary = document.getElementById('modalSummary');

  header.className = 'modal-header ' + (type === 'import' ? 'import-header' : 'export-header');
  title.textContent = type === 'import' ? 'Import Order Details' : 'Export Order Details';

  let metaText = '';
  if (dateSingle) {
    metaText = 'Date: ' + dateSingle;
  } else {
    if (dateFrom && dateTo) metaText = 'From ' + dateFrom + ' to ' + dateTo;
    else if (dateFrom)      metaText = 'From ' + dateFrom;
    else if (dateTo)        metaText = 'Until ' + dateTo;
    else                    metaText = 'All time';
  }
  if (product) metaText += ' | Product: ' + product;
  meta.textContent = metaText;

  overlay.classList.add('open');
  summary.style.display = 'none';
  body.innerHTML = '<div class="modal-loading"><div class="spinner"></div>Loading data...</div>';
  document.body.style.overflow = 'hidden';

  const params = new URLSearchParams({ ajax: '1', type: type });
  if (dateSingle) { params.set('date', dateSingle); }
  else {
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo)   params.set('date_to',   dateTo);
  }
  if (product) params.set('product', product);

  fetch('stocking_management.php?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.rows.length) {
        body.innerHTML = '<div class="modal-empty">No orders found for this period.</div>';
        return;
      }
      renderModal(data.rows, type);
    })
    .catch(() => {
      body.innerHTML = '<div class="modal-empty">Failed to load data. Please try again.</div>';
    });
}

function renderModal(rows, type) {
  const body    = document.getElementById('modalBody');
  const summary = document.getElementById('modalSummary');

  const groups = {};
  rows.forEach(r => {
    if (!groups[r.order_number]) groups[r.order_number] = [];
    groups[r.order_number].push(r);
  });

  const orderCount = Object.keys(groups).length;
  let totalQty = 0, totalValue = 0;
  rows.forEach(r => {
    totalQty   += parseInt(r.quantity)      || 0;
    totalValue += parseFloat(r.total_price) || 0;
  });

  document.getElementById('sumOrders').textContent = orderCount;
  document.getElementById('sumQty').textContent    = totalQty;
  document.getElementById('sumValue').textContent  = formatCurrency(totalValue);
  summary.style.display = 'flex';

  const isImport = type === 'import';
  let html = '<table class="modal-table"><thead><tr>';
  html += '<th>Order No.</th><th>Date</th>';
  html += '<th>' + (isImport ? 'Supplier' : 'Customer') + '</th>';
  html += '<th>Product ID</th><th>Product Name</th>';
  html += '<th>Qty</th><th>Unit Price</th><th>Total</th><th>Status</th>';
  html += '</tr></thead><tbody>';

  Object.entries(groups).forEach(([orderNum, items]) => {
    const first    = items[0];
    const orderQty = items.reduce((s, i) => s + (parseInt(i.quantity)||0), 0);
    const orderVal = items.reduce((s, i) => s + (parseFloat(i.total_price)||0), 0);

    html += '<tr class="group-row"><td colspan="9">';
    html += '<span style="font-family:monospace">' + esc(orderNum) + '</span>';
    html += ' | ' + (isImport ? 'Entry Date: ' : 'Order Date: ') + esc(first.entry_date);
    html += ' | ' + esc(first.supplier || '-');
    html += ' | Qty: <strong>' + orderQty + '</strong>';
    html += ' | Value: <strong>' + formatCurrency(orderVal) + '</strong>';
    html += '</td></tr>';

    items.forEach(r => {
      html += '<tr>';
      html += '<td><span class="order-num">' + esc(r.order_number) + '</span></td>';
      html += '<td>' + esc(r.entry_date) + '</td>';
      html += '<td>' + esc(r.supplier || '-') + '</td>';
      html += '<td><span class="product-chip">' + esc(r.product_id) + '</span></td>';
      html += '<td style="text-align:left;min-width:160px">' + esc(r.product_name) + '</td>';
      html += '<td><strong>' + (parseInt(r.quantity)||0) + '</strong></td>';
      html += '<td class="price">' + formatCurrency(parseFloat(r.unit_price)||0) + '</td>';
      html += '<td class="price">' + formatCurrency(parseFloat(r.total_price)||0) + '</td>';
      html += '<td><span class="status-pill ' + getStatusClass(r.status) + '">' + esc(r.status) + '</span></td>';
      html += '</tr>';
    });
  });

  html += '</tbody></table>';
  body.innerHTML = html;
}

function getStatusClass(status) {
  const map = { Completed:'pill-completed', Draft:'pill-draft', Delivered:'pill-delivered', Pending:'pill-pending', Processed:'pill-processed', Cancelled:'pill-cancelled' };
  return map[status] || 'pill-draft';
}

function formatCurrency(val) {
  if (val >= 1000000) return (val/1000000).toFixed(2).replace(/\.?0+$/,'') + 'M';
  if (val >= 1000)    return (val/1000).toFixed(1).replace(/\.?0+$/,'') + 'K';
  return val.toLocaleString('en-US');
}

function esc(str) {
  if (!str) return '-';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeModal() {
  document.getElementById('drillModal').classList.remove('open');
  document.body.style.overflow = '';
}
function closeModalOutside(e) {
  if (e.target === document.getElementById('drillModal')) closeModal();
}

// ============================================================
// Stock detail modal
// ============================================================
function openStockModal() {
  document.getElementById('stockModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeStockModal() {
  document.getElementById('stockModal').classList.remove('open');
  document.body.style.overflow = '';
}
function closeStockOutside(e) {
  if (e.target === document.getElementById('stockModal')) closeStockModal();
}

// ESC closes both modals
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeModal(); closeStockModal(); }
});
</script>
</body>
</html>
<?php $conn->close(); ?>