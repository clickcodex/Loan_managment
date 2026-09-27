-- =============================================================================
-- GOLDEN TRUST LMS - CONSOLIDATED LIVE DATABASE MIGRATION SCRIPT
-- Run this SQL in phpMyAdmin or MySQL CLI on your live database (e.g. u272390999_Tra)
-- Safe & Idempotent: Can be executed multiple times without errors.
-- =============================================================================

-- 1. ADD REMARKS COLUMN TO LOAN_LEDGER TABLE
-- Supports remarks for all ledger rows including the initial 'Loan Issued' entry
ALTER TABLE `loan_ledger`
  ADD COLUMN IF NOT EXISTS `remarks` text DEFAULT NULL AFTER `description`;

-- 2. ENSURE ALL TRACKING, DELIVERY, HASTE & REMARK COLUMNS EXIST ON LOANS TABLE
ALTER TABLE `loans`
  ADD COLUMN IF NOT EXISTS `delivered` varchar(20) NOT NULL DEFAULT 'No' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `delivery_date` date DEFAULT NULL AFTER `delivered`,
  ADD COLUMN IF NOT EXISTS `delivery_remarks` text DEFAULT NULL AFTER `delivery_date`,
  ADD COLUMN IF NOT EXISTS `haste` varchar(100) NOT NULL DEFAULT 'Customer Self' AFTER `delivery_remarks`,
  ADD COLUMN IF NOT EXISTS `old_receipt_taken` varchar(50) NOT NULL DEFAULT 'Yes' AFTER `haste`,
  ADD COLUMN IF NOT EXISTS `remarks` text DEFAULT NULL AFTER `haste`,
  ADD COLUMN IF NOT EXISTS `bag_no` varchar(50) DEFAULT NULL AFTER `remarks`;

-- 3. ENSURE LOANS STATUS COLUMN SUPPORTS BOTH 'Active' AND 'Running'
ALTER TABLE `loans`
  MODIFY COLUMN `status` varchar(50) NOT NULL DEFAULT 'Running';

-- 4. ENSURE PAYMENTS TABLE HAS DISCOUNT / WAIVER COLUMN
ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `discount` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`;

-- 5. DROP OBSOLETE BACKUP TABLE IF IT EXISTS
DROP TABLE IF EXISTS `database_backups`;

-- 6. ONE-TIME BACKFILL: POPULATE EXISTING 'Loan Issued' LEDGER ROWS WITH LOAN REMARKS
UPDATE `loan_ledger` ll
JOIN `loans` l ON ll.loan_id = l.id
SET ll.remarks = l.remarks
WHERE ll.entry_type = 'Loan Issued'
  AND (ll.remarks IS NULL OR ll.remarks = '')
  AND l.remarks IS NOT NULL
  AND l.remarks != '';

-- =============================================================================
-- END OF CONSOLIDATED MIGRATION
-- =============================================================================
