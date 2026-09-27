-- =============================================================================
-- LOAN MANAGEMENT SYSTEM (LMS v2.0) - DATABASE SCHEMA & SEED DATA
-- Database: MySQL 8.0+ / MariaDB
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. admin
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `security_question` VARCHAR(255) DEFAULT NULL,
  `security_answer_hash` VARCHAR(255) DEFAULT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `session_id` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default admin: admin / admin123
INSERT INTO `admin` (`id`, `username`, `password_hash`, `name`, `email`) VALUES
(1, 'admin', '$2y$10$AJlDbYOTuQvCEwXn8ERzu.vJPutbqkaVYuHKuEdxLaytR41EFW2ku', 'System Administrator', 'admin@example.com');

-- -----------------------------------------------------------------------------
-- 2. customers
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` VARCHAR(30) NOT NULL UNIQUE,
  `account_number` VARCHAR(30) DEFAULT NULL UNIQUE,
  `photo` VARCHAR(255) DEFAULT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `father_name` VARCHAR(100) DEFAULT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `alt_mobile` VARCHAR(20) DEFAULT NULL,
  `aadhaar` VARCHAR(20) DEFAULT NULL,
  `pan` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `village` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `pincode` VARCHAR(10) DEFAULT NULL,
  `guarantor_name` VARCHAR(100) DEFAULT NULL,
  `guarantor_mobile` VARCHAR(20) DEFAULT NULL,
  `guarantor_address` TEXT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Closed', 'Blocked') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`mobile`),
  INDEX (`full_name`),
  INDEX (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. customer_documents
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `customer_documents`;
CREATE TABLE `customer_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `document_type` VARCHAR(50) NOT NULL, -- Photo, Aadhaar, PAN, Address Proof, etc.
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. loans
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `loans`;
CREATE TABLE `loans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `loan_date` DATE NOT NULL,
  `security_type` ENUM(
    'Gold Secured', 
    'Silver Secured', 
    'Gold + Silver Secured', 
    'Guarantor Secured', 
    'Gold + Guarantor', 
    'Silver + Guarantor', 
    'Gold + Silver + Guarantor'
  ) NOT NULL,
  `principal_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_payable_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `interest_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `interest_method` ENUM('Simple', 'Compound') DEFAULT 'Simple',
  `compound_frequency` ENUM('Monthly', 'Quarterly', 'Half-Yearly', 'Yearly') DEFAULT 'Monthly',
  `return_date` DATE DEFAULT NULL,
  `interest_due_date` DATE DEFAULT NULL,
  `status` ENUM('Running', 'Closed', 'Overdue', 'Defaulted') DEFAULT 'Running',
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  INDEX (`loan_number`),
  INDEX (`status`),
  INDEX (`loan_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. loan_guarantors
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `loan_guarantors`;
CREATE TABLE `loan_guarantors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT NOT NULL UNIQUE,
  `guarantor_name` VARCHAR(100) NOT NULL,
  `guarantor_mobile` VARCHAR(20) DEFAULT NULL,
  `guarantor_address` TEXT DEFAULT NULL,
  `relationship` VARCHAR(50) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `aadhaar` VARCHAR(20) DEFAULT NULL,
  `pan` VARCHAR(20) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. collateral_items (Unified Gold + Silver Table)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `collateral_items`;
CREATE TABLE `collateral_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT NOT NULL,
  `item_type` ENUM('GOLD', 'SILVER') NOT NULL,
  `item_name` VARCHAR(100) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `gross_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  `stone_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  `net_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  `purity_preset` VARCHAR(50) DEFAULT NULL, -- e.g. 22K or 92.5%
  `purity_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `market_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `manual_market_value_override` DECIMAL(12,2) DEFAULT NULL,
  `loan_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `rk_number` VARCHAR(50) DEFAULT NULL, -- Physical rack reference
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  INDEX (`item_type`),
  INDEX (`rk_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. collateral_item_photos
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `collateral_item_photos`;
CREATE TABLE `collateral_item_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`) REFERENCES `collateral_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. loan_ledger
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `loan_ledger`;
CREATE TABLE `loan_ledger` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT NOT NULL,
  `payment_id` INT DEFAULT NULL,
  `entry_date` DATE NOT NULL,
  `entry_type` ENUM(
    'Loan Issued', 
    'Interest Added', 
    'Interest Paid', 
    'Principal Paid', 
    'Partial Principal', 
    'Full Settlement', 
    'Penalty Added', 
    'Penalty Paid', 
    'Adjustment', 
    'Closing Entry'
  ) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `debit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `credit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_by` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  INDEX (`payment_id`),
  INDEX (`entry_date`),
  INDEX (`entry_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. payments
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
  `loan_id` INT NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_type` ENUM(
    'Interest Payment', 
    'Principal Payment', 
    'Partial Principal Payment', 
    'Full Settlement', 
    'Advance Interest', 
    'Penalty Payment', 
    'Adjustment - Credit', 
    'Adjustment - Debit', 
    'Refund'
  ) NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `interest_component` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `principal_component` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `penalty_component` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_mode` ENUM('Cash', 'UPI', 'Bank Transfer', 'Cheque') NOT NULL DEFAULT 'Cash',
  `reference_number` VARCHAR(100) DEFAULT NULL, -- Cheque / UTR number
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE RESTRICT,
  INDEX (`payment_date`),
  INDEX (`receipt_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. interest_history
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `interest_history`;
CREATE TABLE `interest_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT NOT NULL,
  `calculation_date` DATE NOT NULL,
  `accrued_interest` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `interest_method` VARCHAR(50) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. gold_rates
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `gold_rates`;
CREATE TABLE `gold_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rate_date` DATE NOT NULL UNIQUE,
  `rate_24k` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate_22k` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate_18k` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate_14k` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `custom_rates` JSON DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default initial rates for today
INSERT INTO `gold_rates` (`rate_date`, `rate_24k`, `rate_22k`, `rate_18k`, `rate_14k`, `remarks`) VALUES
(CURDATE(), 7200.00, 6600.00, 5400.00, 4200.00, 'Initial default gold market rate');

-- -----------------------------------------------------------------------------
-- 12. silver_rates
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `silver_rates`;
CREATE TABLE `silver_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rate_date` DATE NOT NULL UNIQUE,
  `rate_999` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate_925` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rate_800` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `custom_rates` JSON DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default initial silver rates
INSERT INTO `silver_rates` (`rate_date`, `rate_999`, `rate_925`, `rate_800`, `remarks`) VALUES
(CURDATE(), 88.00, 81.40, 70.40, 'Initial default silver market rate');

-- -----------------------------------------------------------------------------
-- 13. rate_karat_presets
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rate_karat_presets`;
CREATE TABLE `rate_karat_presets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('GOLD', 'SILVER') NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `purity_percentage` DECIMAL(5,2) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rate_karat_presets` (`type`, `name`, `purity_percentage`, `display_order`) VALUES
('GOLD', '24K', 100.00, 1),
('GOLD', '22K', 91.67, 2),
('GOLD', '18K', 75.00, 3),
('GOLD', '14K', 58.33, 4),
('SILVER', '99.9%', 99.90, 1),
('SILVER', '92.5%', 92.50, 2),
('SILVER', '80.0%', 80.00, 3);

-- -----------------------------------------------------------------------------
-- 14. settings
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'Golden Trust Finance Co.'),
('company_logo', ''),
('company_address', '123 Main Financial Street, Capital City'),
('company_phone', '+91 98765 43210'),
('company_email', 'info@goldentrust.com'),
('company_gst', '27AAACG1234F1Z5'),
('currency_symbol', '₹'),
('timezone', 'Asia/Kolkata'),
('loan_number_format', 'LMS-{YEAR}-{0000}'),
('default_interest_rate', '18.00'),
('default_interest_method', 'Simple'),
('ltv_limit', '75.00'),
('backup_reminder_days', '7');

-- -----------------------------------------------------------------------------
-- 15. audit_logs
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action` VARCHAR(100) NOT NULL,
  `user_id` INT DEFAULT 1,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `session_id` VARCHAR(255) DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`action`),
  INDEX (`user_id`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. backups
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `backups`;
CREATE TABLE `backups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT DEFAULT 0,
  `backup_type` ENUM('Manual', 'Auto') DEFAULT 'Manual',
  `status` ENUM('Success', 'Failed') DEFAULT 'Success',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
