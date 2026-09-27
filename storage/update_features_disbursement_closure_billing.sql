-- =============================================================================
-- GOLDEN TRUST LMS - CONSOLIDATED LIVE DATABASE MIGRATION SCRIPT
-- Features: Loan Disbursement Receipt, Loan Closure Receipt & Single-Table Billing System
-- Safe & Idempotent: Can be executed multiple times without errors.
-- =============================================================================

-- 1. CREATE BILLING SYSTEM TABLE (SINGLE-TABLE MULTI-PRODUCT INVOICING)
-- Fully compliant with requirement: "Use only one database table for the billing system, supporting multiple products in one bill"
CREATE TABLE IF NOT EXISTS `bills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bill_number` VARCHAR(50) NOT NULL UNIQUE,
  `company_name` VARCHAR(255) NOT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_mobile` VARCHAR(20) DEFAULT NULL,
  `customer_address` TEXT DEFAULT NULL,
  `gst_number` VARCHAR(50) DEFAULT NULL,
  `bill_date` DATE NOT NULL,
  `items` LONGTEXT NOT NULL COMMENT 'JSON array of items: [{"product_name": "...", "quantity": 1, "price": 100.0, "total": 100.0}]',
  `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `payment_mode` VARCHAR(50) NOT NULL DEFAULT 'Cash',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_bills_date` (`bill_date`),
  INDEX `idx_bills_customer` (`customer_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ENSURE BILLS TABLE HAS GST NUMBER COLUMN IF ALREADY CREATED
ALTER TABLE `bills`
  ADD COLUMN IF NOT EXISTS `gst_number` varchar(50) DEFAULT NULL AFTER `customer_address`;

-- 3. ENSURE LOANS TABLE HAS ALL REQUIRED TRACKING & CLOSURE / DELIVERY COLUMNS
ALTER TABLE `loans`
  ADD COLUMN IF NOT EXISTS `delivered` varchar(20) NOT NULL DEFAULT 'No' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `delivery_date` date DEFAULT NULL AFTER `delivered`,
  ADD COLUMN IF NOT EXISTS `delivery_remarks` text DEFAULT NULL AFTER `delivery_date`,
  ADD COLUMN IF NOT EXISTS `haste` varchar(100) NOT NULL DEFAULT 'Customer Self' AFTER `delivery_remarks`,
  ADD COLUMN IF NOT EXISTS `old_receipt_taken` varchar(50) NOT NULL DEFAULT 'Yes' AFTER `haste`,
  ADD COLUMN IF NOT EXISTS `remarks` text DEFAULT NULL AFTER `haste`,
  ADD COLUMN IF NOT EXISTS `bag_no` varchar(50) DEFAULT NULL AFTER `remarks`;

-- 3. ENSURE PAYMENTS TABLE SUPPORTS DISCOUNT / SETTLEMENT CONCESSION
ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `discount` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`;

-- 4. ENSURE LOAN LEDGER SUPPORTS REMARKS
ALTER TABLE `loan_ledger`
  ADD COLUMN IF NOT EXISTS `remarks` text DEFAULT NULL AFTER `description`;

-- =============================================================================
-- END OF CONSOLIDATED MIGRATION
-- =============================================================================
