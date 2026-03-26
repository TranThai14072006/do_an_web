-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 08:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jewelry_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `full_name`, `phone`, `address`, `birthday`, `gender`, `created_at`) VALUES
(1, 1, 'Sophia Nguyen', '0901 234 567', '123 Le Loi, Q1, HCMC', '1995-03-12', 'Female', '2026-03-21 13:03:02'),
(2, 2, 'Minh Tran', '0938 456 789', '45 Vo Van Tan, Q3, HCMC', '1990-07-25', 'Male', '2026-03-21 13:03:02'),
(3, 3, 'David Le', '0912 987 654', '56 Hai Ba Trung, Hanoi', '1988-11-05', 'Male', '2026-03-21 13:03:02'),
(4, 4, 'Emily Pham', '0987 112 233', '22 Nguyen Hue, Q1, HCMC', '1997-01-30', 'Female', '2026-03-21 13:03:02'),
(5, 5, 'Huy Dang', '0909 654 321', '78 Le Duan, Da Nang', '1993-06-18', 'Male', '2026-03-21 13:03:02'),
(6, 6, 'Anna Vu', '0935 111 222', '14 Tran Phu, Hue', '1996-09-22', 'Female', '2026-03-21 13:03:02'),
(7, 7, 'Thanh Ho', '0902 222 333', '99 Nguyen Trai, HCMC', '1991-04-10', 'Male', '2026-03-21 13:03:02'),
(8, 8, 'Mai Do', '0944 777 888', '64 Pasteur, Q3, HCMC', '1994-08-14', 'Female', '2026-03-21 13:03:02'),
(9, 9, 'Kevin Truong', '0988 999 000', '11 Hoang Dieu, Hanoi', '1989-12-03', 'Male', '2026-03-21 13:03:02'),
(10, 10, 'Lisa Nguyen', '0911 222 444', '03 Nguyen Van Linh, Da Nang', '1998-05-27', 'Female', '2026-03-21 13:03:02');

-- --------------------------------------------------------

--
-- Table structure for table `goods_issue`
--

CREATE TABLE `goods_issue` (
  `id` int(11) NOT NULL,
  `issue_number` varchar(50) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `customer` varchar(255) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `status` enum('Draft','Completed') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_issue_items`
--

CREATE TABLE `goods_issue_items` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt`
--

CREATE TABLE `goods_receipt` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `status` enum('Draft','Completed') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_receipt`
--

INSERT INTO `goods_receipt` (`id`, `order_number`, `entry_date`, `supplier`, `total_quantity`, `total_value`, `status`, `created_at`) VALUES
(2, 'ORD001', '2026-03-18', 'ABC Jewelry', 3, 620.00, 'Completed', '2026-03-18 13:02:02'),
(3, 'ORD002', '2026-03-17', 'Golden Shop', 4, 800.00, 'Completed', '2026-03-18 13:02:02'),
(4, 'ORD003', '2026-03-16', 'Silver World', 6, 900.00, 'Draft', '2026-03-18 13:02:02'),
(5, 'ORD004', '2026-03-15', 'Luxury Gems', 1, 150.00, 'Completed', '2026-03-18 13:02:02'),
(6, 'ORD138', '2026-03-14', 'Diamond Center', 1, 700.00, 'Completed', '2026-03-18 13:02:02'),
(7, 'ORD006', '2026-03-13', 'Pearl Store', 5, 1000.00, 'Draft', '2026-03-18 13:02:02'),
(8, 'ORD007', '2026-03-12', 'Gold & Silver Co.', 2, 300.00, 'Completed', '2026-03-18 13:02:02'),
(9, 'ORD008', '2026-03-11', 'Fine Jewelry Ltd.', 7, 1500.00, 'Draft', '2026-03-18 13:02:02'),
(10, 'ORD.546', '2026-12-24', NULL, 11, 2900000.00, 'Completed', '2026-03-19 06:00:03'),
(11, 'ORD.888', '2025-12-22', NULL, 10, 10000.00, 'Completed', '2026-03-22 04:20:51'),
(12, '444', '2026-03-22', 'nemchua', 12, 10800.00, 'Completed', '2026-03-22 04:25:59'),
(13, 'ORD.982', '2026-03-26', 'raumanian.co', 3, 36000000.00, 'Completed', '2026-03-26 07:20:12');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_receipt_items`
--

INSERT INTO `goods_receipt_items` (`id`, `receipt_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`) VALUES
(3, 10, 'R001', 'Kane Moissanite Ring', 9, 100000.00, 900000.00),
(4, 10, 'R002', 'Winston Anchor Ring', 2, 1000000.00, 2000000.00),
(5, 11, 'R001', 'Kane Moissanite Ring', 10, 1000.00, 10000.00),
(6, 12, 'R003', 'Ula Opal Teardrop Ring', 12, 900.00, 10800.00),
(7, 13, 'R004', 'Platinum Clover Charm Ring', 3, 12000000.00, 36000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('Pending','Processed','Delivered','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_date`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ODR.001', 1, '2025-10-01', 12500.00, 'Delivered', '2026-03-21 12:24:15', '2026-03-21 13:07:52'),
(2, 'ODR.002', 2, '2025-09-25', 8750.00, 'Delivered', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(3, 'ODR.003', 3, '2025-09-18', 9200.00, 'Pending', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(4, 'ODR.004', 4, '2025-09-10', 15400.00, 'Cancelled', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(5, 'ODR.005', 5, '2025-09-05', 10200.00, 'Delivered', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(6, 'ODR.006', 6, '2025-08-29', 7800.00, 'Processed', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(7, 'ODR.007', 7, '2025-08-25', 5600.00, 'Pending', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(8, 'ODR.008', 8, '2025-08-20', 11400.00, 'Processed', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(9, 'ODR.009', 9, '2025-08-15', 6700.00, 'Delivered', '2026-03-21 12:24:15', '2026-03-21 12:24:15'),
(10, 'ODR.010', 10, '2025-08-10', 13200.00, 'Pending', '2026-03-21 12:24:15', '2026-03-21 12:24:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 'R001', 'Ula Opal Teardrop Ring', 1, 40.00, 59.96),
(2, 1, 'R002', 'Kane Moissanite Ring', 2, 1000.00, 1371.00),
(3, 1, 'R003', 'Platinum Clover Charm Ring', 1, 1200.00, 1950.00),
(4, 1, 'R004', 'Emerald Bracelet', 1, 1100.00, 1950.00),
(5, 1, 'R005', 'Royal Moissanite Ring', 2, 1600.00, 2200.00),
(6, 1, 'R002', 'Winston Anchor Ring', 1, 1150.00, 1850.00),
(7, 1, 'R005', 'Paisley Moissanite Ring', 1, 980.00, 1380.00),
(8, 1, 'R001', 'Arielle Princess CZ Ring', 1, 1000.00, 1250.00),
(9, 1, 'R003', 'Miracle Queen CZ Ring', 2, 1020.00, 1470.00),
(10, 1, 'R004', 'Niche Crown Stack Ring', 1, 1120.00, 1490.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--
CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `profit_percent` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

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

--
-- Table structure for table `receipt_details`
--

CREATE TABLE `receipt_details` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 10,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'sophia.nguyen', 'sophia.nguyen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(2, 'minh.tran', 'minh.tran@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(3, 'david.le', 'david.le@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(4, 'emily.pham', 'emily.pham@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(5, 'huy.dang', 'huy.dang@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(6, 'anna.vu', 'anna.vu@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(7, 'thanh.ho', 'thanh.ho@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(8, 'mai.do', 'mai.do@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(9, 'kevin.truong', 'kevin.truong@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(10, 'lisa.nguyen', 'lisa.nguyen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16'),
(11, '1', 'nemchua@gmail.com', '$2y$10$POD2RJ5w4FFrxzQZUyc96.Q265UdXO9PGssgw5OcPtyRiUGXHFqny', '2026-03-24 15:29:24'),
(13, 'Nam Trần Thanh', 'thanhnamtran13082006@gmail.com', '$2y$10$HOZzQSuDlinhJ1x4ZPeVxulHrXwBbwn6UGcJRCE2qZuK0DWhGyir6', '2026-03-24 15:50:20'),
(16, 'user', 'user@gmail.com', '$2y$10$MPasv2TvXGiWyl/vcRo5E.sLLefM9SWgRpAfd0CXV1Ja5uxGU5rIC', '2026-03-24 15:52:24'),
(17, 'user02', 'user02@gmail.com', '$2y$10$oY/hqfEnGiA/cDa8AAzHu.k3na7Mmr.ZnYT7gWCz4fMm/8X9q5gY6', '2026-03-24 15:55:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `goods_issue`
--
ALTER TABLE `goods_issue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods_issue_items`
--
ALTER TABLE `goods_issue_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `goods_issue`
--
ALTER TABLE `goods_issue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_issue_items`
--
ALTER TABLE `goods_issue_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `receipt_details`
--
ALTER TABLE `receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_issue_items`
--
ALTER TABLE `goods_issue_items`
  ADD CONSTRAINT `goods_issue_items_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `goods_issue` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_issue_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipt_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD CONSTRAINT `receipt_details_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
