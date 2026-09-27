<?php

namespace App\Models;

use App\Config\Database;

class RateModel {

    public static function getLatestGoldRate(): ?array {
        $row = Database::fetchOne("
            SELECT * FROM gold_rates ORDER BY rate_date DESC, id DESC LIMIT 1
        ");

        if (!$row) {
            return [
                'rate_100' => 80000.00,
                'rate_24k' => 79920.00,
                'rate_22k' => 73336.00,
                'rate_18k' => 60000.00,
                'rate_14k' => 46664.00,
                'custom_rates' => json_encode(['23K' => 76664.00, '21K' => 70000.00, '20K' => 66664.00, '10K' => 33336.00]),
                'rate_date' => date('Y-m-d')
            ];
        }
        if (empty($row['rate_100'])) {
            $row['rate_100'] = round(floatval($row['rate_24k']) / 0.999, 2);
        }

        return $row;
    }

    public static function getLatestSilverRate(): ?array {
        $row = Database::fetchOne("
            SELECT * FROM silver_rates ORDER BY rate_date DESC, id DESC LIMIT 1
        ");

        if (!$row) {
            return [
                'rate_100' => 90.00,
                'rate_999' => 89.91,
                'rate_925' => 83.25,
                'rate_800' => 72.00,
                'custom_rates' => json_encode(['95.0%' => 85.50, '75.0%' => 67.50]),
                'rate_date' => date('Y-m-d')
            ];
        }
        if (empty($row['rate_100'])) {
            $row['rate_100'] = round(floatval($row['rate_999']) / 0.999, 2);
        }

        return $row;
    }

    public static function getKaratPresets(string $type = ''): array {
        $sql = "SELECT * FROM rate_karat_presets WHERE is_active = 1";
        $params = [];

        if (!empty($type)) {
            $sql .= " AND type = :type";
            $params[':type'] = strtoupper($type);
        }

        $sql .= " ORDER BY display_order ASC, id ASC";

        return Database::fetchAll($sql, $params);
    }

    public static function addKaratPreset(string $type, string $name, float $purityPct): int {
        $sql = "INSERT INTO rate_karat_presets (type, name, purity_percentage, is_active, display_order, created_at)
                VALUES (:type, :name, :purity, 1, 10, NOW())";

        Database::execute($sql, [
            ':type'   => strtoupper($type),
            ':name'   => trim($name),
            ':purity' => $purityPct
        ]);

        return intval(Database::lastInsertId());
    }

    public static function deleteKaratPreset(int $id): bool {
        return Database::execute("DELETE FROM rate_karat_presets WHERE id = :id", [':id' => $id]);
    }

    public static function getGoldHistory(int $limit = 20): array {
        return Database::fetchAll("
            SELECT * FROM gold_rates ORDER BY rate_date DESC, id DESC LIMIT " . intval($limit)
        );
    }

    public static function getSilverHistory(int $limit = 20): array {
        return Database::fetchAll("
            SELECT * FROM silver_rates ORDER BY rate_date DESC, id DESC LIMIT " . intval($limit)
        );
    }

    public static function updateGoldRates(array $data): bool {
        $rDate = !empty($data['rate_date']) ? $data['rate_date'] : date('Y-m-d');
        $customRates = !empty($data['custom_rates']) ? (is_array($data['custom_rates']) ? json_encode($data['custom_rates']) : $data['custom_rates']) : null;

        $rate100 = !empty($data['rate_100']) ? floatval($data['rate_100']) : round(floatval($data['rate_24k'] ?? 0) / 0.999, 2);

        $sql = "INSERT INTO gold_rates (
                    rate_date, rate_100, rate_24k, rate_22k, rate_18k, rate_14k, custom_rates, remarks, created_at
                ) VALUES (
                    :rdate, :r100, :r24, :r22, :r18, :r14, :custom, :remarks, NOW()
                ) ON DUPLICATE KEY UPDATE 
                    rate_100 = VALUES(rate_100),
                    rate_24k = VALUES(rate_24k),
                    rate_22k = VALUES(rate_22k),
                    rate_18k = VALUES(rate_18k),
                    rate_14k = VALUES(rate_14k),
                    custom_rates = VALUES(custom_rates),
                    remarks = VALUES(remarks)";

        $result = Database::execute($sql, [
            ':rdate'   => $rDate,
            ':r100'    => $rate100,
            ':r24'     => floatval($data['rate_24k'] ?? ($rate100 * 0.999)),
            ':r22'     => floatval($data['rate_22k'] ?? ($rate100 * 0.9167)),
            ':r18'     => floatval($data['rate_18k'] ?? ($rate100 * 0.75)),
            ':r14'     => floatval($data['rate_14k'] ?? ($rate100 * 0.5833)),
            ':custom'  => $customRates,
            ':remarks' => $data['remarks'] ?? null
        ]);

        if ($result) {
            CollateralModel::recalculateCollateralMarketValues('GOLD', $rate100);
        }

        return $result;
    }

    public static function updateSilverRates(array $data): bool {
        $rDate = !empty($data['rate_date']) ? $data['rate_date'] : date('Y-m-d');
        $customRates = !empty($data['custom_rates']) ? (is_array($data['custom_rates']) ? json_encode($data['custom_rates']) : $data['custom_rates']) : null;

        $rate100 = !empty($data['rate_100']) ? floatval($data['rate_100']) : round(floatval($data['rate_999'] ?? 0) / 0.999, 2);

        $sql = "INSERT INTO silver_rates (
                    rate_date, rate_100, rate_999, rate_925, rate_800, custom_rates, remarks, created_at
                ) VALUES (
                    :rdate, :r100, :r999, :r925, :r800, :custom, :remarks, NOW()
                ) ON DUPLICATE KEY UPDATE 
                    rate_100 = VALUES(rate_100),
                    rate_999 = VALUES(rate_999),
                    rate_925 = VALUES(rate_925),
                    rate_800 = VALUES(rate_800),
                    custom_rates = VALUES(custom_rates),
                    remarks = VALUES(remarks)";

        $result = Database::execute($sql, [
            ':rdate'   => $rDate,
            ':r100'    => $rate100,
            ':r999'    => floatval($data['rate_999']),
            ':r925'    => floatval($data['rate_925']),
            ':r800'    => floatval($data['rate_800']),
            ':custom'  => $customRates,
            ':remarks' => $data['remarks'] ?? null
        ]);

        if ($result) {
            CollateralModel::recalculateCollateralMarketValues('SILVER', $rate100);
        }

        return $result;
    }
}
