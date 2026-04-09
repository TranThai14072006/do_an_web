-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 06:08 PM
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
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `product_id` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `size` varchar(10) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`user_id`, `product_id`, `quantity`, `size`) VALUES
(12, 'R002', 1, '6');

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
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `full_name`, `phone`, `address`, `birthday`, `gender`, `avatar`, `created_at`) VALUES
(1, 1, 'Sophia Nguyen', '0901 234 567', '123 Le Loi, Q1, HCMC', '1995-03-12', 'Female', NULL, '2026-03-27 05:51:33'),
(2, 2, 'Minh Tran', '0938 456 789', '45 Vo Van Tan, Q3, HCMC', '1990-07-25', 'Male', NULL, '2026-03-27 05:51:33'),
(3, 3, 'David Le', '0912 987 654', '56 Hai Ba Trung, Hanoi', '1988-11-05', 'Male', NULL, '2026-03-27 05:51:33'),
(4, 4, 'Emily Pham', '0987 112 233', '22 Nguyen Hue, Q1, HCMC', '1997-01-30', 'Female', NULL, '2026-03-27 05:51:33'),
(5, 5, 'Huy Dang', '0909 654 321', '78 Le Duan, Da Nang', '1993-06-18', 'Male', NULL, '2026-03-27 05:51:33'),
(6, 6, 'Anna Vu', '0935 111 222', '14 Tran Phu, Hue', '1996-09-22', 'Female', NULL, '2026-03-27 05:51:33'),
(7, 7, 'Thanh Ho', '0902 222 333', '99 Nguyen Trai, HCMC', '1991-04-10', 'Male', NULL, '2026-03-27 05:51:33'),
(8, 8, 'Mai Do', '0944 777 888', '64 Pasteur, Q3, HCMC', '1994-08-14', 'Female', NULL, '2026-03-27 05:51:33'),
(9, 9, 'Kevin Truong', '0988 999 000', '11 Hoang Dieu, Hanoi', '1989-12-03', 'Male', NULL, '2026-03-27 05:51:33'),
(10, 10, 'Lisa Nguyen', '0911 222 444', '03 Nguyen Van Linh, Da Nang', '1998-05-27', 'Female', NULL, '2026-03-27 05:51:33'),
(11, 12, 'testing', '0901234567', '39 le duan q1 hcm', NULL, NULL, NULL, '2026-04-02 10:43:31'),
(12, 13, 'user04', '0901234568', '39 le duan q1 hcm', NULL, NULL, 'avatar_13.png', '2026-04-02 10:54:15');

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
(11, 'IMP001', '2025-10-05', 'Premium Gems Co.', 5, 1250.00, 'Completed', '2025-10-05 02:00:00'),
(12, 'IMP002', '2025-10-05', 'Luxury Imports', 3, 780.00, 'Completed', '2025-10-05 07:30:00'),
(13, 'IMP003', '2025-10-12', 'Diamond Wholesale', 4, 960.00, 'Completed', '2025-10-12 03:15:00'),
(14, 'IMP004', '2025-10-12', 'Gold Chain Suppliers', 2, 520.00, 'Completed', '2025-10-12 09:45:00'),
(15, 'IMP005', '2025-11-01', 'Silver Works Inc.', 6, 1380.00, 'Completed', '2025-11-01 04:20:00'),
(16, 'IMP006', '2025-11-01', 'Pearl Distributors', 3, 690.00, 'Completed', '2025-11-01 08:10:00'),
(17, 'IMP007', '2025-11-15', 'Crystal Clear Gems', 4, 920.00, 'Completed', '2025-11-15 02:45:00'),
(18, 'IMP008', '2025-11-15', 'Fine Metals Ltd.', 2, 460.00, 'Completed', '2025-11-15 06:30:00'),
(19, 'IMP009', '2025-12-03', 'Jewel Masters', 5, 1150.00, 'Completed', '2025-12-03 03:00:00'),
(20, 'IMP010', '2025-12-03', 'Gemstone Imports', 3, 690.00, 'Completed', '2025-12-03 07:20:00'),
(21, 'IMP011', '2025-12-10', 'Luxury Rings Co.', 4, 920.00, 'Completed', '2025-12-10 04:15:00'),
(22, 'IMP012', '2025-12-10', 'Chain Manufacturers', 2, 460.00, 'Completed', '2025-12-10 08:40:00'),
(23, 'IMP013', '2026-01-08', 'Diamond Cutters Inc.', 6, 1380.00, 'Completed', '2026-01-08 02:30:00'),
(24, 'IMP014', '2026-01-08', 'Goldsmith Workshop', 3, 690.00, 'Completed', '2026-01-08 06:50:00'),
(25, 'IMP015', '2026-01-20', 'Silver Smiths', 4, 920.00, 'Completed', '2026-01-20 03:25:00'),
(26, 'IMP016', '2026-01-20', 'Pearl Importers', 2, 460.00, 'Completed', '2026-01-20 07:35:00'),
(27, 'IMP017', '2026-02-05', 'Gem Traders', 5, 1150.00, 'Completed', '2026-02-05 04:40:00'),
(28, 'IMP018', '2026-02-05', 'Luxury Accessories', 3, 690.00, 'Completed', '2026-02-05 08:55:00'),
(29, 'IMP019', '2026-02-18', 'Fine Jewelry Imports', 4, 920.00, 'Completed', '2026-02-18 02:15:00'),
(30, 'IMP020', '2026-02-18', 'Chain & Ring Co.', 2, 460.00, 'Completed', '2026-02-18 06:25:00'),
(31, 'ORD.114', '2026-04-08', 'taokhongban', 8, 64000.00, 'Completed', '2026-04-08 09:34:59'),
(32, 'ORD.155', '2026-04-08', 'taokhongban', 5, 45000000.00, 'Completed', '2026-04-08 09:36:16'),
(33, 'IMP021', '2026-01-05', 'Premium Gems Co.', 5, 6630.00, 'Completed', '2026-01-05 01:00:00'),
(34, 'IMP022', '2026-01-05', 'Luxury Imports', 4, 6832.00, 'Completed', '2026-01-05 03:30:00'),
(35, 'IMP023', '2026-01-12', 'Diamond Wholesale', 6, 720.00, 'Completed', '2026-01-12 00:15:00'),
(36, 'IMP024', '2026-01-12', 'Gold Chain Suppliers', 3, 4900.50, 'Completed', '2026-01-12 06:00:00'),
(37, 'IMP025', '2026-01-19', 'Silver Works Inc.', 5, 650.00, 'Completed', '2026-01-19 02:00:00'),
(38, 'IMP026', '2026-01-19', 'Pearl Distributors', 4, 800.00, 'Completed', '2026-01-19 07:20:00'),
(39, 'IMP027', '2026-01-26', 'Crystal Clear Gems', 3, 3978.00, 'Completed', '2026-01-26 01:45:00'),
(40, 'IMP028', '2026-01-26', 'Fine Metals Ltd.', 4, 520.00, 'Completed', '2026-01-26 04:00:00'),
(41, 'IMP029', '2026-02-03', 'Jewel Masters', 5, 6630.00, 'Completed', '2026-02-03 01:00:00'),
(42, 'IMP030', '2026-02-03', 'Gemstone Imports', 4, 480.00, 'Completed', '2026-02-03 03:00:00'),
(43, 'IMP031', '2026-02-10', 'Luxury Rings Co.', 6, 9801.00, 'Completed', '2026-02-10 00:30:00'),
(44, 'IMP032', '2026-02-10', 'Chain Manufacturers', 3, 390.00, 'Completed', '2026-02-10 05:00:00'),
(45, 'IMP033', '2026-02-17', 'Diamond Cutters Inc.', 4, 5304.00, 'Completed', '2026-02-17 02:15:00'),
(46, 'IMP034', '2026-02-17', 'Goldsmith Workshop', 3, 450.00, 'Completed', '2026-02-17 06:30:00'),
(47, 'IMP035', '2026-02-24', 'Silver Smiths', 5, 650.00, 'Completed', '2026-02-24 01:00:00'),
(48, 'IMP036', '2026-02-24', 'Pearl Importers', 4, 800.00, 'Completed', '2026-02-24 04:00:00'),
(49, 'IMP037', '2026-03-02', 'Gem Traders', 5, 6630.00, 'Completed', '2026-03-02 01:00:00'),
(50, 'IMP038', '2026-03-02', 'Luxury Accessories', 4, 6832.00, 'Completed', '2026-03-02 03:00:00'),
(51, 'IMP039', '2026-03-09', 'Fine Jewelry Imports', 6, 720.00, 'Completed', '2026-03-09 00:00:00'),
(52, 'IMP040', '2026-03-09', 'Chain & Ring Co.', 3, 4900.50, 'Completed', '2026-03-09 05:30:00'),
(53, 'IMP041', '2026-03-16', 'ABC Jewelry', 5, 800.00, 'Completed', '2026-03-16 02:00:00'),
(54, 'IMP042', '2026-03-16', 'Golden Shop', 4, 5304.00, 'Completed', '2026-03-16 06:00:00'),
(55, 'IMP043', '2026-03-23', 'Diamond Center', 3, 360.00, 'Completed', '2026-03-23 01:30:00'),
(56, 'IMP044', '2026-03-23', 'Silver World', 5, 650.00, 'Completed', '2026-03-23 04:00:00'),
(57, 'IMP045', '2026-03-30', 'Premium Gems Co.', 4, 4900.50, 'Completed', '2026-03-30 01:00:00'),
(58, 'IMP046', '2026-03-30', 'Luxury Imports', 3, 450.00, 'Completed', '2026-03-30 03:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `product_id` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_receipt_items`
--

INSERT INTO `goods_receipt_items` (`id`, `receipt_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`) VALUES
(3, 10, 'R001', 'Unknown', 9, 100000.00, 900000.00),
(4, 10, 'R002', 'Unknown', 2, 1000000.00, 2000000.00),
(5, 11, 'R001', 'Kane Moissanite Ring', 2, 1326.00, 2652.00),
(6, 11, 'R003', 'Ula Opal Teardrop Ring', 3, 49.38, 148.14),
(7, 12, 'R005', 'Paisley Moissanite Ring', 3, 1176.00, 3528.00),
(8, 13, 'R002', 'Winston Anchor Ring', 4, 1708.00, 6832.00),
(9, 14, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(10, 15, 'R004', 'Platinum Clover Charm Ring', 2, 1633.50, 3267.00),
(11, 15, 'R007', 'The Zenith Ring', 4, 130.00, 520.00),
(12, 16, 'R008', 'Silver Eminence Ring', 3, 150.00, 450.00),
(13, 17, 'R001', 'Kane Moissanite Ring', 4, 1326.00, 5304.00),
(14, 18, 'R009', 'Elysian Flow', 2, 200.00, 400.00),
(15, 19, 'R003', 'Ula Opal Teardrop Ring', 5, 49.38, 246.90),
(16, 20, 'R005', 'Paisley Moissanite Ring', 3, 1176.00, 3528.00),
(17, 21, 'R002', 'Winston Anchor Ring', 4, 1708.00, 6832.00),
(18, 22, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(19, 23, 'R004', 'Platinum Clover Charm Ring', 3, 1633.50, 4900.50),
(20, 23, 'R007', 'The Zenith Ring', 3, 130.00, 390.00),
(21, 24, 'R008', 'Silver Eminence Ring', 3, 150.00, 450.00),
(22, 25, 'R001', 'Kane Moissanite Ring', 4, 1326.00, 5304.00),
(23, 26, 'R009', 'Elysian Flow', 2, 200.00, 400.00),
(24, 27, 'R003', 'Ula Opal Teardrop Ring', 5, 49.38, 246.90),
(25, 28, 'R005', 'Paisley Moissanite Ring', 3, 1176.00, 3528.00),
(26, 29, 'R002', 'Winston Anchor Ring', 4, 1708.00, 6832.00),
(27, 30, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(28, 31, 'R003', 'Ula Opal Teardrop Ring', 8, 8000.00, 64000.00),
(29, 32, 'R002', 'Winston Anchor Ring', 5, 9000000.00, 45000000.00),
(30, 33, 'R001', 'Kane Moissanite Ring', 5, 1326.00, 6630.00),
(31, 34, 'R002', 'Winston Anchor Ring', 4, 1708.00, 6832.00),
(32, 35, 'R006', 'Niche Crown Stack Ring', 4, 120.00, 480.00),
(33, 35, 'R007', 'The Zenith Ring', 2, 130.00, 260.00),
(34, 36, 'R004', 'Platinum Clover Charm Ring', 3, 1633.50, 4900.50),
(35, 37, 'R008', 'Silver Eminence Ring', 3, 150.00, 450.00),
(36, 37, 'R009', 'Elysian Flow', 2, 200.00, 400.00),
(37, 38, 'R003', 'Ula Opal Teardrop Ring', 4, 49.38, 197.52),
(38, 38, 'R010', 'Onyx Edge', 4, 95.00, 380.00),
(39, 39, 'R005', 'Paisley Moissanite Ring', 3, 1176.00, 3528.00),
(40, 40, 'R007', 'The Zenith Ring', 2, 130.00, 260.00),
(41, 40, 'R008', 'Silver Eminence Ring', 2, 150.00, 300.00),
(42, 41, 'R001', 'Kane Moissanite Ring', 5, 1326.00, 6630.00),
(43, 42, 'R009', 'Elysian Flow', 2, 200.00, 400.00),
(44, 42, 'R010', 'Onyx Edge', 2, 95.00, 190.00),
(45, 43, 'R004', 'Platinum Clover Charm Ring', 6, 1633.50, 9801.00),
(46, 44, 'R007', 'The Zenith Ring', 3, 130.00, 390.00),
(47, 45, 'R001', 'Kane Moissanite Ring', 4, 1326.00, 5304.00),
(48, 46, 'R008', 'Silver Eminence Ring', 3, 150.00, 450.00),
(49, 47, 'R006', 'Niche Crown Stack Ring', 3, 120.00, 360.00),
(50, 47, 'R009', 'Elysian Flow', 2, 200.00, 400.00),
(51, 48, 'R003', 'Ula Opal Teardrop Ring', 4, 49.38, 197.52),
(52, 48, 'R010', 'Onyx Edge', 3, 95.00, 285.00),
(53, 49, 'R001', 'Kane Moissanite Ring', 5, 1326.00, 6630.00),
(54, 50, 'R002', 'Winston Anchor Ring', 4, 1708.00, 6832.00),
(55, 51, 'R006', 'Niche Crown Stack Ring', 4, 120.00, 480.00),
(56, 51, 'R007', 'The Zenith Ring', 2, 130.00, 260.00),
(57, 52, 'R004', 'Platinum Clover Charm Ring', 3, 1633.50, 4900.50),
(58, 53, 'R008', 'Silver Eminence Ring', 3, 150.00, 450.00),
(59, 53, 'R009', 'Elysian Flow', 2, 200.00, 400.00),
(60, 54, 'R001', 'Kane Moissanite Ring', 4, 1326.00, 5304.00),
(61, 55, 'R006', 'Niche Crown Stack Ring', 3, 120.00, 360.00),
(62, 56, 'R005', 'Paisley Moissanite Ring', 3, 1176.00, 3528.00),
(63, 56, 'R010', 'Onyx Edge', 2, 95.00, 190.00),
(64, 57, 'R004', 'Platinum Clover Charm Ring', 3, 1633.50, 4900.50),
(65, 58, 'R008', 'Silver Eminence Ring', 3, 150.00, 450.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('Pending','Processed','Delivered','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_date`, `total_amount`, `status`) VALUES
(1, 'ORD-2025-001', 1, '2025-10-05', 360.00, 'Delivered'),
(2, 'ORD-2025-002', 2, '2025-10-12', 375.00, 'Delivered'),
(3, 'ORD-2025-003', 3, '2025-11-01', 3034.00, 'Processed'),
(4, 'ORD-2025-004', 4, '2025-11-15', 1633.50, 'Delivered'),
(5, 'ORD-2025-005', 5, '2025-12-03', 249.38, 'Pending'),
(6, 'ORD-2025-006', 6, '2025-12-10', 1176.00, 'Delivered'),
(7, 'ORD-2025-007', 7, '2026-01-08', 370.00, 'Cancelled'),
(8, 'ORD-2025-008', 8, '2026-01-20', 1476.00, 'Delivered'),
(9, 'ORD-2025-009', 9, '2026-02-05', 1708.00, 'Processed'),
(10, 'ORD-2025-010', 10, '2026-02-18', 1728.50, 'Pending'),
(11, 'ORD202604025B03', 12, '2026-04-02', 3465.38, 'Processed'),
(12, 'ORD202604080E0E', 12, '2026-04-08', 3416.00, 'Processed'),
(13, 'ORD20260408F913', 12, '2026-04-08', 3468515.87, 'Cancelled'),
(14, 'ORD2026040983A2', 12, '2026-04-09', 3464797.63, 'Cancelled'),
(15, 'ORD202604091D33', 12, '2026-04-09', 1633.50, 'Cancelled'),
(16, 'ORD202604091B49', 12, '2026-04-09', 3464668.13, 'Cancelled'),
(17, 'ORD202604093FEC', 12, '2026-04-09', 120.00, 'Cancelled'),
(18, 'ORD-2026-001', 1, '2026-01-04', 1326.00, 'Delivered'),
(19, 'ORD-2026-002', 2, '2026-01-07', 1758.00, 'Delivered'),
(20, 'ORD-2026-003', 3, '2026-01-10', 3367.50, 'Delivered'),
(21, 'ORD-2026-004', 4, '2026-01-14', 345.00, 'Delivered'),
(22, 'ORD-2026-005', 5, '2026-01-17', 1326.00, 'Delivered'),
(23, 'ORD-2026-006', 6, '2026-01-21', 1708.00, 'Delivered'),
(24, 'ORD-2026-007', 7, '2026-01-24', 445.00, 'Delivered'),
(25, 'ORD-2026-008', 8, '2026-01-28', 2652.00, 'Delivered'),
(26, 'ORD-2026-009', 9, '2026-02-03', 1176.00, 'Delivered'),
(27, 'ORD-2026-010', 10, '2026-02-06', 1633.50, 'Delivered'),
(28, 'ORD-2026-011', 1, '2026-02-09', 520.00, 'Delivered'),
(29, 'ORD-2026-012', 2, '2026-02-13', 1326.00, 'Delivered'),
(30, 'ORD-2026-013', 3, '2026-02-16', 1858.00, 'Delivered'),
(31, 'ORD-2026-014', 4, '2026-02-19', 295.00, 'Delivered'),
(32, 'ORD-2026-015', 5, '2026-02-22', 3267.00, 'Delivered'),
(33, 'ORD-2026-016', 6, '2026-02-25', 650.00, 'Delivered'),
(34, 'ORD-2026-017', 7, '2026-03-02', 1326.00, 'Delivered'),
(35, 'ORD-2026-018', 8, '2026-03-05', 1708.00, 'Delivered'),
(36, 'ORD-2026-019', 9, '2026-03-08', 3267.00, 'Delivered'),
(37, 'ORD-2026-020', 10, '2026-03-11', 345.00, 'Delivered'),
(38, 'ORD-2026-021', 1, '2026-03-14', 1176.00, 'Delivered'),
(39, 'ORD-2026-022', 2, '2026-03-17', 2652.00, 'Delivered'),
(40, 'ORD-2026-023', 3, '2026-03-20', 595.00, 'Delivered'),
(41, 'ORD-2026-024', 4, '2026-03-23', 1633.50, 'Delivered'),
(42, 'ORD-2026-025', 5, '2026-03-26', 520.00, 'Delivered'),
(43, 'ORD-2026-026', 6, '2026-03-29', 1326.00, 'Delivered');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_price` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 11, 'R002', 'Winston Anchor Ring', 2, 1708.00, 3416.00),
(2, 11, 'R003', 'Ula Opal Teardrop Ring', 1, 49.38, 49.38),
(3, 1, 'R006', 'Niche Crown Stack Ring', 3, 120.00, 360.00),
(4, 2, 'R007', 'The Zenith Ring', 1, 130.00, 130.00),
(5, 2, 'R008', 'Silver Eminence Ring', 1, 150.00, 150.00),
(6, 2, 'R010', 'Onyx Edge', 1, 95.00, 95.00),
(7, 3, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00),
(8, 3, 'R002', 'Winston Anchor Ring', 1, 1708.00, 1708.00),
(9, 4, 'R004', 'Platinum Clover Charm Ring', 1, 1633.50, 1633.50),
(10, 5, 'R009', 'Elysian Flow', 1, 200.00, 200.00),
(11, 5, 'R003', 'Ula Opal Teardrop Ring', 1, 49.38, 49.38),
(12, 6, 'R005', 'Paisley Moissanite Ring', 1, 1176.00, 1176.00),
(13, 7, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(14, 7, 'R007', 'The Zenith Ring', 1, 130.00, 130.00),
(15, 8, 'R008', 'Silver Eminence Ring', 1, 150.00, 150.00),
(16, 8, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00),
(17, 9, 'R002', 'Winston Anchor Ring', 1, 1708.00, 1708.00),
(18, 10, 'R004', 'Platinum Clover Charm Ring', 1, 1633.50, 1633.50),
(19, 10, 'R010', 'Onyx Edge', 1, 95.00, 95.00),
(20, 12, 'R002', 'Winston Anchor Ring', 2, 1708.00, 3416.00),
(21, 13, 'R002', 'Winston Anchor Ring', 1, 3464668.13, 3464668.13),
(22, 13, 'R003', 'Ula Opal Teardrop Ring', 1, 3847.74, 3847.74),
(23, 14, 'R002', 'Winston Anchor Ring', 1, 3464668.13, 3464668.13),
(24, 14, 'R007', 'The Zenith Ring', 1, 129.50, 129.50),
(25, 15, 'R004', 'Platinum Clover Charm Ring', 1, 1633.50, 1633.50),
(26, 16, 'R002', 'Winston Anchor Ring', 1, 3464668.13, 3464668.13),
(27, 17, 'R006', 'Niche Crown Stack Ring', 1, 120.00, 120.00),
(28, 18, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00),
(29, 19, 'R007', 'The Zenith Ring', 3, 130.00, 390.00),
(30, 19, 'R009', 'Elysian Flow', 1, 200.00, 200.00),
(31, 19, 'R010', 'Onyx Edge', 1, 95.00, 95.00),
(32, 20, 'R002', 'Winston Anchor Ring', 1, 1708.00, 1708.00),
(33, 20, 'R004', 'Platinum Clover Charm Ring', 1, 1633.50, 1633.50),
(34, 21, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(35, 21, 'R009', 'Elysian Flow', 1, 200.00, 200.00),
(36, 22, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00),
(37, 23, 'R002', 'Winston Anchor Ring', 1, 1708.00, 1708.00),
(38, 24, 'R006', 'Niche Crown Stack Ring', 1, 120.00, 120.00),
(39, 24, 'R007', 'The Zenith Ring', 1, 130.00, 130.00),
(40, 24, 'R010', 'Onyx Edge', 2, 95.00, 190.00),
(41, 25, 'R001', 'Kane Moissanite Ring', 2, 1326.00, 2652.00),
(42, 26, 'R005', 'Paisley Moissanite Ring', 1, 1176.00, 1176.00),
(43, 27, 'R004', 'Platinum Clover Charm Ring', 1, 1633.50, 1633.50),
(44, 28, 'R007', 'The Zenith Ring', 2, 130.00, 260.00),
(45, 28, 'R008', 'Silver Eminence Ring', 1, 150.00, 150.00),
(46, 28, 'R009', 'Elysian Flow', 1, 200.00, 200.00),
(47, 29, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00),
(48, 30, 'R002', 'Winston Anchor Ring', 1, 1708.00, 1708.00),
(49, 30, 'R008', 'Silver Eminence Ring', 1, 150.00, 150.00),
(50, 31, 'R003', 'Ula Opal Teardrop Ring', 3, 49.38, 148.14),
(51, 31, 'R010', 'Onyx Edge', 1, 95.00, 95.00),
(52, 32, 'R004', 'Platinum Clover Charm Ring', 2, 1633.50, 3267.00),
(53, 33, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(54, 33, 'R005', 'Paisley Moissanite Ring', 1, 130.00, 130.00),
(55, 33, 'R010', 'Onyx Edge', 3, 95.00, 285.00),
(56, 34, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00),
(57, 35, 'R002', 'Winston Anchor Ring', 1, 1708.00, 1708.00),
(58, 36, 'R004', 'Platinum Clover Charm Ring', 2, 1633.50, 3267.00),
(59, 37, 'R006', 'Niche Crown Stack Ring', 2, 120.00, 240.00),
(60, 37, 'R010', 'Onyx Edge', 1, 95.00, 95.00),
(61, 38, 'R005', 'Paisley Moissanite Ring', 1, 1176.00, 1176.00),
(62, 39, 'R001', 'Kane Moissanite Ring', 2, 1326.00, 2652.00),
(63, 40, 'R007', 'The Zenith Ring', 2, 130.00, 260.00),
(64, 40, 'R008', 'Silver Eminence Ring', 1, 150.00, 150.00),
(65, 40, 'R009', 'Elysian Flow', 1, 200.00, 200.00),
(66, 41, 'R004', 'Platinum Clover Charm Ring', 1, 1633.50, 1633.50),
(67, 42, 'R007', 'The Zenith Ring', 2, 130.00, 260.00),
(68, 42, 'R008', 'Silver Eminence Ring', 1, 150.00, 150.00),
(69, 42, 'R009', 'Elysian Flow', 1, 200.00, 200.00),
(70, 43, 'R001', 'Kane Moissanite Ring', 1, 1326.00, 1326.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `profit_percent` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`, `cost_price`, `profit_percent`, `stock`, `category`, `gender`) VALUES
('R001', 'Kane Moissanite Ring', 1326.00, 'R001.jpg', 1020.00, 30, 22, 'Ring', 'Male'),
('R002', 'Winston Anchor Ring', 3464668.13, 'R002.jpg', 2474762.95, 40, 12, 'Ring', 'Male'),
('R003', 'Ula Opal Teardrop Ring', 3847.73, 'R003.jpg', 3078.19, 25, 23, 'Ring', 'Female'),
('R004', 'Platinum Clover Charm Ring', 1633.50, 'R004.jpg', 1210.00, 35, 10, 'Ring', 'Female'),
('R005', 'Paisley Moissanite Ring', 1176.00, 'R005.jpg', 980.00, 20, 11, 'Ring', 'Female'),
('R006', 'Niche Crown Stack Ring', 120.00, 'R006.jpg', 60.00, 100, 7, 'Ring', 'Female'),
('R007', 'The Zenith Ring', 130.00, 'R007.jpg', 70.00, 85, 3, 'Ring', 'Male'),
('R008', 'Silver Eminence Ring', 150.00, 'R008.jpg', 90.00, 67, 14, 'Ring', 'Male'),
('R009', 'Elysian Flow', 200.00, 'R009.jpg', 120.00, 67, 6, 'Ring', 'Unisex'),
('R010', 'Onyx Edge', 95.00, 'R010.jpg', 50.00, 90, 1, 'Ring', 'Unisex');

-- --------------------------------------------------------

--
-- Table structure for table `product_details`
--

CREATE TABLE `product_details` (
  `id` int(11) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `design` varchar(255) DEFAULT NULL,
  `stone` varchar(100) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `brand` varchar(255) DEFAULT 'ThirtySix Jewellery'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_details`
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

-- --------------------------------------------------------

--
-- Table structure for table `receipt_details`
--

CREATE TABLE `receipt_details` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL
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
  `status` enum('Active','Locked') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `status`, `created_at`, `role`) VALUES
(1, 'sophia.nguyen', 'sophia.nguyen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(2, 'minh.tran', 'minh.tran@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(3, 'david.le', 'david.le@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(4, 'emily.pham', 'emily.pham@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(5, 'huy.dang', 'huy.dang@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(6, 'anna.vu', 'anna.vu@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(7, 'thanh.ho', 'thanh.ho@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(8, 'mai.do', 'mai.do@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(9, 'kevin.truong', 'kevin.truong@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(10, 'lisa.nguyen', 'lisa.nguyen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 06:03:02', 'user'),
(12, 'testing', 'testing@gmail.com', '$2y$10$sWUGm7ga..6eS6Ap1v.bnupEAid2R1SkdRMn61qxwroK9iwU5JbNu', 'Active', '2026-04-02 10:43:31', 'user'),
(13, 'user04', 'user04@gmail.com', '$2y$10$GROhVyKeIbWTz0Gr2.5pne4xfoQHYFNe4T6tW1Ls2.CVbWYF2LLSO', 'Active', '2026-04-02 10:54:15', 'user'),
(15, 'admin01', 'admin01@gmail.com', '$2y$10$5QTkZLt4U00w1ixGKa4T2ukTuKesi0b.TJhuAdisH.5PeuPtjjF9W', 'Active', '2026-04-02 11:30:13', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`user_id`,`product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

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
  ADD KEY `receipt_id` (`receipt_id`);

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
-- Indexes for table `product_details`
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `product_details`
--
ALTER TABLE `product_details`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_details`
--
ALTER TABLE `product_details`
  ADD CONSTRAINT `product_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD CONSTRAINT `receipt_details_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
