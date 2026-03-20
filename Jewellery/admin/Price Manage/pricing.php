<?php
require_once "../config.php";

$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pricing Management 2</title>
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif;}
body {background:#f5f5f5; display:flex; color:#333;}

.btn {
  background: linear-gradient(195deg, #8e4b00, #8e4b00);
  color: #f8ce86;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: bold;
  transition: 0.3s;
  border: none;
  cursor: pointer;
}
.btn:hover {background: linear-gradient(195deg, #d8921b, #a3670b); transform: translateY(-2px);}

/* SIDEBAR */
.sidebar {
  width:220px; background:#8e4b00; color:#f8ce86;
  display:flex; flex-direction:column; padding:20px;
  height:100vh; position:fixed; left:0; top:0;
}
.logo {text-align:center; margin-bottom:30px;}
.logo img {width:80px; border-radius:50%;}
.logo h2 {font-size:18px; margin-top:10px;}
.menu a {display:block; padding:12px; color:#f8ce86; text-decoration:none;
  border-radius:8px; margin-bottom:10px; font-weight:bold; transition:0.3s;}
.menu a:hover, .menu a.active {background:#f8ce86; color:#8e4b00;}

/* MAIN */
main {flex:1; padding:25px 40px; margin-left:220px;}
header h1 {font-size:24px; color:#8e4b00; margin-bottom:20px;}

/* TAB BUTTONS */
input[name="tab"]{display:none;}
label.tab-btn{
  padding:10px 18px; font-weight:600; color:#444; cursor:pointer;
  display:inline-block; margin-right:60px; position:relative;
}
input[name="tab"]:checked + label{color:#8e4b00;}
input[name="tab"]:checked + label::after{
  content:""; left:0; bottom:-2px; width:100%; height:3px;
  background:#8e4b00; border-radius:2px; position:absolute;
}

/* TAB CONTENT */
.tab-content{display:none; animation:fadeIn .3s ease;}
@keyframes fadeIn {from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }
#tab1:checked ~ .contents #content1{display:block;}
#tab2:checked ~ .contents #content2{display:block;}
#tab3:checked ~ .contents #content3{display:block;}

/* SEARCH area */
.search-section{
  background:#fff; border-radius:10px; padding:20px;
  display:flex; gap:20px; align-items:flex-end;
  box-shadow:0 2px 6px rgba(0,0,0,0.08); margin-bottom:25px;
}
.search-group{flex:1; display:flex; flex-direction:column;}
.search-label{font-weight:600; margin-bottom:6px;}
.search-input, .search-input select{
  height:42px; padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:15px;
}
.btn-search,.btn-reset{
  height:42px; padding:0 20px; border:none; font-weight:600; border-radius:6px; cursor:pointer;
}
.btn-search{background:#8e4b00; color:#fff;}
.btn-reset{background:#888; color:#fff;}

/* TABLE */
table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
th, td {
  padding: 14px 16px;
  text-align: center;
  font-size: 15px;
  border-bottom: 1px solid #f0e2d0;
}
th {
  background: linear-gradient(195deg, #8e4b00, #8e4b00);
  color: #f8ce86;
  font-weight: 600;
  letter-spacing: 0.3px;
}
tr:hover td {background: #f9f2e7;}
input[type="number"]{
  width:80px; padding:6px; border:1px solid #ccc; border-radius:5px; text-align:center;
}

/* POPUP */
.popup{
  position:fixed; top:0; left:0; width:100%; height:100%;
  display:none; justify-content:center; align-items:center;
  background:rgba(0,0,0,0.5); z-index:3000;
}
.popup-box{
  background:#fff; border-radius:10px; padding:25px 35px; width:360px;
  text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.2);
}
#savePopup:checked ~ .popup{display:flex;}
.overlay{position:absolute;width:100%;height:100%;top:0;left:0;}
.close-btn{margin-top:15px;display:inline-block;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="logo">
    <img src="../../images/Admin_login.jpg">
    <h2>Luxury Jewelry Admin</h2>
  </div>
  <div class="menu">
    <a href="../Administration_menu.html#products">Jewelry List</a>
    <a href="../Administration_menu.html#product-manage">Product Management</a>
    <a href="#" class="active">Pricing Management</a>
    <a href="../Administration_menu.html#users">Customers</a>
    <a href="../Import product manage/import_management.html">Import Management</a>
    <a href="../Stock Manage/stocking_management.html">Stocking Management</a>
    <a href="../Administration_menu.html#settings">Settings</a>
  </div>
</div>

<main>
  <header><h1>Pricing & Profit Management</h1></header>

  <!-- TAB selection -->
  <input type="radio" id="tab1" name="tab" checked>
  <label for="tab1" class="tab-btn">Selling Price Lookup</label>

  <input type="radio" id="tab2" name="tab">
  <label for="tab2" class="tab-btn">Profit by Product</label>

  <input type="radio" id="tab3" name="tab">
  <label for="tab3" class="tab-btn">Profit by Category</label>

  <!-- POPUP -->
  <input type="checkbox" id="savePopup" hidden>
  <div class="popup">
    <label for="savePopup" class="overlay"></label>
    <div class="popup-box">
      <h3>Change successfully!</h3>
      <label for="savePopup" class="btn close-btn">OK</label>
    </div>
  </div>

  <style>
  .popup {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    display: none; align-items: center; justify-content: center;
    background: rgba(0, 0, 0, 0.4); z-index: 1000;
  }
  .popup-box {
    background: #fff; padding: 25px 40px;
    border-radius: 10px; text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    animation: fadeIn .3s ease;
  }
  .popup-box h3 {color:#8e4b00; margin-bottom:20px;}
  .overlay {position: absolute; top:0; left:0; right:0; bottom:0;}
  .close-btn {background:#8e4b00; color:#fff; padding:10px 20px; border:none; border-radius:8px; cursor:pointer;}
  #savePopup:checked ~ .popup {display: flex;}
  </style>

  <div class="contents">

    <!-- TAB1 -->
    <div id="content1" class="tab-content">
      <form method="GET" action="search.php">
  
      <div class="search-section">

        <div class="search-group">
          <label class="search-label">Product Name</label>
          <input type="text" class="search-input" name="name">
        </div>

        <div class="search-group">
          <label class="search-label">Category</label>
          <select class="search-input" name="category">
            <option value="All">All</option>
            <option>Male</option>
            <option>Female</option>
            <option>Unisex</option>
          </select>
        </div>

    <button type="submit" class="btn-search">Search</button>
    <button type="button" class="btn-reset" onclick="window.location.href='pricing.php'">Reset</button>

  </div>

</form>

      <table>
        <thead><tr><th>No.</th><th>Image</th><th>Product</th><th>Category</th><th>Cost Price</th><th>Profit (%)</th><th>Selling Price</th><th>Action</th></tr></thead>
        <tbody>
        <?php
        $sql = "SELECT * FROM products";
        $result = $conn->query($sql);
        $i = 1;

        while($row = $result->fetch_assoc()):
        $price = $row['cost_price'] + ($row['cost_price'] * $row['profit_percent'] / 100);
        ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><img src="../../images/<?= $row['image'] ?>" width="60"></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['category'] ?></td>
            <td><?= $row['cost_price'] ?></td>
            <td><?= $row['profit_percent'] ?>%</td>
            <td>$<?= number_format($price,2) ?></td>
            <td>
              <a href="edit_price.php?id=<?= $row['id'] ?>" class="btn small">Edit</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- TAB2 -->
    <div id="content2" class="tab-content">
      <table>
        <thead><tr><th>No.</th><th>Image</th><th>Product</th><th>Category</th><th>Profit (%)</th><th>Action</th></tr></thead>
        <tbody>
          <?php
          $sql = "SELECT * FROM products";
          $result = $conn->query($sql);
          $i = 1;

          while($row = $result->fetch_assoc()):
          ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><img src="../../images/<?= $row['image'] ?>" width="60"></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['category'] ?></td>

            <td>
              <form method="POST" action="update_profit.php" style="display:flex;gap:10px;justify-content:center;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="number" name="profit" value="<?= $row['profit_percent'] ?>" min="0" max="100">
                <button class="btn">Save</button>
              </form>
            </td>

          </tr>
          <?php endwhile; ?>
          </tbody>
      </table>
    </div>

    <!-- TAB3 -->
    <div id="content3" class="tab-content">
      <table>
        <thead><tr><th>No.</th><th>Category</th><th>Profit (%)</th><th>Action</th></tr></thead>
        <tbody>
        <?php
        $sql = "SELECT category, AVG(profit_percent) as avg_profit FROM products GROUP BY category";
        $result = $conn->query($sql);
        $i = 1;

        while($row = $result->fetch_assoc()):
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= $row['category'] ?></td>

          <td>
            <form method="POST" action="update_category_profit.php" style="display:flex;gap:10px;justify-content:center;">
              <input type="hidden" name="category" value="<?= $row['category'] ?>">
              <input type="number" name="profit" value="<?= round($row['avg_profit']) ?>">
              <button class="btn">Save</button>
            </form>
          </td>

        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

</body>
</html>
