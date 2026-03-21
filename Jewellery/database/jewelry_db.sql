-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 02:03 PM
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
(1, '[value-2]', '0000-00-00', '[value-4]', 0, 0.00, '', '0000-00-00 00:00:00'),
(2, 'ORD001', '2026-03-18', 'ABC Jewelry', 3, 620.00, 'Completed', '2026-03-18 13:02:02'),
(3, 'ORD002', '2026-03-17', 'Golden Shop', 4, 800.00, 'Completed', '2026-03-18 13:02:02'),
(4, 'ORD003', '2026-03-16', 'Silver World', 6, 900.00, 'Draft', '2026-03-18 13:02:02'),
(5, 'ORD004', '2026-03-15', 'Luxury Gems', 1, 150.00, 'Completed', '2026-03-18 13:02:02'),
(6, 'ORD138', '2026-03-14', 'Diamond Center', 1, 700.00, 'Completed', '2026-03-18 13:02:02'),
(7, 'ORD006', '2026-03-13', 'Pearl Store', 5, 1000.00, 'Draft', '2026-03-18 13:02:02'),
(8, 'ORD007', '2026-03-12', 'Gold & Silver Co.', 2, 300.00, 'Completed', '2026-03-18 13:02:02'),
(9, 'ORD008', '2026-03-11', 'Fine Jewelry Ltd.', 7, 1500.00, 'Draft', '2026-03-18 13:02:02'),
(10, 'ORD.546', '2026-12-24', NULL, 11, 2900000.00, 'Completed', '2026-03-19 06:00:03');

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
(4, 10, 'R002', 'Unknown', 2, 1000000.00, 2000000.00);

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
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`, `cost_price`, `profit_percent`, `stock`, `category`) VALUES
('B001', 'Luxury Gold Bracelet', 0.00, 'B001.jpg', 2000.00, 50, 4, 'Bracelet'),
('E001', 'Pearl Drop Earrings', 0.00, 'E001.jpg', 500.00, 40, 20, 'Earring'),
('E002', 'Crystal Stud Earrings', 0.00, 'E002.jpg', 300.00, 35, 25, 'Earring'),
('N001', 'Diamond Heart Necklace', 0.00, 'N001.jpg', 1500.00, 30, 6, 'Necklace'),
('N002', 'Gold Chain Necklace', 0.00, 'N002.jpg', 1100.00, 25, 9, 'Necklace'),
('R001', 'Kane Moissanite Ring', 0.00, 'R001.jpg', 1020.00, 30, 10, 'Ring'),
('R002', 'Winston Anchor Ring', 0.00, 'R002.jpg', 1220.00, 40, 5, 'Ring'),
('R003', 'Ula Opal Teardrop Ring', 0.00, 'R003.jpg', 39.50, 25, 15, 'Ring'),
('R004', 'Platinum Clover Charm Ring', 0.00, 'R004.jpg', 1210.00, 35, 8, 'Ring'),
('R005', 'Paisley Moissanite Ring', 0.00, 'R005.jpg', 980.00, 20, 12, 'Ring');

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

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `goods_receipt`
--
ALTER TABLE `goods_receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `receipt_details`
--
ALTER TABLE `receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD CONSTRAINT `receipt_details_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
