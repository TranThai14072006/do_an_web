<?php
session_start();
require_once '../config/config.php';

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    header('Location: Administration_menu.php#product-manage');
    exit;
}

// ── Fetch product from DB ─────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT p.*, COALESCE(pd.description,'') AS description,
            COALESCE(pd.material,'')    AS material,
            COALESCE(pd.design,'')      AS design,
            COALESCE(pd.stone,'')       AS stone,
            COALESCE(pd.color,'')       AS color,
            COALESCE(pd.brand,'ThirtySix Jewellery') AS brand
     FROM products p
     LEFT JOIN product_details pd ON pd.product_id = p.id
     WHERE p.id = ?"
);
$stmt->bind_param("s", $code);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: Administration_menu.php#product-manage');
    exit;
}

$success = '';
$error   = '';

// ── Handle POST (save changes) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName       = trim($_POST['product_name']  ?? '');
    $newPrice      = floatval($_POST['price']      ?? 0);
    $newCost       = floatval($_POST['cost_price'] ?? 0);
    $newStock      = intval($_POST['stock']        ?? 0);
    $newGender     = trim($_POST['gender']         ?? $product['gender']);
    $newDesc       = trim($_POST['description']    ?? '');
    $newMaterial   = trim($_POST['material']       ?? '');
    $newDesign     = trim($_POST['design']         ?? '');
    $newStone      = trim($_POST['stone']          ?? '');
    $newColor      = trim($_POST['color']          ?? '');
    $newBrand      = trim($_POST['brand']          ?? 'ThirtySix Jewellery');
    $newCategory   = $newGender; // category = gender
    $newProfit     = ($newCost > 0 && $newPrice > 0)
                     ? intval(round(($newPrice - $newCost) / $newCost * 100)) : 0;

    if (empty($newName)) {
        $error = 'Product Name is required.';
    } else {
        // Upload image if provided
        $newImage = $product['image'];
        if (!empty($_FILES['product_image']['name'])) {
            $uploadDir  = '../images/';
            $ext        = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $fileName   = preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
                $newImage = $fileName;
            } else {
                $error = 'Failed to upload image.';
            }
        }

        if (empty($error)) {
            // UPDATE products
            $upd = $conn->prepare(
                "UPDATE products SET name=?, price=?, cost_price=?, profit_percent=?, stock=?, category=?, gender=?, image=?
                 WHERE id=?"
            );
            $upd->bind_param("sddiiisss", $newName, $newPrice, $newCost, $newProfit, $newStock, $newCategory, $newGender, $newImage, $code);
            if ($upd->execute()) {
                // UPDATE or INSERT product_details
                $checkDet = $conn->prepare("SELECT id FROM product_details WHERE product_id = ?");
                $checkDet->bind_param("s", $code);
                $checkDet->execute();
                $checkDet->store_result();

                if ($checkDet->num_rows > 0) {
                    $det = $conn->prepare(
                        "UPDATE product_details SET description=?, material=?, design=?, stone=?, color=?, brand=?
                         WHERE product_id=?"
                    );
                    $det->bind_param("sssssss", $newDesc, $newMaterial, $newDesign, $newStone, $newColor, $newBrand, $code);
                } else {
                    $det = $conn->prepare(
                        "INSERT INTO product_details (product_id, description, material, design, stone, color, brand)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $det->bind_param("sssssss", $code, $newDesc, $newMaterial, $newDesign, $newStone, $newColor, $newBrand);
                }
                $det->execute();
                $det->close();
                $checkDet->close();
                $upd->close();

                // Refresh product data
                $stmt2 = $conn->prepare(
                    "SELECT p.*, COALESCE(pd.description,'') AS description, COALESCE(pd.material,'') AS material,
                            COALESCE(pd.design,'') AS design, COALESCE(pd.stone,'') AS stone,
                            COALESCE(pd.color,'') AS color, COALESCE(pd.brand,'ThirtySix Jewellery') AS brand
                     FROM products p LEFT JOIN product_details pd ON pd.product_id = p.id WHERE p.id = ?"
                );
                $stmt2->bind_param("s", $code);
                $stmt2->execute();
                $product = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                $success = 'Product updated successfully!';
            } else {
                $error = 'Database error: ' . $conn->error;
                $upd->close();
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
  <title>Edit Product – Admin</title>
  <style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:"Segoe UI",sans-serif; background:#f3f3f3; color:#333; display:flex; min-height:100vh; }
    .sidebar { width:220px; background:#8e4b00; color:#f8ce86; display:flex; flex-direction:column; padding:20px; position:fixed; top:0; left:0; height:100vh; }
    .logo { text-align:center; margin-bottom:30px; }
    .logo img { width:80px; border-radius:50%; }
    .logo h2 { font-size:16px; margin-top:10px; }
    .menu a { display:block; padding:11px 14px; color:#f8ce86; text-decoration:none; border-radius:8px; margin-bottom:8px; font-weight:600; font-size:14px; transition:background 0.3s,color 0.3s; }
    .menu a:hover, .menu a.active { background:#f8ce86; color:#8e4b00; }
    main { margin-left:220px; flex:1; padding:30px; }
    h1 { color:#8e4b00; margin-bottom:24px; font-size:24px; }
    .card { background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); padding:30px; max-width:1100px; }
    .card-title { color:#8e4b00; font-size:18px; font-weight:600; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid #f0e2d0; }
    .form-layout { display:flex; flex-wrap:wrap; gap:30px; }
    .form-col-main { flex:2; min-width:380px; }
    .form-col-side { flex:1; min-width:260px; display:flex; flex-direction:column; }
    .form-section-title { font-weight:600; color:#8e4b00; margin-bottom:12px; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; }
    .form-group { display:flex; flex-direction:column; margin-bottom:14px; }
    .form-group label { font-weight:600; margin-bottom:6px; color:#555; font-size:14px; }
    input[type=text], input[type=number], textarea, select {
      padding:10px 12px; border:1px solid #ddd; border-radius:8px;
      font-size:14px; transition:border-color 0.2s; width:100%; background:#fff;
    }
    input[readonly] { background:#f9f9f9; color:#888; cursor:not-allowed; }
    input:focus, textarea:focus, select:focus { border-color:#8e4b00; outline:none; box-shadow:0 0 0 3px rgba(142,75,0,0.08); }
    textarea { resize:vertical; min-height:90px; }
    .form-row { display:flex; gap:14px; }
    .form-row .form-group { flex:1; }
    .image-wrap { text-align:center; }
    .image-preview { width:200px; height:200px; border:2px dashed #ddd; border-radius:10px; overflow:hidden;
                     display:flex; align-items:center; justify-content:center; background:#fafafa; margin:0 auto 12px; }
    .image-preview img { max-width:100%; max-height:100%; object-fit:contain; }
    .btn-upload { display:inline-block; background:linear-gradient(135deg,#a3670b,#8e4b00); color:white; border:none;
                  padding:9px 18px; border-radius:7px; font-weight:600; cursor:pointer; font-size:13px; transition:0.25s; }
    .btn-upload:hover { background:linear-gradient(135deg,#c17a0d,#a3670b); transform:translateY(-1px); }
    .form-actions { display:flex; justify-content:center; gap:20px; margin-top:30px; padding-top:24px; border-top:2px solid #f0e2d0; }
    .btn { padding:11px 28px; border-radius:8px; font-weight:600; cursor:pointer; border:none; font-size:15px; transition:all 0.3s; text-decoration:none; display:inline-block; }
    .btn-secondary { background:#e8e8e8; color:#555; }
    .btn-secondary:hover { background:#d0d0d0; transform:translateY(-1px); }
    .btn-primary { background:linear-gradient(135deg,#c17a0d,#8e4b00); color:white; box-shadow:0 3px 8px rgba(142,75,0,0.3); }
    .btn-primary:hover { background:linear-gradient(135deg,#d8921b,#a3670b); transform:translateY(-1px); }
    .alert { padding:12px 16px; border-radius:8px; margin-bottom:18px; font-weight:500; font-size:14px; }
    .alert-success { background:#e8f5e9; color:#2e7d32; border-left:4px solid #2e7d32; }
    .alert-error   { background:#ffebee; color:#c62828; border-left:4px solid #c62828; }
    .section-divider { border:none; border-top:1px solid #f0e2d0; margin:16px 0; }
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
      <a href="Import_product/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1>Edit Product — <?php echo htmlspecialchars($product['id']); ?></h1>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?>
        <a href="Administration_menu.php#product-manage" style="margin-left:12px;text-decoration:underline;">← Back to list</a>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-title">Product Information</div>

      <form method="post" action="edit_product.php?code=<?php echo urlencode($code); ?>" enctype="multipart/form-data">
        <div class="form-layout">

          <!-- ── Main Column ── -->
          <div class="form-col-main">

            <div class="form-section-title">Basic Info</div>
            <div class="form-row">
              <div class="form-group">
                <label>Product Code</label>
                <input type="text" name="product_code" value="<?php echo htmlspecialchars($product['id']); ?>" readonly>
              </div>
              <div class="form-group">
                <label>Category (Gender)</label>
                <select name="gender">
                  <option value="Male"   <?php echo $product['gender'] === 'Male'   ? 'selected' : ''; ?>>Male</option>
                  <option value="Female" <?php echo $product['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                  <option value="Unisex" <?php echo $product['gender'] === 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Product Name</label>
              <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
            </div>

            <hr class="section-divider">
            <div class="form-section-title">Pricing & Stock</div>
            <div class="form-row">
              <div class="form-group">
                <label>Selling Price ($)</label>
                <input type="number" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>">
              </div>
              <div class="form-group">
                <label>Cost Price ($)</label>
                <input type="number" name="cost_price" step="0.01" min="0" value="<?php echo $product['cost_price']; ?>">
              </div>
              <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" min="0" value="<?php echo $product['stock']; ?>">
              </div>
            </div>

            <hr class="section-divider">
            <div class="form-section-title">Product Details</div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Material</label>
                <input type="text" name="material" value="<?php echo htmlspecialchars($product['material']); ?>">
              </div>
              <div class="form-group">
                <label>Stone</label>
                <input type="text" name="stone" value="<?php echo htmlspecialchars($product['stone']); ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Design</label>
                <input type="text" name="design" value="<?php echo htmlspecialchars($product['design']); ?>">
              </div>
              <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" value="<?php echo htmlspecialchars($product['color']); ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Brand</label>
              <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>">
            </div>
          </div>

          <!-- ── Side Column (Image) ── -->
          <div class="form-col-side">
            <div class="image-wrap">
              <div class="form-section-title">Product Image</div>
              <div class="image-preview">
                <img id="previewImage" src="../images/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">
              </div>
              <input type="file" id="uploadImage" name="product_image" accept="image/*" hidden>
              <label for="uploadImage" class="btn-upload">Change Image</label>
            </div>
          </div>
        </div>

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