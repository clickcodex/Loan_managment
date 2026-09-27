<?php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Helpers/RackManager.php';

use App\Config\Database;
use App\Models\Loan;
use App\Models\Customer;
use App\Models\PaymentModel;
use App\Models\CollateralModel;
use App\Helpers\RackManager;

echo "======================================================================\n";
echo "TEST SUITE: JEWELLERY DELIVERY & RACK MANAGEMENT AUTOMATION\n";
echo "======================================================================\n\n";

$db = Database::connect();

// Clean up any stale test records from previous runs
$db->exec("DELETE FROM payments WHERE loan_id IN (SELECT id FROM loans WHERE loan_number LIKE 'TEST-LN%')");
$db->exec("DELETE FROM loan_ledger WHERE loan_id IN (SELECT id FROM loans WHERE loan_number LIKE 'TEST-LN%')");
$db->exec("DELETE FROM collateral_items WHERE loan_id IN (SELECT id FROM loans WHERE loan_number LIKE 'TEST-LN%')");
$db->exec("DELETE FROM loans WHERE loan_number LIKE 'TEST-LN%'");
$db->exec("DELETE FROM customers WHERE customer_id LIKE 'CUST-TEST%'");

// Setup Test Customer
$testCustCode = 'CUST-TEST-' . time();
$customerId = Customer::create([
    'customer_id'   => $testCustCode,
    'account_number'=> 'ACC-TEST-' . rand(100, 999),
    'full_name'     => 'Auto Delivery Test Customer',
    'mobile'        => '9876543219',
    'status'        => 'Active'
]);
echo "[SETUP] Created Test Customer ID: {$customerId} ({$testCustCode})\n";

// Slot to test
$testSlot = RackManager::getNextAvailableSlot() ?? 'Rack 3 - Slot 1';
$slotKey  = strtolower($testSlot);
echo "[SETUP] Selected Test Slot: {$testSlot}\n";

$loanAId = null;
$loanBId = null;

try {
    // --- REQUIREMENT 1 & 3: Create Loan A & Assign Slot ---
    $loanANumber = 'TEST-LN-A-' . time();
    $loanAId = Loan::createLoan([
        'customer_id'       => $customerId,
        'loan_number'       => $loanANumber,
        'loan_date'         => date('Y-m-d'),
        'principal_amount'  => 10000.00,
        'interest_rate'     => 1.50,
        'interest_cycle'    => 'Monthly',
        'security_type'     => 'Gold Secured',
        'status'            => 'Running'
    ]);
    $itemAId = CollateralModel::createItem([
        'loan_id'           => $loanAId,
        'item_type'         => 'Gold',
        'item_name'         => '22K Gold Bangles A',
        'quantity'          => 2,
        'gross_weight'      => 15.00,
        'net_weight'        => 14.50,
        'purity_preset'     => '22K (91.6%)',
        'purity_percentage' => 91.60,
        'market_value'      => 95000.00,
        'rk_number'         => $testSlot
    ]);

    echo "\n[TEST 1] Loan A Created (ID: {$loanAId}, Collateral Item ID: {$itemAId}) in Slot: {$testSlot}\n";

    // Verify initial state: Slot is occupied
    $occupiedMap = RackManager::getOccupiedSlotMap();
    if (isset($occupiedMap[$slotKey]) && intval($occupiedMap[$slotKey]['loan_id']) === $loanAId) {
        echo "  -> PASS: Slot {$testSlot} is verified OCCUPIED by Loan A\n";
    } else {
        echo "  -> FAILED: Slot {$testSlot} was not marked occupied by Loan A!\n";
        exit(1);
    }

    // Check initial delivery status
    $loanA = Loan::findById($loanAId);
    if (($loanA['delivered'] ?? 'No') === 'No' && $loanA['status'] === 'Running') {
        echo "  -> PASS: Initial state is Running and Delivered = No\n";
    } else {
        echo "  -> FAILED: Unexpected initial loan state!\n";
        exit(1);
    }

    // --- REQUIREMENT 1: Automatic Jewellery Delivery upon Full Settlement ---
    echo "\n[TEST 2] Recording Full Settlement Payment without manual delivery flag...\n";
    $totalDue = floatval($loanA['remaining_balance']);
    $receiptNoA = 'REC-TEST-A-' . time();

    // Note: jewellery_delivered is omitted / false. Full settlement must auto-deliver!
    $paymentDataA = [
        'receipt_number'      => $receiptNoA,
        'loan_id'             => $loanAId,
        'payment_date'        => date('Y-m-d'),
        'payment_type'        => 'Full Settlement',
        'total_amount'        => $totalDue,
        'discount'            => 0.00,
        'payment_mode'        => 'Cash',
        'reference_number'    => 'AUTO-DELIV-001',
        'remarks'             => 'Full payment test for automatic delivery'
    ];

    $paymentAId = PaymentModel::recordPayment($paymentDataA);
    echo "  -> Recorded Payment ID: {$paymentAId} for amount ₹" . number_format($totalDue, 2) . "\n";

    // Assert Loan A is Closed and automatically Delivered
    $loanAAfter = Loan::findById($loanAId);
    echo "  -> Loan Status: {$loanAAfter['status']} (Expected: Closed)\n";
    echo "  -> Loan Delivered: {$loanAAfter['delivered']} (Expected: Yes)\n";
    echo "  -> Delivery Date: {$loanAAfter['delivery_date']} (Expected: " . date('Y-m-d') . ")\n";

    if ($loanAAfter['status'] !== 'Closed') {
        echo "  -> FAILED: Loan A status should be Closed!\n";
        exit(1);
    }
    if ($loanAAfter['delivered'] !== 'Yes') {
        echo "  -> FAILED: Jewellery was NOT automatically marked as Delivered upon loan closure!\n";
        exit(1);
    }
    echo "  -> PASS: Requirement 1 Verified (Automatic delivery upon loan closure)\n";

    // --- REQUIREMENT 2: Rack Number Retention After Delivery ---
    echo "\n[TEST 3] Verifying Rack Number Retention After Delivery...\n";
    $itemAAfter = Database::fetchOne("SELECT id, rk_number FROM collateral_items WHERE id = :id", [':id' => $itemAId]);
    echo "  -> Collateral Item {$itemAId} rk_number in DB: '{$itemAAfter['rk_number']}'\n";

    if ($itemAAfter['rk_number'] !== $testSlot) {
        echo "  -> FAILED: Collateral rk_number was erased or modified! Expected '{$testSlot}', got '{$itemAAfter['rk_number']}'\n";
        exit(1);
    }
    echo "  -> PASS: Requirement 2 Verified (Rack number permanently retained in collateral record)\n";

    // --- REQUIREMENT 3: Rack Slot Availability & Slot Reuse ---
    echo "\n[TEST 4] Verifying Slot Availability and Reuse...\n";
    $occupiedMapAfterClosure = RackManager::getOccupiedSlotMap();

    if (!isset($occupiedMapAfterClosure[$slotKey])) {
        echo "  -> PASS: Slot {$testSlot} is immediately FREE & AVAILABLE in RackManager!\n";
    } else {
        echo "  -> FAILED: Slot {$testSlot} is still marked occupied by item: " . ($occupiedMapAfterClosure[$slotKey]['item_name'] ?? '') . " (Loan ID: " . ($occupiedMapAfterClosure[$slotKey]['loan_id'] ?? '') . ")\n";
        exit(1);
    }

    if (!RackManager::isSlotOccupied($testSlot)) {
        echo "  -> PASS: RackManager::isSlotOccupied('{$testSlot}') returned false\n";
    } else {
        echo "  -> FAILED: RackManager::isSlotOccupied returned true for freed slot!\n";
        exit(1);
    }

    // Assign the same slot to a NEW Loan B
    echo "\n[TEST 5] Assigning the released slot '{$testSlot}' to a new Loan B...\n";
    $loanBNumber = 'TEST-LN-B-' . time();
    $loanBId = Loan::createLoan([
        'customer_id'       => $customerId,
        'loan_number'       => $loanBNumber,
        'loan_date'         => date('Y-m-d'),
        'principal_amount'  => 5000.00,
        'interest_rate'     => 1.50,
        'interest_cycle'    => 'Monthly',
        'security_type'     => 'Gold Secured',
        'status'            => 'Running'
    ]);
    $itemBId = CollateralModel::createItem([
        'loan_id'           => $loanBId,
        'item_type'         => 'Gold',
        'item_name'         => 'Gold Ring B',
        'quantity'          => 1,
        'gross_weight'      => 6.00,
        'net_weight'        => 5.80,
        'purity_preset'     => '22K (91.6%)',
        'purity_percentage' => 91.60,
        'market_value'      => 38000.00,
        'rk_number'         => $testSlot
    ]);

    // Verify Slot is now re-occupied by Loan B
    $occupiedMapLoanB = RackManager::getOccupiedSlotMap();
    if (isset($occupiedMapLoanB[$slotKey]) && intval($occupiedMapLoanB[$slotKey]['loan_id']) === $loanBId) {
        echo "  -> PASS: Slot {$testSlot} successfully reassigned to Loan B (Item ID: {$itemBId})\n";
    } else {
        echo "  -> FAILED: Slot {$testSlot} could not be reassigned to Loan B!\n";
        exit(1);
    }

    // Verify Loan A's history STILL retains the rack number
    $itemACheckAgain = Database::fetchOne("SELECT id, rk_number FROM collateral_items WHERE id = :id", [':id' => $itemAId]);
    if ($itemACheckAgain['rk_number'] === $testSlot) {
        echo "  -> PASS: Loan A's collateral item {$itemAId} STILL retains '{$testSlot}' while Loan B is actively in it!\n";
    } else {
        echo "  -> FAILED: Loan A's rk_number was lost!\n";
        exit(1);
    }

    // --- REQUIREMENT 4: Payment Receipt (Rack retained, Company Name removed) ---
    echo "\n[TEST 6] Verifying Payment Receipt layout...\n";
    $payment = PaymentModel::findById($paymentAId);
    $collateralItems = Loan::getCollateralItems($loanAId);
    $baseUrl = '';

    ob_start();
    require __DIR__ . '/../app/Views/payments/receipt.php';
    $receiptHtml = ob_get_clean();

    if (strpos($receiptHtml, 'Golden Trust Finance Co.') !== false) {
        echo "  -> FAILED: 'Golden Trust Finance Co.' still found in payment receipt HTML!\n";
        exit(1);
    } else {
        echo "  -> PASS: 'Golden Trust Finance Co.' is completely REMOVED from receipt!\n";
    }

    if (strpos($receiptHtml, 'Payment Receipt') !== false) {
        echo "  -> PASS: 'Payment Receipt' header is properly displayed!\n";
    } else {
        echo "  -> FAILED: 'Payment Receipt' header missing!\n";
        exit(1);
    }

    if (strpos($receiptHtml, $testSlot) !== false) {
        echo "  -> PASS: Preserved Rack Number '{$testSlot}' is displayed on Loan A's payment receipt!\n";
    } else {
        echo "  -> FAILED: Preserved Rack Number '{$testSlot}' not found on receipt!\n";
        exit(1);
    }

    echo "\n======================================================================\n";
    echo "ALL 4 REQUIREMENTS VERIFIED AND PASSED WITH 100% SUCCESS!\n";
    echo "======================================================================\n";

} finally {
    // Cleanup Test Data
    echo "\n[CLEANUP] Cleaning up test records...\n";
    if ($loanAId || $loanBId) {
        $ids = array_filter([$loanAId, $loanBId]);
        $idList = implode(',', $ids);
        $db->exec("DELETE FROM payments WHERE loan_id IN ({$idList})");
        $db->exec("DELETE FROM loan_ledger WHERE loan_id IN ({$idList})");
        $db->exec("DELETE FROM collateral_items WHERE loan_id IN ({$idList})");
        $db->exec("DELETE FROM loans WHERE id IN ({$idList})");
    }
    $db->exec("DELETE FROM customers WHERE id = {$customerId}");
    echo "  -> Cleanup completed successfully.\n";
}
