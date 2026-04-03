<?php
require_once "../../config/config.php";

$name = $_GET['name'] ?? '';
$category = $_GET['category'] ?? '';
$gender   = $_GET['gender']   ?? '';

$sql = "SELECT * FROM products WHERE 1";

if ($name != '') {
    $sql .= " AND name LIKE '%$name%'";
}

if ($category != '' && $category != 'All') {
    $sql .= " AND category='$category'";
}
if ($gender != '' && $gender != 'All') {
    $sql .= " AND gender='$gender'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pricing Management 2</title>
  <link rel="stylesheet" href="../admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    #content1 { display: block !important; }
    .back-button-container { margin-bottom: 0.5cm; }
  </style>
</head>
<body>

<?php include '../sidebar_include.php'; ?>

<div class="content">

<main>
  <header><h1>Search Results </h1></header>


<div class="back-button-container">
  <button type="button" class="btn" onclick="window.location.href='pricing.php'">Back</button>
</div>

    <!-- TAB1 -->
    <div id="content1">
      <form method="GET" action="search.php" class="search-section">
        <div class="search-group">
          <label class="search-label">Product Name</label>
          <input type="text" name="name" class="search-input" value="<?= htmlspecialchars($name) ?>">
        </div>
        <div class="search-group">
          <label class="search-label">Category</label>
          <input type="text" name="category" class="search-input" placeholder="Ring, Necklace..." value="<?= htmlspecialchars($category) ?>">
        </div>
        <div class="search-group">
          <label class="search-label">Gender</label>
          <select name="gender" class="search-input">
            <option value="All">All</option>
            <option value="Male"   <?= $gender=='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= $gender=='Female'?'selected':'' ?>>Female</option>
            <option value="Unisex" <?= $gender=='Unisex'?'selected':'' ?>>Unisex</option>
          </select>
        </div>
        <button type="submit" class="btn-search">Search</button>
        <button type="button" class="btn-reset" onclick="window.location.href='search.php'">Reset</button>
      </form>

      <table>
        <thead><tr><th>No.</th><th>Product</th><th>Category</th><th>Cost Price</th><th>Profit (%)</th><th>Selling Price</th><th>Action</th></tr></thead>
        <tbody>
        <?php
        $i = 1;
        while($row = $result->fetch_assoc()):
        $price = $row['cost_price'] + ($row['cost_price']*$row['profit_percent']/100);
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= $row['name'] ?></td>
          <td><?= $row['category'] ?></td>
          <td>$<?= number_format($row['cost_price'], 2) ?></td>
          <td><?= $row['profit_percent'] ?>%</td>
          <td>$<?= number_format($price, 2) ?></td>
          <td>
            <a href="edit_price.php?id=<?= $row['id'] ?>" class="btn small">Edit</a>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>
</div>
</body>
</html>

