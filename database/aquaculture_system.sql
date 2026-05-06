-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 02:01 AM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aquaculture_system`
--
CREATE DATABASE IF NOT EXISTS `aquaculture_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `aquaculture_system`;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feed_market_prices`
--

CREATE TABLE `feed_market_prices` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `feed_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feed_market_prices`
--

INSERT INTO `feed_market_prices` (`id`, `farmer_id`, `feed_type`, `price_per_kg`, `updated_at`) VALUES
(1, 2, 'dfggdfgbhgf', '56776575.00', '2026-05-06 01:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `feed_price_history`
--

CREATE TABLE `feed_price_history` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `feed_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_price` decimal(10,2) DEFAULT 0.00,
  `new_price` decimal(10,2) DEFAULT 0.00,
  `changed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feed_price_history`
--

INSERT INTO `feed_price_history` (`id`, `farmer_id`, `feed_type`, `old_price`, `new_price`, `changed_at`) VALUES
(1, 2, 'dfggdfgbhgf', '56776575.00', '56776575.00', '2026-05-06 01:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `feed_records`
--

CREATE TABLE `feed_records` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `feed_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL COMMENT 'Quantity in kg',
  `cost` decimal(10,2) DEFAULT 0.00 COMMENT 'Cost in UGX',
  `feed_date` date NOT NULL,
  `fed_by` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity_kg` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feed_records`
--

INSERT INTO `feed_records` (`id`, `pond_id`, `feed_type`, `quantity`, `cost`, `feed_date`, `fed_by`, `notes`, `created_at`, `quantity_kg`, `total_cost`, `cost_per_kg`) VALUES
(1, 3, 'dfggdfgbhgf', '0.00', '0.00', '2026-05-06', NULL, '', '2026-05-05 23:10:30', '0.07', '3974360.25', '56776575.00'),
(2, 3, 'dfggdfgbhgf', '0.00', '0.00', '2026-05-05', NULL, 'eferfre', '2026-05-05 23:37:34', '0.03', '0.00', '0.01');

-- --------------------------------------------------------

--
-- Table structure for table `fish_stocks`
--

CREATE TABLE `fish_stocks` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `species` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `avg_weight` decimal(6,2) DEFAULT 0.00 COMMENT 'Average weight in kg',
  `stocking_date` date NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_per_fish` decimal(10,2) DEFAULT 0.00,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fish_stocks`
--

INSERT INTO `fish_stocks` (`id`, `pond_id`, `species`, `quantity`, `avg_weight`, `stocking_date`, `source`, `cost_per_fish`, `notes`, `created_at`) VALUES
(1, 1, 'ththh', 676, '565.00', '2026-05-07', 'hgnghnm', '555.00', '', '2026-05-05 21:01:18'),
(8, 1, 'ththh', 444, '444.00', '2026-05-19', 'fffffffffffff', '4555.00', 'rggfhg', '2026-05-05 21:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `harvest_records`
--

CREATE TABLE `harvest_records` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL COMMENT 'Number of fish harvested',
  `avg_weight` decimal(6,2) DEFAULT 0.00 COMMENT 'Average weight in kg',
  `total_weight` decimal(10,2) DEFAULT 0.00 COMMENT 'Total weight in kg',
  `sale_price` decimal(10,2) DEFAULT 0.00 COMMENT 'Price per kg in UGX',
  `total_revenue` decimal(12,2) DEFAULT 0.00 COMMENT 'Total revenue in UGX',
  `buyer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `harvest_date` date NOT NULL,
  `harvested_by` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `harvest_records`
--

INSERT INTO `harvest_records` (`id`, `pond_id`, `quantity`, `avg_weight`, `total_weight`, `sale_price`, `total_revenue`, `buyer_name`, `harvest_date`, `harvested_by`, `notes`, `created_at`) VALUES
(1, 3, 444, '444.00', '0.00', '4000.00', '788544000.00', NULL, '2026-05-12', NULL, NULL, '2026-05-05 23:11:13');

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `vet_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `diagnosis` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `treatment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medication` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` enum('normal','mild','moderate','severe','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `follow_up_date` date DEFAULT NULL,
  `status` enum('open','resolved','monitoring') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`id`, `pond_id`, `vet_id`, `visit_date`, `diagnosis`, `treatment`, `medication`, `severity`, `follow_up_date`, `status`, `notes`, `created_at`, `created_by`) VALUES
(1, 2, 3, '2026-05-05', 'hhfyjhgjhtj', 'hmnghmhgmghmhjmjhmj', '', 'moderate', '2026-05-03', 'monitoring', 'nmhjm jhm hjm jhm jhm jh', '2026-05-05 22:53:25', NULL),
(2, 3, 2, '2026-05-04', 'jhkyujkikj', ',jh,kj,kj,.kj,jk,.k', '', 'moderate', '2026-05-22', 'monitoring', 'k,kj,kj.,jk', '2026-05-05 23:17:33', 2);

-- --------------------------------------------------------

--
-- Table structure for table `mortality_records`
--

CREATE TABLE `mortality_records` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `mortality_date` date NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `estimated_weight` decimal(8,2) DEFAULT 0.00 COMMENT 'Estimated total weight in kg',
  `cause` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `probable_reason` enum('disease','poor_water_quality','predation','oxygen_depletion','unknown','other') COLLATE utf8mb4_unicode_ci DEFAULT 'unknown',
  `action_taken` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loss_value` decimal(12,2) DEFAULT 0.00 COMMENT 'Estimated loss in UGX',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mortality_records`
--

INSERT INTO `mortality_records` (`id`, `pond_id`, `reported_by`, `mortality_date`, `count`, `estimated_weight`, `cause`, `probable_reason`, `action_taken`, `loss_value`, `notes`, `created_at`) VALUES
(1, 3, 2, '2026-05-04', 2, '0.00', 'fdfddd', 'unknown', NULL, '0.00', 'dcdcdcd', '2026-05-05 23:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ponds`
--

CREATE TABLE `ponds` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` decimal(10,2) DEFAULT NULL COMMENT 'Size in square metres',
  `depth` decimal(5,2) DEFAULT NULL COMMENT 'Depth in metres',
  `pond_type` enum('earthen','lined','concrete','cage') COLLATE utf8mb4_unicode_ci DEFAULT 'earthen',
  `status` enum('active','inactive','under_maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `farmer_id` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ponds`
--

INSERT INTO `ponds` (`id`, `name`, `location`, `size`, `depth`, `pond_type`, `status`, `farmer_id`, `notes`, `created_at`) VALUES
(1, 'site', 'west', '566.00', '33.00', 'earthen', 'active', NULL, '', '2026-05-05 20:58:41'),
(2, 'kisubi', 'west', '50.00', '2.50', 'cage', 'active', NULL, 'clia', '2026-05-05 22:44:09'),
(3, 'siteiiu', 'west', '8888.00', '99.00', 'earthen', 'active', 2, '', '2026-05-05 23:05:05');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 17:13:24'),
(2, 2, 'LOGOUT', '::1', '::1', '2026-05-05 17:15:14'),
(3, 3, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 17:15:52'),
(4, 3, 'LOGOUT', '::1', '::1', '2026-05-05 17:38:24'),
(5, 0, 'LOGIN_FAILED', 'admin - ::1', '::1', '2026-05-05 17:39:05'),
(6, 1, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 17:39:48'),
(7, 1, 'LOGOUT', '::1', '::1', '2026-05-05 22:28:57'),
(8, 1, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 22:34:05'),
(9, 1, 'LOGOUT', '::1', '::1', '2026-05-05 22:35:05'),
(10, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 22:35:13'),
(11, 2, 'LOGOUT', '::1', '::1', '2026-05-05 22:43:24'),
(12, 1, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 22:43:29'),
(13, 1, 'LOGOUT', '::1', '::1', '2026-05-05 22:44:18'),
(14, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 22:44:22'),
(15, 2, 'LOGOUT', '::1', '::1', '2026-05-05 22:45:19'),
(16, 3, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 22:45:24'),
(17, 3, 'LOGOUT', '::1', '::1', '2026-05-05 22:54:30'),
(18, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 22:54:35'),
(19, 2, 'RECORD_HARVEST', '788544000', '::1', '2026-05-05 23:11:15'),
(20, 2, 'LOGOUT', '::1', '::1', '2026-05-05 23:18:39'),
(21, 1, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:18:45'),
(22, 1, 'LOGOUT', '::1', '::1', '2026-05-05 23:23:33'),
(23, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:23:38'),
(24, 2, 'LOGOUT', '::1', '::1', '2026-05-05 23:33:03'),
(25, 1, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:33:07'),
(26, 1, 'LOGOUT', '::1', '::1', '2026-05-05 23:34:17'),
(27, 3, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:34:21'),
(28, 3, 'LOGOUT', '::1', '::1', '2026-05-05 23:34:45'),
(29, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:34:49'),
(30, 2, 'LOGOUT', '::1', '::1', '2026-05-05 23:38:53'),
(31, 1, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:39:20'),
(32, 1, 'LOGOUT', '::1', '::1', '2026-05-05 23:46:20'),
(33, 2, 'LOGIN_SUCCESS', '::1', '::1', '2026-05-05 23:46:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','farmer','vet') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `phone`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@mugwefishpond.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+256741733671', 1, NULL, '2026-05-05 17:05:08'),
(2, 'son', 'me505@gmail.com', '$2y$10$eJS8KQM5JjXAQ4T.v7TmLu482NhCVdgTCrvVAaZkBFg.B44wWqxrG', 'farmer', NULL, 1, NULL, '2026-05-05 17:10:49'),
(3, 'son2', 'f05@gmail.com', '$2y$10$U/gy0bzifT92iLmqDZysE.V3I4bFzGZfmG0lEUiVfgAaghfBGFiV2', 'vet', NULL, 1, NULL, '2026-05-05 17:15:41');

-- --------------------------------------------------------

--
-- Table structure for table `vet_recommendations`
--

CREATE TABLE `vet_recommendations` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `vet_id` int(11) DEFAULT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `recommendation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `category` enum('feeding','water_quality','health','stocking','general') COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `status` enum('pending','acknowledged','completed','dismissed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `water_quality`
--

CREATE TABLE `water_quality` (
  `id` int(11) NOT NULL,
  `pond_id` int(11) NOT NULL,
  `water_temp` decimal(4,2) DEFAULT NULL COMMENT 'Temperature in Celsius',
  `ph_level` decimal(4,2) DEFAULT NULL COMMENT 'pH 0-14',
  `dissolved_oxygen` decimal(5,2) DEFAULT NULL COMMENT 'DO in mg/L',
  `ammonia` decimal(5,3) DEFAULT NULL,
  `turbidity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `water_quality`
--

INSERT INTO `water_quality` (`id`, `pond_id`, `water_temp`, `ph_level`, `dissolved_oxygen`, `ammonia`, `turbidity`, `recorded_by`, `notes`, `recorded_at`) VALUES
(1, 3, '0.10', '0.10', '0.10', '0.001', 'clear', 2, 'rfgrgv', '2026-05-05 23:31:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feed_market_prices`
--
ALTER TABLE `feed_market_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_farmer_feed` (`farmer_id`,`feed_type`);

--
-- Indexes for table `feed_price_history`
--
ALTER TABLE `feed_price_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feed_records`
--
ALTER TABLE `feed_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fed_by` (`fed_by`),
  ADD KEY `idx_pond` (`pond_id`),
  ADD KEY `idx_date` (`feed_date`);

--
-- Indexes for table `fish_stocks`
--
ALTER TABLE `fish_stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pond` (`pond_id`);

--
-- Indexes for table `harvest_records`
--
ALTER TABLE `harvest_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `harvested_by` (`harvested_by`),
  ADD KEY `idx_pond` (`pond_id`),
  ADD KEY `idx_date` (`harvest_date`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vet_id` (`vet_id`),
  ADD KEY `idx_pond` (`pond_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `mortality_records`
--
ALTER TABLE `mortality_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_pond` (`pond_id`),
  ADD KEY `idx_date` (`mortality_date`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `ponds`
--
ALTER TABLE `ponds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farmer` (`farmer_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `vet_recommendations`
--
ALTER TABLE `vet_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vet_id` (`vet_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `idx_pond` (`pond_id`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `water_quality`
--
ALTER TABLE `water_quality`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_pond` (`pond_id`),
  ADD KEY `idx_date` (`recorded_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feed_market_prices`
--
ALTER TABLE `feed_market_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feed_price_history`
--
ALTER TABLE `feed_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feed_records`
--
ALTER TABLE `feed_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fish_stocks`
--
ALTER TABLE `fish_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `harvest_records`
--
ALTER TABLE `harvest_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mortality_records`
--
ALTER TABLE `mortality_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ponds`
--
ALTER TABLE `ponds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vet_recommendations`
--
ALTER TABLE `vet_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `water_quality`
--
ALTER TABLE `water_quality`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feed_records`
--
ALTER TABLE `feed_records`
  ADD CONSTRAINT `feed_records_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feed_records_ibfk_2` FOREIGN KEY (`fed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fish_stocks`
--
ALTER TABLE `fish_stocks`
  ADD CONSTRAINT `fish_stocks_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `harvest_records`
--
ALTER TABLE `harvest_records`
  ADD CONSTRAINT `harvest_records_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `harvest_records_ibfk_2` FOREIGN KEY (`harvested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`vet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mortality_records`
--
ALTER TABLE `mortality_records`
  ADD CONSTRAINT `mortality_records_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mortality_records_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ponds`
--
ALTER TABLE `ponds`
  ADD CONSTRAINT `ponds_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vet_recommendations`
--
ALTER TABLE `vet_recommendations`
  ADD CONSTRAINT `vet_recommendations_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vet_recommendations_ibfk_2` FOREIGN KEY (`vet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vet_recommendations_ibfk_3` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `water_quality`
--
ALTER TABLE `water_quality`
  ADD CONSTRAINT `water_quality_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `water_quality_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `query` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `col_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `col_type` varchar(64) COLLATE utf8_bin NOT NULL,
  `col_length` text COLLATE utf8_bin DEFAULT NULL,
  `col_collation` varchar(64) COLLATE utf8_bin NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) COLLATE utf8_bin DEFAULT '',
  `col_default` text COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `column_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `transformation` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `transformation_options` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `input_transformation` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `settings_data` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `export_type` varchar(10) COLLATE utf8_bin NOT NULL,
  `template_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `template_data` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `tables` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `db` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `item_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `item_type` varchar(64) COLLATE utf8_bin NOT NULL,
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `tables` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"aquaculture_system\",\"table\":\"feed_records\"},{\"db\":\"aquaculture_system\",\"table\":\"users\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `master_table` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `master_field` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `foreign_db` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `foreign_table` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `foreign_field` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `search_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `search_data` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `display_field` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `prefs` text COLLATE utf8_bin NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `table_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text COLLATE utf8_bin NOT NULL,
  `schema_sql` text COLLATE utf8_bin DEFAULT NULL,
  `data_sql` longtext COLLATE utf8_bin DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') COLLATE utf8_bin DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-05-06 00:00:43', '{\"Console\\/Mode\":\"show\",\"Console\\/Height\":101.91889,\"NavigationWidth\":356}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) COLLATE utf8_bin NOT NULL,
  `tab` varchar(64) COLLATE utf8_bin NOT NULL,
  `allowed` enum('Y','N') COLLATE utf8_bin NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) COLLATE utf8_bin NOT NULL,
  `usergroup` varchar(64) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `schema.sql`
--
CREATE DATABASE IF NOT EXISTS `schema.sql` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `schema.sql`;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
