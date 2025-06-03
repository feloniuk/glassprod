-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2025 at 03:56 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `glassprod`
--

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `ID` int(10) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Parameter` decimal(10,2) NOT NULL,
  `Dates` varchar(17) NOT NULL DEFAULT '0000-00-00',
  `Times` varchar(17) NOT NULL DEFAULT '00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `data`
--

INSERT INTO `data` (`ID`, `Name`, `Parameter`, `Dates`, `Times`) VALUES
(3734, 'Продуктивність ділянки ПТЛ, т/год', '59.98', '30.05.2025', '21:44:58'),
(3735, 'Продуктивність ділянки ПТЛ, т/год', '59.92', '30.05.2025', '21:45:03'),
(3736, 'Продуктивність ділянки ПТЛ, т/год', '59.88', '30.05.2025', '21:45:08'),
(3737, 'Продуктивність ділянки ПТЛ, т/год', '59.85', '30.05.2025', '21:45:13'),
(3738, 'Продуктивність ділянки ПТЛ, т/год', '59.84', '30.05.2025', '21:45:18'),
(3739, 'Продуктивність ділянки ПТЛ, т/год', '59.85', '30.05.2025', '21:45:24'),
(3740, 'Продуктивність ділянки ПТЛ, т/год', '59.86', '30.05.2025', '21:45:29'),
(3741, 'Продуктивність ділянки ПТЛ, т/год', '59.88', '30.05.2025', '21:45:36'),
(3742, 'Продуктивність ділянки ПТЛ, т/год', '59.91', '30.05.2025', '21:45:42'),
(3743, 'Продуктивність ділянки ПТЛ, т/год', '59.94', '30.05.2025', '21:45:49'),
(3744, 'Продуктивність ділянки ПТЛ, т/год', '59.97', '30.05.2025', '21:45:55'),
(3745, 'Продуктивність ділянки ПТЛ, т/год', '59.99', '30.05.2025', '21:46:01'),
(3746, 'Продуктивність ділянки ПТЛ, т/год', '60.01', '30.05.2025', '21:46:06'),
(3747, 'Продуктивність ділянки ПТЛ, т/год', '60.03', '30.05.2025', '21:46:12'),
(3748, 'Продуктивність ділянки ПТЛ, т/год', '60.04', '30.05.2025', '21:46:17'),
(3749, 'Продуктивність ділянки ПТЛ, т/год', '60.04', '30.05.2025', '21:46:21'),
(3750, 'Продуктивність ділянки ПТЛ, т/год', '60.04', '30.05.2025', '21:46:26'),
(3751, 'Продуктивність ділянки ПТЛ, т/год', '60.04', '30.05.2025', '21:46:30'),
(3752, 'Продуктивність ділянки ПТЛ, т/год', '60.04', '30.05.2025', '21:46:34'),
(3753, 'Продуктивність ділянки ПТЛ, т/год', '60.03', '30.05.2025', '21:46:38'),
(3754, 'Продуктивність ділянки ПТЛ, т/год', '60.02', '30.05.2025', '21:46:42'),
(3690, 'Продуктивність ділянки ПТЛ', '25.00', '30.05.2025', '21:39:10'),
(3691, 'Продуктивність ділянки ПТЛ, т/год', '25.00', '30.05.2025', '21:40:21'),
(3692, 'Продуктивність ділянки ПТЛ, т/год', '25.17', '30.05.2025', '21:41:26'),
(3693, 'Продуктивність ділянки ПТЛ, т/год', '27.44', '30.05.2025', '21:41:33'),
(3694, 'Продуктивність ділянки ПТЛ, т/год', '31.72', '30.05.2025', '21:41:39'),
(3695, 'Продуктивність ділянки ПТЛ, т/год', '37.10', '30.05.2025', '21:41:45'),
(3696, 'Продуктивність ділянки ПТЛ, т/год', '42.90', '30.05.2025', '21:41:51'),
(3697, 'Продуктивність ділянки ПТЛ, т/год', '48.56', '30.05.2025', '21:41:57'),
(3698, 'Продуктивність ділянки ПТЛ, т/год', '53.52', '30.05.2025', '21:42:02'),
(3699, 'Продуктивність ділянки ПТЛ, т/год', '57.71', '30.05.2025', '21:42:07'),
(3700, 'Продуктивність ділянки ПТЛ, т/год', '61.09', '30.05.2025', '21:42:12'),
(3701, 'Продуктивність ділянки ПТЛ, т/год', '63.50', '30.05.2025', '21:42:16'),
(3702, 'Продуктивність ділянки ПТЛ, т/год', '65.03', '30.05.2025', '21:42:20'),
(3703, 'Продуктивність ділянки ПТЛ, т/год', '65.79', '30.05.2025', '21:42:24'),
(3704, 'Продуктивність ділянки ПТЛ, т/год', '65.86', '30.05.2025', '21:42:28'),
(3705, 'Продуктивність ділянки ПТЛ, т/год', '65.41', '30.05.2025', '21:42:32'),
(3706, 'Продуктивність ділянки ПТЛ, т/год', '64.58', '30.05.2025', '21:42:35'),
(3707, 'Продуктивність ділянки ПТЛ, т/год', '63.51', '30.05.2025', '21:42:39'),
(3708, 'Продуктивність ділянки ПТЛ, т/год', '62.37', '30.05.2025', '21:42:44'),
(3709, 'Продуктивність ділянки ПТЛ, т/год', '61.24', '30.05.2025', '21:42:48'),
(3710, 'Продуктивність ділянки ПТЛ, т/год', '60.20', '30.05.2025', '21:42:53'),
(3711, 'Продуктивність ділянки ПТЛ, т/год', '59.34', '30.05.2025', '21:42:57'),
(3712, 'Продуктивність ділянки ПТЛ, т/год', '58.69', '30.05.2025', '21:43:02'),
(3713, 'Продуктивність ділянки ПТЛ, т/год', '58.24', '30.05.2025', '21:43:08'),
(3714, 'Продуктивність ділянки ПТЛ, т/год', '58.01', '30.05.2025', '21:43:13'),
(3715, 'Продуктивність ділянки ПТЛ, т/год', '57.97', '30.05.2025', '21:43:19'),
(3716, 'Продуктивність ділянки ПТЛ, т/год', '58.08', '30.05.2025', '21:43:25'),
(3717, 'Продуктивність ділянки ПТЛ, т/год', '58.31', '30.05.2025', '21:43:32'),
(3718, 'Продуктивність ділянки ПТЛ, т/год', '58.62', '30.05.2025', '21:43:38'),
(3719, 'Продуктивність ділянки ПТЛ, т/год', '58.96', '30.05.2025', '21:43:45'),
(3720, 'Продуктивність ділянки ПТЛ, т/год', '59.33', '30.05.2025', '21:43:52'),
(3721, 'Продуктивність ділянки ПТЛ, т/год', '59.66', '30.05.2025', '21:43:58'),
(3722, 'Продуктивність ділянки ПТЛ, т/год', '59.95', '30.05.2025', '21:44:04'),
(3723, 'Продуктивність ділянки ПТЛ, т/год', '60.20', '30.05.2025', '21:44:10'),
(3724, 'Продуктивність ділянки ПТЛ, т/год', '60.37', '30.05.2025', '21:44:15'),
(3725, 'Продуктивність ділянки ПТЛ, т/год', '60.49', '30.05.2025', '21:44:20'),
(3726, 'Продуктивність ділянки ПТЛ, т/год', '60.54', '30.05.2025', '21:44:25'),
(3727, 'Продуктивність ділянки ПТЛ, т/год', '60.54', '30.05.2025', '21:44:30'),
(3728, 'Продуктивність ділянки ПТЛ, т/год', '60.50', '30.05.2025', '21:44:34'),
(3729, 'Продуктивність ділянки ПТЛ, т/год', '60.44', '30.05.2025', '21:44:38'),
(3730, 'Продуктивність ділянки ПТЛ, т/год', '60.35', '30.05.2025', '21:44:42'),
(3731, 'Продуктивність ділянки ПТЛ, т/год', '60.25', '30.05.2025', '21:44:46'),
(3732, 'Продуктивність ділянки ПТЛ, т/год', '60.15', '30.05.2025', '21:44:50'),
(3733, 'Продуктивність ділянки ПТЛ, т/год', '60.06', '30.05.2025', '21:44:54');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `min_stock_level` int(11) DEFAULT 0,
  `current_stock` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `name`, `category_id`, `unit`, `min_stock_level`, `current_stock`, `price`, `supplier_id`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Пісок кварцовий', 1, 'т', 50, 120, '1500.00', 4, 'Високоякісний кварцовий пісок для виробництва скла', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(2, 'Сода кальцинована', 1, 'т', 20, 45, '8500.00', 4, 'Карбонат натрію технічний', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(3, 'Вапняк', 1, 'т', 30, 75, '450.00', 4, 'Карбонат кальцію для скловиробництва', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(4, 'Доломіт', 1, 'т', 15, 32, '680.00', 5, 'Доломітова мука для скломаси', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(5, 'Борна кислота', 2, 'кг', 500, 1200, '45.00', 5, 'Для зниження температури плавлення', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(6, 'Оксид цинку', 2, 'кг', 200, 850, '125.00', 5, 'Для підвищення хімічної стійкості скла', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(7, 'Природний газ', 3, 'м³', 10000, 25000, '8.50', 4, 'Паливо для печей', '2025-06-03 04:02:48', '2025-06-03 04:02:48'),
(8, 'Електроенергія', 3, 'кВт·год', 50000, 120000, '2.85', 4, 'Електропостачання виробництва', '2025-06-03 04:02:48', '2025-06-03 04:02:48');

-- --------------------------------------------------------

--
-- Table structure for table `material_categories`
--

CREATE TABLE `material_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `material_categories`
--

INSERT INTO `material_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Основні матеріали', 'Сировина для виробництва скла', '2025-06-03 04:02:48'),
(2, 'Хімічні реагенти', 'Хімічні речовини для обробки', '2025-06-03 04:02:48'),
(3, 'Паливо та енергія', 'Паливо та енергоносії', '2025-06-03 04:02:48'),
(4, 'Допоміжні матеріали', 'Допоміжні матеріали для виробництва', '2025-06-03 04:02:48');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `delivered_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `material_id`, `quantity`, `unit_price`, `total_price`, `delivered_quantity`) VALUES
(1, 1, 1, 25, '1500.00', '37500.00', 0),
(2, 1, 2, 10, '8500.00', '85000.00', 0),
(3, 1, 7, 1000, '8.50', '8500.00', 0),
(4, 2, 4, 50, '680.00', '34000.00', 0),
(5, 2, 5, 200, '45.00', '9000.00', 0),
(6, 2, 6, 125, '125.00', '15625.00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','ordered','delivered') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `needed_date` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`id`, `material_id`, `quantity`, `requested_by`, `status`, `priority`, `request_date`, `needed_date`, `approved_by`, `approved_date`, `comments`, `total_cost`) VALUES
(1, 1, 25, 3, 'pending', 'high', '2025-06-03 04:02:48', '2025-06-15', NULL, NULL, 'Терміново потрібно для нового замовлення', '37500.00'),
(2, 2, 10, 3, 'approved', 'medium', '2025-06-03 04:02:48', '2025-06-20', NULL, NULL, 'Планова закупівля', '85000.00'),
(3, 5, 300, 3, 'approved', 'low', '2025-06-03 04:02:48', '2025-06-25', 1, '2025-06-03 04:09:28', 'Поповнення запасів', '13500.00'),
(4, 7, 5000, 3, 'approved', 'urgent', '2025-06-03 04:02:48', '2025-06-10', NULL, NULL, 'Критично низький рівень запасів', '42500.00');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_type` enum('order','production','adjustment','manual') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `material_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `performed_by`, `movement_date`, `notes`) VALUES
(1, 1, 'in', 100, 'order', 1, 3, '2025-06-03 04:02:48', 'Поступлення від постачальника'),
(2, 2, 'in', 50, 'order', 1, 3, '2025-06-03 04:02:48', 'Поступлення від постачальника'),
(3, 3, 'out', 25, 'production', NULL, 3, '2025-06-03 04:02:48', 'Витрата на виробництво'),
(4, 1, 'out', 30, 'production', NULL, 3, '2025-06-03 04:02:48', 'Витрата на виробництво партії №125'),
(5, 7, 'in', 15000, 'manual', NULL, 3, '2025-06-03 04:02:48', 'Початкові залишки');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_orders`
--

CREATE TABLE `supplier_orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('draft','sent','confirmed','in_progress','delivered','completed','cancelled') DEFAULT 'draft',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expected_delivery` date DEFAULT NULL,
  `actual_delivery` date DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_orders`
--

INSERT INTO `supplier_orders` (`id`, `supplier_id`, `order_number`, `status`, `order_date`, `expected_delivery`, `actual_delivery`, `total_amount`, `created_by`, `notes`) VALUES
(1, 4, 'ORD-2025-001', 'confirmed', '2025-06-03 04:02:48', '2025-06-12', NULL, '127500.00', 2, 'Терміновий заказ основних матеріалів'),
(2, 5, 'ORD-2025-002', 'sent', '2025-06-03 04:02:48', '2025-06-18', NULL, '58500.00', 2, 'Планова закупівля хімічних реагентів');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('director','procurement_manager','warehouse_keeper','supplier') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `phone`, `company_name`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'director', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'director@glassprod.ua', 'Петренко Олександр Іванович', 'director', '+380501234567', NULL, '2025-06-03 04:02:48', '2025-06-03 04:06:06', 1),
(2, 'procurement', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'procurement@glassprod.ua', 'Коваленко Марія Петрівна', 'procurement_manager', '+380501234568', NULL, '2025-06-03 04:02:48', '2025-06-03 04:06:06', 1),
(3, 'warehouse', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'warehouse@glassprod.ua', 'Сидоренко Василь Миколайович', 'warehouse_keeper', '+380501234569', NULL, '2025-06-03 04:02:48', '2025-06-03 04:06:06', 1),
(4, 'supplier1', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'supplier1@supplier.ua', 'Іваненко Андрій Сергійович', 'supplier', '+380501234570', 'СклоМатеріали ТОВ', '2025-06-03 04:02:48', '2025-06-03 04:06:06', 1),
(5, 'supplier2', '$2y$10$RFOEzBbs/tQ/poTCMK7hheNOFJcm0CtFtUmDC2vDzEu2PAfvCVrvm', 'supplier2@supplier.ua', 'Мельник Ольга Василівна', 'supplier', '+380501234571', 'ПромСкло Україна', '2025-06-03 04:02:48', '2025-06-03 04:06:06', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `material_categories`
--
ALTER TABLE `material_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `supplier_orders`
--
ALTER TABLE `supplier_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

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
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `material_categories`
--
ALTER TABLE `material_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `supplier_orders`
--
ALTER TABLE `supplier_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `material_categories` (`id`),
  ADD CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `supplier_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  ADD CONSTRAINT `purchase_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `supplier_orders`
--
ALTER TABLE `supplier_orders`
  ADD CONSTRAINT `supplier_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `supplier_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
