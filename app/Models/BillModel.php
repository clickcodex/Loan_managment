<?php

namespace App\Models;

use App\Config\Database;

class BillModel {

    public static function ensureSchema(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `bills` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bill_number` VARCHAR(50) NOT NULL UNIQUE,
            `company_name` VARCHAR(255) NOT NULL,
            `customer_name` VARCHAR(255) NOT NULL,
            `customer_mobile` VARCHAR(20) DEFAULT NULL,
            `customer_address` TEXT DEFAULT NULL,
            `gst_number` VARCHAR(50) DEFAULT NULL,
            `bill_date` DATE NOT NULL,
            `items` LONGTEXT NOT NULL COMMENT 'JSON array of items: [{\"product_name\": \"...\", \"quantity\": 1, \"price\": 100.0, \"total\": 100.0}]',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        Database::execute($sql);
        Database::execute("ALTER TABLE `bills` ADD COLUMN IF NOT EXISTS `gst_number` varchar(50) DEFAULT NULL AFTER `customer_address`;");
    }

    public static function generateNextBillNumber(): string {
        self::ensureSchema();
        $row = Database::fetchOne("SELECT MAX(id) as max_id FROM bills");
        $nextId = (intval($row['max_id'] ?? 0)) + 1;
        $year = date('Y');
        return sprintf("BILL-%s-%04d", $year, $nextId);
    }

    public static function getDefaultCompanyName(): string {
        $setting = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'company_name' LIMIT 1");
        return !empty($setting['setting_value']) ? $setting['setting_value'] : 'Golden Trust Finance Co.';
    }

    public static function create(array $data): int {
        self::ensureSchema();

        $itemsJson = is_array($data['items']) ? json_encode($data['items']) : $data['items'];

        $sql = "INSERT INTO bills (
            bill_number, company_name, customer_name, customer_mobile, customer_address, gst_number,
            bill_date, items, subtotal, discount_amount, tax_amount, total_amount, payment_mode, notes
        ) VALUES (
            :bill_number, :company_name, :customer_name, :customer_mobile, :customer_address, :gst_number,
            :bill_date, :items, :subtotal, :discount_amount, :tax_amount, :total_amount, :payment_mode, :notes
        )";

        $params = [
            ':bill_number'      => $data['bill_number'],
            ':company_name'     => $data['company_name'],
            ':customer_name'    => $data['customer_name'],
            ':customer_mobile'  => $data['customer_mobile'] ?? null,
            ':customer_address' => $data['customer_address'] ?? null,
            ':gst_number'       => !empty($data['gst_number']) ? trim($data['gst_number']) : null,
            ':bill_date'        => $data['bill_date'] ?? date('Y-m-d'),
            ':items'            => $itemsJson,
            ':subtotal'         => floatval($data['subtotal'] ?? 0),
            ':discount_amount'  => floatval($data['discount_amount'] ?? 0),
            ':tax_amount'       => floatval($data['tax_amount'] ?? 0),
            ':total_amount'     => floatval($data['total_amount'] ?? 0),
            ':payment_mode'     => $data['payment_mode'] ?? 'Cash',
            ':notes'            => $data['notes'] ?? null
        ];

        Database::execute($sql, $params);
        return intval(Database::lastInsertId());
    }

    public static function getAll(string $search = '', string $startDate = '', string $endDate = ''): array {
        self::ensureSchema();

        $sql = "SELECT * FROM bills WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (bill_number LIKE :s1 OR customer_name LIKE :s2 OR customer_mobile LIKE :s3 OR company_name LIKE :s4 OR gst_number LIKE :s5)";
            $term = '%' . $search . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
        }

        if (!empty($startDate)) {
            $sql .= " AND bill_date >= :sdate";
            $params[':sdate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND bill_date <= :edate";
            $params[':edate'] = $endDate;
        }

        $sql .= " ORDER BY id DESC";

        $rows = Database::fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['items_decoded'] = json_decode($row['items'] ?? '[]', true) ?: [];
            $row['item_count'] = count($row['items_decoded']);
        }
        return $rows;
    }

    public static function findById(int $id): ?array {
        self::ensureSchema();
        $row = Database::fetchOne("SELECT * FROM bills WHERE id = :id LIMIT 1", [':id' => $id]);
        if (!$row) {
            return null;
        }
        $row['items_decoded'] = json_decode($row['items'] ?? '[]', true) ?: [];
        return $row;
    }

    public static function delete(int $id): bool {
        self::ensureSchema();
        Database::execute("DELETE FROM bills WHERE id = :id", [':id' => $id]);
        return true;
    }

    public static function getStats(): array {
        self::ensureSchema();
        $totalBills = Database::fetchOne("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total_revenue FROM bills");
        $todayBills = Database::fetchOne("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as today_revenue FROM bills WHERE bill_date = CURDATE()");
        $thisMonth = Database::fetchOne("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as month_revenue FROM bills WHERE MONTH(bill_date) = MONTH(CURDATE()) AND YEAR(bill_date) = YEAR(CURDATE())");

        return [
            'total_count'   => intval($totalBills['cnt'] ?? 0),
            'total_revenue' => floatval($totalBills['total_revenue'] ?? 0),
            'today_count'   => intval($todayBills['cnt'] ?? 0),
            'today_revenue' => floatval($todayBills['today_revenue'] ?? 0),
            'month_count'   => intval($thisMonth['cnt'] ?? 0),
            'month_revenue' => floatval($thisMonth['month_revenue'] ?? 0),
        ];
    }
}
