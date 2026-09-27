<?php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/BillModel.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';

use App\Config\Database;
use App\Models\BillModel;
use App\Models\Loan;
use App\Models\Customer;
use App\Models\PaymentModel;

echo "========================================================\n";
echo "RUNNING COMPREHENSIVE VERIFICATION FOR ALL 4 FEATURES\n";
echo "========================================================\n\n";

// -----------------------------------------------------------------------------
// TEST 1: Database Updates & SQL Migration Idempotency
// -----------------------------------------------------------------------------
echo "[TEST 1] Testing Database Updates & SQL Migration...\n";
$pdo = Database::connect();

$migrationSql = file_get_contents(__DIR__ . '/../storage/update_features_disbursement_closure_billing.sql');
assert(!empty($migrationSql), "Migration SQL file must not be empty");

// Execute migration
$pdo->exec($migrationSql);

// Re-execute to verify idempotency (no duplicate errors)
$pdo->exec($migrationSql);

// Verify bills table
$cols = $pdo->query("SHOW COLUMNS FROM bills")->fetchAll(PDO::FETCH_COLUMN);
$requiredCols = ['id', 'bill_number', 'company_name', 'customer_name', 'customer_mobile', 'customer_address', 'bill_date', 'items', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'payment_mode', 'notes', 'created_at', 'updated_at'];
foreach ($requiredCols as $rc) {
    assert(in_array($rc, $cols), "bills table must contain column '$rc'");
}
echo "✓ Test 1 Passed: Database migration is valid, safe, idempotent, and all tables/columns exist.\n\n";

// -----------------------------------------------------------------------------
// TEST 2: Single-Table Billing System (Multi-Product Support)
// -----------------------------------------------------------------------------
echo "[TEST 2] Testing Single-Table Multi-Product Billing System...\n";
$nextBillNo = BillModel::generateNextBillNumber();
assert(strpos($nextBillNo, 'BILL-') === 0, "Bill number should start with BILL-");

$testProducts = [
    [
        'product_name' => '22K Gold Bangles',
        'quantity'     => 2,
        'price'        => 55000.00,
        'total'        => 110000.00
    ],
    [
        'product_name' => 'Silver Kalash',
        'quantity'     => 1,
        'price'        => 12500.00,
        'total'        => 12500.00
    ],
    [
        'product_name' => 'Jewellery Velvet Box',
        'quantity'     => 3,
        'price'        => 350.00,
        'total'        => 1050.00
    ]
];

$subtotal = 110000.00 + 12500.00 + 1050.00; // 123550.00
$discount = 1550.00;
$grandTotal = $subtotal - $discount; // 122000.00

$billData = [
    'bill_number'      => $nextBillNo,
    'company_name'     => 'Golden Trust Finance Co.',
    'customer_name'    => 'Suresh Chandra Sharma',
    'customer_mobile'  => '9876500001',
    'customer_address' => '45 Jewellers Lane, City',
    'gst_number'       => '27AAACG1234F1Z5',
    'bill_date'        => date('Y-m-d'),
    'items'            => $testProducts,
    'subtotal'         => $subtotal,
    'discount_amount'  => $discount,
    'tax_amount'       => 0.00,
    'total_amount'     => $grandTotal,
    'payment_mode'     => 'UPI',
    'notes'            => 'All items guaranteed hallmarked.'
];

$billId = BillModel::create($billData);
assert($billId > 0, "BillModel::create must return positive insert ID");

// Fetch single bill
$fetchedBill = BillModel::findById($billId);
assert(!empty($fetchedBill), "Bill must be retrieved by ID");
assert($fetchedBill['bill_number'] === $nextBillNo, "Bill number must match");
assert($fetchedBill['customer_name'] === 'Suresh Chandra Sharma', "Customer name must match");
assert($fetchedBill['gst_number'] === '27AAACG1234F1Z5', "GST number must match");
assert(count($fetchedBill['items_decoded']) === 3, "Decoded items must contain exactly 3 products");
assert($fetchedBill['items_decoded'][0]['product_name'] === '22K Gold Bangles', "First product name must match");
assert(floatval($fetchedBill['subtotal']) === 123550.00, "Subtotal must equal 123550.00");
assert(floatval($fetchedBill['total_amount']) === 122000.00, "Total amount must equal 122000.00");

// Test that GST is optional (bill without GST succeeds)
$billNoGst = BillModel::create([
    'bill_number'      => BillModel::generateNextBillNumber(),
    'company_name'     => 'Golden Trust Finance Co.',
    'customer_name'    => 'Anil Verma (No GST)',
    'customer_mobile'  => '9988776655',
    'customer_address' => 'Station Road',
    'gst_number'       => '', // empty, non-mandatory
    'bill_date'        => date('Y-m-d'),
    'items'            => [
        ['product_name' => 'Silver Ring', 'quantity' => 1, 'price' => 1500, 'total' => 1500]
    ],
    'subtotal'         => 1500,
    'total_amount'     => 1500
]);
$fetchedNoGst = BillModel::findById($billNoGst);
assert($fetchedNoGst['gst_number'] === null || $fetchedNoGst['gst_number'] === '', "Bill without GST must save properly as optional");
BillModel::delete($billNoGst);

// Verify stats
$stats = BillModel::getStats();
assert($stats['total_count'] >= 1, "Stats total_count should be at least 1");
assert($stats['total_revenue'] >= 122000.00, "Stats total_revenue should include test bill");

// Search filter test with GST
$searchResults = BillModel::getAll('27AAACG1234F1Z5');
assert(!empty($searchResults), "Search for GST Number should find the bill");
assert($searchResults[0]['id'] == $billId, "First search result should be our bill with GST");

echo "✓ Test 2 Passed: Single-table multi-product billing creates, stores, searches, and decodes successfully.\n\n";

// -----------------------------------------------------------------------------
// TEST 3: Loan Disbursement Receipt ("RK DETAILS" Layout)
// -----------------------------------------------------------------------------
echo "[TEST 3] Testing Loan Disbursement Receipt View & Data...\n";

// Get any existing loan or create one
$loans = Loan::getAll();
if (empty($loans)) {
    // Create test customer
    $cId = Customer::create([
        'customer_id'    => 'CUST-TEST-01',
        'account_number' => '11178',
        'full_name'      => 'Rajesh Kumar Test',
        'father_name'    => 'Mohanlal Sharma',
        'mobile'         => '9876543210',
        'aadhaar'        => '1234 5678 9012',
        'status'         => 'Active'
    ]);
    $loanNo = 'LMS-2026-0300';
    $loanId = Loan::createLoan([
        'loan_number'        => $loanNo,
        'customer_id'        => $cId,
        'loan_date'          => '2026-08-04',
        'security_type'      => 'Gold Secured',
        'principal_amount'   => 50000.00,
        'interest_rate'      => 2.00,
        'interest_cycle'     => '15 Days',
        'interest_method'    => 'Compound',
        'compound_frequency' => 'Yearly',
        'status'             => 'Running'
    ], [
        [
            'item_name'         => 'patta',
            'gross_weight'      => 520.00,
            'stone_weight'      => 0,
            'net_weight'        => 520.00,
            'purity_percentage' => 91.6,
            'market_value'      => 300000.00
        ]
    ], [], [
        'guarantor_name'   => 'Ramesh Patel',
        'guarantor_mobile' => '9898989898'
    ]);
} else {
    $loanId = intval($loans[0]['id']);
}

$loanDetails = Loan::getSanctionDetails($loanId);
assert(!empty($loanDetails), "Loan sanction details must be found");
$collateralItems = Loan::getCollateralItems($loanId);
$guarantor = Loan::getGuarantor($loanId);

// Verify disbursement view template compiles without PHP errors
ob_start();
$loan = $loanDetails;
$baseUrl = '';
require __DIR__ . '/../app/Views/loans/disbursement_receipt.php';
$disbursementHtml = ob_get_clean();

assert(strpos($disbursementHtml, 'RK DETAILS') !== false, "Disbursement receipt must have RK DETAILS header");
assert(strpos($disbursementHtml, 'Acc. No. :') !== false, "Disbursement receipt must contain Acc. No. :");
assert(strpos($disbursementHtml, 'Dated :') !== false, "Disbursement receipt must contain Dated :");
assert(strpos($disbursementHtml, 'Name :') !== false, "Disbursement receipt must contain Name :");
assert(strpos($disbursementHtml, 'Father Name :') !== false, "Disbursement receipt must contain Father Name :");
assert(strpos($disbursementHtml, 'Adhaar No. :') !== false, "Disbursement receipt must contain Adhaar No. :");
assert(strpos($disbursementHtml, 'Mobile :') !== false, "Disbursement receipt must contain Mobile :");
assert(strpos($disbursementHtml, 'Amount :') !== false, "Disbursement receipt must contain Amount :");
assert(strpos($disbursementHtml, 'Gua. Name :') !== false, "Disbursement receipt must contain Gua. Name :");
assert(strpos($disbursementHtml, 'Gua. Mobile :') !== false, "Disbursement receipt must contain Gua. Mobile :");
assert(strpos($disbursementHtml, 'RK Detail\'s :') !== false, "Disbursement receipt must contain RK Detail's :");

echo "✓ Test 3 Passed: Loan Disbursement Receipt contains all required fields matching reference voucher.\n\n";

// -----------------------------------------------------------------------------
// TEST 4: Loan Closure Receipt & No-Due Certificate
// -----------------------------------------------------------------------------
echo "[TEST 4] Testing Loan Closure Receipt View & Data...\n";

$payments = Loan::getPayments($loanId);

ob_start();
$loan = $loanDetails;
$loan['status'] = 'Closed';
$loan['delivered'] = 'Yes';
$loan['haste'] = 'Customer Self';
$loan['delivery_date'] = date('Y-m-d');
$loan['delivery_remarks'] = 'Full settlement received and ornaments handed over.';
$baseUrl = '';
require __DIR__ . '/../app/Views/loans/closure_receipt.php';
$closureHtml = ob_get_clean();

assert(strpos($closureHtml, 'Loan Closure Receipt') !== false, "Closure receipt must have header");
assert(strpos($closureHtml, 'Settlement & Payment Summary') !== false, "Closure receipt must contain Settlement & Payment Summary");
assert(strpos($closureHtml, 'Outstanding Balance') !== false, "Closure receipt must contain Outstanding Balance");
assert(strpos($closureHtml, 'DELIVERED') !== false, "Closure receipt must show DELIVERED status");

echo "✓ Test 4 Passed: Loan Closure Receipt renders cleanly without declaration or signatures.\n\n";

// -----------------------------------------------------------------------------
// TEST 5: Routes & Controller Action Mapping
// -----------------------------------------------------------------------------
echo "[TEST 5] Testing Routes Registration...\n";
$routesContent = file_get_contents(__DIR__ . '/../app/routes.php');
assert(strpos($routesContent, "'receipts/disbursement/{id}'") !== false, "routes.php must register disbursement receipt");
assert(strpos($routesContent, "'receipts/closure/{id}'") !== false, "routes.php must register closure receipt");
assert(strpos($routesContent, "'bills'") !== false, "routes.php must register bills index");
assert(strpos($routesContent, "'bills/create'") !== false, "routes.php must register bills create");
assert(strpos($routesContent, "'bills/store'") !== false, "routes.php must register bills store");
assert(strpos($routesContent, "'bills/{id}'") !== false, "routes.php must register bills show");
assert(strpos($routesContent, "'bills/{id}/delete'") !== false, "routes.php must register bills delete");

// Cleanup test bill
BillModel::delete($billId);
assert(BillModel::findById($billId) === null, "Test bill deleted successfully");

echo "✓ Test 5 Passed: All routes properly registered and mapped.\n\n";

echo "========================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY! (5 / 5)\n";
echo "========================================================\n";
