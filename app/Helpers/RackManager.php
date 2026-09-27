<?php

namespace App\Helpers;

use App\Config\Database;
use Exception;

class RackManager {

    private static bool $tablesChecked = false;

    /**
     * Ensure racks and rack_slots tables exist, and seed initial 10 racks (15 slots each) if empty.
     */
    public static function ensureTablesExist(): void {
        if (self::$tablesChecked) {
            return;
        }

        $db = Database::connect();
        if ($db->inTransaction()) {
            return;
        }

        self::$tablesChecked = true;

        $createRacksTable = "CREATE TABLE IF NOT EXISTS `racks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `rack_number` int(11) NOT NULL UNIQUE,
            `name` varchar(100) NOT NULL,
            `total_slots` int(11) NOT NULL DEFAULT 15,
            `location` varchar(100) DEFAULT 'Main Vault',
            `description` text DEFAULT NULL,
            `status` enum('Active','Maintenance','Disabled') NOT NULL DEFAULT 'Active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $createSlotsTable = "CREATE TABLE IF NOT EXISTS `rack_slots` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `rack_id` int(11) NOT NULL,
            `slot_number` int(11) NOT NULL,
            `slot_name` varchar(100) NOT NULL,
            `status` enum('Available','Occupied','Reserved','Disabled') NOT NULL DEFAULT 'Available',
            `notes` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_rack_slot` (`rack_id`, `slot_number`),
            KEY `idx_slot_name` (`slot_name`),
            CONSTRAINT `fk_rack_slots_rack` FOREIGN KEY (`rack_id`) REFERENCES `racks` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $db->exec($createRacksTable);
        $db->exec($createSlotsTable);

        // Check if racks table is empty; if so, seed default 10 racks with 15 slots each
        $count = $db->query("SELECT COUNT(*) FROM `racks`")->fetchColumn();
        if ($count == 0) {
            for ($r = 1; $r <= 10; $r++) {
                $stmt = $db->prepare("INSERT INTO `racks` (`rack_number`, `name`, `total_slots`, `location`, `description`, `status`) VALUES (:num, :name, :slots, :loc, :desc, 'Active')");
                $stmt->execute([
                    ':num'   => $r,
                    ':name'  => 'Rack ' . $r,
                    ':slots' => 15,
                    ':loc'   => 'Main Vault Room',
                    ':desc'  => 'Standard 15-slot high security physical rack'
                ]);

                $rackId = intval($db->lastInsertId());

                for ($s = 1; $s <= 15; $s++) {
                    $slotName = sprintf("Rack %d - Slot %d", $r, $s);
                    $slotStmt = $db->prepare("INSERT INTO `rack_slots` (`rack_id`, `slot_number`, `slot_name`, `status`) VALUES (:rid, :snum, :sname, 'Available')");
                    $slotStmt->execute([
                        ':rid'   => $rackId,
                        ':snum'  => $s,
                        ':sname' => $slotName
                    ]);
                }
            }
        }
    }

    /**
     * Standardized slot name format helper.
     */
    public static function formatSlotName(int $rackNumber, int $slotNumber): string {
        return sprintf("Rack %d - Slot %d", $rackNumber, $slotNumber);
    }

    /**
     * Get all currently occupied rack slots mapped to their item details.
     * Only items linked to active/running loans are considered occupying slots.
     */
    public static function getOccupiedSlotMap(): array {
        self::ensureTablesExist();

        $sql = "SELECT ci.id as item_id, ci.item_name, ci.item_type, ci.rk_number, ci.gross_weight, ci.net_weight, ci.purity_preset, ci.purity_percentage,
                       ci.market_value, ci.manual_market_value_override,
                       l.id as loan_id, l.loan_number, l.status as loan_status,
                       c.id as customer_id, c.full_name as customer_name, c.mobile as customer_mobile
                FROM collateral_items ci
                JOIN loans l ON ci.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE ci.rk_number IS NOT NULL 
                  AND TRIM(ci.rk_number) != ''
                  AND l.status != 'Closed'
                  AND (l.delivered = 'No' OR l.delivered IS NULL)";

        $rows = Database::fetchAll($sql);
        $map = [];

        foreach ($rows as $row) {
            $normalizedKey = strtolower(trim($row['rk_number']));
            $map[$normalizedKey] = $row;
        }

        return $map;
    }

    /**
     * Find the next available rack slot across all active database racks and slots.
     */
    public static function getNextAvailableSlot(): ?string {
        self::ensureTablesExist();
        $occupiedMap = self::getOccupiedSlotMap();

        $sql = "SELECT rs.slot_name 
                FROM rack_slots rs
                JOIN racks r ON rs.rack_id = r.id
                WHERE r.status = 'Active' AND rs.status = 'Available'
                ORDER BY r.rack_number ASC, rs.slot_number ASC";

        $allSlots = Database::fetchAll($sql);

        foreach ($allSlots as $slotRow) {
            $slotName = $slotRow['slot_name'];
            $key = strtolower(trim($slotName));

            if (!isset($occupiedMap[$key])) {
                return $slotName;
            }
        }

        return null;
    }

    /**
     * Generate dynamic matrix data for UI visualizer and management dashboard.
     */
    public static function getRackGridData(): array {
        self::ensureTablesExist();
        $occupiedMap = self::getOccupiedSlotMap();

        $racksRows = Database::fetchAll("SELECT * FROM racks ORDER BY rack_number ASC");
        $racks = [];
        $totalCapacity = 0;
        $totalOccupied = 0;

        foreach ($racksRows as $rRow) {
            $rackId = intval($rRow['id']);
            $rNum = intval($rRow['rack_number']);
            $rName = $rRow['name'];

            $slotsRows = Database::fetchAll("SELECT * FROM rack_slots WHERE rack_id = :rid ORDER BY slot_number ASC", [':rid' => $rackId]);

            $slots = [];
            $rackOccupiedCount = 0;

            foreach ($slotsRows as $sRow) {
                $sNum = intval($sRow['slot_number']);
                $sName = $sRow['slot_name'];
                $key = strtolower(trim($sName));

                $isOccupied = isset($occupiedMap[$key]);
                $itemInfo = $isOccupied ? $occupiedMap[$key] : null;

                if ($isOccupied) {
                    $rackOccupiedCount++;
                    $totalOccupied++;
                }

                $slots[] = [
                    'id'          => intval($sRow['id']),
                    'rack_id'     => $rackId,
                    'slot_number' => $sNum,
                    'slot_name'   => $sName,
                    'is_occupied' => $isOccupied,
                    'status'      => $sRow['status'],
                    'notes'       => $sRow['notes'],
                    'item'        => $itemInfo
                ];
            }

            $rackCapacity = count($slots);
            $totalCapacity += $rackCapacity;

            $racks[] = [
                'id'              => $rackId,
                'rack_number'     => $rNum,
                'rack_title'      => $rName,
                'name'            => $rName,
                'location'        => $rRow['location'],
                'description'     => $rRow['description'],
                'status'          => $rRow['status'],
                'total_slots'     => $rackCapacity,
                'occupied_count'  => $rackOccupiedCount,
                'available_count' => max(0, $rackCapacity - $rackOccupiedCount),
                'occupancy_pct'   => $rackCapacity > 0 ? round(($rackOccupiedCount / $rackCapacity) * 100, 1) : 0,
                'slots'           => $slots
            ];
        }

        return [
            'total_capacity' => $totalCapacity,
            'total_occupied' => $totalOccupied,
            'total_free'     => max(0, $totalCapacity - $totalOccupied),
            'occupancy_pct'  => $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100, 1) : 0,
            'racks'          => $racks
        ];
    }

    /**
     * Get all racks with slots and item mapping.
     */
    public static function getAllRacks(): array {
        return self::getRackGridData()['racks'];
    }

    /**
     * Find a rack by ID with its slots.
     */
    public static function getRackById(int $id): ?array {
        self::ensureTablesExist();
        $rack = Database::fetchOne("SELECT * FROM racks WHERE id = :id", [':id' => $id]);
        if (!$rack) return null;

        $occupiedMap = self::getOccupiedSlotMap();
        $slotsRows = Database::fetchAll("SELECT * FROM rack_slots WHERE rack_id = :id ORDER BY slot_number ASC", [':id' => $id]);
        
        $slots = [];
        $occupiedCount = 0;
        foreach ($slotsRows as $s) {
            $key = strtolower(trim($s['slot_name']));
            $isOcc = isset($occupiedMap[$key]);
            if ($isOcc) $occupiedCount++;

            $slots[] = array_merge($s, [
                'is_occupied' => $isOcc,
                'item'        => $isOcc ? $occupiedMap[$key] : null
            ]);
        }

        $rack['slots'] = $slots;
        $rack['occupied_count'] = $occupiedCount;
        $rack['total_slots'] = count($slots);
        return $rack;
    }

    /**
     * Add a new rack and automatically generate its slots.
     */
    public static function createRack(array $data): int {
        self::ensureTablesExist();

        $rackNumber = intval($data['rack_number'] ?? 0);
        if ($rackNumber <= 0) {
            // Auto generate next rack number
            $maxRow = Database::fetchOne("SELECT MAX(rack_number) as max_num FROM racks");
            $rackNumber = intval($maxRow['max_num'] ?? 0) + 1;
        }

        // Check if rack number exists
        $existing = Database::fetchOne("SELECT id FROM racks WHERE rack_number = :num", [':num' => $rackNumber]);
        if ($existing) {
            throw new Exception("Rack Number #{$rackNumber} already exists. Please choose a different rack number.");
        }

        $name = trim($data['name'] ?? ('Rack ' . $rackNumber));
        $totalSlots = max(1, intval($data['total_slots'] ?? 15));
        $location = trim($data['location'] ?? 'Main Vault');
        $description = trim($data['description'] ?? '');
        $statusInput = $data['status'] ?? 'Active';
        $status = in_array($statusInput, ['Active', 'Maintenance', 'Disabled']) ? $statusInput : 'Active';

        $sql = "INSERT INTO racks (rack_number, name, total_slots, location, description, status, created_at)
                VALUES (:num, :name, :slots, :loc, :desc, :status, NOW())";
        
        Database::execute($sql, [
            ':num'    => $rackNumber,
            ':name'   => $name,
            ':slots'  => $totalSlots,
            ':loc'    => $location,
            ':desc'   => $description,
            ':status' => $status
        ]);

        $rackId = intval(Database::lastInsertId());

        // Create the initial slots
        for ($s = 1; $s <= $totalSlots; $s++) {
            $slotName = sprintf("%s - Slot %d", $name, $s);
            Database::execute(
                "INSERT INTO rack_slots (rack_id, slot_number, slot_name, status, created_at) VALUES (:rid, :snum, :sname, 'Available', NOW())",
                [':rid' => $rackId, ':snum' => $s, ':sname' => $slotName]
            );
        }

        return $rackId;
    }

    /**
     * Update an existing rack.
     */
    public static function updateRack(int $id, array $data): bool {
        self::ensureTablesExist();

        $rack = Database::fetchOne("SELECT * FROM racks WHERE id = :id", [':id' => $id]);
        if (!$rack) {
            throw new Exception("Rack not found.");
        }

        $name = trim($data['name'] ?? $rack['name']);
        $location = trim($data['location'] ?? $rack['location']);
        $description = trim($data['description'] ?? $rack['description']);
        $statusInput = $data['status'] ?? $rack['status'];
        $status = in_array($statusInput, ['Active', 'Maintenance', 'Disabled']) ? $statusInput : $rack['status'];

        $sql = "UPDATE racks SET name = :name, location = :loc, description = :desc, status = :status WHERE id = :id";
        return Database::execute($sql, [
            ':id'     => $id,
            ':name'   => $name,
            ':loc'    => $location,
            ':desc'   => $description,
            ':status' => $status
        ]);
    }

    /**
     * Delete a rack. Validates that no active collateral items are occupying any of its slots.
     */
    public static function deleteRack(int $id): bool {
        self::ensureTablesExist();

        $rack = self::getRackById($id);
        if (!$rack) {
            throw new Exception("Rack not found.");
        }

        if ($rack['occupied_count'] > 0) {
            throw new Exception("Cannot delete '{$rack['name']}' because it currently has {$rack['occupied_count']} occupied slot(s). Please transfer or unassign items first.");
        }

        return Database::execute("DELETE FROM racks WHERE id = :id", [':id' => $id]);
    }

    /**
     * Add a new slot to an existing rack.
     */
    public static function createSlot(int $rackId, array $data): int {
        self::ensureTablesExist();

        $rack = Database::fetchOne("SELECT * FROM racks WHERE id = :id", [':id' => $rackId]);
        if (!$rack) {
            throw new Exception("Rack not found.");
        }

        $slotNumber = intval($data['slot_number'] ?? 0);
        if ($slotNumber <= 0) {
            $maxSlotRow = Database::fetchOne("SELECT MAX(slot_number) as max_s FROM rack_slots WHERE rack_id = :rid", [':rid' => $rackId]);
            $slotNumber = intval($maxSlotRow['max_s'] ?? 0) + 1;
        }

        // Check if slot number exists for this rack
        $existing = Database::fetchOne("SELECT id FROM rack_slots WHERE rack_id = :rid AND slot_number = :snum", [
            ':rid'  => $rackId,
            ':snum' => $slotNumber
        ]);
        if ($existing) {
            throw new Exception("Slot #{$slotNumber} already exists in {$rack['name']}.");
        }

        $slotName = trim($data['slot_name'] ?? '');
        if (empty($slotName)) {
            $slotName = sprintf("%s - Slot %d", $rack['name'], $slotNumber);
        }

        $notes = trim($data['notes'] ?? '');
        $statusInput = $data['status'] ?? 'Available';
        $status = in_array($statusInput, ['Available', 'Reserved', 'Disabled']) ? $statusInput : 'Available';

        Database::execute(
            "INSERT INTO rack_slots (rack_id, slot_number, slot_name, status, notes, created_at) VALUES (:rid, :snum, :sname, :status, :notes, NOW())",
            [
                ':rid'    => $rackId,
                ':snum'   => $slotNumber,
                ':sname'  => $slotName,
                ':status' => $status,
                ':notes'  => $notes ?: null
            ]
        );

        $newSlotId = intval(Database::lastInsertId());

        // Update total slots count on rack
        $total = Database::fetchOne("SELECT COUNT(*) as cnt FROM rack_slots WHERE rack_id = :rid", [':rid' => $rackId]);
        Database::execute("UPDATE racks SET total_slots = :tot WHERE id = :rid", [
            ':tot' => intval($total['cnt'] ?? 1),
            ':rid' => $rackId
        ]);

        return $newSlotId;
    }

    /**
     * Update an existing slot.
     */
    public static function updateSlot(int $id, array $data): bool {
        self::ensureTablesExist();

        $slot = Database::fetchOne("SELECT * FROM rack_slots WHERE id = :id", [':id' => $id]);
        if (!$slot) {
            throw new Exception("Slot not found.");
        }

        $oldSlotName = $slot['slot_name'];
        $newSlotName = trim($data['slot_name'] ?? $oldSlotName);
        $statusInput = $data['status'] ?? $slot['status'];
        $status = in_array($statusInput, ['Available', 'Occupied', 'Reserved', 'Disabled']) ? $statusInput : $slot['status'];
        $notes = trim($data['notes'] ?? ($slot['notes'] ?? ''));

        Database::execute(
            "UPDATE rack_slots SET slot_name = :sname, status = :status, notes = :notes WHERE id = :id",
            [
                ':id'     => $id,
                ':sname'  => $newSlotName,
                ':status' => $status,
                ':notes'  => $notes ?: null
            ]
        );

        // If slot name changed, also update any linked collateral item references
        if ($oldSlotName !== $newSlotName) {
            Database::execute(
                "UPDATE collateral_items SET rk_number = :newname WHERE rk_number = :oldname",
                [':newname' => $newSlotName, ':oldname' => $oldSlotName]
            );
        }

        return true;
    }

    /**
     * Delete a slot if unoccupied.
     */
    public static function deleteSlot(int $id): bool {
        self::ensureTablesExist();

        $slot = Database::fetchOne("SELECT * FROM rack_slots WHERE id = :id", [':id' => $id]);
        if (!$slot) {
            throw new Exception("Slot not found.");
        }

        $occupiedMap = self::getOccupiedSlotMap();
        $key = strtolower(trim($slot['slot_name']));

        if (isset($occupiedMap[$key])) {
            $item = $occupiedMap[$key];
            throw new Exception("Cannot delete slot '{$slot['slot_name']}' because item '{$item['item_name']}' (Loan: {$item['loan_number']}) is stored in it.");
        }

        $rackId = intval($slot['rack_id']);
        Database::execute("DELETE FROM rack_slots WHERE id = :id", [':id' => $id]);

        // Update total slots count on rack
        $total = Database::fetchOne("SELECT COUNT(*) as cnt FROM rack_slots WHERE rack_id = :rid", [':rid' => $rackId]);
        Database::execute("UPDATE racks SET total_slots = :tot WHERE id = :rid", [
            ':tot' => intval($total['cnt'] ?? 0),
            ':rid' => $rackId
        ]);

        return true;
    }

    /**
     * Reassign / Transfer collateral item from its current slot to a target slot.
     */
    public static function transferSlot(int $itemId, string $newSlotName): bool {
        self::ensureTablesExist();

        $item = Database::fetchOne("SELECT id, item_name, rk_number FROM collateral_items WHERE id = :id", [':id' => $itemId]);
        if (!$item) {
            throw new Exception("Collateral item not found.");
        }

        $newSlotName = trim($newSlotName);
        if (empty($newSlotName)) {
            throw new Exception("Target slot name cannot be empty.");
        }

        // Check if target slot is occupied by another item
        $occupiedMap = self::getOccupiedSlotMap();
        $targetKey = strtolower($newSlotName);

        if (isset($occupiedMap[$targetKey]) && $occupiedMap[$targetKey]['item_id'] != $itemId) {
            $occItem = $occupiedMap[$targetKey];
            throw new Exception("Slot '{$newSlotName}' is already occupied by '{$occItem['item_name']}' (Loan: {$occItem['loan_number']}).");
        }

        Database::execute("UPDATE collateral_items SET rk_number = :rk WHERE id = :id", [
            ':rk' => $newSlotName,
            ':id' => $itemId
        ]);

        return true;
    }

    /**
     * Unassign slot from a collateral item.
     */
    public static function unassignSlot(int $itemId): bool {
        return Database::execute("UPDATE collateral_items SET rk_number = NULL WHERE id = :id", [':id' => $itemId]);
    }

    /**
     * Check if a specific slot is currently occupied by an active, non-delivered loan.
     */
    public static function isSlotOccupied(string $slotName, ?int $excludeItemId = null): bool {
        $slotKey = strtolower(trim($slotName));
        if (empty($slotKey)) {
            return false;
        }

        $occupiedMap = self::getOccupiedSlotMap();
        if (!isset($occupiedMap[$slotKey])) {
            return false;
        }

        if ($excludeItemId !== null && intval($occupiedMap[$slotKey]['item_id']) === $excludeItemId) {
            return false;
        }

        return true;
    }

    /**
     * Batch auto-assign available slots to any collateral items missing a rack assignment.
     */
    public static function autoAssignAllUnassigned(): int {
        self::ensureTablesExist();

        // Find unassigned collateral items linked to active/running loans
        $sql = "SELECT ci.id 
                FROM collateral_items ci
                JOIN loans l ON ci.loan_id = l.id
                WHERE (ci.rk_number IS NULL OR TRIM(ci.rk_number) = '')
                  AND l.status != 'Closed'
                  AND (l.delivered = 'No' OR l.delivered IS NULL)
                ORDER BY ci.id ASC";

        $unassignedItems = Database::fetchAll($sql);
        $assignedCount = 0;

        foreach ($unassignedItems as $item) {
            $nextSlot = self::getNextAvailableSlot();
            if (!$nextSlot) {
                break; // Vault full
            }

            Database::execute(
                "UPDATE collateral_items SET rk_number = :rk WHERE id = :id",
                [':rk' => $nextSlot, ':id' => $item['id']]
            );

            $assignedCount++;
        }

        return $assignedCount;
    }

    /**
     * Count unassigned active collateral items.
     */
    public static function getUnassignedCount(): int {
        $row = Database::fetchOne("
            SELECT COUNT(*) as cnt 
            FROM collateral_items ci
            JOIN loans l ON ci.loan_id = l.id
            WHERE (ci.rk_number IS NULL OR TRIM(ci.rk_number) = '')
              AND l.status != 'Closed'
              AND (l.delivered = 'No' OR l.delivered IS NULL)
        ");

        return intval($row['cnt'] ?? 0);
    }
}
