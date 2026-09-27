-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 03, 2026 at 05:13 AM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u272390999_u272390999_Tra`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer_hash` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password_hash`, `name`, `email`, `security_question`, `security_answer_hash`, `remember_token`, `session_id`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$AJlDbYOTuQvCEwXn8ERzu.vJPutbqkaVYuHKuEdxLaytR41EFW2ku', 'System Administrator', 'admin@example.com', NULL, NULL, '3b56af2ef5f817af568adab55770eb4ebce124493d61fbcbe4d47d8e2d69bba7', '4rgssekk2ka5ndmohpk9sslqoh', '2026-09-03 05:06:53', '2026-07-30 05:59:28', '2026-09-03 05:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action`, `user_id`, `ip_address`, `session_id`, `old_values`, `new_values`, `details`, `created_at`) VALUES
(1, 'System Erased & Reset', 1, '2405:201:301c:e155:d00f:30bb:9d79:8058', '4rgssekk2ka5ndmohpk9sslqoh', NULL, '{\"backup_file\":\"backup_2026-09-03_05-12-04.sql\"}', 'Erased all operational data with automatic safety backup download', '2026-09-03 05:12:04'),
(2, 'Customer Created', 1, '2405:201:301c:e155:d00f:30bb:9d79:8058', '4rgssekk2ka5ndmohpk9sslqoh', NULL, '{\"id\":1,\"customer_id\":\"CUST-2026-0001\",\"full_name\":\"test\",\"mobile\":\"9988776655\"}', 'Created new customer profile', '2026-09-03 05:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `backup_type` enum('Manual','Auto') DEFAULT 'Manual',
  `status` enum('Success','Failed') DEFAULT 'Success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collateral_items`
--

CREATE TABLE `collateral_items` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `item_type` enum('GOLD','SILVER') NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `gross_weight` decimal(10,3) NOT NULL DEFAULT 0.000,
  `stone_weight` decimal(10,3) NOT NULL DEFAULT 0.000,
  `net_weight` decimal(10,3) NOT NULL DEFAULT 0.000,
  `purity_preset` varchar(50) DEFAULT NULL,
  `purity_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `market_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `manual_market_value_override` decimal(12,2) DEFAULT NULL,
  `loan_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rk_number` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collateral_item_photos`
--

CREATE TABLE `collateral_item_photos` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_id` varchar(30) NOT NULL,
  `account_number` varchar(30) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `alt_mobile` varchar(20) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `guarantor_name` varchar(100) DEFAULT NULL,
  `guarantor_mobile` varchar(20) DEFAULT NULL,
  `guarantor_address` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Active','Closed','Blocked') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_id`, `account_number`, `photo`, `full_name`, `father_name`, `mobile`, `alt_mobile`, `aadhaar`, `pan`, `address`, `village`, `city`, `state`, `pincode`, `guarantor_name`, `guarantor_mobile`, `guarantor_address`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CUST-2026-0001', 'Acc-1', NULL, 'test', 'test', '9988776655', NULL, '3212132132121', '3212313213', 'teas', 'test', 'Ratlam', 'Mp', '457001', 'test', 'test', 'test', 'asdfas', 'Active', '2026-09-03 05:12:44', '2026-09-03 05:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `customer_documents`
--

CREATE TABLE `customer_documents` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `database_backups`
--

CREATE TABLE `database_backups` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filesize` int(11) NOT NULL DEFAULT 0,
  `backup_type` enum('Manual','Scheduled') DEFAULT 'Manual',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gold_rates`
--

CREATE TABLE `gold_rates` (
  `id` int(11) NOT NULL,
  `rate_date` date NOT NULL,
  `rate_100` decimal(10,2) NOT NULL DEFAULT 8000.00,
  `rate_24k` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_22k` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_18k` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_14k` decimal(10,2) NOT NULL DEFAULT 0.00,
  `custom_rates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_rates`)),
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gold_rates`
--

INSERT INTO `gold_rates` (`id`, `rate_date`, `rate_100`, `rate_24k`, `rate_22k`, `rate_18k`, `rate_14k`, `custom_rates`, `remarks`, `created_at`) VALUES
(1, '2026-07-30', 8000.00, 7800.00, 7150.26, 5850.00, 4549.74, '{\"23K\":7474.74,\"21K\":6825,\"20K\":6499.74,\"10K\":3250.26}', 'Multi-karat verification test', '2026-07-30 05:59:28'),
(4, '2026-08-03', 8000.00, 140000.00, 128338.00, 105000.00, 81662.00, '{\"23K\":134162,\"21K\":122500,\"20K\":116662,\"10K\":58338,\"21\":112000}', '', '2026-08-03 11:33:10'),
(5, '2026-08-06', 14000.00, 13986.00, 12833.80, 10500.00, 8166.20, '{\"24K\":13986,\"23K\":13416.2,\"21K\":12250,\"20K\":11666.2,\"10K\":5833.8,\"21\":11200}', '', '2026-08-06 06:44:49'),
(6, '2026-08-08', 140000.00, 139860.00, 128338.00, 105000.00, 81662.00, '{\"24K\":139860,\"23K\":134162,\"21K\":122500,\"20K\":116662,\"10K\":58338,\"21\":112000,\"24\":138600}', '', '2026-08-08 04:02:20');

-- --------------------------------------------------------

--
-- Table structure for table `interest_history`
--

CREATE TABLE `interest_history` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `calculation_date` date NOT NULL,
  `accrued_interest` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_method` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `loan_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `loan_date` date NOT NULL,
  `security_type` enum('Gold Secured','Silver Secured','Gold + Silver Secured','Guarantor Secured','Gold + Guarantor','Silver + Guarantor','Gold + Silver + Guarantor') NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_payable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `interest_cycle` varchar(50) DEFAULT '15 Days',
  `interest_method` enum('Simple','Compound') DEFAULT 'Simple',
  `compound_frequency` enum('Monthly','Quarterly','Half-Yearly','Yearly') DEFAULT 'Monthly',
  `return_date` date DEFAULT NULL,
  `interest_due_date` date DEFAULT NULL,
  `status` enum('Running','Closed','Overdue','Defaulted') DEFAULT 'Running',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_guarantors`
--

CREATE TABLE `loan_guarantors` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `guarantor_name` varchar(100) NOT NULL,
  `guarantor_mobile` varchar(20) DEFAULT NULL,
  `guarantor_address` text DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_ledger`
--

CREATE TABLE `loan_ledger` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `entry_type` varchar(50) NOT NULL DEFAULT 'Loan Issued',
  `description` varchar(255) NOT NULL,
  `debit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_topups`
--

CREATE TABLE `loan_topups` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `topup_date` date NOT NULL,
  `topup_amount` decimal(12,2) NOT NULL,
  `new_principal` decimal(12,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_type` enum('Interest Payment','Principal Payment','Partial Principal Payment','Full Settlement','Advance Interest','Penalty Payment','Adjustment - Credit','Adjustment - Debit','Refund') NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_component` decimal(12,2) NOT NULL DEFAULT 0.00,
  `principal_component` decimal(12,2) NOT NULL DEFAULT 0.00,
  `penalty_component` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('Cash','UPI','Bank Transfer','Cheque') NOT NULL DEFAULT 'Cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `racks`
--

CREATE TABLE `racks` (
  `id` int(11) NOT NULL,
  `rack_number` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `total_slots` int(11) NOT NULL DEFAULT 15,
  `location` varchar(100) DEFAULT 'Main Vault',
  `description` text DEFAULT NULL,
  `status` enum('Active','Maintenance','Disabled') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `racks`
--

INSERT INTO `racks` (`id`, `rack_number`, `name`, `total_slots`, `location`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rack 1', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(2, 2, 'Rack 2', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(3, 3, 'Rack 3', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(4, 4, 'Rack 4', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(5, 5, 'Rack 5', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(6, 6, 'Rack 6', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(7, 7, 'Rack 7', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(8, 8, 'Rack 8', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(9, 9, 'Rack 9', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(10, 10, 'Rack 10', 15, 'Main Vault Room', 'Standard 15-slot high security physical rack', 'Active', '2026-08-19 12:22:21', '2026-08-19 12:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `rack_slots`
--

CREATE TABLE `rack_slots` (
  `id` int(11) NOT NULL,
  `rack_id` int(11) NOT NULL,
  `slot_number` int(11) NOT NULL,
  `slot_name` varchar(100) NOT NULL,
  `status` enum('Available','Occupied','Reserved','Disabled') NOT NULL DEFAULT 'Available',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rack_slots`
--

INSERT INTO `rack_slots` (`id`, `rack_id`, `slot_number`, `slot_name`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Rack 1 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(2, 1, 2, 'Rack 1 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(3, 1, 3, 'Rack 1 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(4, 1, 4, 'Rack 1 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(5, 1, 5, 'Rack 1 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(6, 1, 6, 'Rack 1 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(7, 1, 7, 'Rack 1 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(8, 1, 8, 'Rack 1 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(9, 1, 9, 'Rack 1 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(10, 1, 10, 'Rack 1 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(11, 1, 11, 'Rack 1 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(12, 1, 12, 'Rack 1 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(13, 1, 13, 'Rack 1 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(14, 1, 14, 'Rack 1 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(15, 1, 15, 'Rack 1 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(16, 2, 1, 'Rack 2 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(17, 2, 2, 'Rack 2 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(18, 2, 3, 'Rack 2 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(19, 2, 4, 'Rack 2 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(20, 2, 5, 'Rack 2 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(21, 2, 6, 'Rack 2 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(22, 2, 7, 'Rack 2 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(23, 2, 8, 'Rack 2 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(24, 2, 9, 'Rack 2 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(25, 2, 10, 'Rack 2 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(26, 2, 11, 'Rack 2 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(27, 2, 12, 'Rack 2 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(28, 2, 13, 'Rack 2 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(29, 2, 14, 'Rack 2 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(30, 2, 15, 'Rack 2 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(31, 3, 1, 'Rack 3 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(32, 3, 2, 'Rack 3 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(33, 3, 3, 'Rack 3 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(34, 3, 4, 'Rack 3 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(35, 3, 5, 'Rack 3 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(36, 3, 6, 'Rack 3 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(37, 3, 7, 'Rack 3 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(38, 3, 8, 'Rack 3 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(39, 3, 9, 'Rack 3 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(40, 3, 10, 'Rack 3 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(41, 3, 11, 'Rack 3 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(42, 3, 12, 'Rack 3 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(43, 3, 13, 'Rack 3 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(44, 3, 14, 'Rack 3 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(45, 3, 15, 'Rack 3 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(46, 4, 1, 'Rack 4 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(47, 4, 2, 'Rack 4 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(48, 4, 3, 'Rack 4 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(49, 4, 4, 'Rack 4 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(50, 4, 5, 'Rack 4 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(51, 4, 6, 'Rack 4 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(52, 4, 7, 'Rack 4 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(53, 4, 8, 'Rack 4 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(54, 4, 9, 'Rack 4 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(55, 4, 10, 'Rack 4 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(56, 4, 11, 'Rack 4 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(57, 4, 12, 'Rack 4 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(58, 4, 13, 'Rack 4 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(59, 4, 14, 'Rack 4 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(60, 4, 15, 'Rack 4 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(61, 5, 1, 'Rack 5 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(62, 5, 2, 'Rack 5 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(63, 5, 3, 'Rack 5 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(64, 5, 4, 'Rack 5 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(65, 5, 5, 'Rack 5 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(66, 5, 6, 'Rack 5 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(67, 5, 7, 'Rack 5 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(68, 5, 8, 'Rack 5 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(69, 5, 9, 'Rack 5 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(70, 5, 10, 'Rack 5 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(71, 5, 11, 'Rack 5 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(72, 5, 12, 'Rack 5 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(73, 5, 13, 'Rack 5 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(74, 5, 14, 'Rack 5 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(75, 5, 15, 'Rack 5 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(76, 6, 1, 'Rack 6 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(77, 6, 2, 'Rack 6 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(78, 6, 3, 'Rack 6 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(79, 6, 4, 'Rack 6 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(80, 6, 5, 'Rack 6 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(81, 6, 6, 'Rack 6 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(82, 6, 7, 'Rack 6 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(83, 6, 8, 'Rack 6 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(84, 6, 9, 'Rack 6 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(85, 6, 10, 'Rack 6 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(86, 6, 11, 'Rack 6 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(87, 6, 12, 'Rack 6 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(88, 6, 13, 'Rack 6 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(89, 6, 14, 'Rack 6 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(90, 6, 15, 'Rack 6 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(91, 7, 1, 'Rack 7 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(92, 7, 2, 'Rack 7 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(93, 7, 3, 'Rack 7 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(94, 7, 4, 'Rack 7 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(95, 7, 5, 'Rack 7 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(96, 7, 6, 'Rack 7 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(97, 7, 7, 'Rack 7 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(98, 7, 8, 'Rack 7 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(99, 7, 9, 'Rack 7 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(100, 7, 10, 'Rack 7 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(101, 7, 11, 'Rack 7 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(102, 7, 12, 'Rack 7 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(103, 7, 13, 'Rack 7 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(104, 7, 14, 'Rack 7 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(105, 7, 15, 'Rack 7 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(106, 8, 1, 'Rack 8 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(107, 8, 2, 'Rack 8 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(108, 8, 3, 'Rack 8 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(109, 8, 4, 'Rack 8 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(110, 8, 5, 'Rack 8 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(111, 8, 6, 'Rack 8 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(112, 8, 7, 'Rack 8 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(113, 8, 8, 'Rack 8 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(114, 8, 9, 'Rack 8 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(115, 8, 10, 'Rack 8 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(116, 8, 11, 'Rack 8 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(117, 8, 12, 'Rack 8 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(118, 8, 13, 'Rack 8 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(119, 8, 14, 'Rack 8 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(120, 8, 15, 'Rack 8 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(121, 9, 1, 'Rack 9 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(122, 9, 2, 'Rack 9 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(123, 9, 3, 'Rack 9 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(124, 9, 4, 'Rack 9 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(125, 9, 5, 'Rack 9 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(126, 9, 6, 'Rack 9 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(127, 9, 7, 'Rack 9 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(128, 9, 8, 'Rack 9 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(129, 9, 9, 'Rack 9 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(130, 9, 10, 'Rack 9 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(131, 9, 11, 'Rack 9 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(132, 9, 12, 'Rack 9 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(133, 9, 13, 'Rack 9 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(134, 9, 14, 'Rack 9 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(135, 9, 15, 'Rack 9 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(136, 10, 1, 'Rack 10 - Slot 1', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(137, 10, 2, 'Rack 10 - Slot 2', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(138, 10, 3, 'Rack 10 - Slot 3', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(139, 10, 4, 'Rack 10 - Slot 4', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(140, 10, 5, 'Rack 10 - Slot 5', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(141, 10, 6, 'Rack 10 - Slot 6', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(142, 10, 7, 'Rack 10 - Slot 7', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(143, 10, 8, 'Rack 10 - Slot 8', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(144, 10, 9, 'Rack 10 - Slot 9', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(145, 10, 10, 'Rack 10 - Slot 10', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(146, 10, 11, 'Rack 10 - Slot 11', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(147, 10, 12, 'Rack 10 - Slot 12', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(148, 10, 13, 'Rack 10 - Slot 13', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(149, 10, 14, 'Rack 10 - Slot 14', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21'),
(150, 10, 15, 'Rack 10 - Slot 15', 'Available', NULL, '2026-08-19 12:22:21', '2026-08-19 12:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `rate_karat_presets`
--

CREATE TABLE `rate_karat_presets` (
  `id` int(11) NOT NULL,
  `type` enum('GOLD','SILVER') NOT NULL,
  `name` varchar(50) NOT NULL,
  `purity_percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_karat_presets`
--

INSERT INTO `rate_karat_presets` (`id`, `type`, `name`, `purity_percentage`, `is_active`, `display_order`, `created_at`) VALUES
(1, 'GOLD', '24K', 100.00, 1, 1, '2026-07-30 05:59:28'),
(2, 'GOLD', '22K', 91.67, 1, 2, '2026-07-30 05:59:28'),
(3, 'GOLD', '18K', 75.00, 1, 3, '2026-07-30 05:59:28'),
(4, 'GOLD', '14K', 58.33, 1, 4, '2026-07-30 05:59:28'),
(5, 'SILVER', '99.9%', 99.90, 1, 1, '2026-07-30 05:59:28'),
(6, 'SILVER', '92.5%', 92.50, 1, 2, '2026-07-30 05:59:28'),
(7, 'SILVER', '80.0%', 80.00, 1, 3, '2026-07-30 05:59:28'),
(8, 'GOLD', '23K', 95.83, 1, 2, '2026-07-30 06:40:03'),
(9, 'GOLD', '21K', 87.50, 1, 4, '2026-07-30 06:40:03'),
(10, 'GOLD', '20K', 83.33, 1, 5, '2026-07-30 06:40:03'),
(11, 'GOLD', '10K', 41.67, 1, 8, '2026-07-30 06:40:03'),
(12, 'SILVER', '95.0%', 95.00, 1, 2, '2026-07-30 06:40:03'),
(13, 'SILVER', '75.0%', 75.00, 1, 5, '2026-07-30 06:40:03'),
(14, 'GOLD', '21', 80.00, 1, 10, '2026-07-30 06:41:11'),
(15, 'GOLD', '100% (Fine)', 100.00, 1, 0, '2026-08-06 06:32:10'),
(16, 'SILVER', '100% (Pure)', 100.00, 1, 0, '2026-08-06 06:32:10'),
(17, 'GOLD', '24', 99.00, 1, 10, '2026-08-08 04:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'Golden Trust Finance Co.', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(2, 'company_logo', '', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(3, 'company_address', '123 Main Financial Street, Capital City', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(4, 'company_phone', '+91 98765 43210', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(5, 'company_email', 'info@goldentrust.com', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(6, 'company_gst', '27AAACG1234F1Z5', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(7, 'currency_symbol', '₹', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(8, 'timezone', 'Asia/Kolkata', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(9, 'loan_number_format', 'LMS-{YEAR}-{0000}', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(10, 'default_interest_rate', '18.00', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(11, 'default_interest_method', 'Simple', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(12, 'ltv_limit', '75.00', '2026-07-30 05:59:28', '2026-07-30 05:59:28'),
(13, 'backup_reminder_days', '7', '2026-07-30 05:59:28', '2026-07-30 05:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `silver_rates`
--

CREATE TABLE `silver_rates` (
  `id` int(11) NOT NULL,
  `rate_date` date NOT NULL,
  `rate_100` decimal(10,2) NOT NULL DEFAULT 90.00,
  `rate_999` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_925` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_800` decimal(10,2) NOT NULL DEFAULT 0.00,
  `custom_rates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_rates`)),
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `silver_rates`
--

INSERT INTO `silver_rates` (`id`, `rate_date`, `rate_100`, `rate_999`, `rate_925`, `rate_800`, `custom_rates`, `remarks`, `created_at`) VALUES
(1, '2026-07-30', 90.00, 92.00, 85.10, 73.60, NULL, 'Verification test silver rate update', '2026-07-30 05:59:28'),
(3, '2026-08-03', 90.00, 210000.00, 194250.00, 168000.00, '{\"95.0%\":199500,\"75.0%\":157500}', '', '2026-08-03 11:33:24'),
(4, '2026-08-08', 220.00, 219.78, 203.50, 176.00, '{\"95.0%\":209,\"75.0%\":165}', '', '2026-08-08 04:02:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `action` (`action`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collateral_items`
--
ALTER TABLE `collateral_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `item_type` (`item_type`),
  ADD KEY `rk_number` (`rk_number`);

--
-- Indexes for table `collateral_item_photos`
--
ALTER TABLE `collateral_item_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `mobile` (`mobile`),
  ADD KEY `full_name` (`full_name`),
  ADD KEY `account_number_2` (`account_number`);

--
-- Indexes for table `customer_documents`
--
ALTER TABLE `customer_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `database_backups`
--
ALTER TABLE `database_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `gold_rates`
--
ALTER TABLE `gold_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rate_date` (`rate_date`);

--
-- Indexes for table `interest_history`
--
ALTER TABLE `interest_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_number` (`loan_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `loan_number_2` (`loan_number`),
  ADD KEY `status` (`status`),
  ADD KEY `loan_date` (`loan_date`);

--
-- Indexes for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `entry_date` (`entry_date`),
  ADD KEY `entry_type` (`entry_type`),
  ADD KEY `idx_ledger_payment_id` (`payment_id`);

--
-- Indexes for table `loan_topups`
--
ALTER TABLE `loan_topups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `payment_date` (`payment_date`),
  ADD KEY `receipt_number_2` (`receipt_number`);

--
-- Indexes for table `racks`
--
ALTER TABLE `racks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rack_number` (`rack_number`);

--
-- Indexes for table `rack_slots`
--
ALTER TABLE `rack_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rack_slot` (`rack_id`,`slot_number`),
  ADD KEY `idx_slot_name` (`slot_name`);

--
-- Indexes for table `rate_karat_presets`
--
ALTER TABLE `rate_karat_presets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `silver_rates`
--
ALTER TABLE `silver_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rate_date` (`rate_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collateral_items`
--
ALTER TABLE `collateral_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collateral_item_photos`
--
ALTER TABLE `collateral_item_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_documents`
--
ALTER TABLE `customer_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `database_backups`
--
ALTER TABLE `database_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gold_rates`
--
ALTER TABLE `gold_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `interest_history`
--
ALTER TABLE `interest_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_topups`
--
ALTER TABLE `loan_topups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `racks`
--
ALTER TABLE `racks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rack_slots`
--
ALTER TABLE `rack_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `rate_karat_presets`
--
ALTER TABLE `rate_karat_presets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `silver_rates`
--
ALTER TABLE `silver_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collateral_items`
--
ALTER TABLE `collateral_items`
  ADD CONSTRAINT `collateral_items_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `collateral_item_photos`
--
ALTER TABLE `collateral_item_photos`
  ADD CONSTRAINT `collateral_item_photos_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `collateral_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_documents`
--
ALTER TABLE `customer_documents`
  ADD CONSTRAINT `customer_documents_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `database_backups`
--
ALTER TABLE `database_backups`
  ADD CONSTRAINT `database_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interest_history`
--
ALTER TABLE `interest_history`
  ADD CONSTRAINT `interest_history_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  ADD CONSTRAINT `loan_guarantors_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  ADD CONSTRAINT `loan_ledger_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_topups`
--
ALTER TABLE `loan_topups`
  ADD CONSTRAINT `loan_topups_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `rack_slots`
--
ALTER TABLE `rack_slots`
  ADD CONSTRAINT `fk_rack_slots_rack` FOREIGN KEY (`rack_id`) REFERENCES `racks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
