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
-- Database: `user_db`
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
(10, 'lisa.nguyen', 'lisa.nguyen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-21 12:48:16');

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
