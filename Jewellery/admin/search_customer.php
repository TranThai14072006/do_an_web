<?php
session_start();
require_once '../config/config.php';

// ── Handle AJAX POST actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']  ?? '';
    $uid    = intval($_POST['user_id'] ?? 0);

    $response = ['success' => false, 'message' => 'Unknown action.'];

    if ($uid > 0) {
        if ($action === 'lock') {
            $stmt = $conn->prepare("UPDATE users SET status = 'Locked' WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $ok = $stmt->execute();
            $stmt->close();
            $response = ['success' => $ok, 'message' => $ok ? 'Account locked successfully.' : $conn->error];

        } elseif ($action === 'unlock') {
            $stmt = $conn->prepare("UPDATE users SET status = 'Active' WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $ok = $stmt->execute();
            $stmt->close();
            $response = ['success' => $ok, 'message' => $ok ? 'Account unlocked successfully.' : $conn->error];

        } elseif ($action === 'reset') {
            $newHash = password_hash('default123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $newHash, $uid);
            $ok = $stmt->execute();
            $stmt->close();
            $response = ['success' => $ok, 'message' => $ok ? 'Password reset to "default123".' : $conn->error];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ── Build search query ────────────────────────────────────────────────────
$search_name   = trim($_GET['name']   ?? '');
$search_status = trim($_GET['status'] ?? '');

$sql = "SELECT c.id, c.full_name AS name, c.phone, c.address,
               u.id AS uid, u.email, u.username,
               COALESCE(u.status, 'Active') AS status
        FROM customers c
        JOIN users u ON c.user_id = u.id
        WHERE 1=1";
$params = [];
$types  = '';

if ($search_name !== '') {
    $sql .= " AND c.full_name LIKE ?";
    $params[] = '%' . $search_name . '%';
    $types   .= 's';
}
if ($search_status !== '') {
    $sql .= " AND COALESCE(u.status,'Active') = ?";
    $params[] = $search_status;
    $types   .= 's';
}
$sql .= " ORDER BY c.id";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $results = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Customers – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{display:flex;background:#f3f3f3;color:#333;min-height:100vh;}
    .sidebar{width:220px;background:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;position:fixed;top:0;left:0;height:100vh;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;border-radius:50%;}
    .logo h2{font-size:16px;margin-top:10px;}
    .menu a{display:block;padding:11px 14px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:8px;font-weight:600;font-size:14px;transition:background 0.3s,color 0.3s;}
    .menu a:hover,.menu a.active{background:#f8ce86;color:#8e4b00;}
    main{margin-left:220px;flex:1;padding:30px;}
    h1{color:#8e4b00;margin-bottom:24px;font-size:24px;}
    .search-form{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:22px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;}
    .form-group{display:flex;flex-direction:column;flex:1;min-width:180px;}
    .form-group label{font-weight:600;color:#555;margin-bottom:6px;font-size:13px;}
    input,select{padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:border-color 0.2s;}
    input:focus,select:focus{border-color:#8e4b00;box-shadow:0 0 0 3px rgba(142,75,0,0.08);}
    .btn{display:inline-flex;align-items:center;gap:6px;background:#8e4b00;color:#f8ce86;border:none;border-radius:8px;padding:10px 18px;font-weight:600;cursor:pointer;transition:0.3s;font-size:14px;text-decoration:none;}
    .btn:hover{background:#a3670b;transform:translateY(-1px);}
    .btn.secondary{background:#e0e0e0;color:#555;}
    .btn.secondary:hover{background:#c8c8c8;}
    .btn.small{padding:6px 11px;font-size:12px;}
    .btn.lock-btn{background:#e65100;color:white;}
    .btn.lock-btn:hover{background:#bf360c;}
    .btn.unlock-btn{background:#2e7d32;color:white;}
    .btn.unlock-btn:hover{background:#1b5e20;}
    .btn.reset-btn{background:#1565c0;color:white;}
    .btn.reset-btn:hover{background:#0d47a1;}
    table{width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
    th,td{padding:13px 15px;text-align:center;font-size:14px;border-bottom:1px solid #f0e2d0;}
    th{background:linear-gradient(135deg,#8e4b00,#a3670b);color:#f8ce86;font-weight:600;}
    tr:hover td{background:#fdf6ee;}
    .empty-row td{color:#aaa;padding:24px;}
    .status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;}
    .status-badge.active{background:#e8f5e9;color:#2e7d32;}
    .status-badge.locked{background:#ffebee;color:#c62828;}
    .popup{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:999;}
    .popup.active{display:flex;}
    .popup-content{background:white;border-radius:12px;padding:28px 32px;max-width:440px;width:90%;box-shadow:0 8px 24px rgba(0,0,0,0.2);}
    .popup-content h3{margin-bottom:14px;color:#8e4b00;}
    .popup-content p{margin-bottom:8px;font-size:14px;}
    .popup-content strong{color:#555;}
    .popup-footer{text-align:center;margin-top:18px;}
    .toast{position:fixed;bottom:30px;right:30px;background:#8e4b00;color:#f8ce86;padding:14px 22px;border-radius:8px;opacity:0;pointer-events:none;transform:translateY(20px);transition:all 0.4s ease;z-index:1000;font-weight:600;}
    .toast.show{opacity:1;transform:translateY(0);}
    .result-count{color:#888;font-size:13px;margin-bottom:12px;}
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <img src="../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>
    <div class="menu">
      <a href="Administration_menu.php#products">Jewelry List</a>
      <a href="Administration_menu.php#product-manage">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users" class="active">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import_product/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1><i class="fas fa-users" style="font-size:20px;margin-right:10px;"></i>Search Customers</h1>

    <form class="search-form" method="get" action="search_customer.php">
      <div class="form-group">
        <label>Customer Name</label>
        <input type="text" name="name" placeholder="Search by name..."
               value="<?php echo htmlspecialchars($search_name); ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="">All</option>
          <option value="Active" <?php echo $search_status === 'Active' ? 'selected' : ''; ?>>Active</option>
          <option value="Locked" <?php echo $search_status === 'Locked' ? 'selected' : ''; ?>>Locked</option>
        </select>
      </div>
      <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
      <button type="button" class="btn secondary"
              onclick="window.location.href='Administration_menu.php#users'">← Back</button>
    </form>

    <p class="result-count"><?php echo count($results); ?> customer(s) found</p>

    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($results)): ?>
          <tr class="empty-row"><td colspan="5">No customers found.</td></tr>
        <?php else: ?>
          <?php foreach ($results as $c): ?>
          <tr>
            <td><?php echo intval($c['id']); ?></td>
            <td><?php echo htmlspecialchars($c['name']); ?></td>
            <td><?php echo htmlspecialchars($c['email']); ?></td>
            <td>
              <span class="status-badge <?php echo strtolower($c['status']); ?>">
                <?php echo htmlspecialchars($c['status']); ?>
              </span>
            </td>
            <td>
              <button class="btn small view-btn"
                      data-name="<?php echo htmlspecialchars($c['name']); ?>"
                      data-email="<?php echo htmlspecialchars($c['email']); ?>"
                      data-phone="<?php echo htmlspecialchars($c['phone'] ?? '—'); ?>"
                      data-address="<?php echo htmlspecialchars($c['address'] ?? '—'); ?>"
                      data-status="<?php echo htmlspecialchars($c['status']); ?>">View</button>

              <button class="btn small reset-btn action-btn"
                      data-action="reset"
                      data-uid="<?php echo intval($c['uid']); ?>"
                      data-label="Reset Password">Reset PW</button>

              <?php if ($c['status'] === 'Locked'): ?>
                <button class="btn small unlock-btn action-btn"
                        data-action="unlock"
                        data-uid="<?php echo intval($c['uid']); ?>"
                        data-label="Unlock Account">Unlock</button>
              <?php else: ?>
                <button class="btn small lock-btn action-btn"
                        data-action="lock"
                        data-uid="<?php echo intval($c['uid']); ?>"
                        data-label="Lock Account">Lock</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </main>

  <!-- ── View Customer Popup ── -->
  <div class="popup" id="viewPopup">
    <div class="popup-content">
      <h3>Customer Details</h3>
      <p><strong>Name:</strong> <span id="vName"></span></p>
      <p><strong>Email:</strong> <span id="vEmail"></span></p>
      <p><strong>Phone:</strong> <span id="vPhone"></span></p>
      <p><strong>Address:</strong> <span id="vAddress"></span></p>
      <p><strong>Status:</strong> <span id="vStatus"></span></p>
      <div class="popup-footer">
        <button class="btn secondary" onclick="closePopup('viewPopup')">Close</button>
      </div>
    </div>
  </div>

  <!-- ── Confirm Action Popup ── -->
  <div class="popup" id="confirmPopup">
    <div class="popup-content" style="text-align:center;">
      <h3 id="confirmTitle">Confirm Action</h3>
      <p id="confirmMsg" style="margin-bottom:0;">Are you sure?</p>
      <div class="popup-footer">
        <button class="btn" id="confirmOkBtn">Confirm</button>
        <button class="btn secondary" onclick="closePopup('confirmPopup')" style="margin-left:10px;">Cancel</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast"></div>

  <script>
    function openPopup(id)  { document.getElementById(id).classList.add('active'); }
    function closePopup(id) { document.getElementById(id).classList.remove('active'); }

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2800);
    }

    // View customer details
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('vName').textContent    = btn.dataset.name;
        document.getElementById('vEmail').textContent   = btn.dataset.email;
        document.getElementById('vPhone').textContent   = btn.dataset.phone;
        document.getElementById('vAddress').textContent = btn.dataset.address;
        document.getElementById('vStatus').textContent  = btn.dataset.status;
        openPopup('viewPopup');
      });
    });

    // Action buttons (lock / unlock / reset)
    let pendingAction = '', pendingUid = '';
    const actionMsgs = {
      reset:  'Reset this customer\'s password to "default123"?',
      lock:   'Lock this customer\'s account?',
      unlock: 'Unlock this customer\'s account?',
    };

    document.querySelectorAll('.action-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingAction = btn.dataset.action;
        pendingUid    = btn.dataset.uid;
        document.getElementById('confirmTitle').textContent = btn.dataset.label || 'Confirm';
        document.getElementById('confirmMsg').textContent   = actionMsgs[pendingAction] || 'Are you sure?';
        openPopup('confirmPopup');
      });
    });

    document.getElementById('confirmOkBtn').addEventListener('click', () => {
      fetch('search_customer.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=${encodeURIComponent(pendingAction)}&user_id=${encodeURIComponent(pendingUid)}`
      })
      .then(r => r.json())
      .then(data => {
        closePopup('confirmPopup');
        showToast(data.message);
        if (data.success) setTimeout(() => location.reload(), 1600);
      });
    });
  </script>
</body>
</html>