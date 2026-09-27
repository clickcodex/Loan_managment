<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Config\Database;
use App\Models\Loan;

echo "=== STARTING ALL LOANS LEDGER & SANCTION RECEIPT TEST SUITE ===\n\n";

// 1. Test Loan::getAllLedgerEntries() & Loan::getLedgerSummary()
echo "[1] Testing Master Loan Ledger Querying...\n";
$allEntries = Loan::getAllLedgerEntries([]);
$summary = Loan::getLedgerSummary([]);

echo "  -> Total Ledger Records in DB: " . count($allEntries) . "\n";
echo "  -> Summary Total Debit (Outflow):  ₹" . number_format($summary['total_debit'], 2) . "\n";
echo "  -> Summary Total Credit (Inflow): ₹" . number_format($summary['total_credit'], 2) . "\n";
echo "  -> Summary Net Cash Flow:         ₹" . number_format($summary['net_flow'], 2) . "\n";
echo "  -> Summary Distinct Loans Count:  " . $summary['total_loans'] . "\n";

assert(count($allEntries) === $summary['total_count'], "Entries count must match summary total_count");
assert($summary['total_debit'] >= 0, "Total debit must be non-negative");
assert($summary['total_credit'] >= 0, "Total credit must be non-negative");
echo "  [PASS] Base ledger retrieval and aggregation verified!\n\n";

// 2. Test Filtering by Flow Type
echo "[2] Testing Flow Type Filtering (Debit vs Credit)...\n";
$debitEntries = Loan::getAllLedgerEntries(['flow_type' => 'debit']);
$creditEntries = Loan::getAllLedgerEntries(['flow_type' => 'credit']);

foreach ($debitEntries as $e) {
    assert(floatval($e['debit']) > 0, "Debit filter must only return entries with debit > 0");
}
foreach ($creditEntries as $e) {
    assert(floatval($e['credit']) > 0, "Credit filter must only return entries with credit > 0");
}
echo "  -> Found " . count($debitEntries) . " Debit (Outflow) entries\n";
echo "  -> Found " . count($creditEntries) . " Credit (Inflow) entries\n";
echo "  [PASS] Flow type filtering verified!\n\n";

// 3. Test Date Filtering
echo "[3] Testing Date Filtering...\n";
if (!empty($allEntries)) {
    $firstDate = $allEntries[count($allEntries) - 1]['entry_date'];
    $lastDate = $allEntries[0]['entry_date'];
    $dateFiltered = Loan::getAllLedgerEntries([
        'start_date' => $firstDate,
        'end_date'   => $lastDate
    ]);
    assert(count($dateFiltered) > 0, "Date filter should return matching records");
    echo "  -> Date range from {$firstDate} to {$lastDate}: " . count($dateFiltered) . " records\n";
}
echo "  [PASS] Date range filtering verified!\n\n";

// 4. Test Loan Sanction Receipt Rendering
echo "[4] Testing Loan Sanction Receipt Template...\n";
$recentLoan = Database::fetchOne("SELECT id FROM loans ORDER BY id DESC LIMIT 1");
if ($recentLoan) {
    $loanId = intval($recentLoan['id']);
    $loan = Database::fetchOne("
        SELECT l.*, c.id as cust_id, c.customer_id as cust_code, c.account_number as customer_account_number,
               c.full_name as customer_name, c.father_name, c.mobile as customer_mobile, c.alt_mobile,
               c.aadhaar, c.pan, c.address as customer_address, c.village, c.city, c.state, c.pincode
        FROM loans l
        JOIN customers c ON l.customer_id = c.id
        WHERE l.id = :id
        LIMIT 1
    ", [':id' => $loanId]);

    $collateralItems = Loan::getCollateralItems($loanId);
    $guarantor = Loan::getGuarantor($loanId);

    $totalGross = 0;
    $totalNet = 0;
    $totalValuation = 0;
    foreach ($collateralItems as $ci) {
        $totalGross += floatval($ci['gross_weight'] ?? 0);
        $totalNet += floatval($ci['net_weight'] ?? 0);
        $totalValuation += floatval($ci['market_value'] ?? 0);
    }
    $ltv = $totalValuation > 0 ? (floatval($loan['principal_amount']) / $totalValuation) * 100 : 0;

    $baseUrl = 'http://localhost/TransactionManagement';

    ob_start();
    include __DIR__ . '/../app/Views/loans/sanction_receipt.php';
    $renderedReceipt = ob_get_clean();

    assert(strpos($renderedReceipt, 'Loan Sanction & Disbursal Voucher') !== false, "Receipt must contain Sanction title");
    assert(strpos($renderedReceipt, $loan['loan_number']) !== false, "Receipt must contain loan number");
    assert(strpos($renderedReceipt, $loan['customer_name']) !== false, "Receipt must contain customer name");
    assert(strpos($renderedReceipt, 'Principal Amount') !== false, "Receipt must contain Principal Amount");
    assert(strpos($renderedReceipt, 'Borrower\'s Signature') !== false, "Receipt must contain signature block");
    echo "  -> Rendered Loan Sanction Receipt for Loan #{$loan['loan_number']} (ID: {$loanId})\n";
    echo "  [PASS] Loan Sanction Receipt renders cleanly!\n\n";
}

// 5. Verify Camera Elements in Customer Views
echo "[5] Verifying Camera Integration in Customer Forms...\n";
$createHtml = file_get_contents(__DIR__ . '/../app/Views/customers/create.php');
$editHtml = file_get_contents(__DIR__ . '/../app/Views/customers/edit.php');

assert(strpos($createHtml, 'openCameraModal') !== false, "customers/create.php must contain openCameraModal");
assert(strpos($createHtml, 'camera-capture.js') !== false, "customers/create.php must load camera-capture.js");
assert(strpos($createHtml, 'photo_camera_base64') !== false, "customers/create.php must have photo_camera_base64 hidden input");

assert(strpos($editHtml, 'openCameraModal') !== false, "customers/edit.php must contain openCameraModal");
assert(strpos($editHtml, 'camera-capture.js') !== false, "customers/edit.php must load camera-capture.js");
assert(strpos($editHtml, 'photo_camera_base64') !== false, "customers/edit.php must have photo_camera_base64 hidden input");

echo "  -> Verified Register Customer view has camera button & base64 input\n";
echo "  -> Verified Edit Customer view has camera button & base64 input\n";
echo "  [PASS] Customer views camera integration verified!\n\n";

// 6. Verify Route Registrations
echo "[6] Verifying Route Registrations...\n";
$routesContent = file_get_contents(__DIR__ . '/../app/routes.php');
assert(strpos($routesContent, "'loan-ledger'") !== false, "routes.php must contain loan-ledger route");
assert(strpos($routesContent, "'receipts/sanction/{id}'") !== false, "routes.php must contain receipts/sanction/{id} route");
echo "  [PASS] Routes verified in routes.php!\n\n";

// 7. Verify LoanController->showSanctionReceipt('33') direct invocation
echo "[7] Testing LoanController->showSanctionReceipt('33') direct controller call...\n";
\App\Helpers\Session::set('admin_id', 1);
$loanController = new \App\Controllers\LoanController();
ob_start();
$loanController->showSanctionReceipt('33');
$controllerOutput = ob_get_clean();

assert(!empty($controllerOutput), "Controller must render output");
assert(strpos($controllerOutput, 'Loan Sanction') !== false, "Controller output must contain 'Loan Sanction'");
echo "  -> Controller rendered " . strlen($controllerOutput) . " bytes successfully!\n";
echo "  [PASS] Direct LoanController->showSanctionReceipt('33') execution verified!\n\n";

echo "=== ALL SUITE CHECKS COMPLETED AND PASSED PERFECTLY! ===\n";
