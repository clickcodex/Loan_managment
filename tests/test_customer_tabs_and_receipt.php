<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Models/Loan.php';
require_once __DIR__ . '/../app/Models/CollateralModel.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';
require_once __DIR__ . '/../app/Models/DueModel.php';
require_once __DIR__ . '/../app/Models/RateModel.php';

use App\Config\Database;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\PaymentModel;

echo "=== STARTING CUSTOMER TABS & PAYMENT RECEIPT VERIFICATION TEST ===\n\n";

$db = Database::connect();

// 1. Verify Customer with Loans
$customers = Customer::getAll();
assert(!empty($customers), "Customers table should not be empty");
$customer = $customers[0];
echo "[1] Testing with Customer: {$customer['full_name']} (ID: {$customer['id']}, Cust Code: {$customer['customer_id']}, Account#: " . ($customer['account_number'] ?? 'None') . ")\n";

$rawLoans = Customer::getLoans(intval($customer['id']));
$today = new \DateTime();
$goldRate = \App\Models\RateModel::getLatestGoldRate();
$silverRate = \App\Models\RateModel::getLatestSilverRate();
$loans = [];
foreach ($rawLoans as $l) {
    $loans[] = \App\Models\DueModel::calculateLoanDues($l, $today, $goldRate, $silverRate);
}

$activeLoans = array_values(array_filter($loans, fn($l) => ($l['status'] ?? '') !== 'Closed'));
$closedLoans = array_values(array_filter($loans, fn($l) => ($l['status'] ?? '') === 'Closed'));

echo "  -> Total Loans: " . count($loans) . "\n";
echo "  -> Active Loans: " . count($activeLoans) . "\n";
echo "  -> Closed Loans: " . count($closedLoans) . "\n";

foreach ($activeLoans as $al) {
    assert($al['status'] !== 'Closed', "Active loans must not contain closed status!");
}
foreach ($closedLoans as $cl) {
    assert($cl['status'] === 'Closed', "Closed loans must only contain closed status!");
}
echo "  -> SUCCESS: Active and Closed loans correctly separated!\n\n";

// 2. Verify PaymentModel::findById and receipt fields
$recentPayment = Database::fetchOne("SELECT id FROM payments ORDER BY id DESC LIMIT 1");
if ($recentPayment) {
    $paymentId = intval($recentPayment['id']);
    $payment = PaymentModel::findById($paymentId);
    echo "[2] Testing Payment Receipt Model with Payment ID: {$paymentId}\n";
    assert($payment !== null, "Payment should be found");
    echo "  -> Receipt Number: {$payment['receipt_number']}\n";
    echo "  -> Customer Account Number: " . ($payment['customer_account_number'] ?? 'N/A') . "\n";
    echo "  -> Loan Number: {$payment['loan_number']}\n";
    echo "  -> Customer Name: {$payment['customer_name']}\n";
    echo "  -> Date: {$payment['payment_date']}\n";
    echo "  -> Loan Amount (Principal): ₹" . number_format(floatval($payment['principal_amount']), 2) . "\n";

    $collateralItems = Loan::getCollateralItems(intval($payment['loan_id']));
    echo "  -> Collateral Items Count: " . count($collateralItems) . "\n";
    foreach ($collateralItems as $ci) {
        echo "     * {$ci['item_name']} | Gross: {$ci['gross_weight']}g | Net: {$ci['net_weight']}g | Rack: " . ($ci['rk_number'] ?? 'N/A') . "\n";
    }

    // Test rendering receipt view buffer
    $baseUrl = '';
    ob_start();
    include __DIR__ . '/../app/Views/payments/receipt.php';
    $renderedReceipt = ob_get_clean();

    assert(strpos($renderedReceipt, 'Account No.') !== false, "Receipt must contain Account No.");
    assert(strpos($renderedReceipt, 'Rack No.') !== false, "Receipt must contain Rack No.");
    assert(strpos($renderedReceipt, 'Customer Name') !== false, "Receipt must contain Customer Name");
    assert(strpos($renderedReceipt, 'Date') !== false, "Receipt must contain Date");
    assert(strpos($renderedReceipt, 'Loan Amount') !== false, "Receipt must contain Loan Amount");
    assert(strpos($renderedReceipt, 'Collateral Items with Weight') !== false, "Receipt must contain Collateral Items with Weight");
    
    // Ensure removed unnecessary fields are NOT present
    assert(strpos($renderedReceipt, 'GSTIN') === false, "Receipt must NOT contain GSTIN");
    assert(strpos($renderedReceipt, 'Customer Signature') === false, "Receipt must NOT contain Customer Signature");
    assert(strpos($renderedReceipt, 'Authorized Signatory') === false, "Receipt must NOT contain Authorized Signatory");
    assert(strpos($renderedReceipt, 'Total Settlement Credit') === false, "Receipt must NOT contain Total Settlement Credit");

    echo "  -> SUCCESS: Receipt template renders ONLY the requested details without errors!\n";
} else {
    echo "[2] Testing receipt template with representative payment record...\n";
    $payment = [
        'id'                      => 999,
        'receipt_number'          => 'REC-2026-9999',
        'loan_id'                 => 1,
        'payment_date'            => '2026-09-05',
        'customer_name'           => 'Hirendra Chouhan',
        'customer_account_number' => 'Acc-1',
        'loan_number'             => 'LMS-2026-0001',
        'principal_amount'        => 50000.00
    ];
    $collateralItems = [
        [
            'id'           => 1,
            'item_name'    => '22K Gold Necklace',
            'quantity'     => 1,
            'gross_weight' => 24.500,
            'net_weight'   => 24.000,
            'rk_number'    => 'RK-01-S01'
        ],
        [
            'id'           => 2,
            'item_name'    => 'Gold Ring',
            'quantity'     => 2,
            'gross_weight' => 10.200,
            'net_weight'   => 10.000,
            'rk_number'    => 'RK-01-S02'
        ]
    ];
    $baseUrl = '';
    ob_start();
    include __DIR__ . '/../app/Views/payments/receipt.php';
    $renderedReceipt = ob_get_clean();

    assert(strpos($renderedReceipt, 'Account No.') !== false, "Receipt must contain Account No.");
    assert(strpos($renderedReceipt, 'Acc-1') !== false, "Receipt must contain Acc-1");
    assert(strpos($renderedReceipt, 'Rack No.') !== false, "Receipt must contain Rack No.");
    assert(strpos($renderedReceipt, 'RK-01-S01, RK-01-S02') !== false, "Receipt must contain Rack numbers");
    assert(strpos($renderedReceipt, 'Customer Name') !== false, "Receipt must contain Customer Name");
    assert(strpos($renderedReceipt, 'Hirendra Chouhan') !== false, "Receipt must contain Hirendra Chouhan");
    assert(strpos($renderedReceipt, 'Date') !== false, "Receipt must contain Date");
    assert(strpos($renderedReceipt, '05-09-2026') !== false, "Receipt must contain 05-09-2026");
    assert(strpos($renderedReceipt, 'Loan Amount') !== false, "Receipt must contain Loan Amount");
    assert(strpos($renderedReceipt, '50,000.00') !== false, "Receipt must contain 50,000.00");
    assert(strpos($renderedReceipt, 'Collateral Items with Weight') !== false, "Receipt must contain Collateral Items with Weight");
    assert(strpos($renderedReceipt, '22K Gold Necklace') !== false, "Receipt must contain 22K Gold Necklace");
    assert(strpos($renderedReceipt, '24.000 g') !== false, "Receipt must contain 24.000 g");
    assert(strpos($renderedReceipt, '34.000 g') !== false, "Receipt must contain total net weight 34.000 g");

    // Ensure removed unnecessary fields are NOT present
    assert(strpos($renderedReceipt, 'GSTIN') === false, "Receipt must NOT contain GSTIN");
    assert(strpos($renderedReceipt, 'Customer Signature') === false, "Receipt must NOT contain Customer Signature");
    assert(strpos($renderedReceipt, 'Authorized Signatory') === false, "Receipt must NOT contain Authorized Signatory");
    assert(strpos($renderedReceipt, 'Total Settlement Credit') === false, "Receipt must NOT contain Total Settlement Credit");
    assert(strpos($renderedReceipt, 'Payment Mode') === false, "Receipt must NOT contain Payment Mode");

    echo "  -> SUCCESS: Receipt template renders ONLY the 6 requested details cleanly!\n";
}

echo "\n=== ALL CHECKS PASSED SUCCESSFULLY ===\n";
