
USE jewelry_db;



-- Bảng chi tiết sản phẩm
CREATE TABLE goods_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT,
    product_id VARCHAR(50),
    product_name VARCHAR(255),
    quantity INT,
    unit_price DECIMAL(15,2),
    total_price DECIMAL(15,2),
    FOREIGN KEY (receipt_id) REFERENCES goods_receipt(id) ON DELETE CASCADE
);

-- Bảng sản phẩm (optional)
CREATE TABLE products (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255),
    price DECIMAL(15,2),
    image VARCHAR(255)
);