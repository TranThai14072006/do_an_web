-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: jewelry_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,1,'Sophia Nguyen','0901 234 567','123 Le Loi, Q1, HCMC','1995-03-12','Female','2026-03-27 05:51:33'),(2,2,'Minh Tran','0938 456 789','45 Vo Van Tan, Q3, HCMC','1990-07-25','Male','2026-03-27 05:51:33'),(3,3,'David Le','0912 987 654','56 Hai Ba Trung, Hanoi','1988-11-05','Male','2026-03-27 05:51:33'),(4,4,'Emily Pham','0987 112 233','22 Nguyen Hue, Q1, HCMC','1997-01-30','Female','2026-03-27 05:51:33'),(5,5,'Huy Dang','0909 654 321','78 Le Duan, Da Nang','1993-06-18','Male','2026-03-27 05:51:33'),(6,6,'Anna Vu','0935 111 222','14 Tran Phu, Hue','1996-09-22','Female','2026-03-27 05:51:33'),(7,7,'Thanh Ho','0902 222 333','99 Nguyen Trai, HCMC','1991-04-10','Male','2026-03-27 05:51:33'),(8,8,'Mai Do','0944 777 888','64 Pasteur, Q3, HCMC','1994-08-14','Female','2026-03-27 05:51:33'),(9,9,'Kevin Truong','0988 999 000','11 Hoang Dieu, Hanoi','1989-12-03','Male','2026-03-27 05:51:33'),(10,10,'Lisa Nguyen','0911 222 444','03 Nguyen Van Linh, Da Nang','1998-05-27','Female','2026-03-27 05:51:33');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipt`
--

DROP TABLE IF EXISTS `goods_receipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt`
--

LOCK TABLES `goods_receipt` WRITE;
/*!40000 ALTER TABLE `goods_receipt` DISABLE KEYS */;
INSERT INTO `goods_receipt` VALUES (1,'[value-2]','0000-00-00','[value-4]',0,0.00,'','0000-00-00 00:00:00'),(2,'ORD001','2026-03-18','ABC Jewelry',3,620.00,'Completed','2026-03-18 13:02:02'),(3,'ORD002','2026-03-17','Golden Shop',4,800.00,'Completed','2026-03-18 13:02:02'),(4,'ORD003','2026-03-16','Silver World',6,900.00,'Draft','2026-03-18 13:02:02'),(5,'ORD004','2026-03-15','Luxury Gems',1,150.00,'Completed','2026-03-18 13:02:02'),(6,'ORD138','2026-03-14','Diamond Center',1,700.00,'Completed','2026-03-18 13:02:02'),(7,'ORD006','2026-03-13','Pearl Store',5,1000.00,'Draft','2026-03-18 13:02:02'),(8,'ORD007','2026-03-12','Gold & Silver Co.',2,300.00,'Completed','2026-03-18 13:02:02'),(9,'ORD008','2026-03-11','Fine Jewelry Ltd.',7,1500.00,'Draft','2026-03-18 13:02:02'),(10,'ORD.546','2026-12-24',NULL,11,2900000.00,'Completed','2026-03-19 06:00:03');
/*!40000 ALTER TABLE `goods_receipt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipt_items`
--

DROP TABLE IF EXISTS `goods_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_items`
--

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (3,10,'R001','Unknown',9,100000.00,900000.00),(4,10,'R002','Unknown',2,1000000.00,2000000.00);
/*!40000 ALTER TABLE `goods_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-2025-001',1,'2025-10-05',520.00,'Delivered'),(2,'ORD-2025-002',2,'2025-10-12',310.00,'Delivered'),(3,'ORD-2025-003',3,'2025-11-01',780.00,'Processed'),(4,'ORD-2025-004',4,'2025-11-15',410.00,'Delivered'),(5,'ORD-2025-005',5,'2025-12-03',225.00,'Pending'),(6,'ORD-2025-006',6,'2025-12-10',640.00,'Delivered'),(7,'ORD-2025-007',7,'2026-01-08',290.00,'Cancelled'),(8,'ORD-2025-008',8,'2026-01-20',870.00,'Delivered'),(9,'ORD-2025-009',9,'2026-02-05',355.00,'Processed'),(10,'ORD-2025-010',10,'2026-02-18',490.00,'Pending');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_details`
--

DROP TABLE IF EXISTS `product_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `design` varchar(255) DEFAULT NULL,
  `stone` varchar(255) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `brand` varchar(255) DEFAULT 'ThirtySix Jewellery',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_details`
--

LOCK TABLES `product_details` WRITE;
/*!40000 ALTER TABLE `product_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `profit_percent` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES ('B001','Luxury Gold Bracelet',0.00,'B001.jpg',2000.00,50,0,'Unisex'),('E001','Pearl Drop Earrings',0.00,'E001.jpg',500.00,40,0,'Unisex'),('E002','Crystal Stud Earrings',0.00,'E002.jpg',300.00,35,0,'Unisex'),('N001','Diamond Heart Necklace',0.00,'N001.jpg',1500.00,30,0,'Unisex'),('N002','Gold Chain Necklace',0.00,'N002.jpg',1100.00,25,0,'Unisex'),('R001','Kane Moissanite Ring',0.00,'R001.jpg',1020.00,30,9,'Unisex'),('R002','Winston Anchor Ring',0.00,'R002.jpg',1220.00,40,2,'Unisex'),('R003','Ula Opal Teardrop Ring',0.00,'R003.jpg',39.50,25,0,'Unisex'),('R004','Platinum Clover Charm Ring',0.00,'R004.jpg',1210.00,35,0,'Unisex'),('R005','Paisley Moissanite Ring',0.00,'R005.jpg',980.00,20,0,'Unisex');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

<<<<<<< HEAD

-- --------------------------------------------------------
Cấu trúc bảng cho bảng `product_details`
--

CREATE TABLE `product_details` (
  `id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `design` varchar(255) DEFAULT NULL,
  `stone` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_details`
--

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

--
-- Chỉ mục cho các bảng đã đổ
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
ALTER TABLE `product_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `product_details`
  ADD CONSTRAINT `product_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

CREATE TABLE `cart` (
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE `cart`
  ADD PRIMARY KEY (`product_id`);
ALTER TABLE cart ADD COLUMN size VARCHAR(10) DEFAULT '';
=======
>>>>>>> 74b9b53 (Refactor: Decouple Customer Management from Admin Menu, implement real DB pagination, and enforce secure Role-Based Access Control (RBAC) across all login portals)
--
-- Table structure for table `receipt_details`
--

DROP TABLE IF EXISTS `receipt_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_details`
--

LOCK TABLES `receipt_details` WRITE;
/*!40000 ALTER TABLE `receipt_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipt_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'sophia.nguyen','sophia.nguyen@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(2,'minh.tran','minh.tran@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(3,'david.le','david.le@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(4,'emily.pham','emily.pham@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(5,'huy.dang','huy.dang@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(6,'anna.vu','anna.vu@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(7,'thanh.ho','thanh.ho@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(8,'mai.do','mai.do@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(9,'kevin.truong','kevin.truong@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(10,'lisa.nguyen','lisa.nguyen@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','2026-03-21 06:03:02','user'),(11,'admin','admin@gmail.com','admin01','Active','2026-03-27 06:45:58','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-27 15:06:42
