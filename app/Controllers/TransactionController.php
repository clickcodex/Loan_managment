<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\TransactionModel;

class TransactionController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $date   = trim($_GET['date'] ?? date('Y-m-d'));
        $type   = trim($_GET['type'] ?? 'all');
        $search = trim($_GET['search'] ?? '');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $transactions = TransactionModel::getTransactions($date, $type, $search);
        $summary      = TransactionModel::getDailySummary($date);

        $this->render('transactions.index', [
            'pageTitle'    => "Daily Credit & Debit Cashbook (" . date('d M Y', strtotime($date)) . ")",
            'date'         => $date,
            'type'         => $type,
            'search'       => $search,
            'transactions' => $transactions,
            'summary'      => $summary
        ]);
    }

    public function exportPdf(): void {
        AuthMiddleware::check();

        $date = trim($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $transactions = TransactionModel::getTransactions($date, 'all');
        $summary      = TransactionModel::getDailySummary($date);

        // Separate credits and debits for printable register
        $credits = array_values(array_filter($transactions, fn($t) => $t['category'] === 'CREDIT'));
        $debits  = array_values(array_filter($transactions, fn($t) => $t['category'] === 'DEBIT'));

        // Load standalone PDF printable view
        require_once __DIR__ . '/../Views/transactions/pdf.php';
    }
}
