<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\AuditLogger;

class AuditModel {

    public static function getLogs(string $category = '', string $search = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT a.*, adm.username as admin_username, adm.name as admin_name
                FROM audit_logs a
                LEFT JOIN admin adm ON a.user_id = adm.id
                WHERE 1=1";

        $params = [];

        if (!empty($category) && $category !== 'all') {
            $catSql = self::getCategorySqlCondition($category, $params);
            if (!empty($catSql)) {
                $sql .= " AND (" . $catSql . ")";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (a.action LIKE :s1 OR a.details LIKE :s2 OR a.ip_address LIKE :s3 OR adm.name LIKE :s4 OR adm.username LIKE :s5)";
            $term = '%' . trim($search) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
        }

        $sql .= " ORDER BY a.id DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $logs = Database::fetchAll($sql, $params);

        foreach ($logs as &$log) {
            $oldArr = !empty($log['old_values']) ? json_decode($log['old_values'], true) : null;
            $newArr = !empty($log['new_values']) ? json_decode($log['new_values'], true) : null;
            $log['diffs'] = AuditLogger::computeFieldDiff($oldArr, $newArr);
            $log['category'] = self::deriveCategory($log['action']);
        }

        return $logs;
    }

    public static function getTotalLogsCount(string $category = '', string $search = ''): int {
        $sql = "SELECT COUNT(*) as cnt
                FROM audit_logs a
                LEFT JOIN admin adm ON a.user_id = adm.id
                WHERE 1=1";

        $params = [];

        if (!empty($category) && $category !== 'all') {
            $catSql = self::getCategorySqlCondition($category, $params);
            if (!empty($catSql)) {
                $sql .= " AND (" . $catSql . ")";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (a.action LIKE :s1 OR a.details LIKE :s2 OR a.ip_address LIKE :s3 OR adm.name LIKE :s4 OR adm.username LIKE :s5)";
            $term = '%' . trim($search) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
        }

        $row = Database::fetchOne($sql, $params);
        return intval($row['cnt'] ?? 0);
    }

    public static function getLogById(int $id): ?array {
        $sql = "SELECT a.*, adm.username as admin_username, adm.name as admin_name
                FROM audit_logs a
                LEFT JOIN admin adm ON a.user_id = adm.id
                WHERE a.id = :id";

        $log = Database::fetchOne($sql, [':id' => $id]);
        if ($log) {
            $oldArr = !empty($log['old_values']) ? json_decode($log['old_values'], true) : null;
            $newArr = !empty($log['new_values']) ? json_decode($log['new_values'], true) : null;
            $log['old_arr'] = $oldArr;
            $log['new_arr'] = $newArr;
            $log['diffs']   = AuditLogger::computeFieldDiff($oldArr, $newArr);
            $log['category'] = self::deriveCategory($log['action']);
        }

        return $log;
    }

    public static function getAuditKpis(): array {
        $total = Database::fetchOne("SELECT COUNT(*) as cnt FROM audit_logs")['cnt'] ?? 0;
        $today = Database::fetchOne("SELECT COUNT(*) as cnt FROM audit_logs WHERE DATE(created_at) = CURDATE()")['cnt'] ?? 0;
        $uniqueIps = Database::fetchOne("SELECT COUNT(DISTINCT ip_address) as cnt FROM audit_logs")['cnt'] ?? 0;

        return [
            'total_logs'  => intval($total),
            'today_logs'  => intval($today),
            'unique_ips'  => intval($uniqueIps)
        ];
    }

    private static function getCategorySqlCondition(string $cat, array &$params): string {
        switch (strtolower($cat)) {
            case 'auth':
                return "a.action LIKE '%Login%' OR a.action LIKE '%Logout%' OR a.action LIKE '%Password%'";
            case 'customers':
                return "a.action LIKE '%Customer%'";
            case 'loans':
                return "a.action LIKE '%Loan%'";
            case 'collateral':
                return "a.action LIKE '%Collateral%'";
            case 'payments':
                return "a.action LIKE '%Payment%' OR a.action LIKE '%Receipt%'";
            case 'rates':
                return "a.action LIKE '%Rate%' OR a.action LIKE '%Gold%' OR a.action LIKE '%Silver%'";
            case 'system':
                return "a.action LIKE '%Backup%' OR a.action LIKE '%Restore%' OR a.action LIKE '%Erase%' OR a.action LIKE '%Setting%'";
            default:
                return "";
        }
    }

    public static function deriveCategory(string $action): string {
        $a = strtolower($action);
        if (str_contains($a, 'login') || str_contains($a, 'logout') || str_contains($a, 'password')) return 'Authentication';
        if (str_contains($a, 'customer')) return 'Customer Management';
        if (str_contains($a, 'loan')) return 'Loan Accounts';
        if (str_contains($a, 'collateral')) return 'Collateral Vault';
        if (str_contains($a, 'payment') || str_contains($a, 'receipt')) return 'Payments Engine';
        if (str_contains($a, 'rate') || str_contains($a, 'gold') || str_contains($a, 'silver')) return 'Daily Bullion Rates';
        if (str_contains($a, 'backup') || str_contains($a, 'restore') || str_contains($a, 'erase') || str_contains($a, 'setting')) return 'System Security';
        return 'General Operation';
    }
}
