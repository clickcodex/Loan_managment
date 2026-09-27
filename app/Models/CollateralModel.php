<?php

namespace App\Models;

use App\Config\Database;

class CollateralModel {

    public static function getAll(string $search = '', string $itemType = '', string $purityPreset = ''): array {
        $sql = "SELECT ci.*, 
                       l.loan_number, l.status as loan_status, l.delivered as loan_delivered, l.delivery_date, l.customer_id,
                       c.full_name as customer_name, c.mobile as customer_mobile,
                       (SELECT file_path FROM collateral_item_photos cip WHERE cip.item_id = ci.id LIMIT 1) as primary_photo,
                       (SELECT COUNT(*) FROM collateral_item_photos cip WHERE cip.item_id = ci.id) as photo_count
                FROM collateral_items ci
                JOIN loans l ON ci.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE 1=1";

        $params = [];

        if (!empty($itemType)) {
            $sql .= " AND ci.item_type = :itype";
            $params[':itype'] = strtoupper($itemType);
        }

        if (!empty($purityPreset)) {
            $sql .= " AND ci.purity_preset = :preset";
            $params[':preset'] = $purityPreset;
        }

        if (!empty($search)) {
            $sql .= " AND (ci.item_name LIKE :s1 
                        OR ci.rk_number LIKE :s2 
                        OR l.loan_number LIKE :s3 
                        OR c.full_name LIKE :s4)";
            $searchTerm = '%' . $search . '%';
            $params[':s1'] = $searchTerm;
            $params[':s2'] = $searchTerm;
            $params[':s3'] = $searchTerm;
            $params[':s4'] = $searchTerm;
        }

        $sql .= " ORDER BY ci.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function getPortfolioStats(): array {
        return Database::fetchOne("
            SELECT 
                COALESCE(SUM(CASE WHEN ci.item_type = 'GOLD' AND l.status IN ('Active', 'Running') THEN ci.net_weight ELSE 0 END), 0) as gold_net_weight,
                COALESCE(SUM(CASE WHEN ci.item_type = 'GOLD' AND l.status IN ('Active', 'Running') THEN COALESCE(ci.manual_market_value_override, ci.market_value) ELSE 0 END), 0) as gold_market_value,
                COUNT(CASE WHEN ci.item_type = 'GOLD' AND l.status IN ('Active', 'Running') THEN 1 END) as gold_items_count,
                
                COALESCE(SUM(CASE WHEN ci.item_type = 'SILVER' AND l.status IN ('Active', 'Running') THEN ci.net_weight ELSE 0 END), 0) as silver_net_weight,
                COALESCE(SUM(CASE WHEN ci.item_type = 'SILVER' AND l.status IN ('Active', 'Running') THEN COALESCE(ci.manual_market_value_override, ci.market_value) ELSE 0 END), 0) as silver_market_value,
                COUNT(CASE WHEN ci.item_type = 'SILVER' AND l.status IN ('Active', 'Running') THEN 1 END) as silver_items_count
            FROM collateral_items ci
            JOIN loans l ON ci.loan_id = l.id
        ") ?: [
            'gold_net_weight' => 0, 'gold_market_value' => 0, 'gold_items_count' => 0,
            'silver_net_weight' => 0, 'silver_market_value' => 0, 'silver_items_count' => 0
        ];
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("
            SELECT ci.*, l.loan_number, l.status as loan_status, l.loan_date, l.principal_amount,
                   l.delivered as loan_delivered, l.delivery_date, l.delivery_remarks, l.haste,
                   c.id as cust_id, c.full_name as customer_name, c.mobile as customer_mobile
            FROM collateral_items ci
            JOIN loans l ON ci.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            WHERE ci.id = :id
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function createItem(array $data): int {
        $rkNumber = !empty($data['rk_number']) ? trim($data['rk_number']) : \App\Helpers\RackManager::getNextAvailableSlot();

        $sql = "INSERT INTO collateral_items (
                    loan_id, item_type, item_name, quantity, gross_weight,
                    stone_weight, net_weight, purity_preset, purity_percentage,
                    market_value, manual_market_value_override, loan_value,
                    rk_number, remarks, created_at
                ) VALUES (
                    :loan_id, :item_type, :item_name, :quantity, :gross_weight,
                    :stone_weight, :net_weight, :purity_preset, :purity_percentage,
                    :market_value, :manual_market_value_override, :loan_value,
                    :rk_number, :remarks, NOW()
                )";

        Database::execute($sql, [
            ':loan_id'                      => $data['loan_id'],
            ':item_type'                    => strtoupper($data['item_type']),
            ':item_name'                    => $data['item_name'],
            ':quantity'                     => intval($data['quantity'] ?? 1),
            ':gross_weight'                 => floatval($data['gross_weight'] ?? 0),
            ':stone_weight'                 => floatval($data['stone_weight'] ?? 0),
            ':net_weight'                   => floatval($data['net_weight'] ?? 0),
            ':purity_preset'                => $data['purity_preset'] ?? null,
            ':purity_percentage'            => floatval($data['purity_percentage'] ?? 0),
            ':market_value'                 => floatval($data['market_value'] ?? 0),
            ':manual_market_value_override' => !empty($data['manual_market_value_override']) ? floatval($data['manual_market_value_override']) : null,
            ':loan_value'                   => floatval($data['loan_value'] ?? 0),
            ':rk_number'                    => $rkNumber,
            ':remarks'                      => !empty($data['remarks']) ? $data['remarks'] : null
        ]);

        return intval(Database::lastInsertId());
    }

    public static function updateItem(int $id, array $data): bool {
        $sql = "UPDATE collateral_items SET 
                    item_name = :item_name,
                    quantity = :quantity,
                    gross_weight = :gross_weight,
                    stone_weight = :stone_weight,
                    net_weight = :net_weight,
                    purity_preset = :purity_preset,
                    purity_percentage = :purity_percentage,
                    market_value = :market_value,
                    manual_market_value_override = :override_val,
                    loan_value = :loan_value,
                    rk_number = :rk_number,
                    remarks = :remarks
                WHERE id = :id";

        return Database::execute($sql, [
            ':id'           => $id,
            ':item_name'    => $data['item_name'],
            ':quantity'     => intval($data['quantity'] ?? 1),
            ':gross_weight' => floatval($data['gross_weight'] ?? 0),
            ':stone_weight' => floatval($data['stone_weight'] ?? 0),
            ':net_weight'   => floatval($data['net_weight'] ?? 0),
            ':purity_preset'=> $data['purity_preset'] ?? null,
            ':purity_percentage' => floatval($data['purity_percentage'] ?? 0),
            ':market_value' => floatval($data['market_value'] ?? 0),
            ':override_val' => !empty($data['manual_market_value_override']) ? floatval($data['manual_market_value_override']) : null,
            ':loan_value'   => floatval($data['loan_value'] ?? 0),
            ':rk_number'    => $data['rk_number'] ?: null,
            ':remarks'      => $data['remarks'] ?: null
        ]);
    }

    public static function deleteItem(int $id): bool {
        return Database::execute("DELETE FROM collateral_items WHERE id = :id", [':id' => $id]);
    }

    public static function addPhoto(int $itemId, string $filePath, string $origName): bool {
        return Database::execute("
            INSERT INTO collateral_item_photos (item_id, file_path, original_name, created_at)
            VALUES (:item_id, :file_path, :orig_name, NOW())
        ", [
            ':item_id'   => $itemId,
            ':file_path' => $filePath,
            ':orig_name' => $origName
        ]);
    }

    public static function getPhotos(int $itemId): array {
        return Database::fetchAll("SELECT * FROM collateral_item_photos WHERE item_id = :item_id ORDER BY id DESC", [':item_id' => $itemId]);
    }

    public static function deletePhoto(int $photoId): bool {
        return Database::execute("DELETE FROM collateral_item_photos WHERE id = :id", [':id' => $photoId]);
    }

    public static function findPhotoById(int $photoId): ?array {
        return Database::fetchOne("SELECT * FROM collateral_item_photos WHERE id = :id LIMIT 1", [':id' => $photoId]);
    }

    /**
     * Recalculate and update collateral_items market_value based on latest base rate and item purity.
     *
     * @param string $itemType 'GOLD', 'SILVER', or empty string for both.
     * @param float|null $baseRate100 Base rate for 100% pure metal (Gold: ₹/10g, Silver: ₹/g).
     * @return int Number of collateral items updated.
     */
    public static function recalculateCollateralMarketValues(string $itemType = '', ?float $baseRate100 = null): int {
        $types = [];
        $upperType = strtoupper(trim($itemType));
        if ($upperType === 'GOLD' || $upperType === 'SILVER') {
            $types[] = $upperType;
        } else {
            $types = ['GOLD', 'SILVER'];
        }

        $totalUpdated = 0;

        // Load active preset mappings from rate_karat_presets table
        $presetRows = Database::fetchAll("SELECT type, name, purity_percentage FROM rate_karat_presets WHERE is_active = 1");
        $presetMap = [];
        foreach ($presetRows as $pr) {
            $pType = strtoupper($pr['type']);
            $pName = strtoupper(trim($pr['name']));
            $presetMap[$pType][$pName] = floatval($pr['purity_percentage']);
        }

        // Standard fallback mappings
        $standardGold = [
            '24K' => 100.00, '23K' => 95.83, '22K' => 91.67, '21K' => 87.50,
            '20K' => 83.33, '18K' => 75.00, '14K' => 58.33, '10K' => 41.67,
            '100% (FINE)' => 100.00, '100%' => 100.00
        ];
        $standardSilver = [
            '100% (PURE)' => 100.00, '100%' => 100.00, '99.9%' => 99.90,
            '95.0%' => 95.00, '92.5%' => 92.50, '80.0%' => 80.00, '75.0%' => 75.00
        ];

        foreach ($types as $type) {
            $rate100 = $baseRate100;
            if ($rate100 === null || $rate100 <= 0) {
                if ($type === 'GOLD') {
                    $latest = RateModel::getLatestGoldRate();
                    $rate100 = floatval($latest['rate_100'] ?? round(floatval($latest['rate_24k'] ?? 79920) / 0.999, 2));
                } else {
                    $latest = RateModel::getLatestSilverRate();
                    $rate100 = floatval($latest['rate_100'] ?? round(floatval($latest['rate_999'] ?? 90) / 0.999, 2));
                }
            }

            if ($rate100 <= 0) {
                continue;
            }

            $items = Database::fetchAll("SELECT id, gross_weight, stone_weight, net_weight, purity_preset, purity_percentage, market_value FROM collateral_items WHERE item_type = :itype", [
                ':itype' => $type
            ]);

            foreach ($items as $item) {
                $itemId = intval($item['id']);
                $netWt = floatval($item['net_weight']);
                if ($netWt <= 0 && floatval($item['gross_weight']) > 0) {
                    $netWt = max(0, floatval($item['gross_weight']) - floatval($item['stone_weight'] ?? 0));
                }

                $purityPct = floatval($item['purity_percentage']);
                if ($purityPct <= 0) {
                    $presetKey = strtoupper(trim($item['purity_preset'] ?? ''));
                    if (isset($presetMap[$type][$presetKey])) {
                        $purityPct = $presetMap[$type][$presetKey];
                    } elseif ($type === 'GOLD' && isset($standardGold[$presetKey])) {
                        $purityPct = $standardGold[$presetKey];
                    } elseif ($type === 'SILVER' && isset($standardSilver[$presetKey])) {
                        $purityPct = $standardSilver[$presetKey];
                    } elseif (preg_match('/(\d+(\.\d+)?)/', $presetKey, $m)) {
                        $purityPct = floatval($m[1]);
                    } else {
                        $purityPct = 100.00;
                    }
                }

                if ($type === 'GOLD') {
                    // Gold base rate is ₹ / 10g => gram rate = (rate_100 / 10.0) * (purity / 100.0)
                    $ratePerGram = ($rate100 / 10.0) * ($purityPct / 100.0);
                } else {
                    // Silver base rate is ₹ / 1g => gram rate = rate_100 * (purity / 100.0)
                    $ratePerGram = $rate100 * ($purityPct / 100.0);
                }

                $newMarketValue = round($netWt * $ratePerGram, 2);

                Database::execute("
                    UPDATE collateral_items 
                    SET market_value = :mval,
                        net_weight = :net,
                        purity_percentage = :purity
                    WHERE id = :id
                ", [
                    ':mval'   => $newMarketValue,
                    ':net'    => $netWt,
                    ':purity' => $purityPct,
                    ':id'     => $itemId
                ]);

                $totalUpdated++;
            }
        }

        return $totalUpdated;
    }
}
