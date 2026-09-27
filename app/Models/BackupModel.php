<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BackupModel {

    private static function getBackupDir(): string {
        $dir = __DIR__ . '/../../storage/backups';
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        return realpath($dir) ?: $dir;
    }

    public static function generateBackup(): ?array {
        $pdo = Database::connect();
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $dumpContent = "-- =============================================================================\n";
        $dumpContent .= "-- GOLDEN TRUST LMS DATABASE BACKUP DUMP\n";
        $dumpContent .= "-- Generated Date: " . date('Y-m-d H:i:s') . "\n";
        $dumpContent .= "-- =============================================================================\n\n";
        $dumpContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Get Create Table Statement
            $stmtCreate = $pdo->query("SHOW CREATE TABLE `" . $table . "`");
            $rowCreate = $stmtCreate->fetch(PDO::FETCH_ASSOC);
            $createSql = $rowCreate['Create Table'] ?? '';

            $dumpContent .= "-- -----------------------------------------------------------------------------\n";
            $dumpContent .= "-- Table structure for `$table` \n";
            $dumpContent .= "-- -----------------------------------------------------------------------------\n";
            $dumpContent .= "DROP TABLE IF EXISTS `$table`;\n";
            $dumpContent .= $createSql . ";\n\n";

            // Get Data Rows
            $stmtRows = $pdo->query("SELECT * FROM `" . $table . "`");
            $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $dumpContent .= "-- Data dumping for table `$table` \n";
                foreach ($rows as $row) {
                    $cols = array_keys($row);
                    $escapedCols = array_map(fn($c) => "`" . $c . "`", $cols);
                    $escapedVals = array_map(function($v) use ($pdo) {
                        if ($v === null) return "NULL";
                        return $pdo->quote($v);
                    }, array_values($row));

                    $dumpContent .= "INSERT INTO `$table` (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $escapedVals) . ");\n";
                }
                $dumpContent .= "\n";
            }
        }

        $dumpContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $fullPath = self::getBackupDir() . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($fullPath, $dumpContent) !== false) {
            $fileSize = filesize($fullPath);

            Database::execute("INSERT INTO backups (filename, file_size, backup_type, status, created_at) VALUES (:fn, :fs, 'Manual', 'Success', NOW())", [
                ':fn' => $filename,
                ':fs' => $fileSize
            ]);

            $insertId = Database::lastInsertId();
            return [
                'id'         => $insertId,
                'filename'   => $filename,
                'filepath'   => $fullPath,
                'file_size'  => $fileSize,
                'status'     => 'Success',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        return null;
    }

    public static function eraseAndResetDatabase(): bool {
        $pdo = Database::connect();
        $tablesToTruncate = [
            'collateral_item_photos',
            'collateral_items',
            'payments',
            'loan_ledger',
            'loan_topups',
            'loan_guarantors',
            'interest_history',
            'loans',
            'customer_documents',
            'customers',
            'audit_logs',
            'backups'
        ];

        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            foreach ($tablesToTruncate as $t) {
                $pdo->exec("TRUNCATE TABLE `$t`;");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return true;
        } catch (\Throwable $e) {
            error_log("Erase DB Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getAllBackups(): array {
        $records = Database::fetchAll("SELECT * FROM backups ORDER BY id DESC");

        // Verify physical file existence and add relative path
        foreach ($records as &$b) {
            $b['physical_exists'] = file_exists(self::getBackupDir() . DIRECTORY_SEPARATOR . $b['filename']);
        }

        return $records;
    }

    public static function findBackupById(int $id): ?array {
        $b = Database::fetchOne("SELECT * FROM backups WHERE id = :id", [':id' => $id]);
        if ($b) {
            $b['filepath'] = self::getBackupDir() . DIRECTORY_SEPARATOR . $b['filename'];
            $b['physical_exists'] = file_exists($b['filepath']);
        }
        return $b;
    }

    public static function restoreFromSqlFile(string $filePath): bool {
        if (!file_exists($filePath)) return false;

        $sql = file_get_contents($filePath);
        if (empty($sql)) return false;

        $pdo = Database::connect();
        try {
            $pdo->exec($sql);
            return true;
        } catch (\Throwable $e) {
            error_log("Restore Error: " . $e->getMessage());
            return false;
        }
    }

    public static function deleteBackup(int $id): bool {
        $b = self::findBackupById($id);
        if ($b) {
            if (file_exists($b['filepath'])) {
                @unlink($b['filepath']);
            }
            return Database::execute("DELETE FROM backups WHERE id = :id", [':id' => $id]);
        }
        return false;
    }

    public static function getLastBackupDate(): ?string {
        $row = Database::fetchOne("SELECT created_at FROM backups WHERE status = 'Success' ORDER BY id DESC LIMIT 1");
        return $row['created_at'] ?? null;
    }

    public static function isBackupDue(int $days = 7): bool {
        $lastDate = self::getLastBackupDate();
        if (!$lastDate) return true;

        $lastTimestamp = strtotime($lastDate);
        $dueThreshold  = strtotime("-{$days} days");

        return $lastTimestamp < $dueThreshold;
    }
}
