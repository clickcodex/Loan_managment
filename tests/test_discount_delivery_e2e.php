<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Helpers/RackManager.php';
require_once __DIR__ . '/../app/Helpers/AuditLogger.php';
require_once __DIR__ . '/../app/Helpers/Session.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';

use App\Config\Database;
use App\Helpers\RackManager;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\CollateralModel;
use App\Models\PaymentModel;

echo "=== STARTING DISCOUNT, FULL SETTLEMENT & JEWELLERY DELIVERY E2E TEST ===\n\n";

$db = Database::connect();

// 1. Get or create a test customer
$customerStmt = $db->query("SELECT id FROM customers LIMIT 1");
$customer = $customerStmt->fetch();
$createdTestCustomer = false;
if (!$customer) {
    $customerId = Customer::create([
        'customer_id'    => Customer::generateNextCustomerId(),
        'account_number' => Customer::generateNextAccountNumber(),
        'full_name'      => 'Test Automated Customer',
        'mobile'         => '9876543210',
        'status'         => 'Active'
    ]);
    $createdTestCustomer = true;
} else {
    $customerId = intval($customer['id']);
}

RackManager::ensureTablesExist();
$testSlot = RackManager::getNextAvailableSlot() ?: 'Rack 1 - Slot 1';
$slotKey = strtolower(trim($testSlot));

echo "[1] Test Slot Selected: {$testSlot} (Key: {$slotKey})\n";

// 3. Create a test Loan for ₹10,000
$testLoanNumber = 'TEST-LN-' . time();
$loanData = [
    'customer_id'       => $customerId,
    'loan_number'       => $testLoanNumber,
    'loan_date'         => date('Y-m-d'),
    'principal_amount'  => 10000.00,
    'interest_rate'     => 1.50,
    'interest_cycle'    => 'Monthly',
    'security_type'     => 'Gold Secured',
    'status'            => 'Running',
    'notes'             => 'Automated test loan for discount & delivery'
];

$loanId = Loan::createLoan($loanData);
echo "[2] Created Loan ID: {$loanId} ({$testLoanNumber}) with Principal ₹10,000.00\n";

// 4. Create a Collateral item assigned to $testSlot
$collateralData = [
    'loan_id'           => $loanId,
    'item_type'         => 'Gold',
    'item_name'         => '22K Gold Chain Test',
    'quantity'          => 1,
    'gross_weight'      => 20.00,
    'net_weight'        => 19.50,
    'purity_preset'     => '22K (91.6%)',
    'purity_percentage' => 91.60,
    'market_rate'       => 7000.00,
    'market_value'      => 136500.00,
    'rk_number'         => $testSlot,
    'remarks'           => 'Pledged in slot ' . $testSlot
];

$collateralId = CollateralModel::createItem($collateralData);
echo "[3] Created Collateral Item ID: {$collateralId} assigned to Slot: {$testSlot}\n";

// 5. Verify Slot is now OCCUPIED
$occupiedSlots = RackManager::getOccupiedSlotMap();
if (isset($occupiedSlots[$slotKey])) {
    echo "  -> SUCCESS: Slot {$testSlot} is verified OCCUPIED by Loan {$testLoanNumber} (Collateral: {$occupiedSlots[$slotKey]['item_name']})\n";
} else {
    echo "  -> ERROR: Slot {$testSlot} was expected to be occupied, but was not found in occupied map!\n";
    exit(1);
}

// 6. Test Payment with Discount: Amount Received = ₹9,500, Discount = ₹500, Jewellery Delivered = true
$receiptNo = 'RCT-TEST-' . time();
$paymentData = [
    'receipt_number'      => $receiptNo,
    'loan_id'             => $loanId,
    'payment_date'        => date('Y-m-d'),
    'payment_type'        => 'Full Settlement',
    'total_amount'        => 9500.00,
    'discount'            => 500.00,
    'is_full_settlement'  => true,
    'jewellery_delivered' => true,
    'delivery_remarks'    => 'Delivered in good condition during settlement',
    'haste'               => 'Customer Self',
    'interest_component'  => 9500.00,
    'principal_component' => 0.00,
    'penalty_component'   => 0.00,
    'payment_mode'        => 'Cash',
    'reference_number'    => 'CASH-SETTLE-001',
    'remarks'             => 'Test full settlement with ₹500 discount'
];

$paymentId = PaymentModel::recordPayment($paymentData);
echo "[4] Recorded Payment ID: {$paymentId}, Amount Received: ₹9,500.00, Discount: ₹500.00, Total Deduction: ₹10,000.00\n";

// 7. Verify Loan status & delivery in database
$updatedLoan = Loan::findById($loanId);
echo "[5] Verifying Updated Loan State:\n";
echo "  - Status: {$updatedLoan['status']} (Expected: Closed)\n";
echo "  - Delivered: {$updatedLoan['delivered']} (Expected: Yes)\n";
echo "  - Delivery Date: {$updatedLoan['delivery_date']} (Expected: " . date('Y-m-d') . ")\n";

if ($updatedLoan['status'] !== 'Closed') {
    echo "  -> FAILED: Loan status should be Closed!\n";
    exit(1);
}
if ($updatedLoan['delivered'] !== 'Yes') {
    echo "  -> FAILED: Loan delivered flag should be 'Yes'!\n";
    exit(1);
}

// 8. Verify Ledger Transactions
$ledger = Loan::getLedger($loanId);
echo "[6] Verifying Loan Ledger Entries (" . count($ledger) . " entries):\n";
$latestBalance = 0;
foreach ($ledger as $entry) {
    echo "  * [{$entry['entry_date']}] {$entry['entry_type']}: Dr ₹{$entry['debit']}, Cr ₹{$entry['credit']}, Bal ₹{$entry['balance']} | {$entry['description']}\n";
    $latestBalance = floatval($entry['balance']);
}

if ($latestBalance > 0) {
    echo "  -> FAILED: Final ledger running balance is ₹{$latestBalance}, expected ₹0.00!\n";
    exit(1);
} else {
    echo "  -> SUCCESS: Final ledger balance is ₹0.00 (Fully Settled)\n";
}

// 9. Verify Rack Slot is now EMPTY and AVAILABLE
$occupiedSlotsAfter = RackManager::getOccupiedSlotMap();
if (!isset($occupiedSlotsAfter[$slotKey])) {
    echo "[7] SUCCESS: Slot {$testSlot} is now EMPTY & AVAILABLE in RackManager visualizer!\n";
} else {
    echo "[7] ERROR: Slot {$testSlot} is STILL listed as occupied by Loan ID: " . $occupiedSlotsAfter[$slotKey]['loan_id'] . "\n";
    exit(1);
}

// 10. Test Standalone Delivery for a second loan
echo "\n--- TESTING STANDALONE JEWELLERY DELIVERY METHOD ---\n";
$testLoan2 = 'TEST-LN2-' . time();
$loan2Id = Loan::createLoan([
    'customer_id'       => $customerId,
    'loan_number'       => $testLoan2,
    'loan_date'         => date('Y-m-d'),
    'principal_amount'  => 5000.00,
    'interest_rate'     => 1.50,
    'interest_cycle'    => 'Monthly',
    'security_type'     => 'Gold Secured',
    'status'            => 'Running'
]);
$collateral2Id = CollateralModel::createItem([
    'loan_id'           => $loan2Id,
    'item_type'         => 'Gold',
    'item_name'         => 'Gold Ring Test 2',
    'quantity'          => 1,
    'gross_weight'      => 8.00,
    'net_weight'        => 7.80,
    'purity_preset'     => '22K (91.6%)',
    'purity_percentage' => 91.60,
    'market_rate'       => 7000.00,
    'market_value'      => 54600.00,
    'rk_number'         => $testSlot
]);

$occupiedBeforeStandalone = RackManager::getOccupiedSlotMap();
if (!isset($occupiedBeforeStandalone[$slotKey])) {
    echo "  -> ERROR: Slot {$testSlot} not occupied after Loan 2!\n";
    exit(1);
}
echo "[8] Standalone Loan {$testLoan2} occupied slot {$testSlot}\n";

// Deliver jewellery
Loan::deliverJewellery($loan2Id, 'Handed over to customer', 'Customer Self');
$occupiedAfterStandalone = RackManager::getOccupiedSlotMap();
if (!isset($occupiedAfterStandalone[$slotKey])) {
    echo "[9] SUCCESS: Standalone Loan::deliverJewellery successfully released slot {$testSlot}!\n";
} else {
    echo "[9] ERROR: Slot {$testSlot} still occupied after deliverJewellery!\n";
    exit(1);
}

// Cleanup test data
$db->exec("DELETE FROM payments WHERE loan_id IN ({$loanId}, {$loan2Id})");
$db->exec("DELETE FROM loan_ledger WHERE loan_id IN ({$loanId}, {$loan2Id})");
$db->exec("DELETE FROM collateral_items WHERE loan_id IN ({$loanId}, {$loan2Id})");
$db->exec("DELETE FROM loans WHERE id IN ({$loanId}, {$loan2Id})");
if (!empty($createdTestCustomer)) {
    $db->exec("DELETE FROM customers WHERE id = {$customerId}");
}
echo "[10] Cleaned up temporary test data.\n";

echo "\n=== ALL TESTS PASSED SUCCESSFULLY (100% GREEN) ===\n";
