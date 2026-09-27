<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';
require_once __DIR__ . '/../app/Models/DueModel.php';
require_once __DIR__ . '/../app/Models/RateModel.php';
require_once __DIR__ . '/../app/Helpers/RackManager.php';
require_once __DIR__ . '/../app/Helpers/AuditLogger.php';
require_once __DIR__ . '/../app/Helpers/Session.php';

use App\Config\Database;
use App\Models\Loan;
use App\Models\Customer;
use App\Models\PaymentModel;
use App\Models\DueModel;

echo "========================================================\n";
echo "LOAN CALCULATIONS & RUNNING BALANCE VERIFICATION SUITE\n";
echo "========================================================\n\n";

// -------------------------------------------------------------
// TEST 1: Verify the exact Ashok Kalu Patidar scenario (User Screenshot)
// -------------------------------------------------------------
echo "[TEST 1] Testing Ashok Kalu Patidar Scenario (Loan from 2025-01-19, ₹10k @ 3% / 15-day cycle)...\n";
$mockLoan = [
    'id' => 9999,
    'loan_number' => 'LMS-TEST-MOCK',
    'principal_amount' => 10000.00,
    'interest_rate' => 3.00,
    'interest_cycle' => '15 Days',
    'interest_method' => 'Compound',
    'compound_frequency' => 'Yearly',
    'loan_date' => '2025-01-19',
    'security_type' => 'Silver Secured',
    'status' => 'Running'
];

$refDate = new DateTime('2026-09-21');
$dues = DueModel::calculateLoanDues($mockLoan, $refDate);

echo "  -> Accrued Interest calculated: ₹{$dues['accrued_interest']}\n";
echo "  -> Compounded Principal (Yr 2 Base): ₹{$dues['compounded_principal']}\n";
echo "  -> Current Year Cycles: {$dues['current_year_cycles']}\n";

// Simulate payment of 5000:
$totalPayableWithPaid = ($dues['principal_amount'] + $dues['accrued_interest']) - 5000.00;
echo "  -> Total Payable after ₹5,000 payment: ₹" . number_format($totalPayableWithPaid, 2) . "\n";

if ($dues['accrued_interest'] == 7068.00 && $totalPayableWithPaid == 12068.00 && $dues['compounded_principal'] == 13600.00) {
    echo "  -> PASS: Mathematical calculation matches user screenshot ₹12,068.00 and Yr 2 Base ₹13,600.00 exactly!\n\n";
} else {
    echo "  -> FAILED: Math mismatch!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 2: Real Database Flow - Issue Loan, Check Initial Balances
// -------------------------------------------------------------
echo "[TEST 2] Issue Loan Flow (Principal ₹10,000 @ 3% / 15-day cycle)...\n";
$customer = Database::fetchOne("SELECT * FROM customers WHERE status = 'Active' LIMIT 1");
if (!$customer) {
    $custId = Customer::create([
        'customer_id' => 'CUST-TEST-MATH',
        'full_name' => 'Math Test Customer',
        'mobile' => '9988776655',
        'status' => 'Active'
    ]);
    $customer = Customer::findById($custId);
}
$customerId = intval($customer['id']);

$testLoanNumber = 'LMS-MATH-' . time();
$loanId = Loan::createLoan([
    'loan_number' => $testLoanNumber,
    'customer_id' => $customerId,
    'loan_date' => date('Y-m-d'),
    'security_type' => 'Silver Secured',
    'principal_amount' => 10000.00,
    'interest_rate' => 3.00,
    'interest_cycle' => '15 Days',
    'interest_method' => 'Compound',
    'compound_frequency' => 'Yearly',
    'remarks' => 'Math verification loan'
], [], [
    [
        'item_name' => 'Test Silver Bar',
        'quantity' => 1,
        'gross_weight' => 500.0,
        'net_weight' => 500.0,
        'purity_preset' => '100% (Pure)',
        'purity_percentage' => 100.0,
        'market_value' => 45000.00
    ]
]);

$createdLoan = Loan::findById($loanId);
$createdLedger = Loan::getLedger($loanId);
echo "  -> Loan Created ID: {$loanId} ({$createdLoan['loan_number']})\n";
echo "  -> Principal: ₹{$createdLoan['principal_amount']}, Initial Total Payable: ₹{$createdLoan['total_payable_amount']}\n";
echo "  -> Ledger Row count: " . count($createdLedger) . ", First Row Debit: ₹{$createdLedger[0]['debit']}, Balance: ₹{$createdLedger[0]['balance']}\n";

// For Day 1, 1st cycle interest is 150.00, initial dues = 10,150.00
if (floatval($createdLoan['principal_amount']) === 10000.00 &&
    floatval($createdLoan['total_payable_amount']) === 10150.00 &&
    floatval($createdLedger[0]['balance']) === 10150.00) {
    echo "  -> PASS: Loan issue calculations and initial ledger balance are 100% accurate!\n\n";
} else {
    echo "  -> FAILED: Loan issue calculation error!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 3: Edit Loan Flow - Update Principal & Rate, Verify Recalculation
// -------------------------------------------------------------
echo "[TEST 3] Edit Loan Flow (Update Principal to ₹20,000 @ 3% / 15-day cycle)...\n";
Loan::updateLoan($loanId, [
    'principal_amount' => 20000.00,
    'interest_rate' => 3.00,
    'interest_cycle' => '15 Days',
    'security_type' => 'Silver Secured',
    'remarks' => 'Edited math verification loan'
], [], [
    [
        'item_name' => 'Test Silver Bar',
        'quantity' => 1,
        'gross_weight' => 500.0,
        'net_weight' => 500.0,
        'purity_preset' => '100% (Pure)',
        'purity_percentage' => 100.0,
        'market_value' => 45000.00
    ]
]);

$editedLoan = Loan::findById($loanId);
$editedLedger = Loan::getLedger($loanId);
echo "  -> Edited Principal: ₹{$editedLoan['principal_amount']}\n";
echo "  -> Edited Total Payable: ₹{$editedLoan['total_payable_amount']}, Remaining Bal: ₹{$editedLoan['remaining_balance']}\n";
echo "  -> Edited Ledger Row 0: Debit: ₹{$editedLedger[0]['debit']}, Balance: ₹{$editedLedger[0]['balance']}\n";

// For 20,000 @ 3% monthly / 15-day cycle, 1st cycle interest = 300.00, total = 20,300.00
if (floatval($editedLoan['principal_amount']) === 20000.00 &&
    floatval($editedLoan['total_payable_amount']) === 20300.00 &&
    floatval($editedLedger[0]['balance']) === 20300.00) {
    echo "  -> PASS: Edit loan calculations and ledger balance synchronization work seamlessly!\n\n";
} else {
    echo "  -> FAILED: Edit loan calculation error!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 4: Payment Flow - Verify Ledger Synchronization & Balance
// -------------------------------------------------------------
echo "[TEST 4] Payment Flow (Pay ₹5,000 partial payment)...\n";
$paymentId = PaymentModel::recordPayment([
    'loan_id' => $loanId,
    'total_amount' => 5000.00,
    'discount' => 0.00,
    'payment_mode' => 'Cash',
    'payment_date' => date('Y-m-d'),
    'remarks' => 'Partial payment test'
]);

$loanAfterPay = Loan::findById($loanId);
$ledgerAfterPay = Loan::getLedger($loanId);
$lastLedgerRow = end($ledgerAfterPay);

echo "  -> Payment Recorded ID: {$paymentId}\n";
echo "  -> Loan Status: {$loanAfterPay['status']}, Remaining Balance: ₹{$loanAfterPay['remaining_balance']}\n";
echo "  -> Latest Ledger Balance: ₹{$lastLedgerRow['balance']}\n";

// 20,300 - 5,000 = 15,300
if (floatval($lastLedgerRow['balance']) === 15300.00 &&
    floatval($loanAfterPay['remaining_balance']) === 15300.00 &&
    ($loanAfterPay['status'] === 'Running' || $loanAfterPay['status'] === 'Active')) {
    echo "  -> PASS: Payment deduction and ledger running balance match ₹15,300.00 perfectly!\n\n";
} else {
    echo "  -> FAILED: Payment balance mismatch!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 5: Verify Long-Term Elapsed Loan Ledger (Accrued Interest Sync)
// -------------------------------------------------------------
echo "[TEST 5] Long-Term Elapsed Loan Ledger Test...\n";
// Update loan date to 2025-01-19 and principal back to 10,000 to mirror user's exact loan
Database::execute("UPDATE loans SET loan_date = '2025-01-19', principal_amount = 10000.00, total_payable_amount = 10150.00 WHERE id = :id", [':id' => $loanId]);
Database::execute("UPDATE loan_ledger SET entry_date = '2025-01-19', debit = 10150.00, description = 'Initial loan disbursement (Principal: ₹10,000.00 + 1st Cycle Int: ₹150.00)' WHERE loan_id = :id AND entry_type = 'Loan Issued'", [':id' => $loanId]);

PaymentModel::recalculateLedgerAndBalance($loanId);

$simulatedLedger = Loan::getLedger($loanId);
echo "  -> Ledger Entry Count: " . count($simulatedLedger) . "\n";
foreach ($simulatedLedger as $idx => $entry) {
    echo "     Row {$idx}: [{$entry['entry_date']}] {$entry['entry_type']} | Debit: ₹{$entry['debit']} | Credit: ₹{$entry['credit']} | Balance: ₹{$entry['balance']} | {$entry['description']}\n";
}

$simulatedLastRow = end($simulatedLedger);
echo "  -> Final Ledger Running Balance: ₹{$simulatedLastRow['balance']}\n";

$dueInfoCheck = DueModel::calculateLoanDues(Loan::findById($loanId));
echo "  -> Live DueModel Total Payable: ₹{$dueInfoCheck['total_payable']}\n";

if (floatval($simulatedLastRow['balance']) === floatval($dueInfoCheck['total_payable'])) {
    echo "  -> PASS: Ledger final running balance (₹{$simulatedLastRow['balance']}) matches DueModel Total Payable (₹{$dueInfoCheck['total_payable']}) EXACTLY!\n\n";
} else {
    echo "  -> FAILED: Ledger balance does not match live total dues!\n";
    exit(1);
}

// Clean up test data
Database::execute("DELETE FROM payments WHERE loan_id = :id", [':id' => $loanId]);
Database::execute("DELETE FROM loan_ledger WHERE loan_id = :id", [':id' => $loanId]);
Database::execute("DELETE FROM collateral_items WHERE loan_id = :id", [':id' => $loanId]);
Database::execute("DELETE FROM loans WHERE id = :id", [':id' => $loanId]);
echo "Cleaned up test loan {$testLoanNumber}.\n";

echo "\nALL TESTS PASSED SUCCESSFULLY! NO CALCULATION ERRORS FOUND.\n";
