<?php
session_start();
require_once '../config/config.php';

// ── Handle AJAX POST (lock / unlock / reset password) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($uid <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user.']);
        exit;
    }

    if ($action === 'lock') {
        $ok = $conn->query("UPDATE users SET status = 'locked' WHERE id = $uid AND role = 'user'");
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Account locked.' : $conn->error]);
    } elseif ($action === 'unlock') {
        $ok = $conn->query("UPDATE users SET status = 'active' WHERE id = $uid AND role = 'user'");
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Account unlocked.' : $conn->error]);
    } elseif ($action === 'reset') {
        $newHash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt    = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'user'");
        $stmt->bind_param('si', $newHash, $uid);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Password reset to 123456.' : $conn->error]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

// ── List variables ──────────────────────────────────────────────────────────
$limit  = 10;
$page   = isset($_GET['page'])   && is_numeric($_GET['page'])   ? max(1,(int)$_GET['page'])   : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = "WHERE role = 'user'";
if ($search !== '') $where .= " AND username LIKE '%" . $conn->real_escape_string($search) . "%'";
if ($status !== '') $where .= " AND status = '"  . $conn->real_escape_string($status)  . "'";

$offset    = ($page - 1) * $limit;
$rows      = [];
$res       = $conn->query("SELECT id, username, email, status, created_at FROM users $where ORDER BY id ASC LIMIT $offset, $limit");
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

$cnt_res   = $conn->query("SELECT COUNT(id) AS total FROM users $where");
$total     = $cnt_res ? (int)$cnt_res->fetch_assoc()['total'] : 0;
$pages     = max(1, (int)ceil($total / $limit));

// Build base URL for pagination
function pageUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    return 'customer_management.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Management – Luxury Jewelry</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    /* ── Force section visible on standalone page ── */
    .section { display: block !important; }

    /* ── Status badge ── */
    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .4px;
      text-transform: capitalize;
    }
    .badge-active  { background: #e8f5e9; color: #2e7d32; }
    .badge-locked  { background: #ffebee; color: #c62828; }

    /* ── Action buttons row ── */
    .action-cell { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }

    /* ── Pagination info ── */
    .pagination-info { text-align: center; font-size: 13px; color: #888; margin-top: 6px; }

    /* ── Modal overlay ── */
    .modal-bg {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.45); z-index: 1000;
      align-items: center; justify-content: center;
    }
    .modal-bg.open { display: flex; }
    .modal-box {
      background: #fff; border-radius: 12px;
      padding: 28px 32px; max-width: 420px; width: 90%;
      box-shadow: 0 8px 30px rgba(0,0,0,.18); text-align: center;
    }
    .modal-box h3 { margin: 0 0 10px; color: #8e4b00; font-size: 20px; }
    .modal-box p  { margin: 0 0 20px; color: #555; font-size: 15px; }
    .modal-actions { display: flex; justify-content: center; gap: 14px; }
    .btn-confirm {
      padding: 9px 22px; border: none; border-radius: 8px;
      font-weight: 700; cursor: pointer; font-size: 14px;
      background: linear-gradient(135deg,#8e4b00,#c17a0d); color: #fff;
      transition: .25s;
    }
    .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(142,75,0,.3); }
    .btn-cancel  {
      padding: 9px 22px; border: 1px solid #ccc; border-radius: 8px;
      font-weight: 600; cursor: pointer; font-size: 14px;
      background: #f5f5f5; color: #333; transition: .25s;
    }
    .btn-cancel:hover { background: #e0e0e0; }

    /* ── View detail panel ── */
    .detail-grid { text-align: left; margin: 10px 0 20px; }
    .detail-grid dt { font-weight: 700; color: #8e4b00; font-size: 12px; text-transform: uppercase; margin-top: 10px; }
    .detail-grid dd { color: #333; font-size: 15px; margin: 2px 0 0 0; }

    /* ── Toast ── */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: #8e4b00; color: #f8ce86;
      padding: 13px 22px; border-radius: 8px;
      font-weight: 700; font-size: 14px;
      box-shadow: 0 4px 14px rgba(0,0,0,.2);
      transform: translateY(20px); opacity: 0;
      pointer-events: none; transition: all .35s; z-index: 2000;
    }
    .toast.show { transform: translateY(0); opacity: 1; }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 40px 20px; color: #aaa; }
    .empty-state i { font-size: 48px; display: block; margin-bottom: 10px; }
  </style>
</head>
<body>

<?php include 'sidebar_include.php'; ?>

  <!-- ── Main content ── -->
  <div class="content">
    <section class="section">
      <header><h1>Customer Management</h1></header>

      <!-- Search & Filter -->
      <form class="search-section" method="GET" action="customer_management.php">
        <div class="search-group">
          <label class="search-label" for="s-name">Search by Username</label>
          <input type="text" id="s-name" name="search" class="search-input"
                 placeholder="Enter username…" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="search-group">
          <label class="search-label" for="s-status">Filter by Status</label>
          <select id="s-status" name="status" class="search-select">
            <option value="">All</option>
            <option value="active" <?php echo $status==='active' ?'selected':''; ?>>Active</option>
            <option value="locked" <?php echo $status==='locked' ?'selected':''; ?>>Locked</option>
          </select>
        </div>
        <button type="submit" class="btn-search" title="Search">
          <i class="material-icons-round">search</i>
        </button>
        <?php if ($search !== '' || $status !== ''): ?>
        <a href="customer_management.php" class="btn small" style="align-self:flex-end;background:#e0e0e0;color:#333;">
          <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
      </form>

      <!-- Table -->
      <div class="user-list">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Email</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="fas fa-users-slash"></i>
                  No customers found<?php echo ($search||$status) ? ' matching your filter' : ''; ?>.
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $i => $c):
              $isLocked = strtolower($c['status'] ?? '') === 'locked';
              $created  = !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—';
            ?>
            <tr>
              <td><?php echo $offset + $i + 1; ?></td>
              <td><?php echo htmlspecialchars($c['username']); ?></td>
              <td><?php echo htmlspecialchars($c['email']); ?></td>
              <td>
                <span class="badge <?php echo $isLocked ? 'badge-locked' : 'badge-active'; ?>">
                  <?php echo $isLocked ? 'Locked' : 'Active'; ?>
                </span>
              </td>
              <td><?php echo $created; ?></td>
              <td>
                <div class="action-cell">
                  <!-- View detail -->
                  <button class="btn small btn-view"
                          data-id="<?php echo $c['id']; ?>"
                          data-username="<?php echo htmlspecialchars($c['username']); ?>"
                          data-email="<?php echo htmlspecialchars($c['email']); ?>"
                          data-status="<?php echo htmlspecialchars($c['status'] ?? 'active'); ?>"
                          data-created="<?php echo $created; ?>"
                          title="View details">
                    <i class="fas fa-eye"></i> View
                  </button>
                  <!-- Reset password -->
                  <button class="btn small btn-action"
                          data-action="reset"
                          data-id="<?php echo $c['id']; ?>"
                          data-name="<?php echo htmlspecialchars($c['username']); ?>"
                          style="background:#e6a817;color:#fff;"
                          title="Reset password to 123456">
                    <i class="fas fa-key"></i> Reset
                  </button>
                  <!-- Lock / Unlock -->
                  <?php if ($isLocked): ?>
                  <button class="btn small btn-action"
                          data-action="unlock"
                          data-id="<?php echo $c['id']; ?>"
                          data-name="<?php echo htmlspecialchars($c['username']); ?>"
                          style="background:#2e7d32;color:#fff;"
                          title="Unlock account">
                    <i class="fas fa-lock-open"></i> Unlock
                  </button>
                  <?php else: ?>
                  <button class="btn small btn-action"
                          data-action="lock"
                          data-id="<?php echo $c['id']; ?>"
                          data-name="<?php echo htmlspecialchars($c['username']); ?>"
                          style="background:#dc3545;color:#fff;"
                          title="Lock account">
                    <i class="fas fa-lock"></i> Lock
                  </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1 || $total > 0): ?>
      <div class="pagination" style="margin-top:16px;">
        <?php if ($page > 1): ?>
          <button class="pagination-btn"
                  onclick="location.href='<?php echo pageUrl(['page'=>$page-1]); ?>'">
            <span class="arrow-icon">&#10094;</span>
          </button>
        <?php endif; ?>

        <?php
          $range = 2;
          $s_pg  = max(1, $page - $range);
          $e_pg  = min($pages, $page + $range);
        ?>
        <?php if ($s_pg > 1): ?>
          <button class="pagination-btn" onclick="location.href='<?php echo pageUrl(['page'=>1]); ?>'">1</button>
          <?php if ($s_pg > 2): ?><span style="padding:0 4px;align-self:center;">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $s_pg; $i <= $e_pg; $i++): ?>
          <button class="pagination-btn <?php echo ($i===$page)?'active':''; ?>"
                  onclick="location.href='<?php echo pageUrl(['page'=>$i]); ?>'">
            <?php echo $i; ?>
          </button>
        <?php endfor; ?>

        <?php if ($e_pg < $pages): ?>
          <?php if ($e_pg < $pages - 1): ?><span style="padding:0 4px;align-self:center;">…</span><?php endif; ?>
          <button class="pagination-btn"
                  onclick="location.href='<?php echo pageUrl(['page'=>$pages]); ?>'">
            <?php echo $pages; ?>
          </button>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
          <button class="pagination-btn"
                  onclick="location.href='<?php echo pageUrl(['page'=>$page+1]); ?>'">
            <span class="arrow-icon">&#10095;</span>
          </button>
        <?php endif; ?>
      </div>
      <div class="pagination-info">
        Showing <?php echo $total > 0 ? $offset+1 : 0; ?>–<?php echo min($offset+$limit, $total); ?>
        of <?php echo $total; ?> customer(s)
      </div>
      <?php endif; ?>

    </section>
  </div><!-- /.content -->

  <!-- ── View Detail Modal ── -->
  <div class="modal-bg" id="viewModal">
    <div class="modal-box">
      <h3><i class="fas fa-user-circle" style="margin-right:8px;"></i>Customer Details</h3>
      <dl class="detail-grid">
        <dt>ID</dt>       <dd id="d-id">—</dd>
        <dt>Username</dt> <dd id="d-username">—</dd>
        <dt>Email</dt>    <dd id="d-email">—</dd>
        <dt>Status</dt>   <dd id="d-status">—</dd>
        <dt>Registered</dt><dd id="d-created">—</dd>
      </dl>
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeModal('viewModal')">Close</button>
      </div>
    </div>
  </div>

  <!-- ── Confirm Action Modal ── -->
  <div class="modal-bg" id="confirmModal">
    <div class="modal-box">
      <h3 id="confirm-title">Confirm</h3>
      <p  id="confirm-msg">Are you sure?</p>
      <div class="modal-actions">
        <button class="btn-confirm" id="confirm-ok">Yes, proceed</button>
        <button class="btn-cancel"  onclick="closeModal('confirmModal')">Cancel</button>
      </div>
    </div>
  </div>

  <!-- ── Toast ── -->
  <div class="toast" id="toast">Done!</div>

  <script>
    // ── Modal helpers ──────────────────────────────────────────────────
    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    // Close on backdrop click
    document.querySelectorAll('.modal-bg').forEach(bg => {
      bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
    });

    // ── Toast ─────────────────────────────────────────────────────────
    function showToast(msg, ok = true) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.background = ok ? '#8e4b00' : '#c62828';
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ── View buttons ───────────────────────────────────────────────────
    document.querySelectorAll('.btn-view').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('d-id').textContent      = btn.dataset.id;
        document.getElementById('d-username').textContent = btn.dataset.username;
        document.getElementById('d-email').textContent    = btn.dataset.email;
        document.getElementById('d-created').textContent  = btn.dataset.created;
        const st = btn.dataset.status.toLowerCase();
        document.getElementById('d-status').innerHTML =
          `<span class="badge ${st==='locked'?'badge-locked':'badge-active'}">${st==='locked'?'Locked':'Active'}</span>`;
        openModal('viewModal');
      });
    });

    // ── Action buttons (lock / unlock / reset) ─────────────────────────
    const actionLabels = {
      lock:   { title: 'Lock Account',      msg: (n) => `Lock account of <strong>${n}</strong>?`,     ok: 'Lock'   },
      unlock: { title: 'Unlock Account',    msg: (n) => `Unlock account of <strong>${n}</strong>?`,   ok: 'Unlock' },
      reset:  { title: 'Reset Password',    msg: (n) => `Reset password of <strong>${n}</strong> to <code>123456</code>?`, ok: 'Reset' },
    };

    let pendingAction = null;
    let pendingId     = null;

    document.querySelectorAll('.btn-action').forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.dataset.action;
        const name   = btn.dataset.name;
        const id     = btn.dataset.id;
        const lbl    = actionLabels[action];

        document.getElementById('confirm-title').textContent = lbl.title;
        document.getElementById('confirm-msg').innerHTML     = lbl.msg(name);
        document.getElementById('confirm-ok').textContent    = lbl.ok;

        pendingAction = action;
        pendingId     = id;
        openModal('confirmModal');
      });
    });

    document.getElementById('confirm-ok').addEventListener('click', () => {
      if (!pendingAction || !pendingId) return;
      closeModal('confirmModal');

      const body = new URLSearchParams({
        ajax_action: pendingAction,
        user_id:     pendingId,
      });

      fetch('customer_management.php', {
        method:  'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body:    body.toString(),
      })
      .then(r => r.json())
      .then(data => {
        showToast(data.message, data.success);
        if (data.success) setTimeout(() => location.reload(), 1400);
      })
      .catch(() => showToast('Network error. Please try again.', false));
    });
  </script>
</body>
</html>
