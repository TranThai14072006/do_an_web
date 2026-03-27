<?php
session_start();

$search_name   = trim($_GET['name']   ?? '');
$search_status = trim($_GET['status'] ?? '');

// TODO: Replace with real DB query
// Sample data
$all_customers = [
    ['id'=>1,'name'=>'Alice Nguyen','email'=>'alice@example.com','status'=>'Active'],
    ['id'=>2,'name'=>'Emma Tran',   'email'=>'emma@example.com', 'status'=>'Locked'],
    ['id'=>3,'name'=>'David Le',    'email'=>'david@example.com','status'=>'Active'],
];

$results = array_filter($all_customers, function($c) use ($search_name, $search_status) {
    $matchName   = $search_name   === '' || stripos($c['name'],   $search_name)   !== false;
    $matchStatus = $search_status === '' || strtolower($c['status']) === strtolower($search_status);
    return $matchName && $matchStatus;
});

// Handle AJAX actions (reset password / lock / unlock)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']      ?? '';
    $customerId = (int)($_POST['customer_id'] ?? 0);

    // TODO: Implement real DB actions
    $response = ['success' => false, 'message' => 'Unknown action'];

    if ($action === 'reset') {
        // $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?")->execute([password_hash('default123', PASSWORD_DEFAULT), $customerId]);
        $response = ['success' => true, 'message' => 'Password has been reset!'];
    } elseif ($action === 'unlock') {
        // $pdo->prepare("UPDATE customers SET status = 'Active' WHERE id = ?")->execute([$customerId]);
        $response = ['success' => true, 'message' => 'Account unlocked successfully!'];
    } elseif ($action === 'lock') {
        // $pdo->prepare("UPDATE customers SET status = 'Locked' WHERE id = ?")->execute([$customerId]);
        $response = ['success' => true, 'message' => 'Account locked successfully!'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Results – Customers</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{display:flex;background:#f3f3f3;color:#333;min-height:100vh;}
    .sidebar{width:220px;background-color:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;border-radius:50%;}
    .logo h2{font-size:18px;margin-top:10px;}
    .menu a{display:block;padding:12px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:10px;font-weight:bold;transition:background 0.3s,color 0.3s;}
    .menu a:hover,.menu a.active{background-color:#f8ce86;color:#8e4b00;}
    main{flex:1;padding:25px;}
    h1{color:#8e4b00;margin-bottom:20px;font-size:24px;}
    .search-form{background:white;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;}
    .form-group{display:flex;flex-direction:column;flex:1;min-width:180px;}
    .form-group label{font-weight:bold;color:#333;margin-bottom:6px;}
    input,select{padding:10px;border:1px solid #ccc;border-radius:6px;font-size:15px;outline:none;transition:border-color 0.2s;}
    input:focus,select:focus{border-color:#8e4b00;}
    .btn{display:inline-block;background-color:#8e4b00;color:#f8ce86;border:none;border-radius:6px;padding:10px 16px;font-weight:bold;cursor:pointer;transition:0.3s;}
    .btn:hover{background-color:#f8ce86;color:#8e4b00;}
    .btn.secondary{background:#ccc;color:#333;}
    .btn.secondary:hover{background:#aaa;}
    .btn.small{padding:6px 10px;font-size:13px;}
    table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.08);}
    th,td{padding:14px 16px;text-align:center;font-size:15px;border-bottom:1px solid #f0e2d0;}
    th{background:linear-gradient(195deg,#8e4b00,#a3670b);color:#f8ce86;font-weight:600;}
    tr:hover td{background:#f9f2e7;}
    .popup{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:999;}
    .popup.active{display:flex;}
    .popup-content{background:white;border-radius:10px;padding:25px 30px;max-width:400px;width:90%;box-shadow:0 5px 15px rgba(0,0,0,0.2);text-align:center;}
    .close-btn{margin-top:15px;background:#ccc;color:#333;}
    .close-btn:hover{background:#aaa;}
    .toast{position:fixed;bottom:30px;right:30px;background-color:#8e4b00;color:#f8ce86;padding:14px 22px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.2);opacity:0;pointer-events:none;transform:translateY(20px);transition:all 0.4s ease;z-index:1000;font-weight:600;}
    .toast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
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
      <a href="Import product manage/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1>Search Results</h1>

    <form class="search-form" method="get" action="search_customer.php">
      <div class="form-group">
        <label>Customer Name</label>
        <input type="text" name="name" placeholder="Enter customer name"
               value="<?php echo htmlspecialchars($search_name); ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="">All</option>
          <option value="Active"  <?php echo $search_status === 'Active'  ? 'selected' : ''; ?>>Active</option>
          <option value="Locked"  <?php echo $search_status === 'Locked'  ? 'selected' : ''; ?>>Locked</option>
        </select>
      </div>
      <button type="submit" class="btn">Search</button>
      <button type="button" class="btn secondary"
              onclick="window.location.href='Administration_menu.php#users'">Back</button>
    </form>

    <table>
      <tr><th>ID</th><th>Customer Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
      <?php if (empty($results)): ?>
        <tr><td colspan="5" style="text-align:center;color:#999;">No customers found.</td></tr>
      <?php else: ?>
        <?php foreach ($results as $c): ?>
        <tr>
          <td><?php echo $c['id']; ?></td>
          <td><?php echo htmlspecialchars($c['name']); ?></td>
          <td><?php echo htmlspecialchars($c['email']); ?></td>
          <td><?php echo htmlspecialchars($c['status']); ?></td>
          <td>
            <button class="btn small view-btn"
                    data-name="<?php echo htmlspecialchars($c['name']); ?>"
                    data-email="<?php echo htmlspecialchars($c['email']); ?>"
                    data-status="<?php echo htmlspecialchars($c['status']); ?>">View</button>
            <button class="btn small action-btn" data-action="reset"
                    data-id="<?php echo $c['id']; ?>">Reset</button>
            <?php if ($c['status'] === 'Locked'): ?>
              <button class="btn small action-btn" data-action="unlock"
                      data-id="<?php echo $c['id']; ?>">Unlock</button>
            <?php else: ?>
              <button class="btn small action-btn" data-action="lock"
                      data-id="<?php echo $c['id']; ?>">Lock</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </main>

  <!-- Popups -->
  <div class="popup" id="viewPopup">
    <div class="popup-content">
      <h2>Customer Details</h2>
      <p><strong>Name:</strong> <span id="viewName"></span></p>
      <p><strong>Email:</strong> <span id="viewEmail"></span></p>
      <p><strong>Status:</strong> <span id="viewStatus"></span></p>
      <button class="btn close-btn" onclick="closePopup('viewPopup')">Close</button>
    </div>
  </div>

  <div class="popup" id="confirmPopup">
    <div class="popup-content">
      <h2 id="confirmTitle">Confirm</h2>
      <p id="confirmMsg">Are you sure?</p>
      <button class="btn" id="confirmOkBtn">Confirm</button>
      <button class="btn secondary" onclick="closePopup('confirmPopup')">Cancel</button>
    </div>
  </div>

  <div id="toast" class="toast">Action completed!</div>

  <script>
    function openPopup(id)  { document.getElementById(id).classList.add('active'); }
    function closePopup(id) { document.getElementById(id).classList.remove('active'); }

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2500);
    }

    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('viewName').textContent   = btn.dataset.name;
        document.getElementById('viewEmail').textContent  = btn.dataset.email;
        document.getElementById('viewStatus').textContent = btn.dataset.status;
        openPopup('viewPopup');
      });
    });

    document.querySelectorAll('.action-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.dataset.action;
        const id     = btn.dataset.id;
        const titles = {reset:'Reset Password', unlock:'Unlock Account', lock:'Lock Account'};
        const msgs   = {
          reset:  'Are you sure you want to reset this customer\'s password?',
          unlock: 'Are you sure you want to unlock this account?',
          lock:   'Are you sure you want to lock this account?',
        };
        document.getElementById('confirmTitle').textContent = titles[action] || 'Confirm';
        document.getElementById('confirmMsg').textContent   = msgs[action]   || 'Are you sure?';

        document.getElementById('confirmOkBtn').onclick = () => {
          fetch('search_customer.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `action=${encodeURIComponent(action)}&customer_id=${encodeURIComponent(id)}`
          })
          .then(r => r.json())
          .then(data => {
            closePopup('confirmPopup');
            showToast(data.message);
            if (data.success) setTimeout(() => location.reload(), 1800);
          });
        };

        openPopup('confirmPopup');
      });
    });
  </script>
</body>
</html>