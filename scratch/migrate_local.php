<?php
require_once __DIR__ . '/../app/Config/Database.php';
$p = App\Config\Database::connect();
try {
    $count = $p->exec("
        UPDATE loan_ledger ll
        JOIN loans l ON ll.loan_id = l.id
        SET ll.remarks = l.remarks
        WHERE ll.entry_type = 'Loan Issued'
          AND (ll.remarks IS NULL OR ll.remarks = '')
          AND l.remarks IS NOT NULL
          AND l.remarks != ''
    ");
    echo "Backfilled $count existing Loan Issued rows with remarks.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
