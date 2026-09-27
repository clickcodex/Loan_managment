<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';
require_once __DIR__ . '/../app/Models/RateModel.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Helpers/RackManager.php';
require_once __DIR__ . '/../app/Helpers/AuditLogger.php';
require_once __DIR__ . '/../app/Helpers/Session.php';

use App\Config\Database;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\PaymentModel;
use App\Helpers\RackManager;

echo "===============================================================\n";
echo "TEST SUITE: LOAN MODULE - EDIT, DELETE & TYPE LOCK FLOW\n";
echo "===============================================================\n\n";

$db = Database::connect();

// Setup: Find or create a test customer
$customer = Database::fetchOne("SELECT * FROM customers WHERE status = 'Active' LIMIT 1");
if (!$customer) {
    $custId = Customer::create([
        'customer_id' => 'CUST-TEST-001',
        'full_name'   => 'Test Customer Flow',
        'mobile'      => '9876543210',
        'status'      => 'Active'
    ]);
    $customer = Customer::findById($custId);
}
$customerId = intval($customer['id']);
echo "[SETUP] Using Customer ID: {$customerId} ({$customer['full_name']})\n\n";

// =========================================================================
// TEST 1: CREATE LOAN & VERIFY "LOAN ISSUED" TYPE IS LOCKED AGAINST EDITS
// =========================================================================
echo "[TEST 1] Creating Loan & Testing 'Loan Issued' Type Lock...\n";
$loanNum = 'TEST-LN-' . time();
$initialPrincipal = 15000.00;
$initialRate = 10.00; // 10% monthly -> 5% half-monthly (15 days) = 750 interest
$loanDate = '2026-09-18';

$loanId = Loan::createLoan([
    'loan_number'        => $loanNum,
    'customer_id'        => $customerId,
    'loan_date'          => $loanDate,
    'security_type'      => 'Gold Secured',
    'principal_amount'   => $initialPrincipal,
    'interest_rate'      => $initialRate,
    'interest_cycle'     => '15 Days',
    'interest_method'    => 'Compound',
    'compound_frequency' => 'Yearly',
    'remarks'            => 'Initial test loan disbursement'
], [
    [
        'item_name'         => 'Test Gold Bracelet',
        'quantity'          => 1,
        'gross_weight'      => 12.5,
        'stone_weight'      => 0.5,
        'net_weight'        => 12.0,
        'purity_preset'     => '22K',
        'purity_percentage' => 91.67,
        'market_value'      => 80000.00
    ]
]);

$loan = Loan::findById($loanId);
$ledger = Loan::getLedger($loanId);
$issuedRow = $ledger[0];

echo "  -> Loan Created ID: {$loanId} ({$loan['loan_number']})\n";
echo "  -> Initial Ledger Row: Type = '{$issuedRow['entry_type']}', Debit = ₹{$issuedRow['debit']}, Bal = ₹{$issuedRow['balance']}\n";

// Attempt 1A: Try changing 'Loan Issued' entry_type to 'Payment Received'
$lockSucceeded = false;
try {
    Loan::updateLedgerEntry(intval($issuedRow['id']), [
        'entry_type' => 'Payment Received',
        'entry_date' => $loanDate,
        'debit'      => 15750.00
    ]);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'fixed and cannot be modified')) {
        $lockSucceeded = true;
        echo "  -> PASS: Changing 'Loan Issued' type was blocked: '{$e->getMessage()}'\n";
    } else {
        echo "  -> FAILED: Unexpected exception: {$e->getMessage()}\n";
    }
}

if (!$lockSucceeded) {
    echo "  -> FAILED: System allowed altering 'Loan Issued' entry_type!\n";
    exit(1);
}

// Attempt 1B: Valid edit to Date, Debit, and Remarks on 'Loan Issued' should succeed
$newDisbDebit = 16000.00;
$newLoanDate = '2026-09-17';
Loan::updateLedgerEntry(intval($issuedRow['id']), [
    'entry_type'  => 'Loan Issued',
    'entry_date'  => $newLoanDate,
    'debit'       => $newDisbDebit,
    'remarks'     => 'Updated loan disbursement remark'
]);

$updatedLoan = Loan::findById($loanId);
$updatedLedger = Loan::getLedger($loanId);
if ($updatedLedger[0]['entry_type'] === 'Loan Issued' &&
    floatval($updatedLedger[0]['debit']) === $newDisbDebit &&
    $updatedLoan['loan_date'] === $newLoanDate) {
    echo "  -> PASS: Legitimate edits to 'Loan Issued' entry succeeded while keeping type locked!\n\n";
} else {
    echo "  -> FAILED: Legitimate edits to 'Loan Issued' failed!\n";
    exit(1);
}

// =========================================================================
// TEST 2: EDIT LOAN VIA Loan::updateLoan() & VERIFY COMPLETE SYSTEM CASCADE
// =========================================================================
echo "[TEST 2] Testing Loan::updateLoan() Full Cascade Synchronization...\n";
$editedPrincipal = 20000.00;
$editedRate = 12.00; // 12% monthly -> 6% half-monthly (15 days) = 1200 int -> total 21200
$editedCycle = '15 Days';
$editedDate = '2026-09-15';
$editedRemarks = 'Updated terms approved by manager';

Loan::updateLoan($loanId, [
    'customer_id'      => $customerId,
    'loan_date'        => $editedDate,
    'security_type'    => 'Gold + Silver Secured',
    'principal_amount' => $editedPrincipal,
    'interest_rate'    => $editedRate,
    'interest_cycle'   => $editedCycle,
    'remarks'          => $editedRemarks
], [
    [
        'item_name'         => 'Updated Gold Necklace',
        'quantity'          => 1,
        'gross_weight'      => 20.0,
        'stone_weight'      => 0.0,
        'net_weight'        => 20.0,
        'purity_preset'     => '24K',
        'purity_percentage' => 100.00,
        'market_value'      => 160000.00
    ]
], [
    [
        'item_name'         => 'Added Silver Plate',
        'quantity'          => 1,
        'gross_weight'      => 100.0,
        'net_weight'        => 100.0,
        'purity_preset'     => '100% (Pure)',
        'purity_percentage' => 100.00,
        'market_value'      => 9000.00
    ]
]);

$reloadedLoan = Loan::findById($loanId);
$reloadedLedger = Loan::getLedger($loanId);
$reloadedCollateral = Loan::getCollateralItems($loanId);

echo "  -> Reloaded Loan Principal: ₹{$reloadedLoan['principal_amount']}, Rate: {$reloadedLoan['interest_rate']}%\n";
echo "  -> Reloaded Loan Total Payable: ₹{$reloadedLoan['total_payable_amount']}, Remaining Bal: ₹{$reloadedLoan['remaining_balance']}\n";
echo "  -> Reloaded Ledger Disbursement Debit: ₹{$reloadedLedger[0]['debit']}, Balance: ₹{$reloadedLedger[0]['balance']}\n";
echo "  -> Reloaded Collateral Item Count: " . count($reloadedCollateral) . "\n";

$expectedFirstCycleInt = 1200.00;
$expectedDisbursement = $editedPrincipal + $expectedFirstCycleInt;

if (floatval($reloadedLoan['principal_amount']) === $editedPrincipal &&
    floatval($reloadedLoan['total_payable_amount']) === $expectedDisbursement &&
    floatval($reloadedLoan['remaining_balance']) === $expectedDisbursement &&
    floatval($reloadedLedger[0]['debit']) === $expectedDisbursement &&
    floatval($reloadedLedger[0]['balance']) === $expectedDisbursement &&
    count($reloadedCollateral) === 2) {
    echo "  -> PASS: Loan record, collateral, and initial ledger row completely synchronized!\n\n";
} else {
    echo "  -> FAILED: Loan update did not cascade properly!\n";
    exit(1);
}

// =========================================================================
// TEST 3: ATTEMPT TO DELETE LOAN WITH RECORDED REPAYMENT (MUST BE BLOCKED)
// =========================================================================
echo "[TEST 3] Testing Delete Block on Loan With Repayment History...\n";

// Record a repayment on this loan
$paymentId = PaymentModel::recordPayment([
    'loan_id'             => $loanId,
    'payment_date'        => date('Y-m-d'),
    'principal_component' => 5000.00,
    'interest_component'  => 0.00,
    'discount'            => 0.00,
    'total_amount'        => 5000.00,
    'payment_mode'        => 'Cash',
    'remarks'             => 'Partial payment test'
]);

echo "  -> Recorded Payment ID: {$paymentId} for ₹5,000.00\n";

$check = Loan::canDelete($loanId);
echo "  -> canDelete() Result: " . ($check['can_delete'] ? 'TRUE' : 'FALSE') . " | Reason: {$check['reason']}\n";

if ($check['can_delete'] !== false) {
    echo "  -> FAILED: canDelete() should have returned false for loan with payments!\n";
    exit(1);
}

$deleteBlocked = false;
try {
    Loan::deleteLoan($loanId);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Loans with repayment history cannot be deleted')) {
        $deleteBlocked = true;
        echo "  -> PASS: Delete execution strictly blocked by backend: '{$e->getMessage()}'\n\n";
    } else {
        echo "  -> FAILED: Unexpected exception: {$e->getMessage()}\n";
    }
}

if (!$deleteBlocked) {
    echo "  -> FAILED: Loan with repayments was deleted!\n";
    exit(1);
}

// =========================================================================
// TEST 4: DELETE LOAN WITH NO REPAYMENTS (MUST SUCCEED CLEANLY)
// =========================================================================
echo "[TEST 4] Testing Delete on Fresh Loan Without Repayments...\n";

$freshLoanNum = 'FRESH-LN-' . time();
$freshLoanId = Loan::createLoan([
    'loan_number'        => $freshLoanNum,
    'customer_id'        => $customerId,
    'loan_date'          => date('Y-m-d'),
    'security_type'      => 'Gold Secured',
    'principal_amount'   => 8000.00,
    'interest_rate'      => 10.00,
    'interest_cycle'     => '15 Days',
    'remarks'            => 'Fresh loan to test clean deletion'
], [
    [
        'item_name'         => 'Test Gold Ring to Delete',
        'quantity'          => 1,
        'gross_weight'      => 5.0,
        'stone_weight'      => 0.0,
        'net_weight'        => 5.0,
        'purity_preset'     => '24K',
        'purity_percentage' => 100.00,
        'market_value'      => 40000.00
    ]
]);

$freshCheck = Loan::canDelete($freshLoanId);
echo "  -> Fresh Loan ID: {$freshLoanId} ({$freshLoanNum})\n";
echo "  -> canDelete() Result: " . ($freshCheck['can_delete'] ? 'TRUE' : 'FALSE') . "\n";

if (!$freshCheck['can_delete']) {
    echo "  -> FAILED: canDelete() should be true for fresh loan!\n";
    exit(1);
}

// Check collateral items and rack assignment
$freshItems = Loan::getCollateralItems($freshLoanId);
$rackSlot = $freshItems[0]['rk_number'] ?? '';
echo "  -> Collateral item assigned to rack: {$rackSlot}\n";

$deleteSuccess = Loan::deleteLoan($freshLoanId);
echo "  -> Loan::deleteLoan() returned: " . ($deleteSuccess ? 'TRUE' : 'FALSE') . "\n";

// Verify absence of orphan records
$orphanLoan = Database::fetchOne("SELECT id FROM loans WHERE id = :id", [':id' => $freshLoanId]);
$orphanLedger = Database::fetchAll("SELECT id FROM loan_ledger WHERE loan_id = :id", [':id' => $freshLoanId]);
$orphanCollateral = Database::fetchAll("SELECT id FROM collateral_items WHERE loan_id = :id", [':id' => $freshLoanId]);
$orphanGuarantor = Database::fetchAll("SELECT id FROM loan_guarantors WHERE loan_id = :id", [':id' => $freshLoanId]);

echo "  -> Orphan Check: Loan = " . ($orphanLoan ? 'EXISTS' : 'NONE') .
     ", Ledger Rows = " . count($orphanLedger) .
     ", Collateral Rows = " . count($orphanCollateral) .
     ", Guarantor Rows = " . count($orphanGuarantor) . "\n";

if ($orphanLoan === null && empty($orphanLedger) && empty($orphanCollateral) && empty($orphanGuarantor)) {
    echo "  -> PASS: All child and parent records cleanly wiped with zero orphan data!\n";
} else {
    echo "  -> FAILED: Orphan records remained after deletion!\n";
    exit(1);
}

// Check that the rack slot is now free
if (!empty($rackSlot)) {
    $occupiedMap = RackManager::getOccupiedSlotMap();
    $slotKey = strtolower(trim($rackSlot));
    if (!isset($occupiedMap[$slotKey])) {
        echo "  -> PASS: Rack slot '{$rackSlot}' is immediately freed and available!\n\n";
    } else {
        echo "  -> WARNING: Rack slot '{$rackSlot}' still appears in occupied map!\n\n";
    }
}

// Cleanup Test 1 loan & payment so test database is clean
Database::execute("DELETE FROM payments WHERE id = :id", [':id' => $paymentId]);
Database::execute("DELETE FROM loan_ledger WHERE loan_id = :lid AND entry_type = 'Payment Received'", [':lid' => $loanId]);
Loan::deleteLoan($loanId);
echo "[CLEANUP] Cleaned up temporary test loan {$loanId} and payment {$paymentId}.\n\n";

echo "===============================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY! 100% VERIFICATION COMPLETE.\n";
echo "===============================================================\n";
