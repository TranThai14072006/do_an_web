<?php
session_start();
require_once '../config/config.php';

// Guard: only logged-in admins can access this page
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// ──────────────────────────────────────────────
// KPI STATS
// ──────────────────────────────────────────────
// Total products
$total_products = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM products");
if ($r) $total_products = (int)$r->fetch_assoc()['cnt'];

// Total customers
$total_customers = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM customers");
if ($r) $total_customers = (int)$r->fetch_assoc()['cnt'];

// Total orders & revenue
$total_orders = 0; $total_revenue = 0; $pending_orders = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev FROM orders WHERE status = 'Received'");
if ($r) { $row = $r->fetch_assoc(); $total_orders = (int)$row['cnt']; $total_revenue = (float)$row['rev']; }
$r = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'Pending'");
if ($r) $pending_orders = (int)$r->fetch_assoc()['cnt'];

// Low stock products (stock <= 5)
$low_stock = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock <= 5");
if ($r) $low_stock = (int)$r->fetch_assoc()['cnt'];

// Revenue this month
$revenue_month = 0;
$r = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS rev FROM orders WHERE status = 'Received' AND MONTH(order_date)=MONTH(CURDATE()) AND YEAR(order_date)=YEAR(CURDATE())");
if ($r) $revenue_month = (float)$r->fetch_assoc()['rev'];

// ──────────────────────────────────────────────
// RECENT ORDERS (last 8)
// ──────────────────────────────────────────────
$recent_orders = [];
$r = $conn->query("
    SELECT o.id, o.order_number, o.order_date, o.total_amount, o.status,
           COALESCE(c.full_name, 'Unknown') AS customer_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    ORDER BY o.order_date DESC, o.id DESC
    LIMIT 8
");
if ($r) while ($row = $r->fetch_assoc()) $recent_orders[] = $row;

// ──────────────────────────────────────────────
// RECENT IMPORTS (last 6)
// ──────────────────────────────────────────────
$recent_imports = [];
$r = $conn->query("
    SELECT id, order_number, entry_date, supplier, total_quantity, total_value, status
    FROM goods_receipt
    ORDER BY entry_date DESC, id DESC
    LIMIT 6
");
if ($r) while ($row = $r->fetch_assoc()) $recent_imports[] = $row;

// ──────────────────────────────────────────────
// TOP SELLING PRODUCTS (by order_items quantity)
// ──────────────────────────────────────────────
$top_products = [];
$r = $conn->query("
    SELECT p.name, p.image, p.category, SUM(oi.quantity) AS total_sold, SUM(oi.total_price) AS revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'Received'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
if ($r) while ($row = $r->fetch_assoc()) $top_products[] = $row;

// ──────────────────────────────────────────────
// ORDER STATUS COUNTS (for mini chart)
// ──────────────────────────────────────────────
$status_counts = ['Pending'=>0,'Processed'=>0,'Shipping'=>0,'Delivered'=>0,'Cancelled'=>0];
$r = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
if ($r) while ($row = $r->fetch_assoc()) {
    if (isset($status_counts[$row['status']])) $status_counts[$row['status']] = (int)$row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luxury Jewelry Admin Panel</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* ── PAGE HEADER ── */
    .page-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px;
    }
    .page-header h1 { font-size: 24px; color: #8e4b00; font-weight: 700; }
    .page-header .date-badge {
      background: #fff; border: 1px solid #e8d5b0; border-radius: 8px;
      padding: 8px 16px; font-size: 13px; color: #666; display:flex; align-items:center; gap:6px;
    }

    /* ── KPI CARDS ── */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
      margin-bottom: 28px;
    }
    .kpi-card {
      background: #fff;
      border-radius: 14px;
      padding: 22px 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07);
      display: flex; align-items: center; gap: 16px;
      transition: transform .2s, box-shadow .2s;
      border-left: 4px solid transparent;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
    .kpi-card.orange { border-left-color: #f59e0b; }
    .kpi-card.green  { border-left-color: #10b981; }
    .kpi-card.blue   { border-left-color: #3b82f6; }
    .kpi-card.red    { border-left-color: #ef4444; }
    .kpi-card.purple { border-left-color: #8b5cf6; }
    .kpi-icon {
      width: 52px; height: 52px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; flex-shrink: 0;
    }
    .kpi-card.orange .kpi-icon { background: #fef3c7; color: #d97706; }
    .kpi-card.green  .kpi-icon { background: #d1fae5; color: #059669; }
    .kpi-card.blue   .kpi-icon { background: #dbeafe; color: #2563eb; }
    .kpi-card.red    .kpi-icon { background: #fee2e2; color: #dc2626; }
    .kpi-card.purple .kpi-icon { background: #ede9fe; color: #7c3aed; }
    .kpi-info { flex: 1; min-width: 0; }
    .kpi-label { font-size: 12px; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .kpi-value { font-size: 26px; font-weight: 800; color: #1a1a1a; line-height: 1; }
    .kpi-sub   { font-size: 12px; color: #aaa; margin-top: 4px; }

    /* ── SECTION ROW ── */
    .section-row {
      display: grid;
      gap: 20px;
      margin-bottom: 24px;
    }
    .section-row.two-col { grid-template-columns: 1fr 1fr; }
    .section-row.three-col { grid-template-columns: 1fr 1.2fr 0.8fr; }

    /* ── PANEL ── */
    .panel {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07);
      overflow: hidden;
    }
    .panel-header {
      padding: 18px 22px 14px;
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1px solid #f0ebe3;
    }
    .panel-title { font-size: 15px; font-weight: 700; color: #4a2800; display:flex; align-items:center; gap:8px; }
    .panel-title i { color: #8e4b00; }
    .panel-link { font-size: 13px; color: #8e4b00; text-decoration: none; font-weight: 600; }
    .panel-link:hover { text-decoration: underline; }
    .panel-body { padding: 0; }

    /* ── ORDERS TABLE ── */
    .orders-table { width: 100%; border-collapse: collapse; }
    .orders-table th {
      background: #fdf7ee; color: #8e4b00; font-size: 12px;
      text-transform: uppercase; letter-spacing: .5px;
      padding: 10px 16px; text-align: left; font-weight: 700;
    }
    .orders-table td {
      padding: 11px 16px; border-bottom: 1px solid #f5f0e8;
      font-size: 14px; color: #333; vertical-align: middle;
    }
    .orders-table tr:last-child td { border-bottom: none; }
    .orders-table tr:hover td { background: #fefcf7; }

    /* Order status badges */
    .badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    }
    .badge-pending   { background: #fef3c7; color: #92400e; }
    .badge-processed { background: #dbeafe; color: #1d4ed8; }
    .badge-shipping  { background: #ede9fe; color: #6d28d9; }
    .badge-delivered { background: #d1fae5; color: #065f46; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }

    /* ── IMPORT TABLE ── */
    .import-table { width: 100%; border-collapse: collapse; }
    .import-table th {
      background: #fdf7ee; color: #8e4b00; font-size: 12px;
      text-transform: uppercase; letter-spacing: .5px;
      padding: 10px 16px; text-align: left; font-weight: 700;
    }
    .import-table td {
      padding: 11px 16px; border-bottom: 1px solid #f5f0e8;
      font-size: 14px; color: #333; vertical-align: middle;
    }
    .import-table tr:last-child td { border-bottom: none; }
    .import-table tr:hover td { background: #fefcf7; }
    .badge-draft     { background: #fff3cd; color: #856404; }
    .badge-completed { background: #d4edda; color: #155724; }

    /* ── TOP PRODUCTS ── */
    .top-product-item {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 22px; border-bottom: 1px solid #f5f0e8;
    }
    .top-product-item:last-child { border-bottom: none; }
    .top-product-img {
      width: 44px; height: 44px; border-radius: 8px; object-fit: cover;
      border: 1px solid #eee; flex-shrink: 0;
      background: #f5f5f5; display:flex; align-items:center; justify-content:center;
    }
    .top-product-img img { width:44px; height:44px; border-radius:8px; object-fit:cover; }
    .top-product-info { flex: 1; min-width: 0; }
    .top-product-name { font-size: 14px; font-weight: 600; color: #222; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .top-product-cat  { font-size: 12px; color: #888; }
    .top-product-sold { font-size: 13px; font-weight: 700; color: #8e4b00; }

    /* ── STATUS DONUT (pure CSS) ── */
    .status-summary { padding: 18px 22px; }
    .status-row {
      display: flex; align-items: center; gap: 10px;
      padding: 7px 0; border-bottom: 1px solid #f5f0e8;
    }
    .status-row:last-child { border-bottom: none; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .status-row-label { flex: 1; font-size: 13px; color: #555; }
    .status-row-count { font-size: 13px; font-weight: 700; color: #333; }
    .status-bar-wrap { width: 80px; height: 6px; background: #eee; border-radius: 3px; }
    .status-bar-fill { height: 6px; border-radius: 3px; }

    /* ── QUICK ACTIONS ── */
    .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 18px 22px; }
    .qa-btn {
      display: flex; align-items: center; gap: 10px; padding: 13px 16px;
      border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px;
      transition: .2s; border: 1.5px solid transparent;
    }
    .qa-btn:hover { transform: translateY(-1px); }
    .qa-btn.primary   { background: #8e4b00; color: #f8ce86; }
    .qa-btn.primary:hover { background: #a3670b; }
    .qa-btn.secondary { background: #fdf7ee; color: #8e4b00; border-color: #e8d5b0; }
    .qa-btn.secondary:hover { background: #f8ce86; }
    .qa-btn i { font-size: 16px; }

    /* ── EMPTY STATE ── */
    .empty-row td { text-align: center; padding: 30px; color: #aaa; font-size: 14px; }

    /* ── ORDER NUMBER ── */
    .order-num { font-family: monospace; font-weight: 700; color: #8e4b00; font-size: 13px; }

    /* View link */
    .view-link { font-size: 13px; color: #8e4b00; text-decoration: none; font-weight: 600; }
    .view-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
<?php include 'sidebar_include.php'; ?>

<div class="content">

  <!-- ── PAGE HEADER ── -->
  <div class="page-header">
    <h1><i class="fas fa-tachometer-alt" style="margin-right:10px; opacity:.8;"></i>Dashboard</h1>
    <div class="date-badge">
      <i class="fas fa-calendar-alt"></i>
      <?php echo date('l, F j, Y'); ?>
    </div>
  </div>

  <!-- ── KPI CARDS ── -->
  <div class="kpi-grid">
    <div class="kpi-card orange">
      <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
      <div class="kpi-info">
        <div class="kpi-label">Completed Orders Revenue</div>
        <div class="kpi-value">$<?php echo number_format($total_revenue, 0); ?></div>
        <div class="kpi-sub">This month: $<?php echo number_format($revenue_month, 0); ?></div>
      </div>
    </div>
    <div class="kpi-card blue">
      <div class="kpi-icon"><i class="fas fa-shopping-bag"></i></div>
      <div class="kpi-info">
        <div class="kpi-label">Total Orders</div>
        <div class="kpi-value"><?php echo number_format($total_orders); ?></div>
        <div class="kpi-sub"><?php echo $pending_orders; ?> pending</div>
      </div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-icon"><i class="fas fa-users"></i></div>
      <div class="kpi-info">
        <div class="kpi-label">Customers</div>
        <div class="kpi-value"><?php echo number_format($total_customers); ?></div>
        <div class="kpi-sub">Registered accounts</div>
      </div>
    </div>
    <div class="kpi-card red">
      <div class="kpi-icon"><i class="fas fa-boxes"></i></div>
      <div class="kpi-info">
        <div class="kpi-label">Products</div>
        <div class="kpi-value"><?php echo number_format($total_products); ?></div>
        <div class="kpi-sub"><?php echo $low_stock; ?> low stock</div>
      </div>
    </div>
  </div>

  <!-- ── ROW 1: Recent Orders + Order Status ── -->
  <div class="section-row two-col" style="grid-template-columns: 2fr 1fr;">

    <!-- Recent Orders Panel -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-shopping-cart"></i> Recent Orders</div>
        <a href="Order Manage/order_management.php" class="panel-link">View All →</a>
      </div>
      <div class="panel-body">
        <table class="orders-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_orders)): ?>
            <tr class="empty-row"><td colspan="5">No orders yet.</td></tr>
            <?php else: ?>
            <?php foreach ($recent_orders as $order):
              $badge_map = [
                'Pending'   => 'badge-pending',
                'Processed' => 'badge-processed',
                'Processing'=> 'badge-processed',
                'Shipping'  => 'badge-shipping',
                'Shipped'   => 'badge-shipping',
                'Delivered' => 'badge-delivered',
                'Cancelled' => 'badge-cancelled',
              ];
              $ic_map = [
                'Pending'   => 'fa-clock',
                'Processed' => 'fa-cogs',
                'Processing'=> 'fa-cogs',
                'Shipping'  => 'fa-truck',
                'Shipped'   => 'fa-truck',
                'Delivered' => 'fa-check-circle',
                'Cancelled' => 'fa-times-circle',
              ];
              $bc = $badge_map[$order['status']] ?? 'badge-pending';
              $ic = $ic_map[$order['status']] ?? 'fa-question-circle';
            ?>
            <tr>
              <td><span class="order-num"><?php echo htmlspecialchars($order['order_number']); ?></span></td>
              <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
              <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
              <td style="font-weight:700; color:#8e4b00;">$<?php echo number_format($order['total_amount'], 2); ?></td>
              <td>
                <span class="badge <?php echo $bc; ?>">
                  <i class="fas <?php echo $ic; ?>"></i>
                  <?php echo htmlspecialchars($order['status']); ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Order Status Summary Panel -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-chart-pie"></i> Order Status</div>
      </div>
      <div class="status-summary">
        <?php
        $status_colors = [
          'Pending'   => '#f59e0b',
          'Processed' => '#3b82f6',
          'Shipping'  => '#8b5cf6',
          'Delivered' => '#10b981',
          'Cancelled' => '#ef4444',
        ];
        $total_status = max(1, array_sum($status_counts));
        foreach ($status_counts as $st => $cnt):
          $pct = round($cnt / $total_status * 100);
          $col = $status_colors[$st] ?? '#ccc';
        ?>
        <div class="status-row">
          <div class="status-dot" style="background:<?php echo $col; ?>;"></div>
          <div class="status-row-label"><?php echo $st; ?></div>
          <div class="status-bar-wrap">
            <div class="status-bar-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $col; ?>;"></div>
          </div>
          <div class="status-row-count"><?php echo $cnt; ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Quick Actions -->
      <div class="panel-header" style="margin-top:0; padding-top:14px;">
        <div class="panel-title"><i class="fas fa-bolt"></i> Quick Actions</div>
      </div>
      <div class="quick-actions">
        <a href="product_management.php" class="qa-btn primary"><i class="fas fa-plus"></i> Add Product</a>
        <a href="Order Manage/order_management.php" class="qa-btn secondary"><i class="fas fa-list"></i> All Orders</a>
        <a href="Import_product/import_management.php" class="qa-btn secondary"><i class="fas fa-file-import"></i> Imports</a>
        <a href="customer_management.php" class="qa-btn secondary"><i class="fas fa-users"></i> Customers</a>
      </div>
    </div>

  </div><!-- /section-row -->

  <!-- ── ROW 2: Recent Imports + Top Products ── -->
  <div class="section-row two-col" style="grid-template-columns: 1.4fr 1fr;">

    <!-- Recent Imports Panel -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-file-import"></i> Recent Import Receipts</div>
        <a href="Import_product/import_management.php" class="panel-link">View All →</a>
      </div>
      <div class="panel-body">
        <table class="import-table">
          <thead>
            <tr>
              <th>Receipt #</th>
              <th>Date</th>
              <th>Supplier</th>
              <th>Qty</th>
              <th>Value</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_imports)): ?>
            <tr class="empty-row"><td colspan="6">No import records yet.</td></tr>
            <?php else: ?>
            <?php foreach ($recent_imports as $imp):
              $imp_badge = $imp['status'] === 'Completed' ? 'badge-completed' : 'badge-draft';
            ?>
            <tr>
              <td>
                <a href="Import_product/entry_form_detail.php?id=<?php echo $imp['id']; ?>" class="view-link">
                  <?php echo htmlspecialchars($imp['order_number']); ?>
                </a>
              </td>
              <td><?php echo htmlspecialchars($imp['entry_date']); ?></td>
              <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?php echo htmlspecialchars($imp['supplier'] ?? '—'); ?>
              </td>
              <td style="text-align:center; font-weight:600;"><?php echo (int)$imp['total_quantity']; ?></td>
              <td style="font-weight:700; color:#8e4b00;">$<?php echo number_format($imp['total_value'], 2); ?></td>
              <td><span class="badge <?php echo $imp_badge; ?>"><?php echo $imp['status']; ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Top Products Panel -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-fire"></i> Top Selling Products</div>
        <a href="product_management.php" class="panel-link">View All →</a>
      </div>
      <div class="panel-body">
        <?php if (empty($top_products)): ?>
        <div style="text-align:center; padding:40px; color:#aaa; font-size:14px;">No sales data yet.</div>
        <?php else: ?>
        <?php foreach ($top_products as $rank => $tp): ?>
        <div class="top-product-item">
          <div><?php
            $rank_colors = ['#f59e0b','#94a3b8','#cd7c3e','#aaa','#bbb'];
            echo '<div style="width:22px; height:22px; border-radius:50%; background:' . $rank_colors[$rank] . '; color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">' . ($rank+1) . '</div>';
          ?></div>
          <div class="top-product-img">
            <?php if (!empty($tp['image'])): ?>
            <img src="../images/<?php echo htmlspecialchars($tp['image']); ?>"
                 onerror="this.style.opacity='.2'" alt="<?php echo htmlspecialchars($tp['name']); ?>">
            <?php else: ?>
            <i class="fas fa-gem" style="color:#ccc; font-size:20px;"></i>
            <?php endif; ?>
          </div>
          <div class="top-product-info">
            <div class="top-product-name"><?php echo htmlspecialchars($tp['name']); ?></div>
            <div class="top-product-cat"><?php echo htmlspecialchars($tp['category']); ?></div>
          </div>
          <div class="top-product-sold"><?php echo (int)$tp['total_sold']; ?> sold</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /section-row -->

</div><!-- /.content -->

<script>
  // Nothing needed — pure PHP/CSS dashboard
</script>
</body>
</html>