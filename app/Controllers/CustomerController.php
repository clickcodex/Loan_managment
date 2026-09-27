<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Customer;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class CustomerController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $customers = Customer::getAll($search, $status);

        $this->render('customers.index', [
            'pageTitle' => 'Customer Directory',
            'customers' => $customers,
            'search'    => $search,
            'status'    => $status
        ]);
    }

    public function create(): void {
        AuthMiddleware::check();

        $nextId = Customer::generateNextCustomerId();
        $nextAccountNumber = Customer::generateNextAccountNumber();

        $this->render('customers.create', [
            'pageTitle'         => 'Register New Customer',
            'nextCustomerId'    => $nextId,
            'nextAccountNumber' => $nextAccountNumber
        ]);
    }

    public function store(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $fullName = trim($_POST['full_name'] ?? '');
        $mobile   = trim($_POST['mobile'] ?? '');

        if (empty($fullName) || empty($mobile)) {
            Session::setFlash('error', 'Customer Full Name and Primary Mobile Number are required.');
            $this->redirect('/customers/create');
        }

        // Handle Photo Upload (File or Live Camera)
        $photoPath = $this->handleBase64OrFileUpload('photo', 'photo_camera_base64', 'photos');

        $customerId = Customer::generateNextCustomerId();
        $accountNum = trim($_POST['account_number'] ?? '');
        if (empty($accountNum)) {
            $accountNum = Customer::generateNextAccountNumber();
        }

        $data = [
            'customer_id'       => $customerId,
            'account_number'    => $accountNum,
            'photo'             => $photoPath,
            'full_name'         => $fullName,
            'father_name'       => trim($_POST['father_name'] ?? ''),
            'mobile'            => $mobile,
            'alt_mobile'        => trim($_POST['alt_mobile'] ?? ''),
            'aadhaar'           => trim($_POST['aadhaar'] ?? ''),
            'pan'               => strtoupper(trim($_POST['pan'] ?? '')),
            'address'           => trim($_POST['address'] ?? ''),
            'village'           => trim($_POST['village'] ?? ''),
            'city'              => trim($_POST['city'] ?? ''),
            'state'             => trim($_POST['state'] ?? ''),
            'pincode'           => trim($_POST['pincode'] ?? ''),
            'guarantor_name'    => trim($_POST['guarantor_name'] ?? ''),
            'guarantor_mobile'  => trim($_POST['guarantor_mobile'] ?? ''),
            'guarantor_address' => trim($_POST['guarantor_address'] ?? ''),
            'remarks'           => trim($_POST['remarks'] ?? ''),
            'status'            => $_POST['status'] ?? 'Active'
        ];

        $newId = Customer::create($data);

        AuditLogger::log('Customer Created', Session::get('admin_id'), null, [
            'id'          => $newId,
            'customer_id' => $customerId,
            'full_name'   => $fullName,
            'mobile'      => $mobile
        ], 'Created new customer profile');

        Session::setFlash('success', 'Customer profile created successfully with ID: ' . $customerId);
        $this->redirect('/customers/' . $newId);
    }

    public function show(string $id): void {
        AuthMiddleware::check();

        $customer = Customer::findById(intval($id));
        if (!$customer) {
            Session::setFlash('error', 'Customer not found.');
            $this->redirect('/customers');
        }

        $rawLoans = Customer::getLoans($customer['id']);
        $today = new \DateTime();
        $goldRate = \App\Models\RateModel::getLatestGoldRate();
        $silverRate = \App\Models\RateModel::getLatestSilverRate();
        $loans = [];
        foreach ($rawLoans as $l) {
            $loans[] = \App\Models\DueModel::calculateLoanDues($l, $today, $goldRate, $silverRate);
        }
        $financials = Customer::getFinancialSummary($customer['id']);
        $documents = Customer::getDocuments($customer['id']);

        // Auto sync profile photo if customer photo column is empty but Customer Photo document exists
        if (empty($customer['photo']) && !empty($documents)) {
            foreach ($documents as $doc) {
                if (in_array(strtolower($doc['document_type']), ['customer photo', 'photo', 'profile photo', 'passport photo'])) {
                    $customer['photo'] = $doc['file_path'];
                    \App\Config\Database::execute("UPDATE customers SET photo = :photo WHERE id = :id", [
                        ':photo' => $doc['file_path'],
                        ':id'    => $customer['id']
                    ]);
                    break;
                }
            }
        }

        $nextReceiptNumber = \App\Models\PaymentModel::generateNextReceiptNumber();

        $activeLoans = array_values(array_filter($loans, fn($l) => ($l['status'] ?? '') !== 'Closed'));
        $closedLoans = array_values(array_filter($loans, fn($l) => ($l['status'] ?? '') === 'Closed'));

        $this->render('customers.show', [
            'pageTitle'         => 'Customer Profile - ' . htmlspecialchars($customer['full_name']),
            'customer'          => $customer,
            'loans'             => $loans,
            'activeLoans'       => $activeLoans,
            'closedLoans'       => $closedLoans,
            'financials'        => $financials,
            'documents'         => $documents,
            'nextReceiptNumber' => $nextReceiptNumber
        ]);
    }

    public function edit(string $id): void {
        AuthMiddleware::check();

        $customer = Customer::findById(intval($id));
        if (!$customer) {
            Session::setFlash('error', 'Customer not found.');
            $this->redirect('/customers');
        }

        $this->render('customers.edit', [
            'pageTitle' => 'Edit Customer - ' . htmlspecialchars($customer['full_name']),
            'customer'  => $customer
        ]);
    }

    public function update(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $custIntId = intval($id);
        $oldCustomer = Customer::findById($custIntId);
        if (!$oldCustomer) {
            Session::setFlash('error', 'Customer not found.');
            $this->redirect('/customers');
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $mobile   = trim($_POST['mobile'] ?? '');

        if (empty($fullName) || empty($mobile)) {
            Session::setFlash('error', 'Full Name and Primary Mobile are required.');
            $this->redirect('/customers/' . $custIntId . '/edit');
        }

        $photoPath = $this->handleBase64OrFileUpload('photo', 'photo_camera_base64', 'photos');

        $data = [
            'account_number'    => trim($_POST['account_number'] ?? '') ?: ($oldCustomer['account_number'] ?? null),
            'photo'             => $photoPath,
            'full_name'         => $fullName,
            'father_name'       => trim($_POST['father_name'] ?? ''),
            'mobile'            => $mobile,
            'alt_mobile'        => trim($_POST['alt_mobile'] ?? ''),
            'aadhaar'           => trim($_POST['aadhaar'] ?? ''),
            'pan'               => strtoupper(trim($_POST['pan'] ?? '')),
            'address'           => trim($_POST['address'] ?? ''),
            'village'           => trim($_POST['village'] ?? ''),
            'city'              => trim($_POST['city'] ?? ''),
            'state'             => trim($_POST['state'] ?? ''),
            'pincode'           => trim($_POST['pincode'] ?? ''),
            'guarantor_name'    => trim($_POST['guarantor_name'] ?? ''),
            'guarantor_mobile'  => trim($_POST['guarantor_mobile'] ?? ''),
            'guarantor_address' => trim($_POST['guarantor_address'] ?? ''),
            'remarks'           => trim($_POST['remarks'] ?? ''),
            'status'            => $_POST['status'] ?? 'Active'
        ];

        Customer::update($custIntId, $data);

        AuditLogger::log('Customer Updated', Session::get('admin_id'), $oldCustomer, $data, 'Updated customer details');

        Session::setFlash('success', 'Customer details updated successfully.');
        $this->redirect('/customers/' . $custIntId);
    }

    public function updateStatus(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $custIntId = intval($id);
        $status = $_POST['status'] ?? 'Active';

        if (in_array($status, ['Active', 'Closed', 'Blocked'])) {
            Customer::updateStatus($custIntId, $status);
            AuditLogger::log('Customer Status Changed', Session::get('admin_id'), null, ['status' => $status], 'Status changed to ' . $status);
            Session::setFlash('success', 'Customer status updated to ' . $status);
        }

        $this->redirect('/customers/' . $custIntId);
    }

    public function uploadDocument(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $custIntId = intval($id);
        $docType = trim($_POST['document_type'] ?? 'Other Document');

        $filePath = $this->handleBase64OrFileUpload('document_file', 'camera_photo_base64', 'documents');

        if (!$filePath) {
            Session::setFlash('error', 'Please select a document file or take a photo with your camera.');
            $this->redirect('/customers/' . $custIntId);
        }

        $origName = !empty($_FILES['document_file']['name']) 
            ? $_FILES['document_file']['name'] 
            : ($docType . ' (Camera Proof) - ' . date('d M Y H:i') . '.jpg');

        if ($filePath) {
            Customer::addDocument($custIntId, $docType, $filePath, $origName);

            // Auto sync profile photo if document type is Customer Photo or Photo
            if (in_array(strtolower($docType), ['customer photo', 'photo', 'profile photo', 'passport photo'])) {
                \App\Config\Database::execute("UPDATE customers SET photo = :photo WHERE id = :id", [
                    ':photo' => $filePath,
                    ':id'    => $custIntId
                ]);
            }

            AuditLogger::log('Customer Document Uploaded', Session::get('admin_id'), null, [
                'customer_id' => $custIntId,
                'type'        => $docType,
                'file'        => $origName
            ], 'Uploaded KYC document');

            Session::setFlash('success', 'Document / Camera Proof uploaded successfully!');
        } else {
            Session::setFlash('error', 'Failed to upload document file.');
        }

        $this->redirect('/customers/' . $custIntId);
    }

    public function deleteDocument(string $docId): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $intDocId = intval($docId);
        $doc = Customer::findDocumentById($intDocId);

        if ($doc) {
            $cid = $doc['customer_id'];
            Customer::deleteDocument($intDocId);

            // Delete physical file if exists
            $fullFilePath = __DIR__ . '/../../public/' . ltrim($doc['file_path'], '/');
            if (file_exists($fullFilePath)) {
                @unlink($fullFilePath);
            }

            AuditLogger::log('Customer Document Deleted', Session::get('admin_id'), $doc, null, 'Deleted customer document');
            Session::setFlash('success', 'Document deleted.');
            $this->redirect('/customers/' . $cid);
        } else {
            Session::setFlash('error', 'Document not found.');
            $this->redirect('/customers');
        }
    }
}
