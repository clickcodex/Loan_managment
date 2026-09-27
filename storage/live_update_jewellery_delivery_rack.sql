-- =============================================================================
-- GOLDEN TRUST LMS - LIVE DATABASE UPDATE SCRIPT
-- Jewellery Delivery Automation & Rack Retention
-- Run this in phpMyAdmin or MySQL CLI on your live database
-- Safe & Idempotent: Can be run multiple times safely without errors.
-- =============================================================================

-- 1. Ensure all delivery columns exist on the 'loans' table
ALTER TABLE `loans`
  ADD COLUMN IF NOT EXISTS `delivered` varchar(20) NOT NULL DEFAULT 'No' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `delivery_date` date DEFAULT NULL AFTER `delivered`,
  ADD COLUMN IF NOT EXISTS `delivery_remarks` text DEFAULT NULL AFTER `delivery_date`,
  ADD COLUMN IF NOT EXISTS `haste` varchar(100) NOT NULL DEFAULT 'Customer Self' AFTER `delivery_remarks`;

-- 2. Ensure 'rk_number' column exists on the 'collateral_items' table
ALTER TABLE `collateral_items`
  ADD COLUMN IF NOT EXISTS `rk_number` varchar(50) DEFAULT NULL AFTER `loan_value`;

-- 3. Auto-sync existing Closed loans: Mark their jewellery as Delivered
-- Any loan that is already closed will have its delivery status updated
UPDATE `loans`
SET `delivered` = 'Yes',
    `delivery_date` = COALESCE(`delivery_date`, CURDATE()),
    `delivery_remarks` = COALESCE(`delivery_remarks`, 'Jewellery delivered automatically upon loan closure & settlement'),
    `haste` = COALESCE(`haste`, 'Customer Self')
WHERE `status` = 'Closed' 
  AND (`delivered` = 'No' OR `delivered` IS NULL);

-- 4. Restore Rack Number for loan LMS-2026-0006 (Gold Ring) if it was wiped previously
UPDATE `collateral_items` ci
JOIN `loans` l ON ci.loan_id = l.id
SET ci.rk_number = 'Rack 1 - Slot 2'
WHERE l.loan_number = 'LMS-2026-0006'
  AND ci.item_name LIKE '%Gold Ring%'
  AND (ci.rk_number IS NULL OR ci.rk_number = '');

-- =============================================================================
-- END OF SCRIPT
-- =============================================================================
