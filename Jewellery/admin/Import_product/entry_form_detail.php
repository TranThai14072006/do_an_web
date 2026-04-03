<?php
require_once '../../config/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: import_management.php');
    exit;
}

// ── Lấy thông tin phiếu nhập ─────────────────────────────────────────────
$stmt_form = $conn->prepare("SELECT * FROM goods_receipt WHERE id = ?");
$stmt_form->bind_param('i', $id);
$stmt_form->execute();
$form = $stmt_form->get_result()->fetch_assoc();
$stmt_form->close();

if (!$form) {
    header('Location: import_management.php');
    exit;
}

// ── Lấy danh sách sản phẩm trong phiếu ───────────────────────────────────
$stmt_items = $conn->prepare("
    SELECT d.*, p.image
    FROM goods_receipt_items d
    LEFT JOIN products p ON d.product_id = p.id
    WHERE d.receipt_id = ?
    ORDER BY d.id ASC
");
$stmt_items->bind_param('i', $id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

$badge_class = $form['status'] === 'Completed' ? 'badge-completed' : 'badge-draft';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entry Form Detail | Luxury Jewelry Admin</title>
  <link rel="stylesheet" href="../admin_function.css">
  <style>
    header h1 { font-size: 24px; color: #8e4b00; margin-bottom: 20px; }

    .card { background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 25px; }
    .card-header { font-weight: bold; font-size: 18px; color: #8e4b00; margin-bottom: 20px; border-bottom: 2px solid #f1e4e1; padding-bottom: 10px; display: flex; align-items: center; gap: 12px; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
    .info-label { font-weight: 600; color: #555; margin-bottom: 4px; font-size: 13px; }
    .info-value { font-size: 15px; }

    table { width: 100%; border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 20px; }
    th { background-color: #8e4b00; color: #f8ce86; font-weight: 600; text-align: center; padding: 12px 10px; font-size: 14px; }
    td { text-align: center; padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 14px; }
    tr:nth-child(even) td { background: #faf6f2; }
    tr:hover td { background: #f3ede6; }
    .product-image { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; border: 1px solid #ddd; }

    .summary-card { background: #fff8f0; border-radius: 10px; padding: 20px; margin-top: 20px; border: 1px solid #f1e4e1; }
    .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 15px; }
    .summary-row:last-child { border-bottom: none; }
    .summary-total { font-weight: bold; font-size: 18px; color: #8e4b00; }

    .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.3s; border: none; }
    .btn-primary { background-color: #8e4b00; color: #f8ce86; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .btn-primary:hover { background-color: #a3670b; transform: translateY(-2px); }
    .btn-secondary:hover { background-color: #555; }
    .action-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }

    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; }
    .badge-draft { background: #fff3cd; color: #856404; }
    .badge-completed { background: #d4edda; color: #155724; }

    .popup { position:fixed; top:0; left:0; width:100%; height:100%; display:none; justify-content:center; align-items:center; background:rgba(0,0,0,0.5); z-index:3000; }
    .popup-box { background:#fff; border-radius:10px; padding:25px 35px; width:400px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.2); }
    #print_order:checked ~ .popup-print { display:flex; }
    .overlay { position:absolute; width:100%; height:100%; top:0; left:0; }
  </style>
</head>
<body>

<input type="checkbox" id="print_order" hidden>
<div class="popup popup-print">
  <label for="print_order" class="overlay"></label>
  <div class="popup-box">
    <h3>🖨 Print Entry Form</h3>
    <p>Order is printing, please wait...</p>
    <label for="print_order" class="btn btn-primary" style="display:inline-block;margin-top:15px;">OK</label>
  </div>
</div>

<?php include '../sidebar_include.php'; ?>

<main>
  <header>
    <h1>Purchase Order — <?php echo htmlspecialchars($form['order_number']); ?></h1>
  </header>

  <!-- CARD 1: Order Info -->
  <div class="card">
    <div class="card-header">
      Order Information
      <span class="badge <?php echo $badge_class; ?>"><?php echo $form['status']; ?></span>
    </div>
    <div class="info-grid">
      <div>
        <div class="info-label">Entry Form ID</div>
        <div class="info-value">#<?php echo $form['id']; ?></div>
      </div>
      <div>
        <div class="info-label">Order Number</div>
        <div class="info-value"><?php echo htmlspecialchars($form['order_number']); ?></div>
      </div>
      <div>
        <div class="info-label">Date</div>
        <div class="info-value"><?php echo htmlspecialchars($form['entry_date']); ?></div>
      </div>
      <div>
        <div class="info-label">Supplier</div>
        <div class="info-value"><?php echo htmlspecialchars($form['supplier'] ?? '—'); ?></div>
      </div>
    </div>
  </div>

  <!-- CARD 2: Product List -->
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
          <th>Unit Price</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="7" style="color:#888;padding:25px;">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($items as $i => $row): ?>
          <tr>
            <td><?php echo $i + 1; ?></td>
            <td><?php echo htmlspecialchars($row['product_id']); ?></td>
            <td>
              <img src="../../images/<?php echo htmlspecialchars($row['image'] ?? ($row['product_id'] . '.jpg')); ?>"
                   class="product-image" onerror="this.style.opacity='0.2'">
            </td>
            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td><?php echo intval($row['quantity']); ?></td>
            <td>$<?php echo number_format($row['unit_price'], 2); ?></td>
            <td>$<?php echo number_format($row['total_price'], 2); ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="summary-card">
      <div class="summary-row">
        <span>Total Quantity</span>
        <span><?php echo intval($form['total_quantity']); ?></span>
      </div>
      <div class="summary-row summary-total">
        <span>Grand Total</span>
        <span>$<?php echo number_format($form['total_value'], 2); ?></span>
      </div>
    </div>
  </div>

  <div class="action-buttons">
    <a href="import_management.php" class="btn btn-secondary">← Back</a>
    <?php if ($form['status'] === 'Draft'): ?>
      <a href="edit_entry_form.php?id=<?php echo $form['id']; ?>" class="btn btn-secondary">✏ Edit</a>
    <?php endif; ?>
    <button type="button" class="btn btn-primary"
            onclick="document.getElementById('print_order').checked = true;">
      🖨 Print Order
    </button>
  </div>
</main>

<?php $conn->close(); ?>
</body>
</html>

