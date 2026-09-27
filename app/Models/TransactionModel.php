<?php

namespace App\Models;

use App\Config\Database;

class TransactionModel {

    /**
     * Retrieve all Credit and Debit transactions for a specific date (or date range)
     */
    public static function getTransactions(string $date = '', string $type = 'all', string $search = ''): array {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $params = [':date' => $date];
        $searchSql = '';
        if (!empty($search)) {
            $searchSql = " AND (l.loan_number LIKE :s1 OR c.full_name LIKE :s2 OR c.mobile LIKE :s3 OR ll.description LIKE :s4) ";
            $term = '%' . trim($search) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
        }

        // 1. Fetch from loan_ledger joined with loans, customers, and payments
        $sql = "
            SELECT 
                ll.id as ledger_id,
                ll.loan_id,
                ll.entry_date as transaction_date,
                ll.description as transaction_type,
                ll.description,
                ll.debit,
                ll.credit,
                ll.balance,
                ll.payment_id,
                l.loan_number,
                l.security_type,
                l.status as loan_status,
                c.id as customer_id,
                c.full_name as customer_name,
                c.mobile as customer_mobile,
                p.receipt_number,
                p.payment_mode,
                p.reference_number,
                p.remarks as payment_remarks,
                p.principal_component,
                p.interest_component,
                (SELECT GROUP_CONCAT(DISTINCT ci.item_name SEPARATOR ', ') FROM collateral_items ci WHERE ci.loan_id = l.id) as collateral_names
            FROM loan_ledger ll
            JOIN loans l ON ll.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            LEFT JOIN payments p ON ll.payment_id = p.id
            WHERE ll.entry_date = :date
            $searchSql
            ORDER BY ll.id DESC
        ";

        $rows = Database::fetchAll($sql, $params);
        $transactions = [];

        foreach ($rows as $r) {
            $isCredit = floatval($r['credit']) > 0;
            $isDebit  = floatval($r['debit']) > 0;
            $amount   = $isCredit ? floatval($r['credit']) : floatval($r['debit']);
            $entryCategory = $isCredit ? 'CREDIT' : 'DEBIT';

            if ($type === 'credit' && !$isCredit) continue;
            if ($type === 'debit' && !$isDebit) continue;

            // Formulate human-readable description & reference
            $title = '';
            $subTitle = '';
            $mode = $r['payment_mode'] ?: 'Cash';

            if ($isCredit) {
                $interestPart = floatval($r['interest_component'] ?? 0);
                $principalPart = floatval($r['principal_component'] ?? 0);
                if ($interestPart > 0 && $principalPart > 0) {
                    $title = 'Loan Collection (P: ₹' . number_format($principalPart, 2) . ' + I: ₹' . number_format($interestPart, 2) . ')';
                } elseif ($interestPart > 0) {
                    $title = 'Interest Payment Collection';
                } else {
                    $title = 'Principal Repayment';
                }
                $subTitle = !empty($r['receipt_number']) ? 'Receipt #' . $r['receipt_number'] : 'Payment Received';
            } else {
                if (str_contains(strtolower($r['description'] ?? ''), 'top')) {
                    $title = 'Loan Top-Up Disbursement';
                    $subTitle = 'Additional Capital Disbursed';
                } else {
                    $title = 'New Loan Disbursement';
                    $subTitle = 'Capital Outflow for Loan #' . $r['loan_number'];
                }
            }

            $rem = !empty($r['payment_remarks']) ? $r['payment_remarks'] : ($r['description'] ?: '—');

            $transactions[] = [
                'id'                  => $r['ledger_id'],
                'loan_id'             => $r['loan_id'],
                'customer_id'         => $r['customer_id'],
                'transaction_date'    => $r['transaction_date'],
                'category'            => $entryCategory, // CREDIT or DEBIT
                'transaction_type'    => $r['transaction_type'],
                'title'               => $title,
                'sub_title'           => $subTitle,
                'amount'              => $amount,
                'debit'               => floatval($r['debit']),
                'credit'              => floatval($r['credit']),
                'balance'             => floatval($r['balance']),
                'payment_mode'        => $mode,
                'receipt_number'      => $r['receipt_number'] ?: '—',
                'reference_number'    => $r['reference_number'] ?: '—',
                'remarks'             => $rem,
                'loan_number'         => $r['loan_number'],
                'customer_name'       => $r['customer_name'],
                'customer_mobile'     => $r['customer_mobile'],
                'collateral_names'    => !empty($r['collateral_names']) ? $r['collateral_names'] : $r['security_type'],
                'loan_status'         => $r['loan_status']
            ];
        }

        return $transactions;
    }

    /**
     * Compute comprehensive financial totals for the selected date
     */
    public static function getDailySummary(string $date = ''): array {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $all = self::getTransactions($date, 'all');

        $totalCredit = 0;
        $totalDebit  = 0;
        $creditCount = 0;
        $debitCount  = 0;

        $cashCredit = 0;
        $upiCredit  = 0;
        $bankCredit = 0;

        $cashDebit = 0;
        $upiDebit  = 0;
        $bankDebit = 0;

        foreach ($all as $t) {
            if ($t['category'] === 'CREDIT') {
                $totalCredit += $t['amount'];
                $creditCount++;
                $m = strtolower($t['payment_mode'] ?? 'cash');
                if (str_contains($m, 'upi') || str_contains($m, 'online') || str_contains($m, 'gpay')) {
                    $upiCredit += $t['amount'];
                } elseif (str_contains($m, 'bank') || str_contains($m, 'neft') || str_contains($m, 'cheque')) {
                    $bankCredit += $t['amount'];
                } else {
                    $cashCredit += $t['amount'];
                }
            } else {
                $totalDebit += $t['amount'];
                $debitCount++;
                $m = strtolower($t['payment_mode'] ?? 'cash');
                if (str_contains($m, 'upi') || str_contains($m, 'online')) {
                    $upiDebit += $t['amount'];
                } elseif (str_contains($m, 'bank') || str_contains($m, 'neft')) {
                    $bankDebit += $t['amount'];
                } else {
                    $cashDebit += $t['amount'];
                }
            }
        }

        $netCashFlow = $totalCredit - $totalDebit;

        return [
            'date'              => $date,
            'total_credit'      => $totalCredit,
            'total_debit'       => $totalDebit,
            'net_cash_flow'     => $netCashFlow,
            'credit_count'      => $creditCount,
            'debit_count'       => $debitCount,
            'total_count'       => count($all),
            'cash_credit'       => $cashCredit,
            'upi_credit'        => $upiCredit,
            'bank_credit'       => $bankCredit,
            'cash_debit'        => $cashDebit,
            'upi_debit'         => $upiDebit,
            'bank_debit'        => $bankDebit
        ];
    }
}
