CREATE DATABASE IF NOT EXISTS jewelry_db;
USE jewelry_db;

CREATE TABLE goods_receipt (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    entry_date DATE NOT NULL,
    supplier VARCHAR(255),
    total_quantity INT DEFAULT 0,
    total_value DECIMAL(15, 2) DEFAULT 0,
    status ENUM('Draft', 'Completed') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE receipt_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT,
    product_code VARCHAR(50),
    product_name VARCHAR(255),
    quantity INT,
    price DECIMAL(15, 2),
    FOREIGN KEY (receipt_id) REFERENCES goods_receipt(id) ON DELETE CASCADE
);