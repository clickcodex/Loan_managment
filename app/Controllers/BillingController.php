<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\BillModel;
use App\Models\Customer;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class BillingController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $search    = trim($_GET['search'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');

        $bills = BillModel::getAll($search, $startDate, $endDate);
        $stats = BillModel::getStats();

        $this->render('billing.index', [
            'pageTitle' => 'Billing & Invoicing System',
            'bills'     => $bills,
            'stats'     => $stats,
            'search'    => $search,
            'startDate' => $startDate,
            'endDate'   => $endDate
        ]);
    }

    public function create(): void {
        AuthMiddleware::check();

        $defaultCompany = BillModel::getDefaultCompanyName();
        $nextBillNumber = BillModel::generateNextBillNumber();
        $customers      = Customer::getAll('', 'Active');

        $this->render('billing.create', [
            'pageTitle'      => 'Generate New Bill',
            'defaultCompany' => $defaultCompany,
            'nextBillNumber' => $nextBillNumber,
            'customers'      => $customers
        ]);
    }

    public function store(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $companyName     = trim($_POST['company_name'] ?? '');
        $customerName    = trim($_POST['customer_name'] ?? '');
        $customerMobile  = trim($_POST['customer_mobile'] ?? '');
        $customerAddress = trim($_POST['customer_address'] ?? '');
        $gstNumber       = trim($_POST['gst_number'] ?? '');
        $billDate        = trim($_POST['bill_date'] ?? date('Y-m-d'));
        $paymentMode     = trim($_POST['payment_mode'] ?? 'Cash');
        $notes           = trim($_POST['notes'] ?? '');
        $discountAmount  = max(0.00, floatval($_POST['discount_amount'] ?? 0));
        $taxAmount       = max(0.00, floatval($_POST['tax_amount'] ?? 0));

        if (empty($companyName) || empty($customerName)) {
            Session::setFlash('error', 'Company Name and Customer Name are required to generate a bill.');
            $this->redirect('/bills/create');
        }

        // Parse items
        $rawItems = $_POST['items'] ?? [];
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true) ?: [];
        }

        $validItems = [];
        $subtotal = 0.00;

        foreach ($rawItems as $item) {
            $prodName = trim($item['product_name'] ?? '');
            $qty      = floatval($item['quantity'] ?? 0);
            $price    = floatval($item['price'] ?? 0);

            if (!empty($prodName) && $qty > 0 && $price >= 0) {
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;

                $validItems[] = [
                    'product_name' => $prodName,
                    'quantity'     => $qty,
                    'price'        => $price,
                    'total'        => $lineTotal
                ];
            }
        }

        if (empty($validItems)) {
            Session::setFlash('error', 'Please add at least one valid product item with quantity and price.');
            $this->redirect('/bills/create');
        }

        $totalAmount = max(0.00, round($subtotal - $discountAmount + $taxAmount, 2));

        $billNumber = trim($_POST['bill_number'] ?? '');
        if (empty($billNumber)) {
            $billNumber = BillModel::generateNextBillNumber();
        }

        $billData = [
            'bill_number'      => $billNumber,
            'company_name'     => $companyName,
            'customer_name'    => $customerName,
            'customer_mobile'  => $customerMobile,
            'customer_address' => $customerAddress,
            'gst_number'       => $gstNumber,
            'bill_date'        => $billDate,
            'items'            => $validItems,
            'subtotal'         => $subtotal,
            'discount_amount'  => $discountAmount,
            'tax_amount'       => $taxAmount,
            'total_amount'     => $totalAmount,
            'payment_mode'     => $paymentMode,
            'notes'            => $notes
        ];

        try {
            $billId = BillModel::create($billData);

            AuditLogger::log('Bill Created', Session::get('admin_id'), null, [
                'bill_id'      => $billId,
                'bill_number'  => $billNumber,
                'customer'     => $customerName,
                'items_count'  => count($validItems),
                'total_amount' => $totalAmount
            ], 'Created bill in single-table multi-product billing system');

            Session::setFlash('success', 'Bill generated successfully! Bill No: ' . $billNumber);
            $this->redirect('/bills/' . $billId);

        } catch (\Exception $e) {
            Session::setFlash('error', 'Error generating bill: ' . $e->getMessage());
            $this->redirect('/bills/create');
        }
    }

    public function show(string $id): void {
        AuthMiddleware::check();

        $billId = intval($id);
        $bill = BillModel::findById($billId);

        if (!$bill) {
            Session::setFlash('error', 'Bill not found.');
            $this->redirect('/bills');
        }

        $this->render('billing.show', [
            'pageTitle' => 'Bill / Invoice - ' . htmlspecialchars($bill['bill_number']),
            'bill'      => $bill
        ]);
    }

    public function delete(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $billId = intval($id);
        $bill = BillModel::findById($billId);

        if (!$bill) {
            Session::setFlash('error', 'Bill not found.');
            $this->redirect('/bills');
        }

        try {
            BillModel::delete($billId);

            AuditLogger::log('Bill Deleted', Session::get('admin_id'), null, [
                'bill_id'     => $billId,
                'bill_number' => $bill['bill_number'],
                'customer'    => $bill['customer_name']
            ], 'Deleted bill record');

            Session::setFlash('success', 'Bill ' . $bill['bill_number'] . ' deleted successfully.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error deleting bill: ' . $e->getMessage());
        }

        $this->redirect('/bills');
    }
}
