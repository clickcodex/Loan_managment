<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Loan;
use App\Models\Customer;
use App\Models\RateModel;
use App\Models\DocumentModel;
use App\Models\PaymentModel;
use App\Config\Database;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class LoanController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $search       = trim($_GET['search'] ?? '');
        $status       = trim($_GET['status'] ?? '');
        $securityType = trim($_GET['security_type'] ?? '');

        $rawLoans = Loan::getAll($search, $status, $securityType);
        $today = new \DateTime();
        $goldRate = RateModel::getLatestGoldRate();
        $silverRate = RateModel::getLatestSilverRate();

        $loans = [];
        foreach ($rawLoans as $l) {
            $loans[] = \App\Models\DueModel::calculateLoanDues($l, $today, $goldRate, $silverRate);
        }

        $nextReceiptNumber = PaymentModel::generateNextReceiptNumber();

        $this->render('loans.index', [
            'pageTitle'         => 'Loan Accounts Directory',
            'loans'             => $loans,
            'search'            => $search,
            'status'            => $status,
            'securityType'      => $securityType,
            'nextReceiptNumber' => $nextReceiptNumber
        ]);
    }

    public function create(): void {
        AuthMiddleware::check();
        Loan::ensureSchema();

        $preSelectedCustId = intval($_GET['customer_id'] ?? 0);
        $customers = Customer::getAll('', 'Active');
        $nextLoanNumber = Loan::generateNextLoanNumber();
        $goldRate      = RateModel::getLatestGoldRate();
        $silverRate    = RateModel::getLatestSilverRate();
        $goldPresets   = RateModel::getKaratPresets('GOLD');
        $silverPresets = RateModel::getKaratPresets('SILVER');

        $this->render('loans.create', [
            'pageTitle'         => 'Issue New Loan (Unified Loan Model)',
            'customers'         => $customers,
            'preSelectedCustId' => $preSelectedCustId,
            'nextLoanNumber'    => $nextLoanNumber,
            'goldRate'          => $goldRate,
            'silverRate'        => $silverRate,
            'goldPresets'       => $goldPresets,
            'silverPresets'     => $silverPresets
        ]);
    }

    public function store(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $customerId = intval($_POST['customer_id'] ?? 0);
        $principal  = floatval($_POST['principal_amount'] ?? 0);
        $rate       = floatval($_POST['interest_rate'] ?? 0);
        $loanDate   = $_POST['loan_date'] ?? date('Y-m-d');
        $secType    = $_POST['security_type'] ?? 'Gold Secured';

        if ($customerId <= 0 || $principal <= 0) {
            Session::setFlash('error', 'Please select a valid customer and enter a principal amount greater than 0.');
            $this->redirect('/loans/create');
        }

        $customer = Customer::findById($customerId);
        if (!$customer) {
            Session::setFlash('error', 'Selected customer does not exist.');
            $this->redirect('/loans/create');
        }

        $hasGold      = str_contains($secType, 'Gold');
        $hasSilver    = str_contains($secType, 'Silver');
        $hasGuarantor = str_contains($secType, 'Guarantor');

        // Parse Gold Collateral Items
        $goldItems = [];
        if ($hasGold) {
            if (isset($_POST['gold_items']) && is_string($_POST['gold_items'])) {
                $goldItems = json_decode($_POST['gold_items'], true) ?: [];
            } elseif (isset($_POST['gold_items']) && is_array($_POST['gold_items'])) {
                $goldItems = $_POST['gold_items'];
            }
        }

        // Parse Silver Collateral Items
        $silverItems = [];
        if ($hasSilver) {
            if (isset($_POST['silver_items']) && is_string($_POST['silver_items'])) {
                $silverItems = json_decode($_POST['silver_items'], true) ?: [];
            } elseif (isset($_POST['silver_items']) && is_array($_POST['silver_items'])) {
                $silverItems = $_POST['silver_items'];
            }
        }

        // Guarantor Data
        $guarantorData = null;
        if ($hasGuarantor && !empty($_POST['guarantor_name'])) {
            $guarantorData = [
                'guarantor_name'    => trim($_POST['guarantor_name']),
                'guarantor_mobile'  => trim($_POST['guarantor_mobile'] ?? ''),
                'guarantor_address' => trim($_POST['guarantor_address'] ?? ''),
                'relationship'      => trim($_POST['guarantor_relationship'] ?? ''),
                'aadhaar'           => trim($_POST['guarantor_aadhaar'] ?? ''),
                'pan'               => strtoupper(trim($_POST['guarantor_pan'] ?? '')),
                'remarks'           => trim($_POST['guarantor_remarks'] ?? '')
            ];
        }

        $loanNumber = trim($_POST['loan_number'] ?? '');
        if (empty($loanNumber) || Loan::findByLoanNumber($loanNumber)) {
            $loanNumber = Loan::generateNextLoanNumber();
        }

        $loanData = [
            'loan_number'        => $loanNumber,
            'customer_id'        => $customerId,
            'loan_date'          => $loanDate,
            'security_type'      => $secType,
            'principal_amount'   => $principal,
            'interest_rate'      => $rate,
            'interest_cycle'     => $_POST['interest_cycle'] ?? '15 Days',
            'interest_method'    => $_POST['interest_method'] ?? 'Compound',
            'compound_frequency' => $_POST['compound_frequency'] ?? 'Yearly',
            'return_date'        => !empty($_POST['return_date']) ? $_POST['return_date'] : null,
            'interest_due_date'  => !empty($_POST['interest_due_date']) ? $_POST['interest_due_date'] : null,
            'status'             => 'Running',
            'remarks'            => trim($_POST['remarks'] ?? '')
        ];

        try {
            $loanId = Loan::createLoan($loanData, $goldItems, $silverItems, $guarantorData);

            // Check if Sanction Video Proof is uploaded
            if (!empty($_FILES['sanction_video']['name']) && $_FILES['sanction_video']['error'] === UPLOAD_ERR_OK) {
                $filePath = $this->handleFileUpload($_FILES['sanction_video'], 'videos');
                if ($filePath) {
                    DocumentModel::addDocument(
                        $customerId,
                        'Loan Sanction Video Proof (Disbursement) - Loan #' . $loanNumber,
                        $filePath,
                        $_FILES['sanction_video']['name']
                    );
                }
            }

            AuditLogger::log('Loan Created', Session::get('admin_id'), null, [
                'loan_id'          => $loanId,
                'loan_number'      => $loanNumber,
                'customer'         => $customer['full_name'],
                'principal_amount' => $principal,
                'security_type'    => $secType
            ], 'Created new loan account with video proof support');

            Session::setFlash('success', 'Loan created successfully! Loan Number: ' . $loanNumber);
            $this->redirect('/receipts/disbursement/' . $loanId . '?print=1');

        } catch (\Exception $e) {
            Session::setFlash('error', 'Error creating loan: ' . $e->getMessage());
            $this->redirect('/loans/create');
        }
    }

    public function show(string $id): void {
        AuthMiddleware::check();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $collaterals = Loan::getCollateralItems($loanIntId);
        $guarantor   = Loan::getGuarantor($loanIntId);
        $ledger      = Loan::getLedger($loanIntId);
        $payments    = Loan::getPayments($loanIntId);
        $valuation   = Loan::getLoanValuationSummary($loanIntId);
        $topups      = Loan::getTopUps($loanIntId);

        // Fetch videos associated with this customer & loan
        $allDocs = DocumentModel::getAllDocuments('all', intval($loan['customer_id']));
        $videos = array_filter($allDocs, function($d) use ($loan) {
            return $d['is_video'] || strpos(strtolower($d['document_type']), 'video') !== false || strpos(strtolower($d['document_type']), strtolower($loan['loan_number'])) !== false;
        });

        // Calculate Dues & Collateral Shortfall Alert Status
        $dueInfo = \App\Models\DueModel::calculateLoanDues($loan);

        // LTV Calculation
        $principal = floatval($loan['principal_amount']);
        $totalCollateralVal = floatval($valuation['total_market_value']);
        $ltv = $totalCollateralVal > 0 ? ($principal / $totalCollateralVal) * 100 : 0;

        // Get the latest ledger balance
        $latestBalance = !empty($ledger) ? floatval(end($ledger)['balance']) : floatval($loan['principal_amount']);
        $totalReceived = array_sum(array_column($payments, 'total_amount'));
        $nextReceiptNumber = PaymentModel::generateNextReceiptNumber();

        // Check deletion eligibility
        $deleteCheck = Loan::canDelete($loanIntId);
        $canDeleteLoan = $deleteCheck['can_delete'];
        $deleteBlockReason = $deleteCheck['reason'];

        $this->render('loans.show', [
            'pageTitle'         => 'Loan Details - ' . htmlspecialchars($loan['loan_number']),
            'loan'              => $loan,
            'collaterals'       => $collaterals,
            'guarantor'         => $guarantor,
            'ledger'            => $ledger,
            'payments'          => $payments,
            'valuation'         => $valuation,
            'dueInfo'           => $dueInfo,
            'ltv'               => $ltv,
            'videos'            => array_values($videos),
            'topups'            => $topups,
            'latestBalance'     => $latestBalance,
            'totalReceived'     => $totalReceived,
            'nextReceiptNumber' => $nextReceiptNumber,
            'canDeleteLoan'     => $canDeleteLoan,
            'deleteBlockReason' => $deleteBlockReason,
        ]);
    }


    public function uploadVideo(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $videoType = trim($_POST['video_type'] ?? 'Loan Sanction Video Proof (Disbursement)');

        if (empty($_FILES['video_file']['name']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Please select a valid video file to upload.');
            $this->redirect('/loans/' . $loanIntId);
        }

        $filePath = $this->handleFileUpload($_FILES['video_file'], 'videos');
        if ($filePath) {
            DocumentModel::addDocument(
                intval($loan['customer_id']),
                $videoType . ' - Loan #' . $loan['loan_number'],
                $filePath,
                $_FILES['video_file']['name']
            );

            AuditLogger::log('Loan Video Proof Uploaded', Session::get('admin_id'), null, [
                'loan_id'     => $loanIntId,
                'loan_number' => $loan['loan_number'],
                'video_type'  => $videoType,
                'file'        => $_FILES['video_file']['name']
            ], 'Uploaded video proof recording for loan account');

            Session::setFlash('success', 'Video proof successfully uploaded and linked to loan account!');
        } else {
            Session::setFlash('error', 'Failed to upload video proof file. Allowed formats: MP4, WebM, MOV, AVI, MKV.');
        }
        $this->redirect('/loans/' . $loanIntId);
    }

    public function topupForm(string $id): void {
        AuthMiddleware::check();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        if ($loan['status'] !== 'Running') {
            Session::setFlash('error', 'Top-up is only available for Running loan accounts.');
            $this->redirect('/loans/' . $loanIntId);
        }

        // Get the latest ledger balance
        $ledger = Loan::getLedger($loanIntId);
        $latestBalance = !empty($ledger) ? floatval(end($ledger)['balance']) : floatval($loan['principal_amount']);

        $this->render('loans.topup', [
            'pageTitle'     => 'Loan Top-Up — ' . htmlspecialchars($loan['loan_number']),
            'loan'          => $loan,
            'latestBalance' => $latestBalance,
        ]);
    }

    public function topupStore(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanIntId  = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        if ($loan['status'] !== 'Running') {
            Session::setFlash('error', 'Top-up can only be applied to Running loan accounts.');
            $this->redirect('/loans/' . $loanIntId);
        }

        $topupAmount = floatval($_POST['topup_amount'] ?? 0);
        $topupDate   = trim($_POST['topup_date'] ?? date('Y-m-d'));
        $reason      = trim($_POST['reason'] ?? '');

        if ($topupAmount <= 0) {
            Session::setFlash('error', 'Top-Up amount must be greater than ₹0.');
            $this->redirect('/loans/' . $loanIntId);
        }

        try {
            $adminId = intval(Session::get('admin_id'));
            Loan::applyTopUp($loanIntId, $topupAmount, $topupDate, $reason, $adminId);

            AuditLogger::log('Loan Top-Up Applied', $adminId, null, [
                'loan_id'      => $loanIntId,
                'loan_number'  => $loan['loan_number'],
                'customer'     => $loan['customer_name'],
                'topup_amount' => $topupAmount,
                'topup_date'   => $topupDate,
                'reason'       => $reason,
            ], 'Additional disbursement (top-up) applied to existing running loan');

            Session::setFlash('success', 'Top-Up of ₹' . number_format($topupAmount, 2) . ' applied successfully to Loan ' . $loan['loan_number'] . '!');
            $this->redirect('/loans/' . $loanIntId);

        } catch (\Exception $e) {
            Session::setFlash('error', 'Error applying top-up: ' . $e->getMessage());
            $this->redirect('/loans/' . $loanIntId);
        }
    }

    public function deliverJewellery(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $remarks = trim($_POST['delivery_remarks'] ?? '');
        $haste   = trim($_POST['haste'] ?? 'Customer Self');

        try {
            Loan::deliverJewellery($loanIntId, $remarks, $haste);

            Session::setFlash('success', 'Pledged jewellery marked as DELIVERED to ' . htmlspecialchars($haste) . ' and rack storage slots have been freed!');
            $this->redirect('/loans/' . $loanIntId);

        } catch (\Exception $e) {
            Session::setFlash('error', 'Error recording jewellery delivery: ' . $e->getMessage());
            $this->redirect('/loans/' . $loanIntId);
        }
    }

    public function edit(string $id): void {
        AuthMiddleware::check();
        Loan::ensureSchema();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $customers     = Customer::getAll('', 'Active');
        $goldRate      = RateModel::getLatestGoldRate();
        $silverRate    = RateModel::getLatestSilverRate();
        $goldPresets   = RateModel::getKaratPresets('GOLD');
        $silverPresets = RateModel::getKaratPresets('SILVER');
        $collaterals   = Loan::getCollateralItems($loanIntId);
        $guarantor     = Loan::getGuarantor($loanIntId);
        $payments      = Loan::getPayments($loanIntId);

        $goldItems = [];
        $silverItems = [];
        foreach ($collaterals as $ci) {
            $itemData = [
                'id'                           => intval($ci['id']),
                'item_name'                    => $ci['item_name'],
                'quantity'                     => intval($ci['quantity']),
                'gross_weight'                 => floatval($ci['gross_weight']),
                'stone_weight'                 => floatval($ci['stone_weight'] ?? 0),
                'net_weight'                   => floatval($ci['net_weight']),
                'purity_preset'                => $ci['purity_preset'] ?? '24K',
                'purity_percentage'            => floatval($ci['purity_percentage'] ?? 100),
                'market_value'                 => floatval($ci['market_value']),
                'manual_market_value_override' => !empty($ci['manual_market_value_override']) ? floatval($ci['manual_market_value_override']) : '',
                'loan_value'                   => floatval($ci['loan_value'] ?? 0),
                'rk_number'                    => $ci['rk_number'] ?? '',
                'remarks'                      => $ci['remarks'] ?? ''
            ];
            if (strtoupper($ci['item_type'] ?? '') === 'GOLD') {
                $goldItems[] = $itemData;
            } else {
                $silverItems[] = $itemData;
            }
        }

        // Fetch videos associated with this loan
        $allDocs = DocumentModel::getAllDocuments('all', intval($loan['customer_id']));
        $videos = array_filter($allDocs, function($d) use ($loan) {
            return $d['is_video'] || strpos(strtolower($d['document_type']), 'video') !== false || strpos(strtolower($d['document_type']), strtolower($loan['loan_number'])) !== false;
        });

        $this->render('loans.edit', [
            'pageTitle'     => 'Edit Loan - ' . htmlspecialchars($loan['loan_number']),
            'loan'          => $loan,
            'customers'     => $customers,
            'goldRate'      => $goldRate,
            'silverRate'    => $silverRate,
            'goldPresets'   => $goldPresets,
            'silverPresets' => $silverPresets,
            'goldItems'     => $goldItems,
            'silverItems'   => $silverItems,
            'guarantor'     => $guarantor,
            'videos'        => array_values($videos),
            'paymentCount'  => count($payments)
        ]);
    }

    public function update(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $customerId = intval($_POST['customer_id'] ?? $loan['customer_id']);
        $principal  = floatval($_POST['principal_amount'] ?? $loan['principal_amount']);
        $rate       = floatval($_POST['interest_rate'] ?? $loan['interest_rate']);
        $loanDate   = $_POST['loan_date'] ?? $loan['loan_date'];
        $secType    = $_POST['security_type'] ?? $loan['security_type'];

        if ($customerId <= 0 || $principal <= 0) {
            Session::setFlash('error', 'Please select a valid customer and enter a principal amount greater than 0.');
            $this->redirect('/loans/' . $loanIntId . '/edit');
        }

        $customer = Customer::findById($customerId);
        if (!$customer) {
            Session::setFlash('error', 'Selected customer does not exist.');
            $this->redirect('/loans/' . $loanIntId . '/edit');
        }

        $hasGold      = str_contains($secType, 'Gold');
        $hasSilver    = str_contains($secType, 'Silver');
        $hasGuarantor = str_contains($secType, 'Guarantor');

        // Parse Gold Collateral Items
        $goldItems = [];
        if ($hasGold) {
            if (isset($_POST['gold_items']) && is_string($_POST['gold_items'])) {
                $goldItems = json_decode($_POST['gold_items'], true) ?: [];
            } elseif (isset($_POST['gold_items']) && is_array($_POST['gold_items'])) {
                $goldItems = $_POST['gold_items'];
            }
        }

        // Parse Silver Collateral Items
        $silverItems = [];
        if ($hasSilver) {
            if (isset($_POST['silver_items']) && is_string($_POST['silver_items'])) {
                $silverItems = json_decode($_POST['silver_items'], true) ?: [];
            } elseif (isset($_POST['silver_items']) && is_array($_POST['silver_items'])) {
                $silverItems = $_POST['silver_items'];
            }
        }

        // Guarantor Data
        $guarantorData = null;
        if ($hasGuarantor && !empty($_POST['guarantor_name'])) {
            $guarantorData = [
                'guarantor_name'    => trim($_POST['guarantor_name']),
                'guarantor_mobile'  => trim($_POST['guarantor_mobile'] ?? ''),
                'guarantor_address' => trim($_POST['guarantor_address'] ?? ''),
                'relationship'      => trim($_POST['guarantor_relationship'] ?? ''),
                'aadhaar'           => trim($_POST['guarantor_aadhaar'] ?? ''),
                'pan'               => strtoupper(trim($_POST['guarantor_pan'] ?? '')),
                'remarks'           => trim($_POST['guarantor_remarks'] ?? '')
            ];
        }

        $loanData = [
            'customer_id'        => $customerId,
            'loan_date'          => $loanDate,
            'security_type'      => $secType,
            'principal_amount'   => $principal,
            'interest_rate'      => $rate,
            'interest_cycle'     => $_POST['interest_cycle'] ?? '15 Days',
            'interest_method'    => $_POST['interest_method'] ?? 'Compound',
            'compound_frequency' => $_POST['compound_frequency'] ?? 'Yearly',
            'return_date'        => !empty($_POST['return_date']) ? $_POST['return_date'] : null,
            'interest_due_date'  => !empty($_POST['interest_due_date']) ? $_POST['interest_due_date'] : null,
            'remarks'            => trim($_POST['remarks'] ?? '')
        ];

        try {
            Loan::updateLoan($loanIntId, $loanData, $goldItems, $silverItems, $guarantorData);

            // Check if Sanction Video Proof is uploaded
            if (!empty($_FILES['sanction_video']['name']) && $_FILES['sanction_video']['error'] === UPLOAD_ERR_OK) {
                $filePath = $this->handleFileUpload($_FILES['sanction_video'], 'videos');
                if ($filePath) {
                    DocumentModel::addDocument(
                        $customerId,
                        'Loan Sanction Video Proof (Disbursement) - Loan #' . $loan['loan_number'],
                        $filePath,
                        $_FILES['sanction_video']['name']
                    );
                }
            }

            Session::setFlash('success', 'Loan ' . $loan['loan_number'] . ' updated successfully!');
            $this->redirect('/loans/' . $loanIntId);

        } catch (\Exception $e) {
            Session::setFlash('error', 'Error updating loan: ' . $e->getMessage());
            $this->redirect('/loans/' . $loanIntId . '/edit');
        }
    }

    public function delete(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanIntId = intval($id);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        try {
            Loan::deleteLoan($loanIntId);
            Session::setFlash('success', 'Loan ' . $loan['loan_number'] . ' and all related collateral/ledger records have been deleted successfully.');
            $this->redirect('/loans');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Cannot delete loan: ' . $e->getMessage());
            $this->redirect('/loans/' . $loanIntId);
        }
    }

    public function updateLedgerEntry(string $loanId, string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanIntId   = intval($loanId);
        $ledgerIntId = intval($id);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        try {
            // Verify and lock Loan Issued transaction type
            $existingLedger = \App\Config\Database::fetchOne("SELECT entry_type FROM loan_ledger WHERE id = :id LIMIT 1", [':id' => $ledgerIntId]);
            $requestedType = trim($_POST['entry_type'] ?? '');

            if ($existingLedger && $existingLedger['entry_type'] === 'Loan Issued') {
                if ($requestedType !== '' && $requestedType !== 'Loan Issued') {
                    throw new \Exception("The transaction type for 'Loan Issued' is fixed and cannot be modified.");
                }
                $requestedType = 'Loan Issued';
            } elseif ($existingLedger && $existingLedger['entry_type'] !== 'Loan Issued') {
                if ($requestedType === 'Loan Issued') {
                    throw new \Exception("Cannot change transaction type to 'Loan Issued'.");
                }
            }

            $data = [
                'entry_date'       => trim($_POST['entry_date'] ?? date('Y-m-d')),
                'entry_type'       => $requestedType ?: ($existingLedger['entry_type'] ?? ''),
                'description'      => trim($_POST['description'] ?? ''),
                'payment_mode'     => trim($_POST['payment_mode'] ?? 'Cash'),
                'reference_number' => trim($_POST['reference_number'] ?? ''),
                'remarks'          => trim($_POST['remarks'] ?? $_POST['payment_remarks'] ?? ''),
            ];

            if (isset($_POST['debit']) && $_POST['debit'] !== '') {
                $data['debit'] = floatval($_POST['debit']);
            }
            if (isset($_POST['credit']) && $_POST['credit'] !== '') {
                $data['credit'] = floatval($_POST['credit']);
            }

            Loan::updateLedgerEntry($ledgerIntId, $data);

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Ledger entry updated & running balances recalculated successfully!'
                ]);
            }

            Session::setFlash('success', 'Ledger entry updated & running balances recalculated successfully!');
            $this->redirect('/loans/' . $loanIntId);

        } catch (\Exception $e) {
            if ($isAjax) {
                $this->json(['success' => false, 'error' => 'Error updating ledger entry: ' . $e->getMessage()], 400);
            }
            Session::setFlash('error', 'Error updating ledger entry: ' . $e->getMessage());
            $this->redirect('/loans/' . $loanIntId);
        }
    }

    /**
     * Master All Loans Ledger - Consolidated Ledger of all loans
     */
    public function allLedger(): void {
        AuthMiddleware::check();

        $preset     = trim($_GET['preset'] ?? '');
        $startDate  = trim($_GET['start_date'] ?? '');
        $endDate    = trim($_GET['end_date'] ?? '');
        $flowType   = trim($_GET['flow_type'] ?? 'all');
        $entryType  = trim($_GET['entry_type'] ?? '');
        $paymentMode= trim($_GET['payment_mode'] ?? '');
        $search     = trim($_GET['search'] ?? '');

        // Apply quick preset shortcuts
        $today = date('Y-m-d');
        if ($preset === 'today') {
            $startDate = $today;
            $endDate   = $today;
        } elseif ($preset === 'yesterday') {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $startDate = $yesterday;
            $endDate   = $yesterday;
        } elseif ($preset === 'this_week') {
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate   = $today;
        } elseif ($preset === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate   = date('Y-m-t');
        } elseif ($preset === 'last_month') {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate   = date('Y-m-t', strtotime('last day of last month'));
        } elseif ($preset === 'this_year') {
            $startDate = date('Y-01-01');
            $endDate   = date('Y-12-31');
        } elseif ($preset === 'all') {
            $startDate = '';
            $endDate   = '';
        }

        $filters = [
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'flow_type'    => $flowType,
            'entry_type'   => $entryType,
            'payment_mode' => $paymentMode,
            'search'       => $search
        ];

        $entries = Loan::getAllLedgerEntries($filters);
        $summary = Loan::getLedgerSummary($filters);

        $this->render('loans.ledger', [
            'pageTitle'    => 'All Loans Ledger - Consolidated Transaction Register',
            'entries'      => $entries,
            'summary'      => $summary,
            'preset'       => $preset,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'flowType'     => $flowType,
            'entryType'    => $entryType,
            'paymentMode'  => $paymentMode,
            'search'       => $search
        ]);
    }

    /**
     * Show official Loan Sanction / Disbursement Receipt & Pledge Voucher
     */
    public function showSanctionReceipt(string $id): void {
        AuthMiddleware::check();

        $loanId = intval($id);
        $loan = Loan::getSanctionDetails($loanId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $collateralItems = Loan::getCollateralItems($loanId);
        $guarantor       = Loan::getGuarantor($loanId);

        $totalGross = 0;
        $totalNet = 0;
        $totalValuation = 0;
        foreach ($collateralItems as $ci) {
            $totalGross += floatval($ci['gross_weight'] ?? 0);
            $totalNet += floatval($ci['net_weight'] ?? 0);
            $totalValuation += floatval($ci['market_value'] ?? 0);
        }

        $principalAmount = floatval($loan['principal_amount'] ?? 0);
        $ltv = $totalValuation > 0 ? ($principalAmount / $totalValuation) * 100 : 0;

        $this->render('loans.sanction_receipt', [
            'pageTitle'       => 'Loan Sanction Receipt - ' . htmlspecialchars($loan['loan_number']),
            'loan'            => $loan,
            'collateralItems' => $collateralItems,
            'guarantor'       => $guarantor,
            'totalGross'      => $totalGross,
            'totalNet'        => $totalNet,
            'totalValuation'  => $totalValuation,
            'ltv'             => $ltv
        ]);
    }

    /**
     * Show official Loan Disbursement Slip (RK DETAILS Format)
     */
    public function showDisbursementReceipt(string $id): void {
        AuthMiddleware::check();

        $loanId = intval($id);
        $loan = Loan::getSanctionDetails($loanId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $collateralItems = Loan::getCollateralItems($loanId);
        $guarantor       = Loan::getGuarantor($loanId);

        $this->render('loans.disbursement_receipt', [
            'pageTitle'       => 'Loan Disbursement Slip - ' . htmlspecialchars($loan['loan_number']),
            'loan'            => $loan,
            'collateralItems' => $collateralItems,
            'guarantor'       => $guarantor
        ]);
    }

    /**
     * Show official Loan Closure Receipt & No-Due Certificate
     */
    public function showClosureReceipt(string $id): void {
        AuthMiddleware::check();

        $loanId = intval($id);
        $loan = Loan::getSanctionDetails($loanId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $collateralItems = Loan::getCollateralItems($loanId);
        $guarantor       = Loan::getGuarantor($loanId);
        $payments        = Loan::getPayments($loanId);
        $ledger          = Loan::getLedger($loanId);

        $this->render('loans.closure_receipt', [
            'pageTitle'       => 'Loan Closure Receipt - ' . htmlspecialchars($loan['loan_number']),
            'loan'            => $loan,
            'collateralItems' => $collateralItems,
            'guarantor'       => $guarantor,
            'payments'        => $payments,
            'ledger'          => $ledger
        ]);
    }
}
