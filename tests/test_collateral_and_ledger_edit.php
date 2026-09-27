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
use App\Models\PaymentModel;

echo "=== STARTING COLLATERAL ISOLATION & LEDGER EDIT TEST SUITE ===\n\n";

$db = Database::connect();

// 1. Get or create test customer
$customerStmt = $db->query("SELECT id FROM customers LIMIT 1");
$customer = $customerStmt->fetch();
if (!$customer) {
    $customerId = Customer::create([
        'customer_id'    => Customer::generateNextCustomerId(),
        'account_number' => Customer::generateNextAccountNumber(),
        'full_name'      => 'Test Security Type Customer',
        'mobile'         => '9988776655',
        'status'         => 'Active'
    ]);
} else {
    $customerId = intval($customer['id']);
}

// -------------------------------------------------------------
// TEST 1: GOLD SECURED LOAN MUST NOT ADD SILVER COLLATERAL
// -------------------------------------------------------------
echo "[TEST 1] Testing Collateral Isolation for 'Gold Secured'...\n";
$uniqueCode = time() . rand(100, 999);
$goldOnlyLoanData = [
    'loan_number'        => 'TEST-GOLD-' . $uniqueCode,
    'customer_id'        => $customerId,
    'loan_date'          => date('Y-m-d'),
    'security_type'      => 'Gold Secured',
    'principal_amount'   => 20000.00,
    'interest_rate'      => 3.0,
    'interest_cycle'     => '15 Days',
    'interest_method'    => 'Simple',
    'compound_frequency' => 'Monthly',
    'status'             => 'Running',
    'remarks'            => 'Gold secured isolation test'
];

$goldItems = [
    [
        'item_name'         => '22K Gold Bangle',
        'quantity'          => 1,
        'gross_weight'      => 15.0,
        'stone_weight'      => 0.0,
        'net_weight'        => 15.0,
        'purity_preset'     => '22K',
        'purity_percentage' => 91.67,
        'market_value'      => 110000.0,
        'rk_number'         => null
    ]
];

$unwantedSilverItems = [
    [
        'item_name'         => 'Default Silver Chain That Should Not Be Added',
        'quantity'          => 1,
        'gross_weight'      => 50.0,
        'net_weight'        => 50.0,
        'purity_preset'     => '92.5%',
        'purity_percentage' => 92.50,
        'market_value'      => 4500.0,
        'rk_number'         => null
    ]
];

$goldLoanId = Loan::createLoan($goldOnlyLoanData, $goldItems, $unwantedSilverItems, null);
$createdCollaterals = Loan::getCollateralItems($goldLoanId);

$silverCount = 0;
$goldCount = 0;
foreach ($createdCollaterals as $ci) {
    if (strtoupper($ci['item_type']) === 'SILVER') {
        $silverCount++;
    } elseif (strtoupper($ci['item_type']) === 'GOLD') {
        $goldCount++;
    }
}

echo "  -> Collateral items added: Total = " . count($createdCollaterals) . " (Gold: {$goldCount}, Silver: {$silverCount})\n";
if ($silverCount === 0 && $goldCount === 1) {
    echo "  -> SUCCESS: Zero silver items added when Security Type is 'Gold Secured'!\n\n";
} else {
    echo "  -> FAILED: Silver items were mistakenly added to Gold Secured loan!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 2: SILVER SECURED LOAN MUST NOT ADD GOLD COLLATERAL
// -------------------------------------------------------------
echo "[TEST 2] Testing Collateral Isolation for 'Silver Secured'...\n";
$silverOnlyLoanData = [
    'loan_number'        => 'TEST-SILVER-' . $uniqueCode,
    'customer_id'        => $customerId,
    'loan_date'          => date('Y-m-d'),
    'security_type'      => 'Silver Secured',
    'principal_amount'   => 15000.00,
    'interest_rate'      => 3.0,
    'interest_cycle'     => '15 Days',
    'interest_method'    => 'Simple',
    'compound_frequency' => 'Monthly',
    'status'             => 'Running',
    'remarks'            => 'Silver secured isolation test'
];

$unwantedGoldItems = [
    [
        'item_name'         => 'Default Gold Ring That Should Not Be Added',
        'quantity'          => 1,
        'gross_weight'      => 10.0,
        'stone_weight'      => 0.0,
        'net_weight'        => 10.0,
        'purity_preset'     => '22K',
        'purity_percentage' => 91.67,
        'market_value'      => 75000.0,
        'rk_number'         => null
    ]
];

$silverItems = [
    [
        'item_name'         => 'Pure Silver Plate',
        'quantity'          => 1,
        'gross_weight'      => 150.0,
        'net_weight'        => 150.0,
        'purity_preset'     => '92.5%',
        'purity_percentage' => 92.50,
        'market_value'      => 13500.0,
        'rk_number'         => null
    ]
];

$silverLoanId = Loan::createLoan($silverOnlyLoanData, $unwantedGoldItems, $silverItems, null);
$createdSilverCollaterals = Loan::getCollateralItems($silverLoanId);

$silverCount2 = 0;
$goldCount2 = 0;
foreach ($createdSilverCollaterals as $ci) {
    if (strtoupper($ci['item_type']) === 'SILVER') {
        $silverCount2++;
    } elseif (strtoupper($ci['item_type']) === 'GOLD') {
        $goldCount2++;
    }
}

echo "  -> Collateral items added: Total = " . count($createdSilverCollaterals) . " (Gold: {$goldCount2}, Silver: {$silverCount2})\n";
if ($goldCount2 === 0 && $silverCount2 === 1) {
    echo "  -> SUCCESS: Zero gold items added when Security Type is 'Silver Secured'!\n\n";
} else {
    echo "  -> FAILED: Gold items were mistakenly added to Silver Secured loan!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 3: EDIT "LOAN ISSUED" ENTRY & VERIFY RECALCULATION
// -------------------------------------------------------------
echo "[TEST 3] Testing Edit on 'Loan Issued' entry...\n";
$ledger = Loan::getLedger($goldLoanId);
$loanIssuedEntry = $ledger[0];
echo "  -> Initial Loan Issued: Date = {$loanIssuedEntry['entry_date']}, Debit = ₹{$loanIssuedEntry['debit']}, Bal = ₹{$loanIssuedEntry['balance']}\n";

$newDisbursementDebit = 20300.00;
$newDisbursementDate = '2026-09-01';
$newDesc = 'Corrected Initial loan disbursement (Principal: ₹20,000.00 + 1st Cycle Int: ₹300.00)';

Loan::updateLedgerEntry(intval($loanIssuedEntry['id']), [
    'entry_date'  => $newDisbursementDate,
    'debit'       => $newDisbursementDebit,
    'description' => $newDesc
]);

$updatedLedger = Loan::getLedger($goldLoanId);
$updatedLoanIssued = $updatedLedger[0];
$updatedLoan = Loan::findById($goldLoanId);

echo "  -> Updated Loan Issued: Date = {$updatedLoanIssued['entry_date']}, Debit = ₹{$updatedLoanIssued['debit']}, Bal = ₹{$updatedLoanIssued['balance']}\n";
echo "  -> Synchronized Loan Record: Date = {$updatedLoan['loan_date']}, Total Payable = ₹{$updatedLoan['total_payable_amount']}, Remaining Bal = ₹{$updatedLoan['remaining_balance']}\n";

if ($updatedLoanIssued['entry_date'] === $newDisbursementDate &&
    floatval($updatedLoanIssued['debit']) === $newDisbursementDebit &&
    floatval($updatedLoanIssued['balance']) === $newDisbursementDebit &&
    $updatedLoan['loan_date'] === $newDisbursementDate &&
    floatval($updatedLoan['total_payable_amount']) === $newDisbursementDebit) {
    echo "  -> SUCCESS: Loan Issued entry and loan record updated and synchronized!\n\n";
} else {
    echo "  -> FAILED: Loan Issued entry or loan record failed to update properly!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 4: RECORD PAYMENT WITH DISCOUNT, THEN EDIT DISCOUNT ENTRY
// -------------------------------------------------------------
echo "[TEST 4] Recording Payment with Discount, then Editing 'Discount / Concession' Entry...\n";
$paymentId = PaymentModel::recordPayment([
    'loan_id'            => $goldLoanId,
    'payment_date'       => '2026-09-10',
    'total_amount'       => 20250.00,
    'discount'           => 50.00,
    'is_full_settlement' => true,
    'payment_mode'       => 'Cash',
    'reference_number'   => 'REF-DISC-001',
    'remarks'            => 'Full settlement with 50 waiver'
]);

$ledgerAfterPayment = Loan::getLedger($goldLoanId);
echo "  -> Ledger has " . count($ledgerAfterPayment) . " entries after payment:\n";
$discountEntry = null;
$paymentEntry = null;
$closingEntry = null;

foreach ($ledgerAfterPayment as $e) {
    echo "     * [{$e['entry_date']}] {$e['entry_type']}: Dr ₹{$e['debit']}, Cr ₹{$e['credit']}, Bal ₹{$e['balance']}\n";
    if ($e['entry_type'] === 'Discount / Concession') {
        $discountEntry = $e;
    } elseif ($e['entry_type'] === 'Payment Received') {
        $paymentEntry = $e;
    } elseif ($e['entry_type'] === 'Closing Entry') {
        $closingEntry = $e;
    }
}

if (!$discountEntry || !$paymentEntry || !$closingEntry) {
    echo "  -> FAILED: Expected Discount, Payment Received, and Closing Entry!\n";
    exit(1);
}

// Now edit the Discount entry to ₹100.00
echo "\n  -> Editing Discount entry to ₹100.00...\n";
Loan::updateLedgerEntry(intval($discountEntry['id']), [
    'entry_date'  => '2026-09-10',
    'credit'      => 100.00,
    'description' => 'Revised Settlement discount granted (₹100.00)'
]);

$ledgerAfterDiscEdit = Loan::getLedger($goldLoanId);
$updatedDiscount = null;
foreach ($ledgerAfterDiscEdit as $e) {
    if ($e['entry_type'] === 'Discount / Concession') {
        $updatedDiscount = $e;
    }
}

$paymentRecord = PaymentModel::findById($paymentId);
echo "  -> Updated Discount: Credit = ₹{$updatedDiscount['credit']}, Payments table discount = ₹{$paymentRecord['discount']}\n";

if (floatval($updatedDiscount['credit']) === 100.00 && floatval($paymentRecord['discount']) === 100.00) {
    echo "  -> SUCCESS: Discount / Concession entry edited and payments table discount updated!\n\n";
} else {
    echo "  -> FAILED: Discount entry edit failed!\n";
    exit(1);
}

// -------------------------------------------------------------
// TEST 5: EDIT "CLOSING ENTRY" DATE & DESCRIPTION
// -------------------------------------------------------------
echo "[TEST 5] Testing Edit on 'Closing Entry'...\n";
$newClosingDate = '2026-09-11';
$newClosingDesc = 'Loan fully settled & gold ornament delivered to customer';

Loan::updateLedgerEntry(intval($closingEntry['id']), [
    'entry_date'  => $newClosingDate,
    'description' => $newClosingDesc
]);

$ledgerAfterCloseEdit = Loan::getLedger($goldLoanId);
$updatedClose = null;
foreach ($ledgerAfterCloseEdit as $e) {
    if ($e['entry_type'] === 'Closing Entry') {
        $updatedClose = $e;
    }
}

echo "  -> Updated Closing Entry: Date = {$updatedClose['entry_date']}, Desc = '{$updatedClose['description']}', Bal = ₹{$updatedClose['balance']}\n";

if ($updatedClose['entry_date'] === $newClosingDate && $updatedClose['description'] === $newClosingDesc) {
    echo "  -> SUCCESS: Closing entry edited successfully!\n\n";
} else {
    echo "  -> FAILED: Closing entry edit failed!\n";
    exit(1);
}

// -------------------------------------------------------------
// CLEANUP
// -------------------------------------------------------------
echo "[CLEANUP] Cleaning up test loans...\n";
$db->prepare("DELETE FROM collateral_items WHERE loan_id IN (:id1, :id2)")->execute([':id1' => $goldLoanId, ':id2' => $silverLoanId]);
$db->prepare("DELETE FROM loan_ledger WHERE loan_id IN (:id1, :id2)")->execute([':id1' => $goldLoanId, ':id2' => $silverLoanId]);
$db->prepare("DELETE FROM payments WHERE loan_id IN (:id1, :id2)")->execute([':id1' => $goldLoanId, ':id2' => $silverLoanId]);
$db->prepare("DELETE FROM loans WHERE id IN (:id1, :id2)")->execute([':id1' => $goldLoanId, ':id2' => $silverLoanId]);

echo "=== ALL COLLATERAL ISOLATION & LEDGER EDIT TESTS PASSED (100% GREEN) ===\n";
