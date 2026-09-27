<?php
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/DueModel.php';
require_once __DIR__ . '/../app/Models/RateModel.php';

use App\Models\DueModel;

$loan = [
    'id' => 999,
    'principal_amount' => 10000,
    'interest_rate' => 3.0,
    'interest_cycle' => '15 Days',
    'interest_method' => 'Compound',
    'compound_frequency' => 'Yearly',
    'loan_date' => '2025-01-19'
];

$today = new DateTime('2026-09-21');
$res = DueModel::calculateLoanDues($loan, $today);
echo "accrued_interest: " . $res['accrued_interest'] . "\n";
echo "total_payable: " . $res['total_payable'] . "\n";
echo "compounded_principal: " . $res['compounded_principal'] . "\n";
echo "current_year_cycles: " . $res['current_year_cycles'] . "\n";
echo "current_year_interest: " . $res['current_year_interest'] . "\n";
