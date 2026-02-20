-- =====================================================
-- NAIGO - Online Restaurant Reservation System
-- Database Schema
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

CREATE DATABASE IF NOT EXISTS `naig_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `naig_db`;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE `users` (
  `id` varchar(20) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `middleInitial` varchar(5) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `sex` enum('male','female') NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) DEFAULT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zipCode` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('consumer','admin','superadmin') NOT NULL DEFAULT 'consumer',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `secure_question` text DEFAULT NULL,
  `secure_answer` varchar(255) DEFAULT NULL,
  `secure_question2` text DEFAULT NULL,
  `secure_answer2` varchar(255) DEFAULT NULL,
  `secure_question3` text DEFAULT NULL,
  `secure_answer3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: password_reset_otp
-- =====================================================
CREATE TABLE `password_reset_otp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `resend_count` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_resend_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_reset_otp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: login_logs
-- =====================================================
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `action` enum('login','logout') NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: restaurants
-- =====================================================
CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cuisine_type` varchar(100) DEFAULT 'General',
  `capacity` int(11) DEFAULT 50,
  `opening_time` time DEFAULT '09:00:00',
  `closing_time` time DEFAULT '22:00:00',
  `price_range` enum('$','$$','$$$','$$$$') DEFAULT '$$',
  `rating` decimal(2,1) DEFAULT 0.0,
  `image_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: restaurant_tables
-- =====================================================
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `table_number` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 2,
  `location` enum('indoor','outdoor','private','bar') NOT NULL DEFAULT 'indoor',
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `restaurant_tables_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: reservations
-- =====================================================
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `party_size` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `table_id` (`table_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: user_block_requests
-- =====================================================
CREATE TABLE `user_block_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` varchar(20) NOT NULL,
  `target_id` varchar(20) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requester_id` (`requester_id`),
  KEY `target_id` (`target_id`),
  CONSTRAINT `user_block_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_block_requests_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: approvals
-- =====================================================
CREATE TABLE `approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requested_by` varchar(20) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(20) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE: admin_creation_requests
-- =====================================================
CREATE TABLE `admin_creation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requested_by` varchar(20) NOT NULL,
  `requested_role` enum('admin','superadmin') NOT NULL DEFAULT 'admin',
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `admin_creation_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEED DATA: Default Users
-- =====================================================
INSERT INTO `users` (`id`, `firstName`, `lastName`, `middleInitial`, `extension`, `sex`, `birthdate`, `age`, `purok`, `barangay`, `city`, `province`, `zipCode`, `country`, `username`, `email`, `password`, `role`, `is_blocked`, `secure_question`, `secure_answer`, `secure_question2`, `secure_answer2`, `secure_question3`, `secure_answer3`) VALUES
('0001-0001', 'Super', 'Admin', NULL, NULL, 'male', '1990-01-01', 35, 'Purok 1', 'Barangay 1', 'Davao City', 'Davao del Sur', '8000', 'Philippines', 'su', 'superadmin@naigo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 0, 'What is the name of your pet?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'What is your mother''s maiden name?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'What is your father''s middle name?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('0001-0002', 'Admin', 'User', NULL, NULL, 'female', '1992-05-15', 33, 'Purok 2', 'Barangay 2', 'Davao City', 'Davao del Sur', '8000', 'Philippines', 'admin1', 'admin@naigo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, 'What is the name of your pet?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'What is your mother''s maiden name?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'What is your father''s middle name?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('0001-0003', 'Mae', 'Santos', 'L', NULL, 'female', '1998-08-20', 27, 'Purok 3', 'Barangay 3', 'Davao City', 'Davao del Sur', '8000', 'Philippines', 'mae12', 'mae@naigo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'consumer', 0, 'What is the name of your pet?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'What is your mother''s maiden name?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'What is your father''s middle name?', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- =====================================================
-- SEED DATA: Restaurants
-- =====================================================
INSERT INTO `restaurants` (`name`, `description`, `address`, `phone`, `cuisine_type`, `capacity`, `opening_time`, `closing_time`, `price_range`, `rating`, `image_path`, `is_active`) VALUES
('The Aristocrat', 'A legendary Filipino restaurant known for its barbecue chicken and Java rice since 1936.', '432 San Andres St, Malate, Manila', '09171234567', 'Filipino', 80, '06:00:00', '23:00:00', '$$', 4.5, NULL, 1),
('Locavore', 'Modern Filipino cuisine celebrating local ingredients with a creative twist.', 'Ground Floor, Forbeswood Heights, BGC, Taguig', '09181234567', 'Modern Filipino', 45, '11:00:00', '22:00:00', '$$$', 4.7, NULL, 1),
('Manam Comfort Filipino', 'Elevated comfort food that reimagines beloved Filipino classics.', 'SM Mega Fashion Hall, Mandaluyong', '09191234567', 'Filipino Comfort', 60, '10:00:00', '22:00:00', '$$', 4.6, NULL, 1),
('Sentro 1771', 'Contemporary Filipino dining in a warm, sophisticated setting.', '1771 M. Adriatico St, Remedios Circle, Malate, Manila', '09201234567', 'Contemporary Filipino', 50, '11:00:00', '23:00:00', '$$$', 4.4, NULL, 1),
('Mesa Filipino Moderne', 'Traditional Filipino recipes given a modern presentation.', 'SM Aura Premier, BGC, Taguig', '09211234567', 'Filipino Moderne', 70, '10:00:00', '22:00:00', '$$', 4.3, NULL, 1);

-- =====================================================
-- SEED DATA: Restaurant Tables
-- =====================================================
-- The Aristocrat (restaurant_id = 1)
INSERT INTO `restaurant_tables` (`restaurant_id`, `table_number`, `capacity`, `location`, `is_available`) VALUES
(1, 'A1', 2, 'indoor', 1), (1, 'A2', 2, 'indoor', 1), (1, 'A3', 4, 'indoor', 1),
(1, 'A4', 4, 'indoor', 1), (1, 'A5', 6, 'indoor', 1), (1, 'A6', 8, 'indoor', 1),
(1, 'P1', 4, 'outdoor', 1), (1, 'P2', 4, 'outdoor', 1),
(1, 'VIP1', 10, 'private', 1), (1, 'VIP2', 12, 'private', 1);

-- Locavore (restaurant_id = 2)
INSERT INTO `restaurant_tables` (`restaurant_id`, `table_number`, `capacity`, `location`, `is_available`) VALUES
(2, 'L1', 2, 'indoor', 1), (2, 'L2', 2, 'indoor', 1), (2, 'L3', 4, 'indoor', 1),
(2, 'L4', 4, 'indoor', 1), (2, 'L5', 6, 'indoor', 1),
(2, 'B1', 2, 'bar', 1), (2, 'B2', 2, 'bar', 1),
(2, 'VIP1', 8, 'private', 1);

-- Manam (restaurant_id = 3)
INSERT INTO `restaurant_tables` (`restaurant_id`, `table_number`, `capacity`, `location`, `is_available`) VALUES
(3, 'M1', 2, 'indoor', 1), (3, 'M2', 2, 'indoor', 1), (3, 'M3', 4, 'indoor', 1),
(3, 'M4', 4, 'indoor', 1), (3, 'M5', 6, 'indoor', 1), (3, 'M6', 8, 'indoor', 1),
(3, 'O1', 4, 'outdoor', 1), (3, 'O2', 4, 'outdoor', 1),
(3, 'VIP1', 10, 'private', 1);

-- Sentro 1771 (restaurant_id = 4)
INSERT INTO `restaurant_tables` (`restaurant_id`, `table_number`, `capacity`, `location`, `is_available`) VALUES
(4, 'S1', 2, 'indoor', 1), (4, 'S2', 2, 'indoor', 1), (4, 'S3', 4, 'indoor', 1),
(4, 'S4', 6, 'indoor', 1), (4, 'S5', 8, 'indoor', 1),
(4, 'VIP1', 10, 'private', 1), (4, 'VIP2', 12, 'private', 1);

-- Mesa (restaurant_id = 5)
INSERT INTO `restaurant_tables` (`restaurant_id`, `table_number`, `capacity`, `location`, `is_available`) VALUES
(5, 'T1', 2, 'indoor', 1), (5, 'T2', 2, 'indoor', 1), (5, 'T3', 4, 'indoor', 1),
(5, 'T4', 4, 'indoor', 1), (5, 'T5', 6, 'indoor', 1), (5, 'T6', 8, 'indoor', 1),
(5, 'O1', 4, 'outdoor', 1), (5, 'O2', 6, 'outdoor', 1),
(5, 'VIP1', 10, 'private', 1), (5, 'VIP2', 14, 'private', 1);

COMMIT;
