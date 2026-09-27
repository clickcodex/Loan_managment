<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\PaymentModel;
use App\Models\Loan;
use App\Models\DueModel;
use App\Models\RateModel;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class PaymentController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $search      = trim($_GET['search'] ?? '');
        $paymentMode = trim($_GET['mode'] ?? '');

        $payments = PaymentModel::getAll($search, $paymentMode, '');

        $rawLoans = Loan::getAll('', 'Running');
        $nextReceiptNumber = PaymentModel::generateNextReceiptNumber();

        $today = new \DateTime();
        $goldRate = RateModel::getLatestGoldRate();
        $silverRate = RateModel::getLatestSilverRate();

        $loans = [];
        foreach ($rawLoans as $l) {
            $loans[] = DueModel::calculateLoanDues($l, $today, $goldRate, $silverRate);
        }

        $this->render('payments.index', [
            'pageTitle'         => 'Payments & Receipts Directory',
            'payments'          => $payments,
            'search'            => $search,
            'paymentMode'       => $paymentMode,
            'loans'             => $loans,
            'nextReceiptNumber' => $nextReceiptNumber
        ]);
    }

    public function create(): void {
        AuthMiddleware::check();

        $loanId = intval($_GET['loan_id'] ?? 0);
        $rawLoans = Loan::getAll('', 'Running');
        $nextReceiptNumber = PaymentModel::generateNextReceiptNumber();

        $today = new \DateTime();
        $goldRate = RateModel::getLatestGoldRate();
        $silverRate = RateModel::getLatestSilverRate();

        $loans = [];
        foreach ($rawLoans as $l) {
            $loans[] = DueModel::calculateLoanDues($l, $today, $goldRate, $silverRate);
        }

        $selectedLoan = null;
        if ($loanId > 0) {
            foreach ($loans as $l) {
                if ($l['id'] == $loanId) {
                    $selectedLoan = $l;
                    break;
                }
            }
        }

        $this->render('payments.create', [
            'pageTitle'         => 'Receive Loan Payment',
            'loans'             => $loans,
            'selectedLoan'      => $selectedLoan,
            'nextReceiptNumber' => $nextReceiptNumber
        ]);
    }

    public function store(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanId      = intval($_POST['loan_id'] ?? 0);
        $totalAmount = floatval($_POST['total_amount'] ?? 0);
        $paymentMode = $_POST['payment_mode'] ?? 'Cash';
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $redirectTo  = trim($_POST['redirect_to'] ?? '');

        if ($loanId <= 0 || $totalAmount <= 0) {
            Session::setFlash('error', 'Please select a valid loan account and enter an amount received greater than ₹0.');
            $this->redirect($redirectTo ?: ($_SERVER['HTTP_REFERER'] ?? '/payments/create?loan_id=' . $loanId));
        }

        $loan = Loan::findById($loanId);
        if (!$loan) {
            Session::setFlash('error', 'Selected loan account does not exist.');
            $this->redirect($redirectTo ?: ($_SERVER['HTTP_REFERER'] ?? '/payments/create'));
        }

        $receiptNo = trim($_POST['receipt_number'] ?? '');
        if (empty($receiptNo)) {
            $receiptNo = PaymentModel::generateNextReceiptNumber();
        }

        $discount         = max(0.00, floatval($_POST['discount'] ?? 0));
        $isFullSettlement = !empty($_POST['is_full_settlement']);

        // Simple Payment Data: single amount received with discount support
        $data = [
            'receipt_number'      => $receiptNo,
            'loan_id'             => $loanId,
            'payment_date'        => $paymentDate,
            'payment_type'        => ($discount > 0 || $isFullSettlement) ? 'Full Settlement' : 'Payment Received',
            'total_amount'        => $totalAmount,
            'discount'            => $discount,
            'is_full_settlement'  => $isFullSettlement,
            'interest_component'  => $totalAmount,
            'principal_component' => 0.00,
            'penalty_component'   => 0.00,
            'payment_mode'        => $paymentMode,
            'reference_number'    => trim($_POST['reference_number'] ?? ''),
            'remarks'             => trim($_POST['remarks'] ?? '')
        ];

        try {
            $paymentId = PaymentModel::recordPayment($data);

            AuditLogger::log('Payment Collected', Session::get('admin_id'), null, [
                'payment_id'          => $paymentId,
                'receipt_number'      => $receiptNo,
                'loan_number'         => $loan['loan_number'],
                'amount'              => $totalAmount,
                'discount'            => $discount,
                'mode'                => $paymentMode
            ], 'Collected payment & updated ledger balance');

            // If loan is now Closed, audit log the automatic jewellery delivery
            $closedLoan = Loan::findById($loanId);
            if ($closedLoan && ($closedLoan['status'] === 'Closed' || ($closedLoan['delivered'] ?? '') === 'Yes')) {
                AuditLogger::log('Jewellery Delivered', Session::get('admin_id'), null, [
                    'loan_id'     => $loanId,
                    'loan_number' => $loan['loan_number'],
                    'haste'       => $closedLoan['haste'] ?? 'Customer Self',
                    'remarks'     => $closedLoan['delivery_remarks'] ?? 'Jewellery automatically delivered upon loan closure & settlement'
                ], 'Pledged jewellery marked delivered & rack slots released automatically upon loan closure');
            }

            if ($closedLoan && $closedLoan['status'] === 'Closed') {
                Session::setFlash('success', 'Loan successfully CLOSED & fully settled! Receipt#: ' . $receiptNo);
                if (!empty($redirectTo)) {
                    $this->redirect($redirectTo);
                } else {
                    $this->redirect('/receipts/closure/' . $loanId);
                }
            } else {
                Session::setFlash('success', 'Payment recorded successfully! Receipt#: ' . $receiptNo);
                if (!empty($redirectTo)) {
                    $this->redirect($redirectTo);
                } else {
                    $this->redirect('/receipts/payment/' . $paymentId);
                }
            }

        } catch (\Exception $e) {
            Session::setFlash('error', 'Error recording payment: ' . $e->getMessage());
            $this->redirect($redirectTo ?: ($_SERVER['HTTP_REFERER'] ?? '/payments/create?loan_id=' . $loanId));
        }
    }

    public function update(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $paymentId = intval($id);
        $payment = PaymentModel::findById($paymentId);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if (!$payment) {
            if ($isAjax) {
                $this->json(['success' => false, 'error' => 'Payment record not found.'], 404);
            }
            Session::setFlash('error', 'Payment record not found.');
            $this->redirect('/payments');
        }

        $totalAmount = floatval($_POST['total_amount'] ?? 0);
        $paymentMode = trim($_POST['payment_mode'] ?? 'Cash');
        $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $referenceNumber = trim($_POST['reference_number'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($totalAmount <= 0) {
            if ($isAjax) {
                $this->json(['success' => false, 'error' => 'Amount received must be greater than ₹0.'], 400);
            }
            Session::setFlash('error', 'Amount received must be greater than ₹0.');
            $this->redirect('/loans/' . $payment['loan_id']);
        }

        try {
            $oldAmount = floatval($payment['total_amount']);
            $oldRemarks = $payment['remarks'] ?? '';

            PaymentModel::updatePayment($paymentId, [
                'total_amount'     => $totalAmount,
                'payment_mode'     => $paymentMode,
                'payment_date'     => $paymentDate,
                'reference_number' => $referenceNumber,
                'remarks'          => $remarks
            ]);

            AuditLogger::log('Payment Updated', Session::get('admin_id'), null, [
                'payment_id'     => $paymentId,
                'receipt_number' => $payment['receipt_number'],
                'loan_id'        => $payment['loan_id'],
                'old_amount'     => $oldAmount,
                'new_amount'     => $totalAmount,
                'old_remarks'    => $oldRemarks,
                'new_remarks'    => $remarks,
                'mode'           => $paymentMode
            ], 'Corrected payment amount/remarks and recalculated loan ledger balance');

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Payment & ledger balance updated successfully!',
                    'payment' => [
                        'id'           => $paymentId,
                        'total_amount' => $totalAmount,
                        'payment_mode' => $paymentMode,
                        'payment_date' => $paymentDate,
                        'remarks'      => $remarks
                    ]
                ]);
            }

            Session::setFlash('success', 'Payment updated successfully! Receipt: ' . $payment['receipt_number']);
            $this->redirect('/loans/' . $payment['loan_id']);

        } catch (\Exception $e) {
            if ($isAjax) {
                $this->json(['success' => false, 'error' => 'Error updating payment: ' . $e->getMessage()], 500);
            }
            Session::setFlash('error', 'Error updating payment: ' . $e->getMessage());
            $this->redirect('/loans/' . $payment['loan_id']);
        }
    }

    public function showReceipt(string $id): void {
        AuthMiddleware::check();

        $paymentId = intval($id);
        $payment = PaymentModel::findById($paymentId);

        if (!$payment) {
            Session::setFlash('error', 'Payment receipt not found.');
            $this->redirect('/payments');
        }

        $collateralItems = Loan::getCollateralItems(intval($payment['loan_id']));

        $this->render('payments.receipt', [
            'pageTitle'       => 'Payment Receipt - ' . htmlspecialchars($payment['receipt_number']),
            'payment'         => $payment,
            'collateralItems' => $collateralItems
        ]);
    }
}
