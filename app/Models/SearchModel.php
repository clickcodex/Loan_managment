<?php

namespace App\Models;

use App\Config\Database;

class SearchModel {

    public static function searchCustomers(string $query, int $limit = 20): array {
        $query = trim($query);
        if (empty($query)) return [];

        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM loans l WHERE l.customer_id = c.id) as loan_count,
                       (SELECT l.remarks FROM loans l WHERE l.customer_id = c.id AND l.remarks LIKE :c_sub LIMIT 1) as matched_loan_remark
                FROM customers c
                WHERE (c.full_name LIKE :c1 
                   OR c.mobile LIKE :c2 
                   OR c.alt_mobile LIKE :c3 
                   OR c.aadhaar LIKE :c4 
                   OR c.pan LIKE :c5 
                   OR c.customer_id LIKE :c6 
                   OR c.account_number LIKE :c7 
                   OR c.guarantor_name LIKE :c8 
                   OR c.guarantor_mobile LIKE :c9 
                   OR c.address LIKE :c10 
                   OR c.city LIKE :c11
                   OR c.remarks LIKE :c12
                   OR EXISTS (SELECT 1 FROM loans l WHERE l.customer_id = c.id AND l.remarks LIKE :c13))
                ORDER BY c.id DESC
                LIMIT " . intval($limit);

        $term = '%' . $query . '%';
        $params = [':c_sub' => $term];
        for ($i = 1; $i <= 13; $i++) {
            $params[':c' . $i] = $term;
        }

        return Database::fetchAll($sql, $params);
    }

    public static function searchLoans(string $query, int $limit = 20): array {
        $query = trim($query);
        if (empty($query)) return [];

        $sql = "SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile, c.customer_id as cust_code,
                       (SELECT COUNT(*) FROM collateral_items ci WHERE ci.loan_id = l.id) as item_count
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                WHERE (l.loan_number LIKE :l1 
                   OR c.full_name LIKE :l2 
                   OR c.mobile LIKE :l3 
                   OR c.customer_id LIKE :l4 
                   OR c.account_number LIKE :l5 
                   OR c.guarantor_name LIKE :l6 
                   OR l.security_type LIKE :l7
                   OR l.remarks LIKE :l8)
                ORDER BY l.id DESC
                LIMIT " . intval($limit);

        $term = '%' . $query . '%';
        $params = [];
        for ($i = 1; $i <= 8; $i++) {
            $params[':l' . $i] = $term;
        }

        return Database::fetchAll($sql, $params);
    }

    public static function searchCollateralItems(string $query, int $limit = 20): array {
        $query = trim($query);
        if (empty($query)) return [];

        $sql = "SELECT ci.*, l.loan_number, l.customer_id, c.full_name as customer_name, c.mobile as customer_mobile,
                       (SELECT file_path FROM collateral_item_photos cip WHERE cip.item_id = ci.id LIMIT 1) as primary_photo
                FROM collateral_items ci
                JOIN loans l ON ci.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE (ci.item_name LIKE :i1 
                   OR ci.rk_number LIKE :i2 
                   OR l.loan_number LIKE :i3 
                   OR c.full_name LIKE :i4 
                   OR ci.remarks LIKE :i5)
                ORDER BY ci.id DESC
                LIMIT " . intval($limit);

        $term = '%' . $query . '%';
        $params = [];
        for ($i = 1; $i <= 5; $i++) {
            $params[':i' . $i] = $term;
        }

        return Database::fetchAll($sql, $params);
    }

    public static function searchPayments(string $query, int $limit = 20): array {
        $query = trim($query);
        if (empty($query)) return [];

        $sql = "SELECT p.*, l.loan_number, l.customer_id, c.full_name as customer_name, c.mobile as customer_mobile
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE (p.receipt_number LIKE :p1 
                   OR l.loan_number LIKE :p2 
                   OR c.full_name LIKE :p3 
                   OR p.reference_number LIKE :p4 
                   OR p.remarks LIKE :p5)
                ORDER BY p.id DESC
                LIMIT " . intval($limit);

        $term = '%' . $query . '%';
        $params = [];
        for ($i = 1; $i <= 5; $i++) {
            $params[':p' . $i] = $term;
        }

        return Database::fetchAll($sql, $params);
    }

    public static function performGlobalSearch(string $query): array {
        $customers  = self::searchCustomers($query);
        $loans      = self::searchLoans($query);
        $items      = self::searchCollateralItems($query);
        $payments   = self::searchPayments($query);

        $totalCount = count($customers) + count($loans) + count($items) + count($payments);

        return [
            'query'       => $query,
            'total_count' => $totalCount,
            'customers'   => $customers,
            'loans'       => $loans,
            'items'       => $items,
            'payments'    => $payments
        ];
    }
}
