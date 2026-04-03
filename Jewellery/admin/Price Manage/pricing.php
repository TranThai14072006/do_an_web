<?php
require_once "../../config/config.php";

// ============================================================
// Handle inline profit_percent update (Tab 2 - by product)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profit') {
        $id     = trim($_POST['id']     ?? '');
        $profit = intval($_POST['profit'] ?? 0);
        $stmt = $conn->prepare("UPDATE products SET profit_percent = ?,
                                price = ROUND(cost_price * (1 + ? / 100.0), 2)
                                WHERE id = ?");
        $stmt->bind_param('iis', $profit, $profit, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: pricing.php?tab=tab2&saved=1");
        exit;
    }

    if ($_POST['action'] === 'update_category_profit') {
        $category = trim($_POST['category'] ?? '');
        $profit   = intval($_POST['profit']   ?? 0);
        $stmt = $conn->prepare("UPDATE products SET profit_percent = ?,
                                price = ROUND(cost_price * (1 + ? / 100.0), 2)
                                WHERE category = ?");
        $stmt->bind_param('iis', $profit, $profit, $category);
        $stmt->execute();
        $stmt->close();
        header("Location: pricing.php?tab=tab3&saved=1");
        exit;
    }
}

// ============================================================
// Active tab
// ============================================================
$active_tab = $_GET['tab'] ?? 'tab1';
$saved      = isset($_GET['saved']);

// ============================================================
// Tab 1: Selling Price Lookup — search
// ============================================================
$q_name     = trim($_GET['name']     ?? '');
$q_category = trim($_GET['category'] ?? 'All');
$q_gender   = trim($_GET['gender']   ?? 'All');

$where1  = ['1=1'];
$params1 = [];
$types1  = '';
if ($q_name !== '') {
    $where1[]  = 'name LIKE ?';
    $params1[] = '%' . $q_name . '%';
    $types1   .= 's';
}
if ($q_category !== 'All' && $q_category !== '') {
    $where1[]  = 'category = ?';
    $params1[] = $q_category;
    $types1   .= 's';
}
if ($q_gender !== 'All' && $q_gender !== '') {
    $where1[]  = 'gender = ?';
    $params1[] = $q_gender;
    $types1   .= 's';
}
$sql1  = "SELECT * FROM products WHERE " . implode(' AND ', $where1) . " ORDER BY id";
$stmt1 = $conn->prepare($sql1);
if ($params1) $stmt1->bind_param($types1, ...$params1);
$stmt1->execute();
$products_tab1 = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

// ============================================================
// Tab 2: Profit by Product
// ============================================================
$products_tab2 = $conn->query("SELECT * FROM products ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// ============================================================
// Tab 3: Profit by Category
// ============================================================
$categories = $conn->query("SELECT category, AVG(profit_percent) as avg_profit,
                             COUNT(*) as cnt FROM products GROUP BY category ORDER BY category")
                    ->fetch_all(MYSQLI_ASSOC);

// ============================================================
// Tab 4: Cost Price by Lot (breakdown per receipt)
// ============================================================
$q4_name = trim($_GET['lot_name'] ?? '');
$q4_from = trim($_GET['lot_from'] ?? '');
$q4_to   = trim($_GET['lot_to']   ?? '');

$where4  = ["gr.status = 'Completed'"];
$params4 = [];
$types4  = '';
if ($q4_name !== '') {
    $where4[]  = 'gri.product_name LIKE ?';
    $params4[] = '%' . $q4_name . '%';
    $types4   .= 's';
}
if ($q4_from !== '') {
    $where4[]  = 'gr.entry_date >= ?';
    $params4[] = $q4_from;
    $types4   .= 's';
}
if ($q4_to !== '') {
    $where4[]  = 'gr.entry_date <= ?';
    $params4[] = $q4_to;
    $types4   .= 's';
}

$sql4 = "
    SELECT
        gr.id            AS receipt_id,
        gr.order_number  AS receipt_no,
        gr.entry_date,
        gr.supplier,
        gri.product_id,
        gri.product_name,
        gri.quantity,
        gri.unit_price   AS lot_cost,
        gri.total_price  AS lot_total,
        p.profit_percent,
        ROUND(gri.unit_price * (1 + p.profit_percent / 100.0), 2) AS lot_sell_price,
        p.cost_price     AS avg_cost,
        p.price          AS current_sell_price,
        p.image
    FROM goods_receipt gr
    JOIN goods_receipt_items gri ON gr.id = gri.receipt_id
    LEFT JOIN products p ON gri.product_id = p.id
    WHERE " . implode(' AND ', $where4) . "
    ORDER BY gr.entry_date DESC, gr.id DESC, gri.product_id
";
$stmt4 = $conn->prepare($sql4);
if ($params4) $stmt4->bind_param($types4, ...$params4);
$stmt4->execute();
$lot_rows = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt4->close();

// Get category list for dropdown
$cat_list = $conn->query("SELECT DISTINCT category FROM products ORDER BY category")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pricing Management</title>
<link rel="stylesheet" href="../admin_function.css">
<style>
/* TABS */
.tabs{display:flex;gap:0;border-bottom:2px solid #ddd;margin-bottom:24px;}
.tab-btn{padding:11px 22px;font-weight:600;color:#666;cursor:pointer;border:none;background:none;font-size:14px;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .2s;}
.tab-btn.active{color:#8e4b00;border-bottom-color:#8e4b00;}
.tab-content{display:none;}
.tab-content.active{display:block;animation:fadeIn .3s ease;}

/* PAGE-SPECIFIC OVERRIDES */
.search-section{background:#fff;border-radius:10px;padding:20px;display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;box-shadow:0 2px 6px rgba(0,0,0,0.08);margin-bottom:22px;}
.search-group{flex:1;min-width:150px;display:flex;flex-direction:column;}
.search-label{font-weight:600;margin-bottom:6px;font-size:14px;}
.search-input{height:40px;padding:8px 12px;border:1px solid #ccc;border-radius:6px;font-size:14px;width:100%;}
.btn-search,.btn-reset{height:40px;padding:0 18px;border:none;font-weight:600;border-radius:6px;cursor:pointer;font-size:14px;white-space:nowrap;}
.btn-search{background:#8e4b00;color:#fff;}
.btn-reset{background:#888;color:#fff;}
.btn-search:hover{background:#a3670b;}
.btn-reset:hover{background:#666;}
.product-img{width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #eee;}
.profit-form{display:flex;gap:8px;justify-content:center;align-items:center;}
.profit-input{width:72px;padding:6px 8px;border:1px solid #ccc;border-radius:5px;text-align:center;font-size:14px;}
.profit-input:focus{border-color:#8e4b00;outline:none;}
.lot-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#fff3cd;color:#856404;}
.diff-up{color:#2e7d32;font-weight:600;}
.diff-dn{color:#c62828;font-weight:600;}
.diff-eq{color:#888;}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-weight:600;}
.cat-badge{display:inline-block;background:#f8f9fa;border:1px solid #ddd;border-radius:6px;padding:3px 10px;font-size:13px;}
.no-data{text-align:center;padding:30px;color:#888;}
</style>
</head>
<body>

<?php include '../sidebar_include.php'; ?>

<main>
  <header><h1>Pricing & Profit Management</h1></header>

  <?php if ($saved): ?>
    <div class="alert-success">✅ Updated successfully!</div>
  <?php endif; ?>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn <?= $active_tab==='tab1'?'active':'' ?>" onclick="switchTab('tab1')">Selling Price Lookup</button>
    <button class="tab-btn <?= $active_tab==='tab2'?'active':'' ?>" onclick="switchTab('tab2')">Profit by Product</button>
    <button class="tab-btn <?= $active_tab==='tab3'?'active':'' ?>" onclick="switchTab('tab3')">Profit by Category</button>
    <button class="tab-btn <?= $active_tab==='tab4'?'active':'' ?>" onclick="switchTab('tab4')">Cost Price by Lot</button>
  </div>

  <!-- ====== TAB 1: Selling Price Lookup ====== -->
  <div id="tab1" class="tab-content <?= $active_tab==='tab1'?'active':'' ?>">
    <form method="GET" action="pricing.php">
      <input type="hidden" name="tab" value="tab1">
      <div class="search-section">
        <div class="search-group">
          <label class="search-label">Product Name</label>
          <input type="text" class="search-input" name="name" value="<?= htmlspecialchars($q_name) ?>" placeholder="Search...">
        </div>
        <div class="search-group">
          <label class="search-label">Gender</label>
          <select class="search-input" name="gender">
            <option value="All">All</option>
            <option value="Male"   <?= $q_gender === 'Male'   ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $q_gender === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Unisex" <?= $q_gender === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
          </select>
        </div>
        <button type="submit" class="btn-search">Search</button>
        <a href="pricing.php?tab=tab1" class="btn-reset" style="display:flex;align-items:center;text-decoration:none;padding:0 18px;">Reset</a>
      </div>
    </form>

    <table>
      <thead><tr><th>No.</th><th>Image</th><th>Product</th><th>Category</th><th>Cost Price</th><th>Profit %</th><th>Selling Price</th><th>Action</th></tr></thead>
      <tbody>
        <?php if (empty($products_tab1)): ?>
          <tr><td colspan="8" class="no-data">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($products_tab1 as $i => $row):
            $sell = $row['cost_price'] * (1 + $row['profit_percent'] / 100);
          ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><img src="../../images/<?= htmlspecialchars($row['image']) ?>" class="product-img" onerror="this.style.opacity='.2'"></td>
            <td style="text-align:left"><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td>$<?= number_format($row['cost_price'], 2) ?></td>
            <td><?= $row['profit_percent'] ?>%</td>
            <td><strong>$<?= number_format($sell, 2) ?></strong></td>
            <td><a href="edit_price.php?id=<?= urlencode($row['id']) ?>" class="btn small">Edit</a></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ====== TAB 2: Profit by Product ====== -->
  <div id="tab2" class="tab-content <?= $active_tab==='tab2'?'active':'' ?>">
    <p style="color:#666;font-size:14px;margin-bottom:16px;">Edit each product's profit percentage — the selling price will be recalculated automatically.</p>
    <table>
      <thead><tr><th>No.</th><th>Image</th><th>Product</th><th>Category</th><th>Cost Price</th><th>Profit %</th><th>Selling Price</th><th>Save</th></tr></thead>
      <tbody>
        <?php foreach ($products_tab2 as $i => $row):
          $sell = $row['cost_price'] * (1 + $row['profit_percent'] / 100);
        ?>
        <tr id="row-<?= htmlspecialchars($row['id']) ?>">
          <td><?= $i + 1 ?></td>
          <td><img src="../../images/<?= htmlspecialchars($row['image']) ?>" class="product-img" onerror="this.style.opacity='.2'"></td>
          <td style="text-align:left"><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['category']) ?></td>
          <td>$<?= number_format($row['cost_price'], 2) ?></td>
          <td>
            <!-- Inline input — calculates price preview on typing -->
            <input type="number" class="profit-input"
                   id="profit-<?= htmlspecialchars($row['id']) ?>"
                   value="<?= $row['profit_percent'] ?>" min="0" max="999"
                   data-cost="<?= $row['cost_price'] ?>"
                   data-id="<?= htmlspecialchars($row['id']) ?>"
                   oninput="previewPrice(this)">%
          </td>
          <td id="preview-<?= htmlspecialchars($row['id']) ?>">
            <strong>$<?= number_format($sell, 2) ?></strong>
          </td>
          <td>
            <form method="POST" action="pricing.php?tab=tab2" style="display:inline">
              <input type="hidden" name="action" value="update_profit">
              <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
              <input type="hidden" name="profit" id="hidden-<?= htmlspecialchars($row['id']) ?>" value="<?= $row['profit_percent'] ?>">
              <button type="submit" class="btn small"
                      onclick="syncHidden('<?= htmlspecialchars($row['id']) ?>')">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ====== TAB 3: Profit by Category ====== -->
  <div id="tab3" class="tab-content <?= $active_tab==='tab3'?'active':'' ?>">
    <p style="color:#666;font-size:14px;margin-bottom:16px;">Update the profit percentage for all products in a category at once.</p>
    <table>
      <thead><tr><th>No.</th><th>Category</th><th>Products</th><th>Avg Profit %</th><th>Set Profit % for All</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $i => $row): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
          <td><?= $row['cnt'] ?></td>
          <td><?= round($row['avg_profit']) ?>%</td>
          <td>
            <form method="POST" action="pricing.php?tab=tab3" class="profit-form">
              <input type="hidden" name="action" value="update_category_profit">
              <input type="hidden" name="category" value="<?= htmlspecialchars($row['category']) ?>">
              <input type="number" name="profit" class="profit-input" value="<?= round($row['avg_profit']) ?>" min="0" max="999"> %
              <button type="submit" class="btn small">Apply to all</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ====== TAB 4: Cost Price by Lot ====== -->
  <div id="tab4" class="tab-content <?= $active_tab==='tab4'?'active':'' ?>">
    <p style="color:#666;font-size:14px;margin-bottom:16px;">
      Look up cost price per import lot — compare against the current average cost and corresponding selling price.
    </p>

    <!-- Search -->
    <form method="GET" action="pricing.php">
      <input type="hidden" name="tab" value="tab4">
      <div class="search-section">
        <div class="search-group">
          <label class="search-label">Product Name</label>
          <input type="text" class="search-input" name="lot_name"
                 value="<?= htmlspecialchars($q4_name) ?>" placeholder="Search...">
        </div>
        <div class="search-group">
          <label class="search-label">From date</label>
          <input type="date" class="search-input" name="lot_from" value="<?= htmlspecialchars($q4_from) ?>">
        </div>
        <div class="search-group">
          <label class="search-label">To date</label>
          <input type="date" class="search-input" name="lot_to" value="<?= htmlspecialchars($q4_to) ?>">
        </div>
        <button type="submit" class="btn-search">Search</button>
        <a href="pricing.php?tab=tab4" class="btn-reset" style="display:flex;align-items:center;text-decoration:none;padding:0 18px;">Reset</a>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th>Receipt No.</th>
          <th>Date</th>
          <th>Supplier</th>
          <th>Image</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Lot Cost</th>
          <th>Avg Cost</th>
          <th>Diff</th>
          <th>Profit %</th>
          <th>Lot Sell Price</th>
          <th>Current Sell Price</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($lot_rows)): ?>
          <tr><td colspan="12" class="no-data">No data found.</td></tr>
        <?php else: ?>
          <?php
          $prev_receipt = null;
          foreach ($lot_rows as $row):
            $diff      = $row['lot_cost'] - $row['avg_cost'];
            $diff_cls  = $diff > 0 ? 'diff-up' : ($diff < 0 ? 'diff-dn' : 'diff-eq');
            $diff_sign = $diff > 0 ? '+' : '';
            $new_receipt = ($row['receipt_id'] !== $prev_receipt);
            $prev_receipt = $row['receipt_id'];
          ?>
          <tr <?= $new_receipt ? 'style="border-top:2px solid #f8ce86"' : '' ?>>
            <td>
              <?php if ($new_receipt): ?>
                <span class="lot-badge"><?= htmlspecialchars($row['receipt_no']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['entry_date']) ?></td>
            <td style="font-size:13px;color:#666"><?= htmlspecialchars($row['supplier'] ?? '—') ?></td>
            <td>
              <img src="../../images/<?= htmlspecialchars($row['image'] ?? ($row['product_id'].'.jpg')) ?>"
                   class="product-img" onerror="this.style.opacity='.2'">
            </td>
            <td style="text-align:left"><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= intval($row['quantity']) ?></td>
            <td>$<strong><?= number_format($row['lot_cost'], 2) ?></strong></td>
            <td style="color:#666">$<?= number_format($row['avg_cost'], 2) ?></td>
            <td class="<?= $diff_cls ?>">
              <?= $diff_sign ?>$<?= number_format(abs($diff), 2) ?>
            </td>
            <td><?= $row['profit_percent'] ?>%</td>
            <td>$<strong><?= number_format($row['lot_sell_price'], 2) ?></strong></td>
            <td>$<?= number_format($row['current_sell_price'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if (!empty($lot_rows)): ?>
    <div style="margin-top:14px;font-size:13px;color:#666;padding:10px 0;border-top:1px solid #eee;">
      <strong>Legend:</strong>
      <span class="diff-up">+X.XX</span> = lot cost higher than average &nbsp;|&nbsp;
      <span class="diff-dn">-X.XX</span> = lot cost lower than average &nbsp;|&nbsp;
      Lot Sell Price = selling price calculated based on that lot's cost (not the average)
    </div>
    <?php endif; ?>
  </div>

</main>

<script>
// ── Tab switching ──────────────────────────────────────────
function switchTab(id) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.tab-btn')[['tab1','tab2','tab3','tab4'].indexOf(id)].classList.add('active');
  // Update URL without reloading
  const url = new URL(window.location.href);
  url.searchParams.set('tab', id);
  history.replaceState(null, '', url);
}

// ── Preview selling price when typing profit% (Tab 2) ────
function previewPrice(input) {
  const id     = input.dataset.id;
  const cost   = parseFloat(input.dataset.cost) || 0;
  const profit = parseFloat(input.value) || 0;
  const sell   = cost * (1 + profit / 100);
  const preview = document.getElementById('preview-' + id);
  if (preview) {
    preview.innerHTML = '<strong>$' + sell.toFixed(2) + '</strong>';
  }
}

// ── Sync hidden input before submit (Tab 2) ─────────────
function syncHidden(id) {
  const val = document.getElementById('profit-' + id).value;
  document.getElementById('hidden-' + id).value = val;
}
</script>
</body>
</html>
<?php $conn->close(); ?>
