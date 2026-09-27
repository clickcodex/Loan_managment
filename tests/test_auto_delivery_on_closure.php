<?php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';
require_once __DIR__ . '/../app/Helpers/RackManager.php';

use App\Config\Database;
use App\Models\Loan;
use App\Models\CollateralModel;
use App\Models\PaymentModel;
use App\Helpers\RackManager;

echo "======================================================================\n";
echo "TEST SUITE: AUTOMATIC JEWELLERY DELIVERY ON FULL LOAN CLOSURE\n";
echo "======================================================================\n";

$db = Database::connect();
$loanId = null;
$customerId = null;

try {
    // 1. Setup Customer
    $custCode = 'CUST-AUTODELIV-' . time();
    $db->prepare("INSERT INTO customers (customer_id, full_name, mobile, address, status, created_at) VALUES (:c, 'Auto Delivery Test User', '9999988888', 'Test Address', 'Active', NOW())")
       ->execute([':c' => $custCode]);
    $customerId = intval($db->lastInsertId());

    // 2. Select test slot
    $testSlot = 'Rack 2 - Slot 5';
    $slotKey = strtolower(trim($testSlot));

    // 3. Create Loan
    $loanNumber = 'TEST-LN-AUTO-' . time();
    $loanId = Loan::createLoan([
        'customer_id'       => $customerId,
        'loan_number'       => $loanNumber,
        'loan_date'         => date('Y-m-d'),
        'principal_amount'  => 10000.00,
        'interest_rate'     => 2.00,
        'interest_cycle'    => 'Monthly',
        'interest_method'   => 'Simple',
        'security_type'     => 'Gold Secured',
        'status'            => 'Running'
    ]);

    // 4. Create Collateral Item assigned to test slot
    $itemId = CollateralModel::createItem([
        'loan_id'           => $loanId,
        'item_type'         => 'Gold',
        'item_name'         => '22K Gold Bangles',
        'quantity'          => 2,
        'gross_weight'      => 20.00,
        'net_weight'        => 19.50,
        'purity_preset'     => '22K',
        'purity_percentage' => 91.67,
        'market_value'      => 120000.00,
        'rk_number'         => $testSlot
    ]);

    echo "[TEST 1] Loan created (ID: {$loanId}) with Collateral Item in Slot: '{$testSlot}'\n";
    $loanBefore = Loan::findById($loanId);
    assert($loanBefore['status'] === 'Running', "Loan should be Running");
    assert(($loanBefore['delivered'] ?? 'No') === 'No', "Loan delivered should initially be No");

    // Verify slot is occupied in RackManager
    $occupied = RackManager::getOccupiedSlotMap();
    assert(isset($occupied[$slotKey]), "Slot should be occupied before payment");
    echo "  -> PASS: Slot is occupied and loan is Running with Delivered = No\n";

    // 5. Partial Payment (Should NOT close loan, should NOT deliver jewellery)
    echo "\n[TEST 2] Partial Payment of ₹3,000 (Balance remains ₹7,200)\n";
    $partialPayId = PaymentModel::recordPayment([
        'receipt_number'   => 'REC-PARTIAL-' . time(),
        'loan_id'          => $loanId,
        'payment_date'     => date('Y-m-d'),
        'payment_type'     => 'Payment Received',
        'total_amount'     => 3000.00,
        'payment_mode'     => 'Cash',
        'reference_number' => 'PART-001',
        'remarks'          => 'Partial payment test'
    ]);

    $loanAfterPartial = Loan::findById($loanId);
    echo "  -> Remaining Balance: ₹" . number_format($loanAfterPartial['remaining_balance'], 2) . "\n";
    echo "  -> Loan Status: " . $loanAfterPartial['status'] . " (Expected: Running)\n";
    echo "  -> Delivered: " . $loanAfterPartial['delivered'] . " (Expected: No)\n";
    assert($loanAfterPartial['status'] === 'Running', "Loan status should still be Running after partial payment");
    assert($loanAfterPartial['delivered'] === 'No', "Jewellery must NOT be delivered on partial payment");
    assert(isset(RackManager::getOccupiedSlotMap()[$slotKey]), "Slot must remain occupied after partial payment");
    echo "  -> PASS: Partial payment kept loan Running and jewellery un-delivered\n";

    // 6. Full Settlement Payment (Remaining balance paid off)
    $remainingBal = floatval($loanAfterPartial['remaining_balance']);
    echo "\n[TEST 3] Full Settlement Payment of ₹" . number_format($remainingBal, 2) . "\n";
    $fullPayId = PaymentModel::recordPayment([
        'receipt_number'   => 'REC-FULL-' . time(),
        'loan_id'          => $loanId,
        'payment_date'     => date('Y-m-d'),
        'payment_type'     => 'Full Settlement',
        'total_amount'     => $remainingBal,
        'payment_mode'     => 'Cash',
        'reference_number' => 'FULL-001',
        'remarks'          => 'Settlement payment'
    ]);

    $loanAfterFull = Loan::findById($loanId);
    echo "  -> Remaining Balance: ₹" . number_format($loanAfterFull['remaining_balance'], 2) . "\n";
    echo "  -> Loan Status: " . $loanAfterFull['status'] . " (Expected: Closed)\n";
    echo "  -> Delivered: " . $loanAfterFull['delivered'] . " (Expected: Yes)\n";
    echo "  -> Delivery Date: " . $loanAfterFull['delivery_date'] . " (Expected: " . date('Y-m-d') . ")\n";
    echo "  -> Delivery Remarks: " . $loanAfterFull['delivery_remarks'] . "\n";

    assert($loanAfterFull['status'] === 'Closed', "Loan must be Closed after full payment");
    assert($loanAfterFull['delivered'] === 'Yes', "Jewellery must be automatically marked Delivered upon loan closure");
    assert(!empty($loanAfterFull['delivery_date']), "Delivery date must be recorded");
    echo "  -> PASS: Loan is Closed and Jewellery is automatically marked Delivered!\n";

    // 7. Verify RackManager released the slot
    echo "\n[TEST 4] Verifying Rack Slot Availability in RackManager\n";
    $occupiedAfter = RackManager::getOccupiedSlotMap();
    assert(!isset($occupiedAfter[$slotKey]), "Slot should be FREE in RackManager after loan closure");
    echo "  -> PASS: Slot '{$testSlot}' is now immediately FREE & AVAILABLE for new loans!\n";

    // 8. Verify Collateral Item retained its assigned rack number
    $collateralItem = Database::fetchOne("SELECT id, rk_number FROM collateral_items WHERE id = :id", [':id' => $itemId]);
    assert($collateralItem['rk_number'] === $testSlot, "Collateral item must retain historical rack number");
    echo "  -> PASS: Collateral item {$itemId} retained rack assignment '{$collateralItem['rk_number']}' for audit trails\n";

    // 9. Verify UI Templates: No "Deliver Jewellery" button or checkbox in any views
    echo "\n[TEST 5] Verifying UI Views for Removal of Manual Deliver Jewellery Options\n";
    $viewsToCheck = [
        'loans/show.php'     => __DIR__ . '/../app/Views/loans/show.php',
        'payments/index.php' => __DIR__ . '/../app/Views/payments/index.php',
        'dues/index.php'     => __DIR__ . '/../app/Views/dues/index.php',
        'customers/show.php' => __DIR__ . '/../app/Views/customers/show.php',
    ];

    foreach ($viewsToCheck as $viewName => $filePath) {
        $content = file_get_contents($filePath);
        if (strpos($content, 'name="jewellery_delivered"') !== false) {
            echo "  -> FAILED: {$viewName} still contains name=\"jewellery_delivered\" checkbox!\n";
            exit(1);
        }
        if (strpos($content, 'Deliver Jewellery to Customer & Free Up') !== false) {
            echo "  -> FAILED: {$viewName} still contains manual delivery checkbox label!\n";
            exit(1);
        }
        echo "  -> PASS: {$viewName} has no manual delivery checkbox\n";
    }

    $loansShowContent = file_get_contents(__DIR__ . '/../app/Views/loans/show.php');
    if (strpos($loansShowContent, '@click="deliverModal = true"') !== false) {
        echo "  -> FAILED: loans/show.php still contains deliverModal trigger button!\n";
        exit(1);
    }
    if (strpos($loansShowContent, 'deliverModal') !== false) {
        echo "  -> FAILED: loans/show.php still references deliverModal!\n";
        exit(1);
    }
    echo "  -> PASS: loans/show.php has manual Deliver Jewellery button and modal completely removed\n";

    echo "\n======================================================================\n";
    echo "ALL TESTS PASSED WITH 100% SUCCESS!\n";
    echo "======================================================================\n";

} finally {
    // Cleanup
    echo "\n[CLEANUP] Removing test records...\n";
    if ($loanId) {
        $db->exec("DELETE FROM payments WHERE loan_id = {$loanId}");
        $db->exec("DELETE FROM loan_ledger WHERE loan_id = {$loanId}");
        $db->exec("DELETE FROM collateral_items WHERE loan_id = {$loanId}");
        $db->exec("DELETE FROM loans WHERE id = {$loanId}");
    }
    if ($customerId) {
        $db->exec("DELETE FROM customers WHERE id = {$customerId}");
    }
    echo "  -> Cleaned up successfully.\n";
}
