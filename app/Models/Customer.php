<?php

namespace App\Models;

use App\Config\Database;

class Customer {

    public static function getAll(string $search = '', string $status = ''): array {
        $sql = "SELECT c.*, 
                       COUNT(l.id) as total_loans,
                       SUM(CASE WHEN l.status = 'Running' THEN 1 ELSE 0 END) as running_loans,
                       COALESCE(SUM(CASE WHEN l.status = 'Running' THEN l.principal_amount ELSE 0 END), 0) as total_principal
                FROM customers c
                LEFT JOIN loans l ON c.id = l.customer_id
                WHERE 1=1";
        
        $params = [];

        if (!empty($status)) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (c.full_name LIKE :c1 
                        OR c.customer_id LIKE :c2 
                        OR c.account_number LIKE :c3 
                        OR c.mobile LIKE :c4 
                        OR c.aadhaar LIKE :c5 
                        OR c.guarantor_name LIKE :c6)";
            $searchTerm = '%' . $search . '%';
            $params[':c1'] = $searchTerm;
            $params[':c2'] = $searchTerm;
            $params[':c3'] = $searchTerm;
            $params[':c4'] = $searchTerm;
            $params[':c5'] = $searchTerm;
            $params[':c6'] = $searchTerm;
        }

        $sql .= " GROUP BY c.id ORDER BY c.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM customers WHERE id = :id LIMIT 1", [':id' => $id]);
    }

    public static function findByCustomerId(string $customerId): ?array {
        return Database::fetchOne("SELECT * FROM customers WHERE customer_id = :cid LIMIT 1", [':cid' => $customerId]);
    }

    public static function generateNextCustomerId(): string {
        $row = Database::fetchOne("SELECT MAX(id) as max_id FROM customers");
        $nextId = (intval($row['max_id'] ?? 0)) + 1;
        $year = date('Y');
        return sprintf("CUST-%s-%04d", $year, $nextId);
    }

    public static function generateNextAccountNumber(): string {
        $row = Database::fetchOne("SELECT MAX(id) as max_id FROM customers");
        $nextId = (intval($row['max_id'] ?? 0)) + 1;
        return sprintf("Acc-%d", $nextId);
    }

    public static function create(array $data): int {
        $sql = "INSERT INTO customers (
                    customer_id, account_number, photo, full_name, father_name, 
                    mobile, alt_mobile, aadhaar, pan, address, village, city, 
                    state, pincode, guarantor_name, guarantor_mobile, guarantor_address, 
                    remarks, status, created_at
                ) VALUES (
                    :customer_id, :account_number, :photo, :full_name, :father_name,
                    :mobile, :alt_mobile, :aadhaar, :pan, :address, :village, :city,
                    :state, :pincode, :guarantor_name, :guarantor_mobile, :guarantor_address,
                    :remarks, :status, NOW()
                )";

        Database::execute($sql, [
            ':customer_id'       => $data['customer_id'],
            ':account_number'    => !empty($data['account_number']) ? $data['account_number'] : null,
            ':photo'             => !empty($data['photo']) ? $data['photo'] : null,
            ':full_name'         => $data['full_name'],
            ':father_name'       => !empty($data['father_name']) ? $data['father_name'] : null,
            ':mobile'            => $data['mobile'],
            ':alt_mobile'        => !empty($data['alt_mobile']) ? $data['alt_mobile'] : null,
            ':aadhaar'           => !empty($data['aadhaar']) ? $data['aadhaar'] : null,
            ':pan'               => !empty($data['pan']) ? $data['pan'] : null,
            ':address'           => !empty($data['address']) ? $data['address'] : null,
            ':village'           => !empty($data['village']) ? $data['village'] : null,
            ':city'              => !empty($data['city']) ? $data['city'] : null,
            ':state'             => !empty($data['state']) ? $data['state'] : null,
            ':pincode'           => !empty($data['pincode']) ? $data['pincode'] : null,
            ':guarantor_name'    => !empty($data['guarantor_name']) ? $data['guarantor_name'] : null,
            ':guarantor_mobile'  => !empty($data['guarantor_mobile']) ? $data['guarantor_mobile'] : null,
            ':guarantor_address' => !empty($data['guarantor_address']) ? $data['guarantor_address'] : null,
            ':remarks'           => !empty($data['remarks']) ? $data['remarks'] : null,
            ':status'            => $data['status'] ?? 'Active'
        ]);

        return intval(Database::lastInsertId());
    }

    public static function update(int $id, array $data): bool {
        $sql = "UPDATE customers SET 
                    account_number = :account_number,
                    full_name = :full_name,
                    father_name = :father_name,
                    mobile = :mobile,
                    alt_mobile = :alt_mobile,
                    aadhaar = :aadhaar,
                    pan = :pan,
                    address = :address,
                    village = :village,
                    city = :city,
                    state = :state,
                    pincode = :pincode,
                    guarantor_name = :guarantor_name,
                    guarantor_mobile = :guarantor_mobile,
                    guarantor_address = :guarantor_address,
                    remarks = :remarks,
                    status = :status";

        $params = [
            ':id'                => $id,
            ':account_number'    => $data['account_number'] ?: null,
            ':full_name'         => $data['full_name'],
            ':father_name'       => $data['father_name'] ?: null,
            ':mobile'            => $data['mobile'],
            ':alt_mobile'        => $data['alt_mobile'] ?: null,
            ':aadhaar'           => $data['aadhaar'] ?: null,
            ':pan'               => $data['pan'] ?: null,
            ':address'           => $data['address'] ?: null,
            ':village'           => $data['village'] ?: null,
            ':city'              => $data['city'] ?: null,
            ':state'             => $data['state'] ?: null,
            ':pincode'           => $data['pincode'] ?: null,
            ':guarantor_name'    => $data['guarantor_name'] ?: null,
            ':guarantor_mobile'  => $data['guarantor_mobile'] ?: null,
            ':guarantor_address' => $data['guarantor_address'] ?: null,
            ':remarks'           => $data['remarks'] ?: null,
            ':status'            => $data['status'] ?? 'Active'
        ];

        if (!empty($data['photo'])) {
            $sql .= ", photo = :photo";
            $params[':photo'] = $data['photo'];
        }

        $sql .= " WHERE id = :id";

        return Database::execute($sql, $params);
    }

    public static function updateStatus(int $id, string $status): bool {
        return Database::execute("UPDATE customers SET status = :status WHERE id = :id", [
            ':status' => $status,
            ':id'     => $id
        ]);
    }

    public static function getLoans(int $customerId): array {
        return Database::fetchAll("
            SELECT l.*, 
                   COALESCE(SUM(p.principal_component), 0) as paid_principal,
                   COALESCE(SUM(p.interest_component), 0) as paid_interest,
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT(ci.item_name, ' (', ROUND(ci.net_weight, 2), 'g)') SEPARATOR ', ') FROM collateral_items ci WHERE ci.loan_id = l.id) as collateral_items_summary,
                   (SELECT GROUP_CONCAT(DISTINCT ci.item_name SEPARATOR ', ') FROM collateral_items ci WHERE ci.loan_id = l.id) as collateral_names
            FROM loans l
            LEFT JOIN payments p ON l.id = p.loan_id
            WHERE l.customer_id = :cid
            GROUP BY l.id
            ORDER BY l.id DESC
        ", [':cid' => $customerId]);
    }

    public static function getFinancialSummary(int $customerId): array {
        $row = Database::fetchOne("
            SELECT 
                COUNT(l.id) as total_loans,
                SUM(CASE WHEN l.status IN ('Active', 'Running') THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN l.status = 'Closed' THEN 1 ELSE 0 END) as closed_loans,
                COALESCE(SUM(l.principal_amount), 0) as total_disbursed
            FROM loans l
            WHERE l.customer_id = :cid
        ", [':cid' => $customerId]);

        $payments = Database::fetchOne("
            SELECT 
                COALESCE(SUM(p.principal_component), 0) as total_principal_paid,
                COALESCE(SUM(p.interest_component), 0) as total_interest_paid
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            WHERE l.customer_id = :cid
        ", [':cid' => $customerId]);

        $disbursed = floatval($row['total_disbursed'] ?? 0);
        $paidPrincipal = floatval($payments['total_principal_paid'] ?? 0);
        $activeLoans = intval($row['active_loans'] ?? 0);

        return [
            'total_loans'          => intval($row['total_loans'] ?? 0),
            'active_loans'         => $activeLoans,
            'running_loans'        => $activeLoans,
            'closed_loans'         => intval($row['closed_loans'] ?? 0),
            'total_disbursed'      => $disbursed,
            'total_principal_paid' => $paidPrincipal,
            'total_interest_paid'  => floatval($payments['total_interest_paid'] ?? 0),
            'outstanding_principal'=> max(0, $disbursed - $paidPrincipal)
        ];
    }

    public static function addDocument(int $customerId, string $docType, string $filePath, string $origName): int {
        Database::execute("
            INSERT INTO customer_documents (customer_id, document_type, file_path, original_name, created_at)
            VALUES (:cid, :doctype, :filepath, :origname, NOW())
        ", [
            ':cid'      => $customerId,
            ':doctype'  => $docType,
            ':filepath' => $filePath,
            ':origname' => $origName
        ]);
        return intval(Database::lastInsertId());
    }

    public static function getDocuments(int $customerId): array {
        return Database::fetchAll("
            SELECT * FROM customer_documents WHERE customer_id = :cid ORDER BY id DESC
        ", [':cid' => $customerId]);
    }

    public static function deleteDocument(int $docId): bool {
        return Database::execute("DELETE FROM customer_documents WHERE id = :id", [':id' => $docId]);
    }

    public static function findDocumentById(int $docId): ?array {
        return Database::fetchOne("SELECT * FROM customer_documents WHERE id = :id LIMIT 1", [':id' => $docId]);
    }
}
