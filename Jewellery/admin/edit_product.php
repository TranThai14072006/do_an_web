<?php
session_start();
require_once '../config/config.php';

// Retrieve product code from URL param (e.g. edit_product.php?code=R001)
$code = htmlspecialchars($_GET['code'] ?? 'R001');

// Fetch real DB data
$sql = "SELECT p.*, pd.description FROM products p LEFT JOIN product_details pd ON p.id = pd.product_id WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $productRow = $result->fetch_assoc();
    $product = [
        'code'        => $productRow['id'],
        'name'        => $productRow['name'],
        'description' => $productRow['description'],
        'image'       => $productRow['image'],
        'price'       => $productRow['price'],
        'stock'       => $productRow['stock'],
        'category'    => $productRow['category']
    ];
} else {
    // redirect or die
    die("Product not found");
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName        = trim($_POST['product_name'] ?? '');
    $newDescription = trim($_POST['description'] ?? '');
    $newCategory    = trim($_POST['category'] ?? 'Unisex');

    if (empty($newName)) {
        $error = 'Product Name is required.';
    } else {
        // Handle optional new image
        if (!empty($_FILES['product_image']['name'])) {
            $uploadDir  = '../images/';
            $ext        = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $fileName   = preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
                $product['image'] = $fileName;
            } else {
                $error = 'Failed to upload image.';
            }
        }

        if (empty($error)) {
            // Update product in database
            $update_sql = "UPDATE products SET name=?, category=?, image=? WHERE id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssss", $newName, $newCategory, $product['image'], $code);
            
            if ($stmt->execute()) {
                // Check if details exist
                $check_det = $conn->query("SELECT id FROM product_details WHERE product_id='".$conn->real_escape_string($code)."'");
                if ($check_det->num_rows > 0) {
                    $det_sql = "UPDATE product_details SET description=? WHERE product_id=?";
                    $dstmt = $conn->prepare($det_sql);
                    $dstmt->bind_param("ss", $newDescription, $code);
                    $dstmt->execute();
                } else {
                    $det_sql = "INSERT INTO product_details (product_id, description) VALUES (?, ?)";
                    $dstmt = $conn->prepare($det_sql);
                    $dstmt->bind_param("ss", $code, $newDescription);
                    $dstmt->execute();
                }

                $product['name']        = $newName;
                $product['description'] = $newDescription;
                $product['category']    = $newCategory;
                $success = 'Product updated successfully!';
            } else {
                $error = 'Failed to update product: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Product</title>
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
    .btn-change-image { background:linear-gradient(195deg,#a3670b,#8e4b00); color:white; border:none; padding:10px 18px; border-radius:6px; font-weight:bold; cursor:pointer; margin-top:10px; transition:0.2s; }
    .btn-change-image:hover { background:linear-gradient(195deg,#c17a0d,#a3670b); }
    .form-actions { display:flex; justify-content:center; gap:20px; margin-top:30px; padding-top:20px; border-top:1px solid #ddd; }
    .btn { text-decoration:none; padding:10px 20px; border-radius:6px; font-weight:bold; cursor:pointer; border:none; transition:0.3s; }
    .btn-secondary { background:#ccc; color:#333; }
    .btn-secondary:hover { background:#b3b3b3; }
    .btn-primary { background:linear-gradient(195deg,#c17a0d,#8e4b00); color:white; }
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
      <a href="product_management.php" class="active">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1>Edit Product</h1>

    <div class="card">
      <h3>Product Information</h3>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post"
            action="edit_product.php?code=<?php echo urlencode($code); ?>"
            enctype="multipart/form-data">
        <div class="form-layout">

          <!-- Left column -->
          <div class="form-column">
            <div class="form-group">
              <label>Product Code:</label>
              <input type="text" name="product_code"
                     value="<?php echo htmlspecialchars($product['code']); ?>" readonly>
            </div>
            <div class="form-group">
              <label>Product Name:</label>
              <input type="text" name="product_name"
                     value="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="form-group">
              <label>Category:</label>
              <select name="category" style="padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:15px;">
                <option value="Male" <?php echo (($product['category']??'')=='Male')?'selected':''; ?>>Male</option>
                <option value="Female" <?php echo (($product['category']??'')=='Female')?'selected':''; ?>>Female</option>
                <option value="Unisex" <?php echo (($product['category']??'')=='Unisex')?'selected':''; ?>>Unisex</option>
              </select>
            </div>
            <div class="form-group">
              <label>Description:</label>
              <textarea name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
          </div>

          <!-- Right column (image) -->
          <div class="image-column">
            <label style="font-weight:bold;margin-bottom:10px;">Product Image</label>
            <div class="image-preview">
              <img id="previewImage"
                   src="../images/<?php echo htmlspecialchars($product['image']); ?>"
                   alt="Product Image">
            </div>
            <button type="button" class="btn-change-image"
                    onclick="document.getElementById('uploadImage').click()">
              Change Image
            </button>
            <input type="file" id="uploadImage" name="product_image"
                   style="display:none" accept="image/*">
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary"
                  onclick="window.location.href='product_management.php'">Back</button>
          <button type="submit" class="btn btn-primary"
                  style="padding:0.75rem 2rem;font-weight:600;">
            Save Changes
          </button>
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

    // Auto-hide alert messages after 3 seconds
    setTimeout(() => {
      document.querySelectorAll('.alert').forEach(a => a.style.display = 'none');
    }, 3000);
  </script>
</body>
</html>
