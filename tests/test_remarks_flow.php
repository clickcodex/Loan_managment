<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Helpers/RackManager.php';
require_once __DIR__ . '/../app/Helpers/AuditLogger.php';
require_once __DIR__ . '/../app/Helpers/Session.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/RateModel.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';

use App\Config\Database;
use App\Models\Loan;
use App\Models\Customer;

$pdo = Database::connect();

echo "========================================================\n";
echo "TEST 1: Verify storage/live_database_migration.sql execution\n";
echo "========================================================\n";

$sqlMigration = file_get_contents(__DIR__ . '/../storage/live_database_migration.sql');
$statements = array_filter(array_map('trim', explode(';', $sqlMigration)));

foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        echo "Executed SQL: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 60) . "... [OK]\n";
    } catch (Exception $e) {
        echo "SQL ERROR: " . $e->getMessage() . " on statement: $stmt\n";
        exit(1);
    }
}

echo "\n========================================================\n";
echo "TEST 2: Create Loan with Remark and verify first ledger\n";
echo "========================================================\n";

$cust = Database::fetchOne("SELECT id FROM customers LIMIT 1");
if (!$cust) {
    echo "No customer found for test.\n";
    exit(1);
}
$custId = intval($cust['id']);
$loanNumber = 'TEST-REM-' . time();
$initialRemark = "Special festive 10% rate approved for VIP";

$loanData = [
    'loan_number'        => $loanNumber,
    'customer_id'        => $custId,
    'loan_date'          => date('Y-m-d'),
    'security_type'      => 'Gold Secured',
    'principal_amount'   => 12000.00,
    'interest_rate'      => 10.00,
    'interest_cycle'     => '15 Days',
    'interest_method'    => 'Simple',
    'compound_frequency' => 'Monthly',
    'return_date'        => null,
    'interest_due_date'  => null,
    'status'             => 'Active',
    'haste'              => 'Customer Self',
    'remarks'            => $initialRemark
];

$goldItems = [
    [
        'item_name'         => 'Gold Ring',
        'quantity'          => 1,
        'gross_weight'      => 10.0,
        'stone_weight'      => 0.0,
        'net_weight'        => 10.0,
        'purity_preset'     => '22K',
        'purity_percentage' => 91.67,
        'market_value'      => 75000.00
    ]
];

$loanId = Loan::createLoan($loanData, $goldItems, [], null);
echo "Created Loan ID: $loanId with loan_number: $loanNumber\n";

// Check loans table remarks
$loanRow = Database::fetchOne("SELECT * FROM loans WHERE id = :id", [':id' => $loanId]);
if ($loanRow['remarks'] === $initialRemark) {
    echo "PASS: loans.remarks successfully saved: '{$loanRow['remarks']}'\n";
} else {
    echo "FAIL: loans.remarks expected '$initialRemark', got '{$loanRow['remarks']}'\n";
    exit(1);
}

// Check first ledger entry
$ledger = Loan::getLedger($loanId);
if (empty($ledger)) {
    echo "FAIL: Ledger is empty for loan ID: $loanId\n";
    exit(1);
}

$firstLedger = $ledger[0];
echo "First ledger entry_type: {$firstLedger['entry_type']}\n";
echo "First ledger remarks: '{$firstLedger['remarks']}'\n";
echo "First ledger payment_remarks fallback: '{$firstLedger['payment_remarks']}'\n";

if ($firstLedger['remarks'] === $initialRemark && $firstLedger['payment_remarks'] === $initialRemark) {
    echo "PASS: First ledger row correctly has the loan remark!\n";
} else {
    echo "FAIL: First ledger remark mismatch.\n";
    exit(1);
}

echo "\n========================================================\n";
echo "TEST 3: Edit First Ledger Entry Remarks and sync with loan\n";
echo "========================================================\n";

$updatedRemark = "Updated VIP approval: waived processing fee & expedited verification";
$updateData = [
    'entry_date'  => date('Y-m-d'),
    'entry_type'  => 'Loan Issued',
    'description' => 'Initial loan disbursement (Principal: ₹12,000.00 + 1st Cycle Int: ₹600.00)',
    'debit'       => 12600.00,
    'remarks'     => $updatedRemark
];

Loan::updateLedgerEntry(intval($firstLedger['id']), $updateData);

// Re-check loan and ledger
$reloadedLoan = Database::fetchOne("SELECT * FROM loans WHERE id = :id", [':id' => $loanId]);
$reloadedLedger = Loan::getLedger($loanId);

if ($reloadedLoan['remarks'] === $updatedRemark) {
    echo "PASS: loans.remarks updated to: '{$reloadedLoan['remarks']}'\n";
} else {
    echo "FAIL: loans.remarks expected '$updatedRemark', got '{$reloadedLoan['remarks']}'\n";
    exit(1);
}

if ($reloadedLedger[0]['remarks'] === $updatedRemark && $reloadedLedger[0]['payment_remarks'] === $updatedRemark) {
    echo "PASS: loan_ledger.remarks updated to: '{$reloadedLedger[0]['remarks']}'\n";
} else {
    echo "FAIL: loan_ledger.remarks expected '$updatedRemark', got '{$reloadedLedger[0]['remarks']}'\n";
    exit(1);
}

echo "\n========================================================\n";
echo "ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "========================================================\n";
