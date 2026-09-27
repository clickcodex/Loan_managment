<?php

namespace App\Models;

use App\Config\Database;

class DocumentModel {

    public static function getAllDocuments(string $category = 'all', int $customerId = 0, string $search = ''): array {
        $sql = "SELECT cd.*, c.full_name as customer_name, c.customer_id as cust_code, c.mobile as customer_mobile
                FROM customer_documents cd
                JOIN customers c ON cd.customer_id = c.id
                WHERE 1=1";

        $params = [];

        if ($customerId > 0) {
            $sql .= " AND cd.customer_id = :cid";
            $params[':cid'] = $customerId;
        }

        if (!empty($category) && $category !== 'all') {
            if (strtolower($category) === 'video') {
                $sql .= " AND (LOWER(cd.document_type) LIKE '%video%' OR LOWER(cd.file_path) LIKE '%.mp4' OR LOWER(cd.file_path) LIKE '%.webm' OR LOWER(cd.file_path) LIKE '%.mov')";
            } else {
                $sql .= " AND LOWER(cd.document_type) LIKE :cat";
                $params[':cat'] = '%' . strtolower(trim($category)) . '%';
            }
        }

        if (!empty($search)) {
            $sql .= " AND (cd.document_type LIKE :s1 OR cd.original_name LIKE :s2 OR c.full_name LIKE :s3 OR c.customer_id LIKE :s4 OR c.mobile LIKE :s5)";
            $term = '%' . trim($search) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
        }

        $sql .= " ORDER BY cd.id DESC";

        $docs = Database::fetchAll($sql, $params);

        foreach ($docs as &$d) {
            $fullPath = __DIR__ . '/../../public/' . ltrim($d['file_path'], '/');
            $d['physical_exists'] = file_exists($fullPath);
            $d['file_size'] = $d['physical_exists'] ? filesize($fullPath) : 0;
            $d['ext'] = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
            $d['is_image'] = in_array($d['ext'], ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            $d['is_pdf'] = ($d['ext'] === 'pdf');
            $d['is_video'] = in_array($d['ext'], ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
        }

        return $docs;
    }

    public static function getDocumentById(int $id): ?array {
        $sql = "SELECT cd.*, c.full_name as customer_name, c.customer_id as cust_code, c.mobile as customer_mobile
                FROM customer_documents cd
                JOIN customers c ON cd.customer_id = c.id
                WHERE cd.id = :id";

        $d = Database::fetchOne($sql, [':id' => $id]);
        if ($d) {
            $fullPath = __DIR__ . '/../../public/' . ltrim($d['file_path'], '/');
            $d['physical_exists'] = file_exists($fullPath);
            $d['file_size'] = $d['physical_exists'] ? filesize($fullPath) : 0;
            $d['ext'] = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
            $d['is_image'] = in_array($d['ext'], ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            $d['is_pdf'] = ($d['ext'] === 'pdf');
            $d['is_video'] = in_array($d['ext'], ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
        }
        return $d;
    }

    public static function addDocument(int $customerId, string $docType, string $filePath, string $originalName): int {
        $sql = "INSERT INTO customer_documents (customer_id, document_type, file_path, original_name, created_at)
                VALUES (:cid, :dtype, :fpath, :oname, NOW())";

        Database::execute($sql, [
            ':cid'   => $customerId,
            ':dtype' => trim($docType),
            ':fpath' => $filePath,
            ':oname' => $originalName
        ]);

        $insertId = intval(Database::lastInsertId());

        // Sync customer photo if document_type is photo
        if (in_array(strtolower(trim($docType)), ['customer photo', 'photo', 'profile photo', 'passport photo'])) {
            Database::execute("UPDATE customers SET photo = :photo WHERE id = :id", [
                ':photo' => $filePath,
                ':id'    => $customerId
            ]);
        }

        return $insertId;
    }

    public static function deleteDocument(int $id): bool {
        $doc = self::getDocumentById($id);
        if ($doc) {
            if ($doc['physical_exists']) {
                $fullPath = __DIR__ . '/../../public/' . ltrim($doc['file_path'], '/');
                @unlink($fullPath);
            }
            Database::execute("DELETE FROM customer_documents WHERE id = :id", [':id' => $id]);
            return true;
        }
        return false;
    }

    public static function getVaultStats(): array {
        $sqlTotal = "SELECT COUNT(*) as cnt FROM customer_documents";
        $totalDocs = intval(Database::fetchOne($sqlTotal)['cnt'] ?? 0);

        $sqlPhotos = "SELECT COUNT(*) as cnt FROM customer_documents WHERE LOWER(document_type) LIKE '%photo%'";
        $photoDocs = intval(Database::fetchOne($sqlPhotos)['cnt'] ?? 0);

        $sqlKyc = "SELECT COUNT(*) as cnt FROM customer_documents WHERE LOWER(document_type) LIKE '%aadhaar%' OR LOWER(document_type) LIKE '%pan%' OR LOWER(document_type) LIKE '%address%' OR LOWER(document_type) LIKE '%kyc%'";
        $kycDocs = intval(Database::fetchOne($sqlKyc)['cnt'] ?? 0);

        $sqlAgreement = "SELECT COUNT(*) as cnt FROM customer_documents WHERE LOWER(document_type) LIKE '%agreement%' OR LOWER(document_type) LIKE '%loan%'";
        $agreementDocs = intval(Database::fetchOne($sqlAgreement)['cnt'] ?? 0);

        $sqlVideo = "SELECT COUNT(*) as cnt FROM customer_documents WHERE LOWER(document_type) LIKE '%video%' OR LOWER(file_path) LIKE '%.mp4' OR LOWER(file_path) LIKE '%.webm'";
        $videoDocs = intval(Database::fetchOne($sqlVideo)['cnt'] ?? 0);

        return [
            'total'      => $totalDocs,
            'photos'     => $photoDocs,
            'kyc'        => $kycDocs,
            'agreements' => $agreementDocs,
            'videos'     => $videoDocs
        ];
    }
}
