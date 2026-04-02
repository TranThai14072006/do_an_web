-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: jewelry_db
-- ------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- ============================================================
-- 1. BẢNG ĐỘC LẬP (không có FK) - tạo trước
-- ============================================================

--
-- Table: users
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('Active','Locked') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'sophia.nguyen','sophia.nguyen@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(2,'minh.tran','minh.tran@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(3,'david.le','david.le@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(4,'emily.pham','emily.pham@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(5,'huy.dang','huy.dang@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(6,'anna.vu','anna.vu@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(7,'thanh.ho','thanh.ho@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(8,'mai.do','mai.do@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(9,'kevin.truong','kevin.truong@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(10,'lisa.nguyen','lisa.nguyen@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),
(11,'admin','admin@gmail.com','admin01','Active','2026-03-27 06:45:58','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: products
--
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `profit_percent` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`id`, `name`, `price`, `image`, `cost_price`, `profit_percent`, `stock`, `category`, `gender`) VALUES
('R001', 'Kane Moissanite Ring', 1326.00, 'R001.jpg', 1020.00, 30, 10, 'Ring', 'Male'),
('R002', 'Winston Anchor Ring', 1708.00, 'R002.jpg', 1220.00, 40, 5, 'Ring', 'Male'),
('R003', 'Ula Opal Teardrop Ring', 49.38, 'R003.jpg', 39.50, 25, 15, 'Ring', 'Female'),
('R004', 'Platinum Clover Charm Ring', 1633.50, 'R004.jpg', 1210.00, 35, 8, 'Ring', 'Female'),
('R005', 'Paisley Moissanite Ring', 1176.00, 'R005.jpg', 980.00, 20, 12, 'Ring', 'Female'),
('R006', 'Niche Crown Stack Ring', 120.00, 'R006.jpg', 60.00, 100, 10, 'Ring', 'Female'),
('R007', 'The Zenith Ring', 130.00, 'R007.jpg', 70.00, 85, 15, 'Ring', 'Male'),
('R008', 'Silver Eminence Ring', 150.00, 'R008.jpg', 90.00, 67, 8, 'Ring', 'Male'),
('R009', 'Elysian Flow', 200.00, 'R009.jpg', 120.00, 67, 5, 'Ring', 'Unisex'),
('R010', 'Onyx Edge', 95.00, 'R010.jpg', 50.00, 90, 12, 'Ring', 'Unisex');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: goods_receipt
--
DROP TABLE IF EXISTS `goods_receipt`;
CREATE TABLE `goods_receipt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `status` enum('Draft','Completed') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `goods_receipt` WRITE;
/*!40000 ALTER TABLE `goods_receipt` DISABLE KEYS */;
INSERT INTO `goods_receipt` VALUES
(2,'ORD001','2026-03-18','ABC Jewelry',3,620.00,'Completed','2026-03-18 13:02:02'),
(3,'ORD002','2026-03-17','Golden Shop',4,800.00,'Completed','2026-03-18 13:02:02'),
(4,'ORD003','2026-03-16','Silver World',6,900.00,'Draft','2026-03-18 13:02:02'),
(5,'ORD004','2026-03-15','Luxury Gems',1,150.00,'Completed','2026-03-18 13:02:02'),
(6,'ORD138','2026-03-14','Diamond Center',1,700.00,'Completed','2026-03-18 13:02:02'),
(7,'ORD006','2026-03-13','Pearl Store',5,1000.00,'Draft','2026-03-18 13:02:02'),
(8,'ORD007','2026-03-12','Gold & Silver Co.',2,300.00,'Completed','2026-03-18 13:02:02'),
(9,'ORD008','2026-03-11','Fine Jewelry Ltd.',7,1500.00,'Draft','2026-03-18 13:02:02'),
(10,'ORD.546','2026-12-24',NULL,11,2900000.00,'Completed','2026-03-19 06:00:03');
/*!40000 ALTER TABLE `goods_receipt` ENABLE KEYS */;
UNLOCK TABLES;

-- ============================================================
-- 2. BẢNG CÓ FK - tạo sau khi bảng cha đã tồn tại
-- ============================================================

--
-- Table: customers (FK → users)
--
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(1,1,'Sophia Nguyen','0901 234 567','123 Le Loi, Q1, HCMC','1995-03-12','Female',NULL,'2026-03-27 05:51:33'),
(2,2,'Minh Tran','0938 456 789','45 Vo Van Tan, Q3, HCMC','1990-07-25','Male',NULL,'2026-03-27 05:51:33'),
(3,3,'David Le','0912 987 654','56 Hai Ba Trung, Hanoi','1988-11-05','Male',NULL,'2026-03-27 05:51:33'),
(4,4,'Emily Pham','0987 112 233','22 Nguyen Hue, Q1, HCMC','1997-01-30','Female',NULL,'2026-03-27 05:51:33'),
(5,5,'Huy Dang','0909 654 321','78 Le Duan, Da Nang','1993-06-18','Male',NULL,'2026-03-27 05:51:33'),
(6,6,'Anna Vu','0935 111 222','14 Tran Phu, Hue','1996-09-22','Female',NULL,'2026-03-27 05:51:33'),
(7,7,'Thanh Ho','0902 222 333','99 Nguyen Trai, HCMC','1991-04-10','Male',NULL,'2026-03-27 05:51:33'),
(8,8,'Mai Do','0944 777 888','64 Pasteur, Q3, HCMC','1994-08-14','Female',NULL,'2026-03-27 05:51:33'),
(9,9,'Kevin Truong','0988 999 000','11 Hoang Dieu, Hanoi','1989-12-03','Male',NULL,'2026-03-27 05:51:33'),
(10,10,'Lisa Nguyen','0911 222 444','03 Nguyen Van Linh, Da Nang','1998-05-27','Female',NULL,'2026-03-27 05:51:33');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: product_details (FK → products)
--
DROP TABLE IF EXISTS `product_details`;
CREATE TABLE `product_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `design` varchar(255) DEFAULT NULL,
  `stone` varchar(100) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `brand` varchar(255) DEFAULT 'ThirtySix Jewellery',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `product_details` WRITE;
/*!40000 ALTER TABLE `product_details` DISABLE KEYS */;
INSERT INTO `product_details` (`id`, `product_id`, `description`, `material`, `design`, `stone`, `color`, `brand`) VALUES
(1, 'R001', 'A refined sterling silver ring featuring a shimmering teardrop Opal stone, radiating elegance and feminine charm.', 'Sterling Silver', 'Teardrop shape', 'Synthetic Opal', 'Silver', 'ThirtySix Jewellery'),
(2, 'R002', 'A delicate floral-inspired ring with sparkling crystal accents, perfect for a soft feminine look.', 'Sterling Silver', 'Floral design', 'Crystal', 'Black', 'ThirtySix Jewellery'),
(3, 'R003', 'A refined sterling silver ring featuring a shimmering teardrop Opal stone.', 'Sterling Silver', 'Teardrop shape', 'Synthetic Opal', 'Silver', 'ThirtySix Jewellery'),
(4, 'R004', 'A luxurious platinum ring featuring a clover charm symbolizing luck.', 'Platinum', 'Clover charm', 'None', 'Silver', 'ThirtySix Jewellery'),
(5, 'R005', 'An elegant moissanite ring with paisley-inspired patterns.', 'White Gold', 'Paisley pattern', 'Moissanite', 'Silver', 'ThirtySix Jewellery'),
(6, 'R006', 'A trendy crown stack ring perfect for layering.', 'Sterling Silver', 'Stackable crown', 'None', 'Gold', 'ThirtySix Jewellery'),
(7, 'R007', 'A bold masculine ring with a modern design.', 'Stainless Steel', 'Minimalist', 'None', 'Silver', 'ThirtySix Jewellery'),
(8, 'R008', 'A refined silver ring for a sophisticated look.', 'Sterling Silver', 'Polished design', 'None', 'Silver', 'ThirtySix Jewellery'),
(9, 'R009', 'A unisex ring with fluid curves representing balance.', 'Sterling Silver', 'Flowing curve', 'None', 'Silver', 'ThirtySix Jewellery'),
(10, 'R010', 'A stylish ring featuring a striking onyx centerpiece.', 'Stainless Steel', 'Edge-cut', 'Onyx', 'Black', 'ThirtySix Jewellery');
/*!40000 ALTER TABLE `product_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: goods_receipt_items (FK → goods_receipt)
--
DROP TABLE IF EXISTS `goods_receipt_items`;
CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_id` int(11) DEFAULT NULL,
  `product_id` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipt_id` (`receipt_id`),
  CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (3,10,'R001','Unknown',9,100000.00,900000.00),(4,10,'R002','Unknown',2,1000000.00,2000000.00);
/*!40000 ALTER TABLE `goods_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: receipt_details (FK → goods_receipt)
--
DROP TABLE IF EXISTS `receipt_details`;
CREATE TABLE `receipt_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipt_id` (`receipt_id`),
  CONSTRAINT `receipt_details_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: orders (FK → customers)
--
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('Pending','Processed','Delivered','Cancelled') DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,'ORD-2025-001',1,'2025-10-05',520.00,'Delivered'),
(2,'ORD-2025-002',2,'2025-10-12',310.00,'Delivered'),
(3,'ORD-2025-003',3,'2025-11-01',780.00,'Processed'),
(4,'ORD-2025-004',4,'2025-11-15',410.00,'Delivered'),
(5,'ORD-2025-005',5,'2025-12-03',225.00,'Pending'),
(6,'ORD-2025-006',6,'2025-12-10',640.00,'Delivered'),
(7,'ORD-2025-007',7,'2026-01-08',290.00,'Cancelled'),
(8,'ORD-2025-008',8,'2026-01-20',870.00,'Delivered'),
(9,'ORD-2025-009',9,'2026-02-05',355.00,'Processed'),
(10,'ORD-2025-010',10,'2026-02-18',490.00,'Pending');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: order_items (FK → orders)
--
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_price` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: cart (FK → users)
--
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `size` varchar(10) DEFAULT '',
  PRIMARY KEY (`user_id`, `product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
