<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "=======================================================\n";
echo "LMS v2.0 - Features Verification Suite\n";
echo "=======================================================\n\n";

try {
    // 1. Test Rack & Slot Table Auto-Creation & Initial Seeding
    echo "[TEST 1] Testing RackManager ensureTablesExist() & Seeding...\n";
    \App\Helpers\RackManager::ensureTablesExist();
    $db = \App\Config\Database::connect();
    
    $racksCount = $db->query("SELECT COUNT(*) FROM racks")->fetchColumn();
    $slotsCount = $db->query("SELECT COUNT(*) FROM rack_slots")->fetchColumn();
    echo "  -> Found {$racksCount} racks and {$slotsCount} slots in database.\n";
    assert($racksCount >= 10, "Racks count should be at least 10");
    assert($slotsCount >= 150, "Slots count should be at least 150");
    echo "  [PASS] Initial Racks & Slots tables exist and seeded!\n\n";

    // 2. Test Create, Update, Delete Rack
    echo "[TEST 2] Testing Dynamic Rack CRUD Operations...\n";
    $testRackNum = 999;
    // Cleanup if exists
    $existing = $db->query("SELECT id FROM racks WHERE rack_number = {$testRackNum}")->fetch();
    if ($existing) {
        $db->exec("DELETE FROM racks WHERE id = {$existing['id']}");
    }

    $newRackId = \App\Helpers\RackManager::createRack([
        'rack_number' => $testRackNum,
        'name'        => 'Test Safe Vault',
        'total_slots' => 5,
        'location'    => 'Test Locker Room',
        'description' => 'Automated test vault unit'
    ]);
    echo "  -> Created Test Rack with ID: {$newRackId}\n";
    
    $rackData = \App\Helpers\RackManager::getRackById($newRackId);
    assert($rackData['total_slots'] == 5, "Total slots should be 5");
    assert(count($rackData['slots']) == 5, "Slots array should have 5 elements");

    \App\Helpers\RackManager::updateRack($newRackId, [
        'name'        => 'Updated Test Safe Vault',
        'location'    => 'Updated Location',
        'status'      => 'Maintenance'
    ]);
    $updatedRack = \App\Helpers\RackManager::getRackById($newRackId);
    assert($updatedRack['name'] === 'Updated Test Safe Vault', "Rack name should be updated");
    echo "  -> Updated Test Rack successfully.\n";

    // Test Slot CRUD
    echo "[TEST 3] Testing Dynamic Slot CRUD Operations...\n";
    $newSlotId = \App\Helpers\RackManager::createSlot($newRackId, [
        'slot_number' => 6,
        'slot_name'   => 'Updated Test Safe Vault - Slot 6',
        'notes'       => 'Special test slot'
    ]);
    echo "  -> Created Test Slot with ID: {$newSlotId}\n";

    \App\Helpers\RackManager::updateSlot($newSlotId, [
        'slot_name' => 'Updated Test Safe Vault - Slot 6 Renamed',
        'notes'     => 'Updated notes'
    ]);
    echo "  -> Updated Test Slot.\n";

    \App\Helpers\RackManager::deleteSlot($newSlotId);
    echo "  -> Deleted Test Slot.\n";

    // Delete test rack
    \App\Helpers\RackManager::deleteRack($newRackId);
    echo "  -> Deleted Test Rack successfully.\n";
    echo "  [PASS] Dynamic Rack & Slot CRUD working flawlessly!\n\n";

    // 4. Test BaseController Base64 Camera Upload processing
    echo "[TEST 4] Testing Camera Base64 Image Processing in BaseController...\n";
    $dummyController = new class extends \App\Controllers\BaseController {
        public function testBase64(string $base64) {
            return $this->handleBase64Upload($base64, 'test_proofs');
        }
    };

    // 1x1 transparent PNG data URI
    $testBase64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    $savedPath = $dummyController->testBase64($testBase64Png);
    echo "  -> Processed Camera Base64. Saved to: {$savedPath}\n";
    assert(!empty($savedPath), "Saved path should not be empty");
    
    $fullPath = __DIR__ . '/../public/' . ltrim($savedPath, '/');
    assert(file_exists($fullPath), "Saved file must physically exist on disk");
    echo "  -> Verified physical file on disk: " . filesize($fullPath) . " bytes\n";
    @unlink($fullPath); // Cleanup
    echo "  [PASS] Camera Base64 processing verified!\n\n";

    // 5. Test Matrix visualizer generation
    echo "[TEST 5] Testing Visualizer Matrix generation...\n";
    $grid = \App\Helpers\RackManager::getRackGridData();
    echo "  -> Total Vault Capacity: {$grid['total_capacity']}\n";
    echo "  -> Occupied: {$grid['total_occupied']}, Free: {$grid['total_free']}, Occupancy: {$grid['occupancy_pct']}%\n";
    echo "  -> Total Racks in Grid: " . count($grid['racks']) . "\n";
    assert($grid['total_capacity'] >= 150, "Grid capacity should be at least 150");
    echo "  [PASS] Rack Matrix visualizer data verified!\n\n";

    echo "=======================================================\n";
    echo "ALL SUITE TESTS PASSED SUCCESSFULLY! \n";
    echo "=======================================================\n";

} catch (\Throwable $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
