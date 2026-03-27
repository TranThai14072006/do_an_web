<?php
session_start();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code        = trim($_POST['product_code'] ?? '');
    $name        = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($code) || empty($name)) {
        $error = 'Product Code and Product Name are required.';
    } else {
        // Handle image upload
        $imagePath = '';
        if (!empty($_FILES['product_image']['name'])) {
            $uploadDir = '../images/';
            $ext       = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $fileName  = preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
                $imagePath = $fileName;
            } else {
                $error = 'Failed to upload image.';
            }
        }

        if (empty($error)) {
            // TODO: Insert product into database
            // $pdo->prepare("INSERT INTO products (code, name, description, image) VALUES (?,?,?,?)")
            //     ->execute([$code, $name, $description, $imagePath]);

            $success = 'Product added successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Product</title>
  <style>
    body { font-family:"Segoe UI",sans-serif; background-color:#f3f3f3; color:#333; margin:0; display:flex; }
    .sidebar { width:220px; background-color:#8e4b00; color:#f8ce86; display:flex; flex-direction:column; padding:20px; }
    .logo { text-align:center; margin-bottom:30px; }
    .logo img { width:80px; border-radius:50%; }
    .logo h2 { font-size:18px; margin-top:10px; }
    .menu a { display:block; padding:12px; color:#f8ce86; text-decoration:none; border-radius:8px; margin-bottom:10px; font-weight:bold; transition:background 0.3s,color 0.3s; }
    .menu a:hover,.menu a.active { background-color:#f8ce86; color:#8e4b00; }
    main { margin-left:220px; flex:1; padding:25px; }
    h1 { color:#8e4b00; margin-bottom:20px; font-size:22px; }
    .card { background:#fff; border-radius:10px; box-shadow:0 3px 8px rgba(0,0,0,0.1); padding:25px; margin-top:20px; max-width:1100px; }
    .card h3 { color:#8e4b00; margin-bottom:15px; }
    .form-layout { display:flex; flex-wrap:wrap; gap:30px; }
    .form-column { flex:2; min-width:400px; display:flex; flex-direction:column; }
    .form-group { display:flex; flex-direction:column; margin-bottom:15px; }
    .form-group label { font-weight:bold; margin-bottom:6px; color:#333; }
    input,textarea { padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:15px; transition:border-color 0.2s; width:100%; }
    input:focus,textarea:focus { border-color:#8e4b00; outline:none; }
    textarea { resize:vertical; min-height:100px; }
    .image-column { flex:1; min-width:280px; display:flex; flex-direction:column; align-items:center; justify-content:flex-start; }
    .image-preview { width:220px; height:220px; border:2px solid #ddd; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#fafafa; }
    .image-preview img { max-width:100%; max-height:100%; object-fit:contain; }
    .btn-change-image { background:linear-gradient(195deg,#a3670b,#8e4b00); color:white; border:none; padding:10px 18px; border-radius:6px; font-weight:bold; cursor:pointer; margin-top:12px; transition:0.25s; display:inline-block; }
    .btn-change-image:hover { background:linear-gradient(195deg,#c17a0d,#a3670b); transform:translateY(-2px); }
    .form-actions { display:flex; justify-content:center; gap:25px; margin-top:35px; padding-top:25px; border-top:1px solid #ddd; }
    .btn { text-decoration:none; padding:12px 28px; border-radius:8px; font-weight:600; cursor:pointer; border:none; font-size:16px; transition:all 0.3s ease; min-width:160px; text-align:center; }
    .btn-secondary { background:#e0e0e0; color:#333; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
    .btn-secondary:hover { background:#bcbcbc; transform:translateY(-2px); }
    .btn-primary { background:linear-gradient(195deg,#c17a0d,#8e4b00); color:white; box-shadow:0 3px 8px rgba(142,75,0,0.3); }
    .btn-primary:hover { background:linear-gradient(195deg,#d8921b,#a3670b); transform:translateY(-2px); }
    .alert { padding:10px 15px; border-radius:6px; margin-bottom:15px; font-weight:500; }
    .alert-success { background:#e8f5e9; color:#2e7d32; border-left:4px solid #2e7d32; }
    .alert-error   { background:#ffebee; color:#c62828; border-left:4px solid #c62828; }
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
      <a href="Administration_menu.php#product-manage" class="active">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1>Add Product</h1>

    <div class="card">
      <h3>Product Information</h3>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="add_product.php" enctype="multipart/form-data">
        <div class="form-layout">

          <!-- Left column -->
          <div class="form-column">
            <div class="form-group">
              <label>Product Code:</label>
              <input type="text" name="product_code"
                     value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label>Product Name:</label>
              <input type="text" name="product_name"
                     value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label>Description:</label>
              <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
          </div>

          <!-- Right column (image) -->
          <div class="image-column">
            <label style="font-weight:bold;margin-bottom:10px;">Product Image</label>
            <div class="image-preview">
              <img id="previewImage" src="" alt="Product Image">
            </div>
            <input type="file" id="uploadImage" name="product_image" accept="image/*" hidden>
            <label for="uploadImage" class="btn-change-image">Choose Image</label>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="form-actions">
          <button type="button" class="btn btn-secondary"
                  onclick="window.location.href='Administration_menu.php#product-manage'">← Back</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </main>

  <script>
    const upload  = document.getElementById('uploadImage');
    const preview = document.getElementById('previewImage');
    upload.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => preview.src = e.target.result;
        reader.readAsDataURL(file);
      }
    });
  </script>
</body>
</html>