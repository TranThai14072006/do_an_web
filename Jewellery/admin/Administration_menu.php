<?php
session_start();
require_once '../config/config.php';

// Guard: only logged-in admins can access this page
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}





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


    </div>

  </div><!-- /section-row -->



</div><!-- /.content -->

<script>
  // Nothing needed — pure PHP/CSS dashboard
</script>
</body>
</html>