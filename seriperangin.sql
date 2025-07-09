-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 09, 2025 at 09:43 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `seriperangin`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(16) NOT NULL,
  `item_id` int NOT NULL,
  `before_data` json NOT NULL,
  `after_data` json DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `item_id`, `before_data`, `after_data`, `timestamp`, `created_at`) VALUES
(1, 1, 'stock_out', 5, '{\"stock_level\": \"20\"}', '{\"stock_level\": \"15\"}', '2025-07-09 17:15:40', '2025-07-09 09:15:40'),
(2, 2, 'stock_out', 5, '{\"stock_level\": \"15\"}', '{\"stock_level\": \"12\"}', '2025-07-09 17:25:59', '2025-07-09 09:25:59');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `item_name`, `category`, `unit`, `created_at`, `image_path`) VALUES
(3, 'Carrot', 'Vegetables', 'pack', '2025-07-09 01:39:41', 'assets/images/inv_686dc85d4ced43.44706418.jpg'),
(5, 'Fish', 'Fish', 'kg', '2025-07-09 02:22:02', 'assets/images/inv_686dd24a156a53.76193312.png'),
(6, 'Onion', 'Dry Goods', 'kg', '2025-07-09 04:13:26', 'assets/images/inv_686dec66785289.27412172.jpeg'),
(7, 'Water', 'Beverages', 'L', '2025-07-09 09:10:00', 'assets/images/inv_686e31e8338787.29076107.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `stock_logs`
--

CREATE TABLE `stock_logs` (
  `log_id` int NOT NULL,
  `item_id` int NOT NULL,
  `type` enum('in','out') NOT NULL,
  `quantity` int NOT NULL,
  `batch_expiry_date` date DEFAULT NULL,
  `log_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_logs`
--

INSERT INTO `stock_logs` (`log_id`, `item_id`, `type`, `quantity`, `batch_expiry_date`, `log_date`, `user_id`) VALUES
(12, 3, 'in', 50, '2025-07-16', '2025-07-09 02:24:10', 1),
(13, 5, 'in', 25, '2025-07-11', '2025-07-09 02:24:23', 1),
(14, 6, 'in', 50, '2026-07-09', '2025-07-09 04:13:55', 1),
(15, 6, 'in', 5, '2026-07-10', '2025-07-09 04:14:17', 1),
(19, 5, 'out', 5, '2025-07-11', '2025-07-09 09:09:03', 1),
(20, 5, 'out', 5, '2025-07-11', '2025-07-09 09:10:36', 2),
(21, 5, 'in', 5, '2025-07-09', '2025-07-09 09:12:54', 1),
(22, 5, 'out', 5, '2025-07-09', '2025-07-09 09:15:40', 1),
(23, 5, 'out', 3, '2025-07-11', '2025-07-09 09:25:59', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Owner','Staff') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$3xTP.TzbTmYZxRnF7tQfROE5uIUJzrtwwF1w4RlN5lELlhsbsotFK', 'Owner', '2025-07-09 01:28:44'),
(2, 'Aniqah', 'Aniqah', '$2y$10$YDH4.AdejeaNL.4VXj6eGOAvCTGJgw/0isSL4qsTUjaahfEueuVKe', 'Staff', '2025-07-09 02:22:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `audit_logs_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`);

--
-- Constraints for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
