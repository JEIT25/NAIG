-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 04:44 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `naig_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_creation_requests`
--

CREATE TABLE `admin_creation_requests` (
  `id` int(11) NOT NULL,
  `requested_by` varchar(11) NOT NULL COMMENT 'User ID of superadmin requesting',
  `target_username` varchar(50) NOT NULL,
  `target_email` varchar(100) NOT NULL,
  `target_role` enum('admin','superadmin') NOT NULL,
  `target_firstName` varchar(50) NOT NULL,
  `target_lastName` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` int(11) NOT NULL,
  `requested_by` varchar(20) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(20) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approvals`
--

INSERT INTO `approvals` (`id`, `requested_by`, `action_type`, `target_type`, `target_id`, `reason`, `status`, `reviewed_by`, `review_notes`, `created_at`, `updated_at`) VALUES
(4, '1234-5678', 'register_consumer', 'user', '1234-5678', 'New consumer registration', 'approved', '0001-0004', '', '2026-05-08 00:55:14', '2026-05-08 00:58:57');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `action` enum('login','logout') NOT NULL,
  `log_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `action`, `log_time`) VALUES
(1, '0001-0002', 'login', '2026-02-20 08:53:01'),
(2, '0001-0002', 'logout', '2026-02-20 08:54:05'),
(44, '0001-0002', 'login', '2026-02-20 10:37:01'),
(45, '0001-0002', 'logout', '2026-02-20 10:39:09'),
(56, '0001-0002', 'login', '2026-02-20 11:16:31'),
(57, '0001-0002', 'logout', '2026-02-20 11:17:08'),
(66, '0001-0002', 'login', '2026-02-20 11:34:41'),
(67, '0001-0002', 'logout', '2026-02-20 11:35:40'),
(72, '0001-0002', 'login', '2026-02-20 11:39:26'),
(73, '0001-0002', 'logout', '2026-02-20 11:39:33'),
(81, '0001-0002', 'login', '2026-02-20 13:37:18'),
(96, '0001-0004', 'login', '2026-02-25 18:12:39'),
(98, '0001-0004', 'logout', '2026-02-25 18:14:19'),
(99, '0001-0004', 'login', '2026-02-25 18:14:23'),
(100, '0001-0004', 'logout', '2026-02-25 18:17:31'),
(101, '0001-0004', 'login', '2026-02-25 18:17:35'),
(102, '0001-0004', 'logout', '2026-02-25 18:17:37'),
(105, '0001-0004', 'login', '2026-02-25 18:19:05'),
(106, '0001-0004', 'logout', '2026-02-25 18:24:24'),
(109, '0001-0004', 'login', '2026-02-25 18:25:11'),
(110, '0001-0004', 'logout', '2026-02-25 18:25:36'),
(113, '0001-0004', 'login', '2026-02-25 18:35:24'),
(115, '0001-0004', 'logout', '2026-02-25 18:43:55'),
(116, '0001-0004', 'login', '2026-02-25 18:43:58'),
(117, '0001-0004', 'logout', '2026-02-25 18:44:00'),
(124, '0001-0004', 'login', '2026-02-25 18:50:26'),
(126, '0001-0004', 'logout', '2026-02-25 18:55:07'),
(133, '0001-0004', 'login', '2026-02-25 18:57:29'),
(134, '0001-0004', 'logout', '2026-02-25 19:00:53'),
(137, '0001-0004', 'login', '2026-02-25 19:08:09'),
(138, '0001-0004', 'logout', '2026-02-25 19:08:13'),
(139, '0001-0004', 'login', '2026-02-25 19:08:29'),
(140, '0001-0004', 'logout', '2026-02-25 19:08:32'),
(150, '0001-0004', 'login', '2026-02-25 20:03:09'),
(152, '0001-0004', 'login', '2026-02-25 20:08:32'),
(153, '0001-0004', 'logout', '2026-02-25 20:15:13'),
(154, '0001-0004', 'login', '2026-02-25 20:15:33'),
(155, '0001-0004', 'logout', '2026-02-25 20:15:42'),
(158, '0001-0004', 'login', '2026-02-25 20:15:57'),
(159, '0001-0004', 'logout', '2026-02-25 20:16:17'),
(162, '0001-0004', 'login', '2026-02-25 20:17:02'),
(163, '0001-0004', 'logout', '2026-02-26 00:19:09'),
(169, '0001-0004', 'logout', '2026-02-26 00:37:19'),
(174, '0001-0004', 'login', '2026-02-26 00:39:06'),
(175, '0001-0004', 'logout', '2026-02-26 00:39:41'),
(178, '0001-0004', 'login', '2026-02-26 00:56:29'),
(179, '0001-0004', 'logout', '2026-02-26 00:57:05'),
(180, '0001-0004', 'login', '2026-02-26 00:57:09'),
(181, '0001-0004', 'logout', '2026-02-26 00:57:14'),
(186, '0001-0002', 'login', '2026-05-08 08:46:39'),
(187, '0001-0002', 'logout', '2026-05-08 08:50:49'),
(188, '0001-0002', 'login', '2026-05-08 08:58:30'),
(189, '0001-0002', 'logout', '2026-05-08 08:58:46'),
(190, '0001-0004', 'login', '2026-05-08 08:58:50'),
(191, '0001-0004', 'logout', '2026-05-08 08:58:59'),
(192, '1234-5678', 'login', '2026-05-08 08:59:57'),
(193, '1234-5678', 'logout', '2026-05-08 09:00:12'),
(194, '0001-0002', 'login', '2026-05-08 09:00:31'),
(195, '0001-0002', 'logout', '2026-05-08 09:02:51'),
(196, '0001-0002', 'login', '2026-05-08 09:03:10'),
(197, '0001-0002', 'logout', '2026-05-08 09:03:12'),
(198, '0001-0002', 'login', '2026-05-08 09:13:36'),
(199, '0001-0002', 'logout', '2026-05-08 09:14:30'),
(200, '0001-0004', 'login', '2026-05-08 09:14:38'),
(201, '0001-0004', 'logout', '2026-05-08 09:14:55'),
(202, '0001-0002', 'login', '2026-05-08 09:15:05'),
(203, '0001-0002', 'logout', '2026-05-08 09:28:59'),
(204, '0001-0002', 'login', '2026-05-08 09:29:14'),
(205, '0001-0002', 'logout', '2026-05-08 09:30:08'),
(206, '1234-5678', 'login', '2026-05-08 09:30:13'),
(207, '1234-5678', 'logout', '2026-05-08 09:30:22'),
(208, '0001-0004', 'login', '2026-05-08 09:30:29'),
(209, '0001-0004', 'logout', '2026-05-08 09:31:32'),
(210, '0001-0002', 'login', '2026-05-08 09:31:37'),
(211, '0001-0002', 'logout', '2026-05-08 09:34:34'),
(212, '0001-0004', 'login', '2026-05-08 09:34:40'),
(213, '0001-0002', 'logout', '2026-05-08 09:35:58'),
(214, '0001-0004', 'login', '2026-05-08 09:38:19'),
(215, '0001-0004', 'logout', '2026-05-08 09:39:17'),
(216, '0001-0004', 'login', '2026-05-08 09:41:30'),
(217, '0001-0004', 'logout', '2026-05-08 09:48:30'),
(218, '1234-5678', 'login', '2026-05-08 09:53:20'),
(219, '1234-5678', 'logout', '2026-05-08 09:53:23'),
(220, '0001-0004', 'login', '2026-05-08 09:53:30'),
(221, '0001-0004', 'logout', '2026-05-08 09:53:43'),
(222, '0001-0002', 'login', '2026-05-08 10:41:12'),
(223, '0001-0002', 'logout', '2026-05-08 10:41:14'),
(224, '0001-0004', 'login', '2026-05-08 10:42:41'),
(225, '0001-0004', 'logout', '2026-05-08 10:48:16'),
(226, '0001-0002', 'login', '2026-05-08 10:56:10'),
(227, '0001-0002', 'logout', '2026-05-08 10:56:18'),
(228, '0001-0004', 'login', '2026-05-08 10:56:22'),
(229, '0001-0004', 'logout', '2026-05-08 10:57:31'),
(230, '0001-0002', 'login', '2026-05-08 10:59:59'),
(231, '0001-0002', 'logout', '2026-05-08 11:00:01'),
(232, '0001-0004', 'login', '2026-05-08 11:00:07'),
(233, '0001-0004', 'logout', '2026-05-08 11:03:32'),
(234, '0001-0004', 'login', '2026-05-08 11:04:48'),
(235, '0001-0004', 'login', '2026-05-08 11:25:59'),
(236, '0001-0004', 'logout', '2026-05-08 11:50:47'),
(239, '0001-0004', 'login', '2026-05-08 11:51:05'),
(240, '0001-0004', 'logout', '2026-05-08 12:06:43'),
(241, '0001-0004', 'login', '2026-05-08 12:06:48'),
(242, '0001-0004', 'logout', '2026-05-08 12:07:02'),
(243, '1234-5678', 'login', '2026-05-08 12:07:05'),
(244, '1234-5678', 'logout', '2026-05-08 12:07:08'),
(245, '0001-0002', 'login', '2026-05-08 12:07:13'),
(246, '0001-0002', 'logout', '2026-05-08 12:07:28'),
(247, '0001-0004', 'login', '2026-05-08 12:07:34'),
(248, '0001-0004', 'logout', '2026-05-08 12:07:36'),
(249, '0001-0004', 'login', '2026-05-08 12:07:53'),
(250, '0001-0004', 'logout', '2026-05-08 12:08:33'),
(251, '0001-0002', 'login', '2026-05-08 12:08:39'),
(252, '0001-0002', 'logout', '2026-05-08 13:53:46'),
(255, '0001-0002', 'login', '2026-05-08 13:54:00'),
(256, '0001-0002', 'logout', '2026-05-08 14:01:35'),
(257, '0001-0004', 'login', '2026-05-08 14:03:16'),
(258, '0001-0004', 'logout', '2026-05-08 14:03:22'),
(261, '0001-0002', 'login', '2026-05-08 14:07:03'),
(262, '0001-0002', 'logout', '2026-05-08 14:22:02'),
(263, '1234-5678', 'login', '2026-05-08 14:22:13'),
(264, '1234-5678', 'logout', '2026-05-08 14:22:19'),
(265, '0001-0004', 'login', '2026-05-08 14:22:25'),
(266, '0001-0004', 'logout', '2026-05-08 14:22:30'),
(267, '1234-5678', 'login', '2026-05-08 14:27:18'),
(268, '0001-0002', 'login', '2026-05-17 10:19:54'),
(269, '0001-0002', 'logout', '2026-05-17 10:21:01'),
(272, '0001-0002', 'login', '2026-05-17 10:43:27');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `restaurant_id`, `name`, `description`, `price`, `image_path`, `is_available`, `created_at`) VALUES
(1, 1, '1-pc Chickenjoy', 'Crispy fried chicken served with rice and gravy.', 89.00, NULL, 1, '2026-02-20 00:52:28'),
(2, 1, 'Jolly Spaghetti', 'Sweet-style spaghetti with hotdog slices, ground meat, and cheese.', 65.00, NULL, 1, '2026-02-20 00:52:28'),
(3, 1, 'Yumburger', 'Classic Jollibee hamburger with special dressing.', 45.00, NULL, 1, '2026-02-20 00:52:28'),
(4, 1, 'Peach Mango Pie', 'Crispy flaky pie filled with peaches and mangoes.', 46.00, NULL, 1, '2026-02-20 00:52:28'),
(5, 1, 'Palabok Fiesta', 'Rice noodles in shrimp sauce topped with chicharon and egg.', 138.00, NULL, 1, '2026-02-20 00:52:28'),
(6, 2, 'Siomai Chao Fan', 'Savory fried rice topped with steamed pork siomai.', 95.00, NULL, 1, '2026-02-20 00:52:28'),
(7, 2, 'Chinese-Style Fried Chicken', 'Crispy fried chicken with Chinese five-spice flavor.', 99.00, NULL, 1, '2026-02-20 00:52:28'),
(8, 2, 'Sweet & Sour Pork Lauriat', 'Crispy pork in tangy sweet-and-sour sauce with rice and drink.', 215.00, NULL, 1, '2026-02-20 00:52:28'),
(9, 2, 'Halo-Halo SuperSangkap', 'Crushed ice with sweet beans, fruits, leche flan, and ube ice cream.', 175.00, NULL, 1, '2026-02-20 00:52:28'),
(10, 2, 'Beef Wonton Mami', 'Hot noodle soup with beef slices and wontons.', 115.00, NULL, 1, '2026-02-20 00:52:28'),
(11, 3, 'Chicken Inasal Paa', 'Grilled chicken leg marinated in local spices, served with unlimited rice.', 149.00, NULL, 1, '2026-02-20 00:52:28'),
(12, 3, 'Chicken Inasal Pecho', 'Grilled chicken breast, juicy and flavorful with unlimited rice.', 159.00, NULL, 1, '2026-02-20 00:52:28'),
(13, 3, 'Pork BBQ (2 pcs)', 'Two sticks of smoky sweet grilled pork skewers with rice.', 112.00, NULL, 1, '2026-02-20 00:52:28'),
(14, 3, 'Extra Creamy Halo-Halo', 'Refreshing shaved ice dessert with sweet toppings and creamy milk.', 76.00, NULL, 1, '2026-02-20 00:52:28'),
(15, 3, 'Palabok', 'Filipino-style rice noodles in rich savory sauce with toppings.', 99.00, NULL, 1, '2026-02-20 00:52:28'),
(16, 4, 'Hawaiian Overload Pizza', 'Loaded pizza with ham, pineapple, and extra cheese.', 162.00, NULL, 1, '2026-02-20 00:52:28'),
(17, 4, 'Lasagna Supreme', 'Layers of pasta, rich meat sauce, and melted cheese.', 99.00, NULL, 1, '2026-02-20 00:52:28'),
(18, 4, 'All-In Overload Pizza', 'Fully loaded pizza with pepperoni, ham, beef, and veggies.', 174.00, NULL, 1, '2026-02-20 00:52:28'),
(19, 4, 'Winner Wings (4 pcs)', 'Crispy fried chicken wings in your choice of flavor.', 212.00, NULL, 1, '2026-02-20 00:52:28'),
(20, 4, 'Ham & Cheese Pizzawrap', 'Toasted wrap filled with ham, cheese, and pizza sauce.', 66.00, NULL, 1, '2026-02-20 00:52:28'),
(21, 5, 'Classic Mamon', 'Soft and fluffy Filipino sponge cake, lightly sweetened.', 35.00, NULL, 1, '2026-02-20 00:52:28'),
(22, 5, 'Pork Adobo Meal', 'Tender pork braised in soy sauce and vinegar with rice.', 120.00, NULL, 1, '2026-02-20 00:52:28'),
(23, 5, 'Pancit Bihon Bilao', 'Stir-fried thin rice noodles with vegetables and meat. Serves 5-6.', 350.00, NULL, 1, '2026-02-20 00:52:28'),
(24, 5, 'Mocha Roll', 'Moist chocolate sponge cake rolled with mocha cream filling.', 280.00, NULL, 1, '2026-02-20 00:52:28'),
(25, 5, 'Pinoy Spaghetti', 'Sweet Filipino-style spaghetti with hotdogs and cheese.', 85.00, NULL, 1, '2026-02-20 00:52:28'),
(26, 6, 'Chicken Burger', 'Crispy chicken with lettuce and mayo', 89.00, NULL, 1, '2026-02-20 00:52:28'),
(27, 6, 'Beef Burger', 'Classic beef patty with cheese', 99.00, NULL, 1, '2026-02-20 00:52:28'),
(28, 6, 'Pancit Canton', 'Stir-fried noodles with vegetables', 65.00, NULL, 1, '2026-02-20 00:52:28'),
(29, 6, 'Halo-Halo', 'Mixed shaved ice dessert', 75.00, NULL, 1, '2026-02-20 00:52:28'),
(30, 7, 'Fish Ball (10 pcs)', 'Street-style fish balls with sauce', 35.00, NULL, 1, '2026-02-20 00:52:28'),
(31, 7, 'Isaw (5 sticks)', 'Grilled chicken intestines', 50.00, NULL, 1, '2026-02-20 00:52:28'),
(32, 7, 'Turon (3 pcs)', 'Fried banana spring rolls', 45.00, NULL, 1, '2026-02-20 00:52:28');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otp`
--

CREATE TABLE `password_reset_otp` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resend_count` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_resend_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_otp`
--

INSERT INTO `password_reset_otp` (`id`, `user_id`, `otp_code`, `expires_at`, `used`, `created_at`, `resend_count`, `ip_address`, `last_resend_at`) VALUES
(54, '1234-5678', '035128', '2026-05-08 05:05:44', 1, '2026-05-08 02:50:44', 1, '::1', NULL),
(55, '1234-5678', '966140', '2026-05-08 05:09:30', 1, '2026-05-08 02:54:30', 1, '::1', NULL),
(58, '1234-5678', '509108', '2026-05-08 05:13:52', 1, '2026-05-08 02:58:52', 1, '::1', NULL),
(59, '1234-5678', '493770', '2026-05-08 05:18:42', 1, '2026-05-08 03:03:42', 1, '::1', NULL),
(62, '0001-0002', '937649', '2026-05-13 01:39:44', 1, '2026-05-12 23:24:44', 1, '::1', NULL),
(65, '1234-5678', '644024', '2026-05-13 03:21:37', 1, '2026-05-13 01:06:37', 1, '::1', NULL),
(67, '1234-5678', '421881', '2026-05-13 03:23:19', 0, '2026-05-13 01:08:19', 2, '::1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `type` enum('cash_on_delivery','gcash','card','bank') NOT NULL DEFAULT 'cash_on_delivery',
  `label` varchar(50) NOT NULL COMMENT 'e.g. GCash 09xx',
  `details` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `party_size` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cuisine_type` varchar(100) DEFAULT 'General',
  `capacity` int(11) DEFAULT 50,
  `opening_time` time DEFAULT '09:00:00',
  `closing_time` time DEFAULT '22:00:00',
  `price_range` enum('$','c:xampphtdocsNAIG','c:xampphtdocsNAIG$','c:xampphtdocsNAIGc:xampphtdocsNAIG') DEFAULT 'c:xampphtdocsNAIG',
  `rating` decimal(2,1) DEFAULT 0.0,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `description`, `address`, `phone`, `cuisine_type`, `capacity`, `opening_time`, `closing_time`, `price_range`, `rating`, `image_path`, `is_active`, `created_at`) VALUES
(1, 'Jollibee', 'Home of the famous Chickenjoy — crispy, juicy, and loved by every Filipino.', 'P. Burgos St, Cabadbaran City, Agusan Del Norte', NULL, 'General', 50, '09:00:00', '22:00:00', 'c:xampphtdocsNAIG', 0.0, NULL, 1, '2026-01-01 08:00:00'),
(2, 'Chowking', 'Chinese-Filipino fast food — chao fan, siomai, lauriat meals, and halo-halo.', 'National Highway, Cabadbaran City, Agusan Del Norte', NULL, 'General', 50, '09:00:00', '22:00:00', '', 0.0, '', 0, '2026-01-01 08:00:00'),
(3, 'Mang Inasal', 'The home of chicken inasal with unlimited rice. Grilled to perfection.', 'J.C. Aquino Ave, Butuan City, Agusan Del Norte', NULL, 'General', 50, '09:00:00', '22:00:00', 'c:xampphtdocsNAIG', 0.0, NULL, 1, '2026-01-01 08:00:00'),
(4, 'Greenwich', 'Pizza, pasta, and more — the go-to place for barkada hangouts.', 'Montilla Blvd, Butuan City, Agusan Del Norte', NULL, 'General', 50, '09:00:00', '22:00:00', 'c:xampphtdocsNAIG', 0.0, NULL, 1, '2026-01-01 08:00:00'),
(5, 'Goldilocks', 'Filipino bakeshop and restaurant known for cakes, mamon, and classic Pinoy meals.', 'Langihan Rd, Butuan City, Agusan Del Norte', NULL, 'General', 50, '09:00:00', '22:00:00', 'c:xampphtdocsNAIG', 0.0, NULL, 1, '2026-01-01 08:00:00'),
(6, 'FoodGrab Kitchen', 'Fresh local flavors and quick bites.', 'Purok 4, Barangay 9, Cabadbaran City', NULL, 'General', 50, '09:00:00', '22:00:00', 'c:xampphtdocsNAIG', 0.0, NULL, 1, '2026-02-20 00:52:28'),
(7, 'Street Bites', 'Street food favorites delivered.', 'Downtown Cabadbaran', NULL, 'General', 50, '09:00:00', '22:00:00', 'c:xampphtdocsNAIG', 0.0, NULL, 1, '2026-02-20 00:52:28'),
(8, 'Street Bites', '', '', NULL, 'Italian Bites', 50, '09:00:00', '22:00:00', '', 0.0, '', 1, '2026-02-20 10:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `table_number` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 2,
  `location` enum('indoor','outdoor','private','bar') NOT NULL DEFAULT 'indoor',
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `restaurant_id`, `table_number`, `capacity`, `location`, `is_available`, `created_at`) VALUES
(1, 6, 'T1', 2, 'indoor', 1, '2026-02-20 02:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleInitial` varchar(1) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `sex` enum('male','female') NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) NOT NULL,
  `purok` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `zipCode` varchar(10) NOT NULL,
  `country` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `secure_question` varchar(100) DEFAULT NULL,
  `secure_answer` varchar(255) DEFAULT NULL,
  `secure_question2` varchar(100) DEFAULT NULL,
  `secure_answer2` varchar(255) DEFAULT NULL,
  `secure_question3` varchar(100) DEFAULT NULL,
  `secure_answer3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('consumer','admin','superadmin') NOT NULL DEFAULT 'consumer',
  `status` varchar(20) DEFAULT 'registered',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `lastName`, `middleInitial`, `extension`, `sex`, `birthdate`, `age`, `purok`, `barangay`, `city`, `province`, `zipCode`, `country`, `username`, `email`, `password`, `secure_question`, `secure_answer`, `secure_question2`, `secure_answer2`, `secure_question3`, `secure_answer3`, `created_at`, `role`, `status`, `is_blocked`) VALUES
('0001-0002', 'Clark', 'Naig', 'N', '', 'male', '1990-06-20', 35, 'Purok 2', 'Barangay 2', 'Cabadbaran City', 'Agusan Del Norte', '8605', 'Philippines', 'clark21', 'clark@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1. Who is your bestfriend in elementary? *', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2. What is the name of your pet? *', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3. Who is your favorite teacher in highschool? *', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-25 16:17:36', 'superadmin', 'registered', 0),
('0001-0004', 'Psynil', 'Naig', '', '', 'male', '2001-07-22', 24, 'Purok 4', 'Barangay 4', 'Cabadbaran City', 'Agusan Del Norte', '8605', 'Philippines', 'pysnil21', 'psynill@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1. Who is your bestfriend in elementary? *', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2. What is the name of your pet? *', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3. Who is your favorite teacher in highschool? *', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-02-25 16:17:36', 'consumer', 'registered', 0),
('1234-5678', 'Jaira', 'Naig', '', '', 'female', '2003-02-02', 23, 'Purok 5', 'Baranggay 6', 'City of Cabadbaran', 'Agusan Del Norte', '8605', 'Philippines', 'jaira21', 'jaira21@gmail.com', '$2y$10$mE8gl.6Biun3IeIIfLeoNO1Wb/4J0NzWNFqAysIocpwn04Ry/y6vO', 'What is the name of your pet?', '$2y$10$RkDJNPtwonm77rLUc9lBPOURIhrzDwOLmB/L1eM7UfMfoO.vicAsC', 'What is your favorite food?', '$2y$10$0W.RvWsXTUKHy9j/gp.7ue.ZcOan6FRrr/d5KkOnEy7pF4.7Bh0om', 'What is your favorite movie?', '$2y$10$/ziyyFQ5m5DTR3zK6u2mQuUD3LBeGtlrCiuz1tf2XHibpod1yFpiO', '2026-05-08 00:55:14', 'admin', 'registered', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_block_requests`
--

CREATE TABLE `user_block_requests` (
  `id` int(11) NOT NULL,
  `requester_id` varchar(11) NOT NULL,
  `target_id` varchar(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `request_type` enum('block','unblock') NOT NULL DEFAULT 'block',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_block_requests`
--

INSERT INTO `user_block_requests` (`id`, `requester_id`, `target_id`, `reason`, `status`, `request_type`, `created_at`, `updated_at`) VALUES
(5, '0001-0004', '1234-5678', 'Needs blocking', 'approved', 'block', '2026-05-08 09:14:52', '2026-05-08 09:17:03'),
(6, '0001-0004', '1234-5678', 'need blocking', 'approved', 'block', '2026-05-08 12:06:57', '2026-05-08 12:07:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_creation_requests`
--
ALTER TABLE `admin_creation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `status` (`status`),
  ADD KEY `admin_creation_reviewed_by_fk` (`reviewed_by`);

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cart_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cart_item_unique` (`cart_id`,`menu_item_id`),
  ADD KEY `fk_cartitem_menu` (`menu_item_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_logs_user` (`user_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menu_restaurant` (`restaurant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_restaurant` (`restaurant_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderitems_order` (`order_id`),
  ADD KEY `fk_orderitems_menu` (`menu_item_id`);

--
-- Indexes for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_user` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_block_requests`
--
ALTER TABLE `user_block_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_block_requester` (`requester_id`),
  ADD KEY `fk_block_target` (`target_id`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_menu_unique` (`user_id`,`menu_item_id`),
  ADD KEY `fk_fav_user` (`user_id`),
  ADD KEY `fk_fav_menu` (`menu_item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_creation_requests`
--
ALTER TABLE `admin_creation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_block_requests`
--
ALTER TABLE `user_block_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_creation_requests`
--
ALTER TABLE `admin_creation_requests`
  ADD CONSTRAINT `admin_creation_requested_by_fk` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_creation_reviewed_by_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cartitem_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cartitem_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitems_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD CONSTRAINT `password_reset_otp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD CONSTRAINT `restaurant_tables_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_block_requests`
--
ALTER TABLE `user_block_requests`
  ADD CONSTRAINT `fk_block_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_block_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `fk_fav_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
