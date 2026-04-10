<?php
session_start();
require_once '../config/config.php';

// Ensure user is logged in
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? 0;

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    die("Admin details not found. ID: " . htmlspecialchars($admin_id));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Profile Detail</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .card { background:#fff; border-radius:10px; box-shadow:0 3px 8px rgba(0,0,0,0.1); padding:25px; margin-top:20px; max-width:800px; display: flex; flex-direction: column; }
    .card h3 { color:#8e4b00; margin-bottom:20px; border-bottom: 2px solid #f8ce86; padding-bottom: 10px; }
    .profile-info { display: flex; flex-direction: column; gap: 15px; }
    .info-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #eee; }
    .info-label { font-weight: bold; color: #555; width: 35%; text-transform: capitalize; }
    .info-value { color: #222; width: 65%; font-size: 15px; }
    .avatar-lg { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #f8ce86; padding: 3px; background: #8e4b00; margin: 0 auto 20px; display: block; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; text-transform: uppercase; }
    .badge-admin { background: #8e4b00; color: #fff; }
    .badge-active { background: #4CAF50; color: #fff; }
    
    /* New styles for credentials */
    .credentials-card { border-top: 5px solid #8e4b00; margin-top: 30px; }
    .password-container { display: flex; align-items: center; gap: 10px; }
    .password-display { font-family: 'Courier New', Courier, monospace; background: #f4f4f4; padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; word-break: break-all; font-size: 13px; color: #444; }
    .toggle-btn { background: #8e4b00; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; transition: 0.3s; font-size: 13px; display: flex; align-items: center; gap: 5px; }
    .toggle-btn:hover { background: #a65900; }
  </style>
</head>
<body>
  <?php include 'sidebar_include.php'; ?>
  
  <div class="content" style="padding: 25px;">
    <section class="section" style="display:block;">
      <header>
        <h1 style="color:#8e4b00; font-size:22px; margin-bottom:10px;">Admin Profile Detail</h1>
      </header>
      
      <div class="card">
        <img src="../images/Admin_login.jpg" alt="Admin Avatar" class="avatar-lg">
        
        <h3><i class="fas fa-user-circle"></i> Account Information</h3>
        <div class="profile-info">
          <?php foreach ($admin as $key => $value): ?>
            <?php 
              // Hide sensible fields + password (handled separately below)
              if (in_array(strtolower($key), ['password', 'remember_token', 'reset_token', 'id'])) continue; 
            ?>
            <div class="info-row">
              <span class="info-label"><?php echo str_replace('_', ' ', htmlspecialchars($key)); ?></span>
              <span class="info-value">
                <?php 
                  if ($key === 'role') {
                    echo '<span class="badge badge-admin">'.htmlspecialchars($value).'</span>';
                  } elseif ($key === 'status') {
                    echo '<span class="badge '.(strtolower($value)=='active'?'badge-active':'').'">'.htmlspecialchars($value).'</span>';
                  } else {
                    echo htmlspecialchars($value ?? 'N/A'); 
                  }
                ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Account Credentials Section -->
      <div class="card credentials-card">
        <h3><i class="fas fa-lock"></i> Account Credentials</h3>
        <div class="profile-info">
          <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-value"><strong><?php echo htmlspecialchars($admin['username'] ?? 'N/A'); ?></strong></span>
          </div>
          <div class="info-row">
            <span class="info-label">Email Address</span>
            <span class="info-value"><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Password Hash</span>
            <div class="info-value">
              <div class="password-container">
                <span id="pass-field" class="password-display">••••••••••••••••••••••••••••••</span>
                <button type="button" class="toggle-btn" onclick="togglePass()">
                  <i class="fas fa-eye" id="toggle-icon"></i> <span id="btn-text">Show</span>
                </button>
              </div>
              <small style="color: #888; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> This is a secure hash. Plain text passwords are never stored.
              </small>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <script>
    let isHidden = true;
    const passHash = "<?php echo $admin['password']; ?>";
    const passField = document.getElementById('pass-field');
    const toggleIcon = document.getElementById('toggle-icon');
    const btnText = document.getElementById('btn-text');

    function togglePass() {
      if (isHidden) {
        passField.textContent = passHash;
        toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
        btnText.textContent = 'Hide';
        isHidden = false;
      } else {
        passField.textContent = '••••••••••••••••••••••••••••••';
        toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
        btnText.textContent = 'Show';
        isHidden = true;
      }
    }
  </script>
</body>
</html>
