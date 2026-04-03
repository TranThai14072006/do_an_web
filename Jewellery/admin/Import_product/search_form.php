<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Entry Form Management</title>
  <link rel="stylesheet" href="../admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .back-button-container { margin-bottom: 0.5cm; }
    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; }
  </style>
</head>
<body>

<?php include '../sidebar_include.php'; ?>

<div class="content">

  <!-- MAIN -->
  <main>
    <header><h1>Import Management</h1></header> 
    <div class="back-button-container">
      <button type="button" class="btn" onclick="window.location.href='import_management.php'">Back</button>
    </div>
    <!-- SEARCH -->
    <div class="search-section">
      <div class="search-group">
        <label class="search-label">From date</label>
        <input type="date" class="search-input">
      </div>

      <div class="search-group">
        <label class="search-label">To date</label>
        <input type="date" class="search-input">
      </div>

      <button class="btn-search" onclick="window.location.href='search_form.php'">Search</button>
      <button class="btn-reset">Reset</button>
    </div>

    <!-- TABLE -->
    <div class="user-list">
      <table>
        <thead>
          <tr>
            <th>No.</th>
            <th>Order No.</th>
            <th>Date</th>
            <th>Quantity of products</th>
            <th>Total value</th>
            <th>Action</th>
          </tr>
        </thead>
        <?php
        include __DIR__ . "/../../config/config.php";

        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';

        $sql = "SELECT * FROM goods_receipt WHERE 1";

        if($from && $to){
            $sql .= " AND entry_date BETWEEN '$from' AND '$to'";
        }

        $result = $conn->query($sql);
        $i=1;
        ?>

        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= $row['order_number'] ?></td>
              <td><?= $row['entry_date'] ?></td>
              <td><?= $row['total_quantity'] ?></td>
              <td><?= $row['total_value'] ?></td>
              <td>
                <a href="entry_form_detail.php?id=<?= $row['id'] ?>" class="btn small">View Detail</a>
              </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination">
      <button class="pagination-btn">&#10094;</button>
      <button class="pagination-btn active">1</button>
      <button class="pagination-btn">2</button>
      <button class="pagination-btn">3</button>
      <button class="pagination-btn">4</button>
      <button class="pagination-btn">5</button>
      <button class="pagination-btn">&#10095;</button>
    </div>

    <div class="pagination-info">Showing 1-10 of Results</div>
  </main>
</div>
</body>
</html>
