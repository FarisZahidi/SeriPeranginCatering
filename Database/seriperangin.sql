-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 09, 2025 at 03:42 PM
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
  `item_id` int DEFAULT NULL,
  `before_data` json NOT NULL,
  `after_data` json DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `item_id`, `before_data`, `after_data`, `timestamp`, `created_at`) VALUES
(1, 1, 'edit', 12, '{\"unit\": \"pcs\", \"item_id\": 12, \"category\": \"Fish\", \"item_name\": \"Fish\", \"created_at\": \"2025-07-09 23:39:28\", \"image_path\": \"assets/images/inv_686e8d3010e365.91960070.png\"}', '{\"unit\": \"kg\", \"item_id\": 12, \"category\": \"Fish\", \"item_name\": \"Fish\", \"created_at\": \"2025-07-09 23:39:28\", \"image_path\": \"assets/images/inv_686e8d3010e365.91960070.png\"}', '2025-07-09 23:39:31', '2025-07-09 15:39:31'),
(2, 1, 'stock_in', 13, '{\"stock_level\": \"0\"}', '{\"stock_level\": \"10\"}', '2025-07-09 23:40:29', '2025-07-09 15:40:29'),
(3, 1, 'stock_in', 11, '{\"stock_level\": \"0\"}', '{\"stock_level\": \"5\"}', '2025-07-09 23:40:47', '2025-07-09 15:40:47'),
(4, 1, 'stock_in', 12, '{\"stock_level\": \"0\"}', '{\"stock_level\": \"10\"}', '2025-07-09 23:40:56', '2025-07-09 15:40:56'),
(5, 1, 'stock_in', 14, '{\"stock_level\": \"0\"}', '{\"stock_level\": \"20\"}', '2025-07-09 23:41:11', '2025-07-09 15:41:11');

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
(11, 'Chicken', 'Meat', 'kg', '2025-07-09 15:37:21', 'assets/images/inv_686e8cb1be4263.01272956.jpg'),
(12, 'Fish', 'Fish', 'kg', '2025-07-09 15:39:28', 'assets/images/inv_686e8d3010e365.91960070.png'),
(13, 'Carrot', 'Vegetables', 'kg', '2025-07-09 15:39:50', 'assets/images/inv_686e8d4628ddc9.69650433.jpg'),
(14, 'Onion', 'Dry Goods', 'kg', '2025-07-09 15:40:03', 'assets/images/inv_686e8d532d7c14.97318561.jpeg');

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
(36, 13, 'in', 10, '2025-07-16', '2025-07-09 15:40:29', 1),
(37, 11, 'in', 5, '2025-07-12', '2025-07-09 15:40:47', 1),
(38, 12, 'in', 10, '2025-07-11', '2025-07-09 15:40:56', 1),
(39, 14, 'in', 20, '2026-07-09', '2025-07-09 15:41:11', 1);

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
(3, 'Aniqah', 'Aniqah', '$2y$10$/pB99YUSBu9PR75L3Ra7weIGxrxRq2hM1KLroqC03jD0o2Orni3S6', 'Staff', '2025-07-09 15:19:16');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

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
