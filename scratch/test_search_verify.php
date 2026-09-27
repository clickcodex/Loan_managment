<?php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/SearchModel.php';

use App\Config\Database;
use App\Models\SearchModel;

echo "=== Verifying Global Search by Loan Remarks ===\n";

// 1. Check existing loans remarks in DB
$loansWithRemarks = Database::fetchAll("SELECT id, loan_number, customer_id, remarks FROM loans WHERE remarks IS NOT NULL AND TRIM(remarks) != '' LIMIT 5");

if (empty($loansWithRemarks)) {
    // Add a remark to an existing loan for testing
    $loan = Database::fetchOne("SELECT id, loan_number, customer_id FROM loans LIMIT 1");
    if ($loan) {
        $testRemark = "Special emergency loan for medical expenses #TESTREM999";
        Database::query("UPDATE loans SET remarks = :r WHERE id = :id", [':r' => $testRemark, ':id' => $loan['id']]);
        echo "Set test remark on Loan ID {$loan['id']}: '{$testRemark}'\n";
    }
} else {
    echo "Found existing loan with remark: Loan #{$loansWithRemarks[0]['loan_number']}: '{$loansWithRemarks[0]['remarks']}'\n";
}

// Re-fetch a loan with remarks
$testLoan = Database::fetchOne("SELECT l.id, l.loan_number, l.customer_id, l.remarks, c.full_name FROM loans l JOIN customers c ON l.customer_id = c.id WHERE l.remarks IS NOT NULL AND TRIM(l.remarks) != '' LIMIT 1");

if ($testLoan) {
    // Extract a search keyword from remarks
    $words = explode(' ', trim($testLoan['remarks']));
    $keyword = end($words);
    if (strlen($keyword) < 3 && count($words) > 1) {
        $keyword = $words[0];
    }
    echo "\nSearching using keyword from loan remarks: '{$keyword}'...\n";

    // Test searchCustomers
    $custResults = SearchModel::searchCustomers($keyword);
    echo "Customers found: " . count($custResults) . "\n";
    foreach ($custResults as $c) {
        echo " - Customer ID {$c['id']}: {$c['full_name']}, Matched Loan Remark: '{$c['matched_loan_remark']}'\n";
    }

    // Test searchLoans
    $loanResults = SearchModel::searchLoans($keyword);
    echo "Loans found: " . count($loanResults) . "\n";
    foreach ($loanResults as $l) {
        echo " - Loan ID {$l['id']} ({$l['loan_number']}): Remark = '{$l['remarks']}'\n";
    }

    // Assert that the test loan's customer is in custResults and loan is in loanResults
    $foundCustomer = false;
    foreach ($custResults as $c) {
        if ($c['id'] == $testLoan['customer_id']) {
            $foundCustomer = true;
            break;
        }
    }

    $foundLoan = false;
    foreach ($loanResults as $l) {
        if ($l['id'] == $testLoan['id']) {
            $foundLoan = true;
            break;
        }
    }

    if ($foundCustomer && $foundLoan) {
        echo "\n>>> SUCCESS: Global Search successfully found both Customer and Loan by Loan Remarks! <<<\n";
    } else {
        echo "\n>>> FAILED: Customer found: " . ($foundCustomer ? "YES" : "NO") . ", Loan found: " . ($foundLoan ? "YES" : "NO") . " <<<\n";
    }
} else {
    echo "No loan with remarks found to test.\n";
}
