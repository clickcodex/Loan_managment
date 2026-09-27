<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/RateModel.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/Loan.php';

use App\Config\Database;
use App\Models\RateModel;
use App\Models\CollateralModel;
use App\Models\Customer;
use App\Models\Loan;

echo "=== STARTING COLLATERAL RATE & PURITY REVALUATION TEST SUITE ===\n\n";

$db = Database::connect();

// 1. Setup temporary test customer & loan
$custStmt = $db->query("SELECT id FROM customers LIMIT 1");
$cust = $custStmt->fetch();
$customerId = $cust ? intval($cust['id']) : Customer::create([
    'customer_id'    => 'CUST-TEST-' . time(),
    'account_number' => 'ACC-TEST-' . time(),
    'full_name'      => 'Test Purity Customer',
    'mobile'         => '9876543210',
    'status'         => 'Active'
]);

$testLoanNumber = 'LOAN-TEST-RATE-' . time();
$loanId = Loan::createLoan([
    'loan_number'        => $testLoanNumber,
    'customer_id'        => $customerId,
    'loan_date'          => date('Y-m-d'),
    'security_type'      => 'Gold + Silver Secured',
    'principal_amount'   => 50000.00,
    'interest_rate'      => 2.0,
    'interest_cycle'     => '15 Days',
    'interest_method'    => 'Simple',
    'compound_frequency' => 'Monthly',
    'status'             => 'Running',
    'remarks'            => 'Purity auto-update test loan'
], [
    // Gold Item 1: 10g, 90.00% purity
    [
        'item_name'         => 'Test 90% Gold Ring',
        'quantity'          => 1,
        'gross_weight'      => 10.000,
        'stone_weight'      => 0.000,
        'net_weight'        => 10.000,
        'purity_preset'     => 'Custom',
        'purity_percentage' => 90.00,
        'market_value'      => 0.00
    ],
    // Gold Item 2: 10g, 22K (91.67%) purity
    [
        'item_name'         => 'Test 22K Gold Chain',
        'quantity'          => 1,
        'gross_weight'      => 10.000,
        'stone_weight'      => 0.000,
        'net_weight'        => 10.000,
        'purity_preset'     => '22K',
        'purity_percentage' => 91.67,
        'market_value'      => 0.00
    ],
    // Gold Item 3: 15g, 18K (75.00%) purity with 0 purity_percentage stored (tests fallback)
    [
        'item_name'         => 'Test 18K Gold Bangle',
        'quantity'          => 1,
        'gross_weight'      => 15.000,
        'stone_weight'      => 0.000,
        'net_weight'        => 15.000,
        'purity_preset'     => '18K',
        'purity_percentage' => 0.00,
        'market_value'      => 0.00
    ]
], [
    // Silver Item 1: 50g, 92.50% purity
    [
        'item_name'         => 'Test 92.5% Silver Payal',
        'quantity'          => 1,
        'gross_weight'      => 50.000,
        'net_weight'        => 50.000,
        'purity_preset'     => '92.5%',
        'purity_percentage' => 92.50,
        'market_value'      => 0.00
    ],
    // Silver Item 2: 100g, 99.90% purity
    [
        'item_name'         => 'Test 99.9% Silver Bar',
        'quantity'          => 1,
        'gross_weight'      => 100.000,
        'net_weight'        => 100.000,
        'purity_preset'     => '99.9%',
        'purity_percentage' => 99.90,
        'market_value'      => 0.00
    ]
]);

echo "Created test loan #$testLoanNumber (ID: $loanId) with 3 Gold and 2 Silver test items.\n\n";

// -------------------------------------------------------------------------------------------------
// TEST 1: Test Gold Rate Update to ₹150,000 / 10g and verify automatic collateral recalculation
// -------------------------------------------------------------------------------------------------
echo "[TEST 1] Testing Gold Rate Update to ₹150,000 / 10g...\n";
$newGoldRate100 = 150000.00;
RateModel::updateGoldRates([
    'rate_100'     => $newGoldRate100,
    'rate_date'    => date('Y-m-d'),
    'remarks'      => 'Automated test rate ₹150,000 / 10g'
]);

// Expected values for gold items at ₹150,000 / 10g:
// Gram rate for 100% fine gold = 150000 / 10 = 15000
// Item 1 (10g @ 90.00%): 10 * (90 / 100) * 15000 = 135,000.00
// Item 2 (10g @ 91.67%): 10 * (91.67 / 100) * 15000 = 137,505.00
// Item 3 (15g @ 75.00% fallback): 15 * (75 / 100) * 15000 = 168,750.00

$testGoldItems = Database::fetchAll("SELECT * FROM collateral_items WHERE loan_id = :lid AND item_type = 'GOLD' ORDER BY id ASC", [':lid' => $loanId]);

assert(count($testGoldItems) === 3, "Expected 3 gold items");

$val1 = floatval($testGoldItems[0]['market_value']);
$val2 = floatval($testGoldItems[1]['market_value']);
$val3 = floatval($testGoldItems[2]['market_value']);

echo "  -> Item 1 (10g @ 90.00%): Market Value = ₹" . number_format($val1, 2) . " (Expected: ₹135,000.00)\n";
assert(abs($val1 - 135000.00) < 0.01, "Item 1 valuation mismatch!");

echo "  -> Item 2 (10g @ 91.67%): Market Value = ₹" . number_format($val2, 2) . " (Expected: ₹137,505.00)\n";
assert(abs($val2 - 137505.00) < 0.01, "Item 2 valuation mismatch!");

echo "  -> Item 3 (15g @ 18K / 75.00% fallback): Market Value = ₹" . number_format($val3, 2) . " (Expected: ₹168,750.00)\n";
assert(abs($val3 - 168750.00) < 0.01, "Item 3 valuation mismatch!");

echo "  [SUCCESS] All Gold items re-valued accurately based on price update & purity!\n\n";

// -------------------------------------------------------------------------------------------------
// TEST 2: Test Silver Rate Update to ₹120 / gram and verify automatic collateral recalculation
// -------------------------------------------------------------------------------------------------
echo "[TEST 2] Testing Silver Rate Update to ₹120.00 / gram...\n";
$newSilverRate100 = 120.00;
RateModel::updateSilverRates([
    'rate_100'     => $newSilverRate100,
    'rate_999'     => round($newSilverRate100 * 0.999, 2),
    'rate_925'     => round($newSilverRate100 * 0.925, 2),
    'rate_800'     => round($newSilverRate100 * 0.800, 2),
    'rate_date'    => date('Y-m-d'),
    'remarks'      => 'Automated test rate ₹120.00 / gram'
]);

// Expected values for silver items at ₹120 / g:
// Item 1 (50g @ 92.50%): 50 * (92.50 / 100) * 120 = 5,550.00
// Item 2 (100g @ 99.90%): 100 * (99.90 / 100) * 120 = 11,988.00

$testSilverItems = Database::fetchAll("SELECT * FROM collateral_items WHERE loan_id = :lid AND item_type = 'SILVER' ORDER BY id ASC", [':lid' => $loanId]);

assert(count($testSilverItems) === 2, "Expected 2 silver items");

$sVal1 = floatval($testSilverItems[0]['market_value']);
$sVal2 = floatval($testSilverItems[1]['market_value']);

echo "  -> Silver Item 1 (50g @ 92.50%): Market Value = ₹" . number_format($sVal1, 2) . " (Expected: ₹5,550.00)\n";
assert(abs($sVal1 - 5550.00) < 0.01, "Silver Item 1 valuation mismatch!");

echo "  -> Silver Item 2 (100g @ 99.90%): Market Value = ₹" . number_format($sVal2, 2) . " (Expected: ₹11,988.00)\n";
assert(abs($sVal2 - 11988.00) < 0.01, "Silver Item 2 valuation mismatch!");

echo "  [SUCCESS] All Silver items re-valued accurately based on price update & purity!\n\n";

// -------------------------------------------------------------------------------------------------
// TEST 3: Second Rate Change - Verify that subsequent rate change recalculates again dynamically
// -------------------------------------------------------------------------------------------------
echo "[TEST 3] Testing Second Gold Rate Change to ₹140,000 / 10g...\n";
RateModel::updateGoldRates([
    'rate_100'     => 140000.00,
    'rate_date'    => date('Y-m-d'),
    'remarks'      => 'Automated test rate ₹140,000 / 10g'
]);

// Item 1 (10g @ 90.00%): 10 * 0.90 * 14000 = 126,000.00
$updatedItem1 = Database::fetchOne("SELECT market_value FROM collateral_items WHERE id = :id", [':id' => $testGoldItems[0]['id']]);
$newVal1 = floatval($updatedItem1['market_value']);
echo "  -> Item 1 recalculated: ₹" . number_format($newVal1, 2) . " (Expected: ₹126,000.00)\n";
assert(abs($newVal1 - 126000.00) < 0.01, "Second rate update mismatch!");

echo "  [SUCCESS] Dynamic adjustment on subsequent rate update verified!\n\n";

// -------------------------------------------------------------------------------------------------
// TEST 4: CollateralModel::recalculateCollateralMarketValues manual call
// -------------------------------------------------------------------------------------------------
echo "[TEST 4] Testing CollateralModel::recalculateCollateralMarketValues manual sync...\n";
$syncCount = CollateralModel::recalculateCollateralMarketValues();
echo "  -> Total collateral items recalculated across system: $syncCount\n";
assert($syncCount >= 5, "Expected at least 5 items recalculated");

echo "  [SUCCESS] Manual sync recalculated all collateral items!\n\n";

// Cleanup test loan & items
echo "[CLEANUP] Removing test loan and test collaterals...\n";
Database::execute("DELETE FROM collateral_items WHERE loan_id = :lid", [':lid' => $loanId]);
Database::execute("DELETE FROM loans WHERE id = :lid", [':lid' => $loanId]);

echo "\n=== ALL COLLATERAL RATE & PURITY REVALUATION TESTS PASSED (100% GREEN) ===\n";
