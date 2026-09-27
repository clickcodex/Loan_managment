-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 20, 2026 at 05:27 AM
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
-- Database: `u272390999_TransactionMan`
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
(1, 'admin', '$2y$10$AJlDbYOTuQvCEwXn8ERzu.vJPutbqkaVYuHKuEdxLaytR41EFW2ku', 'System Administrator', 'admin@example.com', NULL, NULL, '3b56af2ef5f817af568adab55770eb4ebce124493d61fbcbe4d47d8e2d69bba7', 'tgiqrkjetqai7qr8vj4va6ceno', '2026-08-19 14:09:50', '2026-07-30 05:59:28', '2026-08-19 14:09:50');

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
(1, 'Admin Login Success', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-07-30 06:01:42'),
(2, 'Customer Created', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"id\":2,\"customer_id\":\"CUST-2026-0002\",\"full_name\":\"Vishal\",\"mobile\":\"9876543210\"}', 'Created new customer profile', '2026-07-30 06:14:52'),
(3, 'Loan Created', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"loan_id\":2,\"loan_number\":\"LMS-2026-0002\",\"customer\":\"Vishal\",\"principal_amount\":10000,\"security_type\":\"Gold Secured\"}', 'Created new loan account', '2026-07-30 06:17:20'),
(4, 'Payment Collected', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"payment_id\":5,\"receipt_number\":\"REC-2026-0005\",\"loan_number\":\"LMS-2026-0002\",\"amount\":1000,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-07-30 06:29:42'),
(5, 'Customer Document Uploaded', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"customer_id\":2,\"type\":\"Customer Photo\",\"file\":\"bytelasb infotech logo 2.png\"}', 'Uploaded KYC document', '2026-07-30 06:47:29'),
(6, 'Backup Generated', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"id\":\"2\",\"filename\":\"backup_2026-07-30_08-58-24.sql\",\"filepath\":\"C:\\\\xampp\\\\htdocs\\\\TransactionManagement\\\\storage\\\\backups\\\\backup_2026-07-30_08-58-24.sql\",\"file_size\":36045,\"status\":\"Success\",\"created_at\":\"2026-07-30 08:58:24\"}', 'Generated manual database backup', '2026-07-30 06:58:24'),
(7, 'Backup Generated', 1, '::1', '6pk6g77qk7gbrgeov01v0p03mk', NULL, '{\"id\":\"3\",\"filename\":\"backup_2026-07-30_08-58-31.sql\",\"filepath\":\"C:\\\\xampp\\\\htdocs\\\\TransactionManagement\\\\storage\\\\backups\\\\backup_2026-07-30_08-58-31.sql\",\"file_size\":36782,\"status\":\"Success\",\"created_at\":\"2026-07-30 08:58:31\"}', 'Generated manual database backup', '2026-07-30 06:58:31'),
(8, 'Customer Profile Updated', 1, '127.0.0.1', NULL, '{\"full_name\":\"Ramesh Kumar\",\"mobile\":\"9876543210\",\"address\":\"123 Main Street\",\"interest_rate\":18,\"status\":\"Active\"}', '{\"full_name\":\"Ramesh Kumar\",\"mobile\":\"9123456789\",\"address\":\"456 Commercial Road\",\"interest_rate\":15,\"status\":\"Active\"}', 'Field-level diff verification test', '2026-07-30 07:09:37'),
(9, 'Customer Profile Updated', 1, '127.0.0.1', NULL, '{\"full_name\":\"Ramesh Kumar\",\"mobile\":\"9876543210\",\"address\":\"123 Main Street\",\"interest_rate\":18,\"status\":\"Active\"}', '{\"full_name\":\"Ramesh Kumar\",\"mobile\":\"9123456789\",\"address\":\"456 Commercial Road\",\"interest_rate\":15,\"status\":\"Active\"}', 'Field-level diff verification test', '2026-07-30 07:10:31'),
(10, 'Admin Login Failed', 1, '2405:201:301c:e155:102f:b8ad:a05e:ead1', 'jtfihj17cbf4b9ibjjm7lp4prn', NULL, '{\"username\":\"admin\"}', 'Invalid credentials attempt', '2026-07-30 09:00:51'),
(11, 'Admin Login Failed', 1, '2405:201:301c:e155:102f:b8ad:a05e:ead1', 'jtfihj17cbf4b9ibjjm7lp4prn', NULL, '{\"username\":\"admin\"}', 'Invalid credentials attempt', '2026-07-30 09:01:54'),
(12, 'Admin Login Failed', 1, '2405:201:301c:e155:102f:b8ad:a05e:ead1', 'jtfihj17cbf4b9ibjjm7lp4prn', NULL, '{\"username\":\"admin\"}', 'Invalid credentials attempt', '2026-07-30 09:02:04'),
(13, 'Admin Login Success', 1, '2405:201:301c:e155:102f:b8ad:a05e:ead1', 'en2chs76flc8h7gnii47rlvs6q', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-07-30 09:03:41'),
(14, 'Database Restored', 1, '2405:201:301c:e155:102f:b8ad:a05e:ead1', 'en2chs76flc8h7gnii47rlvs6q', NULL, '{\"filename\":\"backup_2026-07-30_10-38-31.sql\"}', 'Restored database from backup file', '2026-07-30 10:39:36'),
(15, 'Database Restored', 1, '2405:201:301c:e155:102f:b8ad:a05e:ead1', 'en2chs76flc8h7gnii47rlvs6q', NULL, '{\"uploaded_file\":\"backup_2026-07-30_10-39-55.sql\"}', 'Restored database from uploaded .sql file', '2026-07-30 10:40:29'),
(16, 'Admin Login Success', 1, '2405:201:301c:e155:3d12:34f9:e95b:95a9', '90hug6g1tk4nrs64e5rvp4pg30', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-01 09:04:47'),
(17, 'Admin Login Success', 1, '2405:201:301c:e155:1d84:87c5:20ff:ab85', 'n9p563kke01va0mu45ekg11hv9', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-03 07:53:56'),
(18, 'Admin Logout', 1, '2405:201:301c:e155:1d84:87c5:20ff:ab85', 'n9p563kke01va0mu45ekg11hv9', NULL, NULL, 'User logged out', '2026-08-03 08:33:54'),
(19, 'Admin Login Success', 1, '2409:40c4:4:f4ce:9d5f:639c:dcf9:12f9', 'eudup612ir05dt45mdmpp1nkvs', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-03 09:11:34'),
(20, 'Customer Created', 1, '2409:40c4:4:f4ce:9d5f:639c:dcf9:12f9', 'eudup612ir05dt45mdmpp1nkvs', NULL, '{\"id\":3,\"customer_id\":\"CUST-2026-0003\",\"full_name\":\"aakash\",\"mobile\":\"266666666\"}', 'Created new customer profile', '2026-08-03 09:23:02'),
(21, 'Loan Created', 1, '2409:40c4:4:f4ce:9d5f:639c:dcf9:12f9', 'eudup612ir05dt45mdmpp1nkvs', NULL, '{\"loan_id\":3,\"loan_number\":\"LMS-2026-0003\",\"customer\":\"aakash\",\"principal_amount\":50000,\"security_type\":\"Gold Secured\"}', 'Created new loan account with video proof support', '2026-08-03 09:25:01'),
(22, 'Payment Collected', 1, '2409:40c4:4:f4ce:1dc5:89aa:47d9:16b4', 'eudup612ir05dt45mdmpp1nkvs', NULL, '{\"payment_id\":6,\"receipt_number\":\"REC-2026-0006\",\"loan_number\":\"LMS-2026-0003\",\"amount\":30000,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-03 09:56:30'),
(23, 'Admin Login Success', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-03 10:52:44'),
(24, 'Payment Collected', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"payment_id\":7,\"receipt_number\":\"REC-2026-0007\",\"loan_number\":\"LMS-2026-0003\",\"amount\":20000,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-03 10:56:34'),
(25, 'Customer Created', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"id\":4,\"customer_id\":\"CUST-2026-0004\",\"full_name\":\"Aalok patidar\",\"mobile\":\"9669903667\"}', 'Created new customer profile', '2026-08-03 11:01:22'),
(26, 'Loan Created', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"loan_id\":4,\"loan_number\":\"LMS-2026-0004\",\"customer\":\"Aalok patidar\",\"principal_amount\":3000,\"security_type\":\"Silver Secured\"}', 'Created new loan account with video proof support', '2026-08-03 11:05:03'),
(27, 'Loan Created', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"loan_id\":5,\"loan_number\":\"LMS-2026-0005\",\"customer\":\"Aalok patidar\",\"principal_amount\":50000,\"security_type\":\"Guarantor Secured\"}', 'Created new loan account with video proof support', '2026-08-03 11:21:40'),
(28, 'Gold Rates Updated', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"rate_24k\":140000,\"rate_22k\":128338,\"rate_18k\":105000,\"rate_14k\":81662,\"custom_rates\":{\"23K\":134162,\"21K\":122500,\"20K\":116662,\"10K\":58338,\"21\":112000},\"rate_date\":\"2026-08-03\",\"remarks\":\"\",\"created_by\":1}', 'Updated daily multi-karat gold rates', '2026-08-03 11:33:10'),
(29, 'Silver Rates Updated', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, '{\"rate_999\":210000,\"rate_925\":194250,\"rate_800\":168000,\"custom_rates\":{\"95.0%\":199500,\"75.0%\":157500},\"rate_date\":\"2026-08-03\",\"remarks\":\"\",\"created_by\":1}', 'Updated daily multi-karat silver rates', '2026-08-03 11:33:24'),
(30, 'Admin Logout', 1, '2409:40c4:4:f4ce:8dd:b6ff:fee8:2f54', 'tjtrm3nufupfu9r4rbsk4ueqst', NULL, NULL, 'User logged out', '2026-08-03 11:36:01'),
(31, 'Admin Login Success', 1, '2409:40c4:3d:79d7:201b:1079:3c8c:4fce', 'tirpci7883pqb7qjdc0nj0f286', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-04 07:30:10'),
(32, 'Payment Collected', 1, '2409:40c4:3d:79d7:201b:1079:3c8c:4fce', 'tirpci7883pqb7qjdc0nj0f286', NULL, '{\"payment_id\":8,\"receipt_number\":\"REC-2026-0008\",\"loan_number\":\"LMS-2026-0005\",\"amount\":10000,\"mode\":\"UPI\"}', 'Collected payment & updated ledger balance', '2026-08-04 07:44:16'),
(33, 'Admin Login Success', 1, '2409:40c4:3d:79d7:2ced:1aff:feb9:146', 'jrh049knquk6mo1qr8e2a5t466', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-04 12:56:39'),
(34, 'Admin Login Success', 1, '2409:40c4:ea:56c8:90d1:62ff:fece:d9a1', 'hnh666hov4i9th9sca4m6j3t4g', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-06 03:53:42'),
(35, 'Admin Login Success', 1, '2405:201:301c:e155:ca9:92d4:1aa4:46bb', 'cctit4cr09a2bi55pko1uql6ve', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-06 06:18:18'),
(36, 'Gold Rates Updated', 1, '2405:201:301c:e155:ca9:92d4:1aa4:46bb', 'cctit4cr09a2bi55pko1uql6ve', NULL, '{\"rate_24k\":13986,\"rate_22k\":12833.8,\"rate_18k\":10500,\"rate_14k\":8166.2,\"custom_rates\":{\"24K\":13986,\"23K\":13416.2,\"21K\":12250,\"20K\":11666.2,\"10K\":5833.8,\"21\":11200},\"rate_date\":\"2026-08-06\",\"remarks\":\"\",\"created_by\":1}', 'Updated daily multi-karat gold rates', '2026-08-06 06:44:49'),
(37, 'Admin Logout', 1, '2405:201:301c:e155:ca9:92d4:1aa4:46bb', 'cctit4cr09a2bi55pko1uql6ve', NULL, NULL, 'User logged out', '2026-08-06 06:53:25'),
(38, 'Admin Login Success', 1, '2405:201:301c:e155:ca9:92d4:1aa4:46bb', 'lrjokrf87ljb6if8q9nijr40sm', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-06 06:54:14'),
(39, 'Admin Login Success', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-06 06:54:39'),
(40, 'Loan Created', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"loan_id\":7,\"loan_number\":\"LMS-2026-0006\",\"customer\":\"Vishal\",\"principal_amount\":10000,\"security_type\":\"Gold Secured\"}', 'Created new loan account with video proof support', '2026-08-06 06:58:18'),
(41, 'Loan Top-Up Applied', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"loan_id\":7,\"loan_number\":\"LMS-2026-0006\",\"customer\":\"Vishal\",\"topup_amount\":500,\"topup_date\":\"2026-08-06\",\"reason\":\"\"}', 'Additional disbursement (top-up) applied to existing running loan', '2026-08-06 07:02:40'),
(42, 'Payment Collected', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"payment_id\":9,\"receipt_number\":\"REC-2026-0009\",\"loan_number\":\"LMS-2026-0006\",\"amount\":9000,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-06 07:03:09'),
(43, 'Payment Collected', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"payment_id\":10,\"receipt_number\":\"REC-2026-0010\",\"loan_number\":\"LMS-2026-0006\",\"amount\":500,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-06 07:04:17'),
(44, 'Payment Collected', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"payment_id\":12,\"receipt_number\":\"REC-2026-0011\",\"loan_number\":\"LMS-2026-0006\",\"amount\":500,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-06 07:05:18'),
(45, 'Customer Created', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"id\":5,\"customer_id\":\"CUST-2026-0005\",\"full_name\":\"phunsukh wangdu\",\"mobile\":\"9874561230\"}', 'Created new customer profile', '2026-08-06 07:12:49'),
(46, 'Loan Created', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"loan_id\":8,\"loan_number\":\"LMS-2026-0008\",\"customer\":\"phunsukh wangdu\",\"principal_amount\":1000000,\"security_type\":\"Gold Secured\"}', 'Created new loan account with video proof support', '2026-08-06 07:13:58'),
(47, 'Loan Top-Up Applied', 1, '2405:201:301c:e155:304a:1a8:6aaf:8f65', 'bg0u5cig56dcv61ard770hikfv', NULL, '{\"loan_id\":8,\"loan_number\":\"LMS-2026-0008\",\"customer\":\"phunsukh wangdu\",\"topup_amount\":200000,\"topup_date\":\"2026-08-06\",\"reason\":\"\"}', 'Additional disbursement (top-up) applied to existing running loan', '2026-08-06 07:15:37'),
(48, 'Admin Login Success', 1, '2405:201:301c:e155:e0e4:98cc:ea86:7a52', 'ncr9agtrjd1nho74lb8nisnku2', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-07 08:51:37'),
(49, 'Backup Generated', 1, '2405:201:301c:e155:e0e4:98cc:ea86:7a52', 'ncr9agtrjd1nho74lb8nisnku2', NULL, '{\"id\":\"4\",\"filename\":\"backup_2026-08-07_08-51-51.sql\",\"filepath\":\"\\/home\\/u272390999\\/domains\\/greenyellow-dotterel-241538.hostingersite.com\\/public_html\\/storage\\/backups\\/backup_2026-08-07_08-51-51.sql\",\"file_size\":71815,\"status\":\"Success\",\"created_at\":\"2026-08-07 08:51:51\"}', 'Generated manual database backup', '2026-08-07 08:51:51'),
(50, 'Admin Login Success', 1, '2409:40c4:e4:51cb:b8a0:34ff:fef7:1e6e', 'eisnuovbgjdtlpth5nbionjv38', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-07 14:24:26'),
(51, 'Admin Login Success', 1, '2409:40c4:f6:70b8:d468:18ff:fe1e:5bcd', 'k8c9jmp3v0uc08ne6pbrrtnhbn', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-08 03:59:50'),
(52, 'Gold Rates Updated', 1, '2409:40c4:f6:70b8:d468:18ff:fe1e:5bcd', 'k8c9jmp3v0uc08ne6pbrrtnhbn', NULL, '{\"rate_24k\":139860,\"rate_22k\":128338,\"rate_18k\":105000,\"rate_14k\":81662,\"custom_rates\":{\"24K\":139860,\"23K\":134162,\"21K\":122500,\"20K\":116662,\"10K\":58338,\"21\":112000,\"24\":138600},\"rate_date\":\"2026-08-08\",\"remarks\":\"\",\"created_by\":1}', 'Updated daily multi-karat gold rates', '2026-08-08 04:02:20'),
(53, 'Silver Rates Updated', 1, '2409:40c4:f6:70b8:d468:18ff:fe1e:5bcd', 'k8c9jmp3v0uc08ne6pbrrtnhbn', NULL, '{\"rate_999\":219780,\"rate_925\":203500,\"rate_800\":176000,\"custom_rates\":{\"95.0%\":209000,\"75.0%\":165000},\"rate_date\":\"2026-08-08\",\"remarks\":\"\",\"created_by\":1}', 'Updated daily multi-karat silver rates', '2026-08-08 04:02:50'),
(54, 'Silver Rates Updated', 1, '2409:40c4:f6:70b8:d468:18ff:fe1e:5bcd', 'k8c9jmp3v0uc08ne6pbrrtnhbn', NULL, '{\"rate_999\":219.78,\"rate_925\":203.5,\"rate_800\":176,\"custom_rates\":{\"95.0%\":209,\"75.0%\":165},\"rate_date\":\"2026-08-08\",\"remarks\":\"\",\"created_by\":1}', 'Updated daily multi-karat silver rates', '2026-08-08 04:09:32'),
(55, 'Loan Created', 1, '2409:40c4:f6:70b8:d468:18ff:fe1e:5bcd', 'k8c9jmp3v0uc08ne6pbrrtnhbn', NULL, '{\"loan_id\":9,\"loan_number\":\"LMS-2026-0009\",\"customer\":\"phunsukh wangdu\",\"principal_amount\":20000,\"security_type\":\"Silver Secured\"}', 'Created new loan account with video proof support', '2026-08-08 04:11:09'),
(56, 'Admin Login Success', 1, '2409:40c4:f6:70b8:346b:5e09:a8e4:5c32', 'r3b3uqc6sdbujpn0co476o49q1', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-08 06:56:46'),
(57, 'Admin Login Success', 1, '2409:40c4:303a:6c20:3c81:d522:7ca2:8290', 'h8ao3ruc1dsps6ej7nuqh8oino', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-12 06:35:05'),
(58, 'Customer Created', 1, '2409:40c4:303a:6c20:3c81:d522:7ca2:8290', 'h8ao3ruc1dsps6ej7nuqh8oino', NULL, '{\"id\":6,\"customer_id\":\"CUST-2026-0006\",\"full_name\":\"aalok asutos\",\"mobile\":\"266600000\"}', 'Created new customer profile', '2026-08-12 06:56:00'),
(59, 'Loan Created', 1, '2409:40c4:303a:6c20:3c81:d522:7ca2:8290', 'h8ao3ruc1dsps6ej7nuqh8oino', NULL, '{\"loan_id\":10,\"loan_number\":\"LMS-2026-0010\",\"customer\":\"aalok asutos\",\"principal_amount\":50000,\"security_type\":\"Silver Secured\"}', 'Created new loan account with video proof support', '2026-08-12 06:57:15'),
(60, 'Loan Created', 1, '2409:40c4:303a:6c20:3c81:d522:7ca2:8290', 'h8ao3ruc1dsps6ej7nuqh8oino', NULL, '{\"loan_id\":12,\"loan_number\":\"LMS-2026-0011\",\"customer\":\"aalok asutos\",\"principal_amount\":50000,\"security_type\":\"Silver Secured\"}', 'Created new loan account with video proof support', '2026-08-12 07:02:14'),
(61, 'Payment Collected', 1, '2409:40c4:303a:6c20:3c81:d522:7ca2:8290', 'h8ao3ruc1dsps6ej7nuqh8oino', NULL, '{\"payment_id\":13,\"receipt_number\":\"REC-2026-0013\",\"loan_number\":\"LMS-2026-0011\",\"amount\":50000,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-12 07:08:47'),
(62, 'Admin Login Success', 1, '49.43.0.141', '21arljpakple15gmmteb5t7295', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-12 12:47:42'),
(63, 'Admin Login Success', 1, '2409:40c4:37:779f:b8ef:59ff:fe64:b70b', 'mefvjcpkk3l5dndm7okrh405e6', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-14 07:33:42'),
(64, 'Admin Login Success', 1, '2409:40c4:18d:9d83:742a:58ff:fe07:a5e1', 'k63mgj9i1eft0kungdeba5n5pe', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-16 09:46:25'),
(65, 'Payment Collected', 1, '2409:40c4:18d:9d83:742a:58ff:fe07:a5e1', 'k63mgj9i1eft0kungdeba5n5pe', NULL, '{\"payment_id\":14,\"receipt_number\":\"REC-2026-0014\",\"loan_number\":\"LMS-2026-0011\",\"amount\":50000,\"mode\":\"Cash\"}', 'Collected payment & updated ledger balance', '2026-08-16 09:59:11'),
(66, 'Admin Login Success', 1, '2405:201:301c:e155:3c68:c6e4:e16:1791', 'hudsrottg0scmdsdaevpu08gg0', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-17 12:19:08'),
(67, 'Admin Login Success', 1, '2409:40c4:18d:9d83:742a:58ff:fe07:a5e1', 'at62um8corjc1cqf0gq9q2jub1', NULL, '{\"username\":\"Admin\"}', 'User logged in successfully', '2026-08-17 12:22:35'),
(68, 'Backup Generated', 1, '2409:40c4:18d:9d83:742a:58ff:fe07:a5e1', 'at62um8corjc1cqf0gq9q2jub1', NULL, '{\"id\":\"5\",\"filename\":\"backup_2026-08-17_12-22-46.sql\",\"filepath\":\"\\/home\\/u272390999\\/domains\\/greenyellow-dotterel-241538.hostingersite.com\\/public_html\\/storage\\/backups\\/backup_2026-08-17_12-22-46.sql\",\"file_size\":88252,\"status\":\"Success\",\"created_at\":\"2026-08-17 12:22:46\"}', 'Generated manual database backup', '2026-08-17 12:22:46'),
(69, 'Rack Auto-Assigned', 1, '2405:201:301c:e155:3c68:c6e4:e16:1791', 'hudsrottg0scmdsdaevpu08gg0', NULL, '{\"count\":9}', 'Auto-assigned rack slots to unassigned collateral items', '2026-08-17 12:25:27'),
(70, 'Admin Login Success', 1, '2409:40c4:10ae:485a:bcee:98ff:fe70:e196', 'lo9bmn231r94q6134f31qpt848', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-18 14:16:56'),
(71, 'Admin Login Failed', 1, '2409:40c4:41:1530:f0c1:b0a2:1fc5:a82e', 'scg49ledpie1plgg3qfh348i7s', NULL, '{\"username\":\"admin\"}', 'Invalid credentials attempt', '2026-08-19 07:03:00'),
(72, 'Admin Login Success', 1, '2409:40c4:41:1530:f0c1:b0a2:1fc5:a82e', '1ku6cb85chtp9rv3k2hpvertrf', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-19 07:03:22'),
(73, 'Customer Created', 1, '2409:40c4:41:1530:f0c1:b0a2:1fc5:a82e', '1ku6cb85chtp9rv3k2hpvertrf', NULL, '{\"id\":7,\"customer_id\":\"CUST-2026-0007\",\"full_name\":\"jivan\",\"mobile\":\"123456789\"}', 'Created new customer profile', '2026-08-19 07:11:26'),
(74, 'Loan Created', 1, '2409:40c4:41:1530:f0c1:b0a2:1fc5:a82e', '1ku6cb85chtp9rv3k2hpvertrf', NULL, '{\"loan_id\":13,\"loan_number\":\"LMS-2026-0013\",\"customer\":\"jivan\",\"principal_amount\":100000,\"security_type\":\"Gold Secured\"}', 'Created new loan account with video proof support', '2026-08-19 07:19:18'),
(75, 'Loan Top-Up Applied', 1, '2409:40c4:41:1530:f0c1:b0a2:1fc5:a82e', '1ku6cb85chtp9rv3k2hpvertrf', NULL, '{\"loan_id\":13,\"loan_number\":\"LMS-2026-0013\",\"customer\":\"jivan\",\"topup_amount\":8000,\"topup_date\":\"2026-08-19\",\"reason\":\"school\"}', 'Additional disbursement (top-up) applied to existing running loan', '2026-08-19 07:26:04'),
(76, 'Loan Created', 1, '2409:40c4:41:1530:f0c1:b0a2:1fc5:a82e', '1ku6cb85chtp9rv3k2hpvertrf', NULL, '{\"loan_id\":14,\"loan_number\":\"LMS-2026-0014\",\"customer\":\"jivan\",\"principal_amount\":80000,\"security_type\":\"Silver Secured\"}', 'Created new loan account with video proof support', '2026-08-19 07:51:02'),
(77, 'Admin Login Success', 1, '2409:40c4:1ac:71ef:1130:2b8:a16f:bd31', 'd739sc4j3a7868mu6h89pia6hb', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-19 12:43:08'),
(78, 'Admin Login Success', 1, '2409:40c4:301a:c986:e00f:b3ff:fece:2cf7', 'tgiqrkjetqai7qr8vj4va6ceno', NULL, '{\"username\":\"admin\"}', 'User logged in successfully', '2026-08-19 14:09:50');

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

--
-- Dumping data for table `backups`
--

INSERT INTO `backups` (`id`, `filename`, `file_size`, `backup_type`, `status`, `created_at`) VALUES
(1, 'backup_2026-07-30_08-58-07.sql', 35796, 'Manual', 'Success', '2026-07-30 06:58:07'),
(2, 'backup_2026-07-30_08-58-24.sql', 36045, 'Manual', 'Success', '2026-07-30 06:58:24'),
(3, 'backup_2026-07-30_08-58-31.sql', 36782, 'Manual', 'Success', '2026-07-30 06:58:31'),
(4, 'backup_2026-08-07_08-51-51.sql', 71815, 'Manual', 'Success', '2026-08-07 08:51:51'),
(5, 'backup_2026-08-17_12-22-46.sql', 88252, 'Manual', 'Success', '2026-08-17 12:22:46');

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

--
-- Dumping data for table `collateral_items`
--

INSERT INTO `collateral_items` (`id`, `loan_id`, `item_type`, `item_name`, `quantity`, `gross_weight`, `stone_weight`, `net_weight`, `purity_preset`, `purity_percentage`, `market_value`, `manual_market_value_override`, `loan_value`, `rk_number`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'GOLD', '22K Gold Chain', 1, 12.500, 0.500, 12.000, '22K', 91.67, 72600.00, NULL, 0.00, 'RK-G101', 'Hallmarked 22K chain', '2026-07-30 06:08:15', '2026-07-30 06:08:15'),
(2, 1, 'SILVER', 'Silver Coin Set', 2, 100.000, 0.000, 100.000, '99.9%', 99.90, 8800.00, NULL, 0.00, 'RK-S202', '999 fine silver coins', '2026-07-30 06:08:15', '2026-07-30 06:08:15'),
(3, 2, 'GOLD', 'Gold Ring', 1, 10.000, 1.000, 0.000, '18K', 91.67, 0.00, 50000.00, 0.00, 'RK-01', '', '2026-07-30 06:17:20', '2026-07-30 06:17:20'),
(4, 2, 'SILVER', 'Silver Chain', 1, 50.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 1', '', '2026-07-30 06:17:20', '2026-08-17 12:25:27'),
(5, 1, 'GOLD', 'Custom 87.5% Purity Gold Bangle', 1, 15.000, 0.000, 15.000, 'Custom', 87.50, 99750.00, NULL, 0.00, 'RK-G999', 'Custom purity test', '2026-07-30 06:34:27', '2026-07-30 06:34:27'),
(6, 7, 'GOLD', 'Gold Ring', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, 'Rack 1 - Slot 2', '', '2026-08-06 06:58:18', '2026-08-17 12:25:27'),
(7, 7, 'SILVER', 'Silver Chain', 1, 50.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 3', '', '2026-08-06 06:58:18', '2026-08-17 12:25:27'),
(8, 8, 'GOLD', 'Gold Ring', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, 'Rack 1 - Slot 4', '', '2026-08-06 07:13:58', '2026-08-17 12:25:27'),
(9, 8, 'SILVER', 'Silver Chain', 1, 50.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 5', '', '2026-08-06 07:13:58', '2026-08-17 12:25:27'),
(10, 9, 'GOLD', 'Gold Ring', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, 'Rack 1 - Slot 6', '', '2026-08-08 04:11:09', '2026-08-17 12:25:27'),
(11, 9, 'SILVER', 'Silver Chain', 1, 50.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 7', '', '2026-08-08 04:11:09', '2026-08-17 12:25:27'),
(12, 10, 'GOLD', 'Gold Ring', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, 'Rack 1 - Slot 8', '', '2026-08-12 06:57:15', '2026-08-17 12:25:27'),
(13, 10, 'SILVER', 'Silver Chain', 1, 500.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 9', '', '2026-08-12 06:57:15', '2026-08-17 12:25:27'),
(14, 12, 'GOLD', 'Gold Ring', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, '', '', '2026-08-12 07:02:14', '2026-08-12 07:02:14'),
(15, 12, 'SILVER', 'sakli', 1, 500.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, '', '', '2026-08-12 07:02:14', '2026-08-12 07:02:14'),
(16, 13, 'GOLD', 'Gold Ring', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, 'Rack 1 - Slot 10', '', '2026-08-19 07:19:18', '2026-08-19 07:19:18'),
(17, 13, 'SILVER', 'Silver Chain', 1, 50.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 11', '', '2026-08-19 07:19:18', '2026-08-19 07:19:18'),
(18, 14, 'GOLD', 'patat', 1, 10.000, 0.000, 0.000, '22K', 91.67, 0.00, NULL, 0.00, 'Rack 1 - Slot 12', '', '2026-08-19 07:51:02', '2026-08-19 07:51:02'),
(19, 14, 'SILVER', 'sakli', 1, 500.000, 0.000, 0.000, '92.5%', 92.50, 0.00, NULL, 0.00, 'Rack 1 - Slot 13', '', '2026-08-19 07:51:02', '2026-08-19 07:51:02');

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

--
-- Dumping data for table `collateral_item_photos`
--

INSERT INTO `collateral_item_photos` (`id`, `item_id`, `file_path`, `original_name`, `created_at`) VALUES
(1, 2, 'uploads/items/test_gold_ring.jpg', 'gold_ring.jpg', '2026-07-30 06:12:46');

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
(1, 'CUST-2026-0001', 'ACC-1001', NULL, 'Ramesh Kumar', 'Suresh Kumar', '9876543210', '9876543211', '123456789012', 'ABCDE1234F', '12 MG Road', 'Central Market', 'Mumbai', 'Maharashtra', '400001', 'Vikram Singh', '9876543299', '45 Main Bazar, Mumbai', 'Test customer profile for verification', 'Active', '2026-07-30 06:04:38', '2026-07-30 06:04:38'),
(2, 'CUST-2026-0002', 'Acc-001', 'uploads/customers/documents/cust_6a6af381a9b13_1785394049.png', 'Vishal', 'Vishal', '9876543210', '9876543210', '123456987321', 'ABCDE123', 'L-112,white board', 'asdf', 'indore', 'Madhya Pradesh', '452001', 'hirendra', '9876543210', 'L-112,Dongre Nagar, Ratlam, Madhya Pradesh 457001, India, LIG-112, LIG-112', 'test', 'Active', '2026-07-30 06:14:52', '2026-07-30 06:50:17'),
(3, 'CUST-2026-0003', 'ACC-0003', NULL, 'aakash', 'jain', '266666666', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-03 09:23:02', '2026-08-03 09:23:02'),
(4, 'CUST-2026-0004', 'ACC-0004', 'uploads/photos/doc_6a707502f156f_1785754882.jpg', 'Aalok patidar', 'Ashok', '9669903667', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-03 11:01:22', '2026-08-03 11:01:22'),
(5, 'CUST-2026-0005', 'Acc-5', 'uploads/photos/doc_6a7433f18748d_1786000369.jpeg', 'phunsukh wangdu', 'kelash wangdu', '9874561230', '9876543210', '123245641234', 'FMAPM5202A', 'Nagalend Do batti', 'nagalend', 'Nagalend', 'Nagalend', '457002', 'Raju Shrivastav', '7894561230', 'ICE Clg', 'they will pay but i will not take Guaranty', 'Active', '2026-08-06 07:12:49', '2026-08-06 07:12:49'),
(6, 'CUST-2026-0006', 'Acc-6', NULL, 'aalok asutos', 'Patidar', '266600000', NULL, NULL, NULL, NULL, 'kws', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-12 06:56:00', '2026-08-12 06:56:00'),
(7, 'CUST-2026-0007', 'Acc-7', 'uploads/photos/doc_6a85571e329d4_1787123486.jpg', 'jivan', 'mangu maidha andok', '123456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-08-19 07:11:26', '2026-08-19 07:11:26');

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

--
-- Dumping data for table `customer_documents`
--

INSERT INTO `customer_documents` (`id`, `customer_id`, `document_type`, `file_path`, `original_name`, `created_at`) VALUES
(1, 1, 'Aadhaar Card Scan', 'uploads/customers/documents/test_aadhaar.pdf', 'aadhaar_card.pdf', '2026-07-30 06:04:38'),
(2, 2, 'Customer Photo', 'uploads/customers/documents/cust_6a6af381a9b13_1785394049.png', 'bytelasb infotech logo 2.png', '2026-07-30 06:47:29'),
(3, 1, 'Aadhaar Card', 'uploads/documents/test_aadhaar_101.pdf', 'Customer_Aadhaar_Scan.pdf', '2026-07-30 07:15:58'),
(4, 1, 'Loan Sanction Video Proof (Disbursement) - Loan #L', 'uploads/videos/test_sanction.mp4', 'sanction_recording.mp4', '2026-07-30 08:34:16'),
(5, 1, 'Loan Closure Video Proof (Collateral Release) - Lo', 'uploads/videos/test_closure.mp4', 'closure_recording.mp4', '2026-07-30 08:34:16'),
(6, 1, 'Loan Sanction Video Proof (Disbursement) - Loan #L', 'uploads/videos/test_sanction.mp4', 'sanction_recording.mp4', '2026-07-30 08:46:17'),
(7, 1, 'Loan Closure Video Proof (Collateral Release) - Lo', 'uploads/videos/test_closure.mp4', 'closure_recording.mp4', '2026-07-30 08:46:17'),
(8, 2, 'Loan Sanction Video Proof (Disbursement) - Loan #L', 'uploads/videos/doc_6a74308aba13e_1785999498.mp4', '0804 (1).mp4', '2026-08-06 06:58:18');

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

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `loan_number`, `customer_id`, `loan_date`, `security_type`, `principal_amount`, `total_payable_amount`, `remaining_balance`, `interest_rate`, `interest_cycle`, `interest_method`, `compound_frequency`, `return_date`, `interest_due_date`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'LMS-2026-0001', 1, '2026-07-30', 'Gold + Silver + Guarantor', 50000.00, 0.00, 0.00, 18.00, '15 Days', 'Simple', 'Monthly', '2027-07-30', '2026-08-30', 'Closed', 'Verification test mixed collateral loan', '2026-07-30 06:08:15', '2026-07-30 06:21:24'),
(2, 'LMS-2026-0002', 2, '2026-07-30', 'Gold Secured', 10000.00, 0.00, 0.00, 5.00, '15 Days', 'Compound', 'Monthly', '2026-10-30', '2026-11-15', 'Running', NULL, '2026-07-30 06:17:20', '2026-07-30 06:17:20'),
(3, 'LMS-2026-0003', 3, '2026-08-03', 'Gold Secured', 50000.00, 0.00, 0.00, 24.00, '15 Days', 'Compound', 'Monthly', NULL, NULL, 'Closed', NULL, '2026-08-03 09:25:01', '2026-08-03 10:56:34'),
(4, 'LMS-2026-0004', 4, '2026-08-03', 'Silver Secured', 3000.00, 0.00, 0.00, 18.00, '15 Days', 'Compound', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-03 11:05:03', '2026-08-03 11:05:03'),
(5, 'LMS-2026-0005', 4, '2026-08-03', 'Guarantor Secured', 50000.00, 0.00, 0.00, 24.00, '15 Days', 'Compound', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-03 11:21:40', '2026-08-03 11:21:40'),
(7, 'LMS-2026-0006', 2, '2026-08-06', 'Gold Secured', 10500.00, 11025.00, 1025.00, 10.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-06 06:58:18', '2026-08-06 07:05:18'),
(8, 'LMS-2026-0008', 5, '2026-08-06', 'Gold Secured', 1200000.00, 1320000.00, 1320000.00, 20.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-06 07:13:58', '2026-08-06 07:15:37'),
(9, 'LMS-2026-0009', 5, '2026-08-08', 'Silver Secured', 20000.00, 20300.00, 20300.00, 3.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-08 04:11:09', '2026-08-08 04:11:09'),
(10, 'LMS-2026-0010', 6, '2026-08-12', 'Silver Secured', 50000.00, 50750.00, 50750.00, 3.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-12 06:57:15', '2026-08-12 06:57:15'),
(12, 'LMS-2026-0011', 6, '2015-01-12', 'Silver Secured', 50000.00, 50750.00, 0.00, 3.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Closed', NULL, '2026-08-12 07:02:14', '2026-08-16 09:59:11'),
(13, 'LMS-2026-0013', 7, '2026-08-19', 'Gold Secured', 108000.00, 109080.00, 109080.00, 2.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-19 07:19:18', '2026-08-19 07:26:04'),
(14, 'LMS-2026-0014', 7, '2026-08-19', 'Silver Secured', 80000.00, 81200.00, 81200.00, 3.00, '15 Days', 'Simple', 'Monthly', NULL, NULL, 'Running', NULL, '2026-08-19 07:51:02', '2026-08-19 07:51:02');

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

--
-- Dumping data for table `loan_guarantors`
--

INSERT INTO `loan_guarantors` (`id`, `loan_id`, `guarantor_name`, `guarantor_mobile`, `guarantor_address`, `relationship`, `photo`, `aadhaar`, `pan`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vikram Singh', '9876543299', '45 Main Market, Mumbai', 'Friend', NULL, NULL, NULL, 'Default customer guarantor', '2026-07-30 06:08:15', '2026-07-30 06:08:15'),
(2, 5, 'Ashutosh', '9685972222', '', '', NULL, '', '', '', '2026-08-03 11:21:40', '2026-08-03 11:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `loan_ledger`
--

CREATE TABLE `loan_ledger` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` varchar(50) NOT NULL DEFAULT 'Loan Issued',
  `description` varchar(255) NOT NULL,
  `debit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_ledger`
--

INSERT INTO `loan_ledger` (`id`, `loan_id`, `entry_date`, `entry_type`, `description`, `debit`, `credit`, `balance`, `created_by`, `created_at`) VALUES
(1, 1, '2026-07-30', 'Loan Issued', 'Initial loan disbursement (Gold + Silver + Guarantor)', 50000.00, 0.00, 50000.00, 1, '2026-07-30 06:08:15'),
(2, 2, '2026-07-30', 'Loan Issued', 'Initial loan disbursement (Gold Secured)', 10000.00, 0.00, 10000.00, 1, '2026-07-30 06:17:20'),
(3, 1, '2026-07-30', 'Interest Paid', 'Payment via UPI (REC-2026-0001) Ref: UPI/123456789/REF', 0.00, 5000.00, 45000.00, 1, '2026-07-30 06:21:24'),
(4, 1, '2026-07-30', 'Full Settlement', 'Payment via Bank Transfer (REC-2026-0002) Ref: UTR9988776655', 0.00, 45000.00, 0.00, 1, '2026-07-30 06:21:24'),
(5, 1, '2026-07-30', 'Closing Entry', 'Loan account fully settled and closed', 0.00, 0.00, 0.00, 1, '2026-07-30 06:21:24'),
(7, 1, '2026-07-30', '', 'Payment Received via Cash (REC-2026-0003)', 0.00, 1000.00, 0.00, 1, '2026-07-30 06:26:58'),
(8, 1, '2026-07-30', 'Closing Entry', 'Loan account fully settled and closed', 0.00, 0.00, 0.00, 1, '2026-07-30 06:26:58'),
(9, 2, '2026-07-30', '', 'Payment Received via Cash (REC-2026-0005)', 0.00, 1000.00, 9000.00, 1, '2026-07-30 06:29:42'),
(10, 3, '2026-08-03', 'Loan Issued', 'Initial loan disbursement (Gold Secured)', 50000.00, 0.00, 50000.00, 1, '2026-08-03 09:25:01'),
(11, 3, '2026-08-03', '', 'Payment Received via Cash (REC-2026-0006)', 0.00, 30000.00, 20000.00, 1, '2026-08-03 09:56:30'),
(12, 3, '2026-08-03', '', 'Payment Received via Cash (REC-2026-0007)', 0.00, 20000.00, 0.00, 1, '2026-08-03 10:56:34'),
(13, 3, '2026-08-03', 'Closing Entry', 'Loan account fully settled and closed', 0.00, 0.00, 0.00, 1, '2026-08-03 10:56:34'),
(14, 4, '2026-08-03', 'Loan Issued', 'Initial loan disbursement (Silver Secured)', 3000.00, 0.00, 3000.00, 1, '2026-08-03 11:05:03'),
(15, 5, '2026-08-03', 'Loan Issued', 'Initial loan disbursement (Guarantor Secured)', 50000.00, 0.00, 50000.00, 1, '2026-08-03 11:21:40'),
(16, 5, '2026-08-04', '', 'Payment Received via UPI (REC-2026-0008)', 0.00, 10000.00, 40000.00, 1, '2026-08-04 07:44:16'),
(17, 7, '2026-08-06', 'Loan Issued', 'Initial loan disbursement (Principal: ₹10,000.00 + 1st Cycle Int: ₹500.00)', 10500.00, 0.00, 10500.00, 1, '2026-08-06 06:58:18'),
(18, 7, '2026-08-06', 'Loan Top-Up', 'Additional disbursement (Top-Up: ₹500.00 + Int: ₹25.00)', 525.00, 0.00, 11025.00, 1, '2026-08-06 07:02:40'),
(19, 7, '2026-08-06', 'Payment Received', 'Payment Received via Cash (REC-2026-0009)', 0.00, 9000.00, 2025.00, 1, '2026-08-06 07:03:09'),
(20, 7, '2026-08-06', 'Payment Received', 'Payment Received via Cash (REC-2026-0010)', 0.00, 500.00, 1525.00, 1, '2026-08-06 07:04:17'),
(21, 7, '2026-08-06', 'Payment Received', 'Payment Received via Cash (REC-2026-0011)', 0.00, 500.00, 1025.00, 1, '2026-08-06 07:05:18'),
(22, 8, '2026-08-06', 'Loan Issued', 'Initial loan disbursement (Principal: ₹1,000,000.00 + 1st Cycle Int: ₹100,000.00)', 1100000.00, 0.00, 1100000.00, 1, '2026-08-06 07:13:58'),
(23, 8, '2026-08-06', 'Loan Top-Up', 'Additional disbursement (Top-Up: ₹200,000.00 + Int: ₹20,000.00)', 220000.00, 0.00, 1320000.00, 1, '2026-08-06 07:15:37'),
(24, 9, '2026-08-08', 'Loan Issued', 'Initial loan disbursement (Principal: ₹20,000.00 + 1st Cycle Int: ₹300.00)', 20300.00, 0.00, 20300.00, 1, '2026-08-08 04:11:09'),
(25, 10, '2026-08-12', 'Loan Issued', 'Initial loan disbursement (Principal: ₹50,000.00 + 1st Cycle Int: ₹750.00)', 50750.00, 0.00, 50750.00, 1, '2026-08-12 06:57:15'),
(26, 12, '2015-01-12', 'Loan Issued', 'Initial loan disbursement (Principal: ₹50,000.00 + 1st Cycle Int: ₹750.00)', 50750.00, 0.00, 50750.00, 1, '2026-08-12 07:02:14'),
(27, 12, '2026-08-12', 'Payment Received', 'Payment Received via Cash (REC-2026-0013)', 0.00, 50000.00, 750.00, 1, '2026-08-12 07:08:47'),
(28, 12, '2026-08-16', 'Payment Received', 'Payment Received via Cash (REC-2026-0014)', 0.00, 50000.00, 0.00, 1, '2026-08-16 09:59:11'),
(29, 12, '2026-08-16', 'Closing Entry', 'Loan account fully settled and closed', 0.00, 0.00, 0.00, 1, '2026-08-16 09:59:11'),
(30, 13, '2026-08-19', 'Loan Issued', 'Initial loan disbursement (Principal: ₹100,000.00 + 1st Cycle Int: ₹1,000.00)', 101000.00, 0.00, 101000.00, 1, '2026-08-19 07:19:18'),
(31, 13, '2026-08-19', 'Loan Top-Up', 'Additional disbursement (Top-Up: ₹8,000.00 + Int: ₹80.00) — school', 8080.00, 0.00, 109080.00, 1, '2026-08-19 07:26:04'),
(32, 14, '2026-08-19', 'Loan Issued', 'Initial loan disbursement (Principal: ₹80,000.00 + 1st Cycle Int: ₹1,200.00)', 81200.00, 0.00, 81200.00, 1, '2026-08-19 07:51:02');

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

--
-- Dumping data for table `loan_topups`
--

INSERT INTO `loan_topups` (`id`, `loan_id`, `topup_date`, `topup_amount`, `new_principal`, `reason`, `approved_by`, `created_at`) VALUES
(1, 7, '2026-08-06', 500.00, 10500.00, NULL, 1, '2026-08-06 07:02:40'),
(2, 8, '2026-08-06', 200000.00, 1200000.00, NULL, 1, '2026-08-06 07:15:37'),
(3, 13, '2026-08-19', 8000.00, 108000.00, 'school', 1, '2026-08-19 07:26:04');

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

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `receipt_number`, `loan_id`, `payment_date`, `payment_type`, `total_amount`, `interest_component`, `principal_component`, `penalty_component`, `payment_mode`, `reference_number`, `remarks`, `created_at`) VALUES
(1, 'REC-2026-0001', 1, '2026-07-30', 'Interest Payment', 5000.00, 5000.00, 0.00, 0.00, 'UPI', 'UPI/123456789/REF', 'Verification test interest payment', '2026-07-30 06:21:24'),
(2, 'REC-2026-0002', 1, '2026-07-30', 'Full Settlement', 45000.00, 0.00, 45000.00, 0.00, 'Bank Transfer', 'UTR9988776655', 'Verification test full settlement payment', '2026-07-30 06:21:24'),
(4, 'REC-2026-0003', 1, '2026-07-30', '', 1000.00, 1000.00, 0.00, 0.00, 'Cash', '', 'Simple payment test', '2026-07-30 06:26:58'),
(5, 'REC-2026-0005', 2, '2026-07-30', '', 1000.00, 1000.00, 0.00, 0.00, 'Cash', '', '', '2026-07-30 06:29:42'),
(6, 'REC-2026-0006', 3, '2026-08-03', '', 30000.00, 30000.00, 0.00, 0.00, 'Cash', '', '', '2026-08-03 09:56:30'),
(7, 'REC-2026-0007', 3, '2026-08-03', '', 20000.00, 20000.00, 0.00, 0.00, 'Cash', '', '', '2026-08-03 10:56:34'),
(8, 'REC-2026-0008', 5, '2026-08-04', '', 10000.00, 10000.00, 0.00, 0.00, 'UPI', '', 'abcd', '2026-08-04 07:44:16'),
(9, 'REC-2026-0009', 7, '2026-08-06', '', 9000.00, 9000.00, 0.00, 0.00, 'Cash', '', '', '2026-08-06 07:03:09'),
(10, 'REC-2026-0010', 7, '2026-08-06', '', 500.00, 500.00, 0.00, 0.00, 'Cash', '', '', '2026-08-06 07:04:17'),
(12, 'REC-2026-0011', 7, '2026-08-06', '', 500.00, 500.00, 0.00, 0.00, 'Cash', '', '', '2026-08-06 07:05:18'),
(13, 'REC-2026-0013', 12, '2026-08-12', '', 50000.00, 50000.00, 0.00, 0.00, 'Cash', '', '', '2026-08-12 07:08:47'),
(14, 'REC-2026-0014', 12, '2026-08-16', '', 50000.00, 50000.00, 0.00, 0.00, 'Cash', '', '', '2026-08-16 09:59:11');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

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
  ADD KEY `entry_type` (`entry_type`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `collateral_items`
--
ALTER TABLE `collateral_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `collateral_item_photos`
--
ALTER TABLE `collateral_item_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer_documents`
--
ALTER TABLE `customer_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `loan_topups`
--
ALTER TABLE `loan_topups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
