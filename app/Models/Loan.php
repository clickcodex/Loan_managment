<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\RackManager;
use App\Helpers\AuditLogger;
use App\Helpers\Session;
use PDO;

class Loan {

    public static function getAll(string $search = '', string $status = '', string $securityType = ''): array {
        $sql = "SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile, c.customer_id as cust_code,
                       COALESCE(SUM(ci.market_value), 0) as total_collateral_value,
                       COUNT(DISTINCT ci.id) as item_count,
                       GROUP_CONCAT(DISTINCT CONCAT(ci.item_name, ' (', ROUND(ci.net_weight, 2), 'g)') SEPARATOR ', ') as collateral_items_summary,
                       GROUP_CONCAT(DISTINCT ci.item_name SEPARATOR ', ') as collateral_names,
                       COALESCE(SUM(p.principal_component), 0) as paid_principal
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                LEFT JOIN collateral_items ci ON l.id = ci.loan_id
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE 1=1";

        $params = [];

        if (!empty($status)) {
            if ($status === 'Active' || $status === 'Running') {
                $sql .= " AND l.status IN ('Active', 'Running')";
            } else {
                $sql .= " AND l.status = :status";
                $params[':status'] = $status;
            }
        }

        if (!empty($securityType)) {
            $sql .= " AND l.security_type = :stype";
            $params[':stype'] = $securityType;
        }

        if (!empty($search)) {
            $sql .= " AND (l.loan_number LIKE :l1 
                        OR c.full_name LIKE :l2 
                        OR c.mobile LIKE :l3 
                        OR c.customer_id LIKE :l4)";
            $searchTerm = '%' . $search . '%';
            $params[':l1'] = $searchTerm;
            $params[':l2'] = $searchTerm;
            $params[':l3'] = $searchTerm;
            $params[':l4'] = $searchTerm;
        }

        $sql .= " GROUP BY l.id ORDER BY l.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("
            SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile, 
                   c.customer_id as cust_code, c.aadhaar, c.address as customer_address,
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT(ci.item_name, ' (', ROUND(ci.net_weight, 2), 'g)') SEPARATOR ', ') FROM collateral_items ci WHERE ci.loan_id = l.id) as collateral_items_summary,
                   (SELECT GROUP_CONCAT(DISTINCT ci.item_name SEPARATOR ', ') FROM collateral_items ci WHERE ci.loan_id = l.id) as collateral_names,
                   (SELECT balance FROM loan_ledger ll WHERE ll.loan_id = l.id ORDER BY ll.id DESC LIMIT 1) as running_balance
            FROM loans l
            JOIN customers c ON l.customer_id = c.id
            WHERE l.id = :id
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function getSanctionDetails(int $id): ?array {
        return Database::fetchOne("
            SELECT l.*, c.id as cust_id, c.customer_id as cust_code, c.account_number as customer_account_number,
                   c.full_name as customer_name, c.father_name, c.mobile as customer_mobile, c.alt_mobile,
                   c.aadhaar, c.pan, c.address as customer_address, c.village, c.city, c.state, c.pincode
            FROM loans l
            JOIN customers c ON l.customer_id = c.id
            WHERE l.id = :id
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function findByLoanNumber(string $loanNumber): ?array {
        return Database::fetchOne("SELECT * FROM loans WHERE loan_number = :ln LIMIT 1", [':ln' => trim($loanNumber)]);
    }

    public static function generateNextLoanNumber(): string {
        $setting = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'loan_number_format'");
        $format = $setting['setting_value'] ?? 'LMS-{YEAR}-{0000}';

        $row = Database::fetchOne("SELECT MAX(id) as max_id FROM loans");
        $nextId = (intval($row['max_id'] ?? 0)) + 1;
        $year = date('Y');

        do {
            if (strpos($format, '{YEAR}') !== false) {
                $loanNum = str_replace('{YEAR}', $year, $format);
                $loanNum = str_replace('{0000}', sprintf('%04d', $nextId), $loanNum);
            } else {
                $loanNum = sprintf("LMS-%s-%04d", $year, $nextId);
            }

            $exists = self::findByLoanNumber($loanNum);
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $loanNum;
    }

    public static function ensureSchema(): void {
        static $checked = false;
        if ($checked) return;
        $db = Database::connect();
        if ($db->inTransaction()) return;
        $checked = true;

        try {
            $col = $db->query("SHOW COLUMNS FROM `loans` LIKE 'delivered'")->fetch();
            if (!$col) {
                $db->exec("ALTER TABLE `loans` 
                    ADD COLUMN `delivered` varchar(20) NOT NULL DEFAULT 'No',
                    ADD COLUMN `delivery_date` date DEFAULT NULL,
                    ADD COLUMN `delivery_remarks` text DEFAULT NULL,
                    ADD COLUMN `haste` varchar(100) NOT NULL DEFAULT 'Customer Self',
                    ADD COLUMN `old_receipt_taken` varchar(50) NOT NULL DEFAULT 'Yes',
                    ADD COLUMN `bag_no` varchar(50) DEFAULT NULL");
            }

            $statusCol = $db->query("SHOW COLUMNS FROM `loans` LIKE 'status'")->fetch();
            if ($statusCol && str_starts_with(strtolower($statusCol['Type']), 'enum')) {
                $db->exec("ALTER TABLE `loans` MODIFY COLUMN `status` varchar(50) NOT NULL DEFAULT 'Running'");
            }

            $payCol = $db->query("SHOW COLUMNS FROM `payments` LIKE 'discount'")->fetch();
            if (!$payCol) {
                $db->exec("ALTER TABLE `payments` ADD COLUMN `discount` decimal(12,2) NOT NULL DEFAULT 0.00");
            }
        } catch (\Throwable $e) {
            error_log("Loan ensureSchema notice: " . $e->getMessage());
        }
    }

    public static function createLoan(array $loanData, array $goldItems = [], array $silverItems = [], ?array $guarantorData = null): int {
        self::ensureSchema();
        $db = Database::connect();
        $db->beginTransaction();

        $secType = $loanData['security_type'] ?? '';
        if (!str_contains($secType, 'Gold')) {
            $goldItems = [];
        }
        if (!str_contains($secType, 'Silver')) {
            $silverItems = [];
        }
        if (!str_contains($secType, 'Guarantor')) {
            $guarantorData = null;
        }

        try {
            // Calculate Initial Total Payable Amount (Principal + 1st Cycle Interest)
            $principal = floatval($loanData['principal_amount']);
            $rate = floatval($loanData['interest_rate']);
            $cycleStr = $loanData['interest_cycle'] ?? '15 Days';
            $isHalfMonthly = !str_contains(strtolower($cycleStr), '30') && strtolower($cycleStr) !== 'monthly';

            $firstCycleInterest = round($principal * (($rate / ($isHalfMonthly ? 2.0 : 1.0)) / 100.0), 2);
            $totalPayableAmount = floatval($loanData['total_payable_amount'] ?? ($principal + $firstCycleInterest));

            // 1. Insert Loan Master Record
            $sql = "INSERT INTO loans (
                        loan_number, customer_id, loan_date, security_type, principal_amount, total_payable_amount, remaining_balance,
                        interest_rate, interest_cycle, interest_method, compound_frequency, return_date,
                        interest_due_date, status, delivered, delivery_remarks, haste, remarks, created_at
                    ) VALUES (
                        :loan_number, :customer_id, :loan_date, :security_type, :principal_amount, :total_payable_amount, :remaining_balance,
                        :interest_rate, :interest_cycle, :interest_method, :compound_frequency, :return_date,
                        :interest_due_date, :status, 'No', NULL, :haste, :remarks, NOW()
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':loan_number'          => $loanData['loan_number'],
                ':customer_id'         => $loanData['customer_id'],
                ':loan_date'            => $loanData['loan_date'],
                ':security_type'        => $loanData['security_type'],
                ':principal_amount'     => $principal,
                ':total_payable_amount' => $totalPayableAmount,
                ':remaining_balance'    => $totalPayableAmount,
                ':interest_rate'        => $rate,
                ':interest_cycle'       => $loanData['interest_cycle'] ?? '15 Days',
                ':interest_method'      => $loanData['interest_method'] ?? 'Simple',
                ':compound_frequency'   => $loanData['compound_frequency'] ?? 'Monthly',
                ':return_date'          => !empty($loanData['return_date']) ? $loanData['return_date'] : null,
                ':interest_due_date'    => !empty($loanData['interest_due_date']) ? $loanData['interest_due_date'] : null,
                ':status'               => $loanData['status'] ?? 'Active',
                ':haste'                => $loanData['haste'] ?? 'Customer Self',
                ':remarks'              => !empty($loanData['remarks']) ? $loanData['remarks'] : null
            ]);

            $loanId = intval($db->lastInsertId());

            // 2. Insert Gold Collateral Items
            if (!empty($goldItems)) {
                $itemSql = "INSERT INTO collateral_items (
                                loan_id, item_type, item_name, quantity, gross_weight,
                                stone_weight, net_weight, purity_preset, purity_percentage,
                                market_value, manual_market_value_override, loan_value,
                                rk_number, remarks, created_at
                            ) VALUES (
                                :loan_id, 'GOLD', :name, :qty, :gross, :stone, :net,
                                :preset, :purity, :market_val, :override_val, :loan_val,
                                :rk, :remarks, NOW()
                            )";
                $itemStmt = $db->prepare($itemSql);

                foreach ($goldItems as $g) {
                    if (empty($g['item_name'])) continue;
                    $netWt = floatval($g['net_weight'] ?? 0);
                    if ($netWt <= 0 && !empty($g['gross_weight'])) {
                        $netWt = floatval($g['gross_weight']) - floatval($g['stone_weight'] ?? 0);
                    }
                    $purityPct = floatval($g['purity_percentage'] ?? 100.00);
                    $mVal = floatval($g['market_value'] ?? 0);
                    if ($mVal <= 0 && $netWt > 0) {
                        $goldRate = RateModel::getLatestGoldRate();
                        $base100 = floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2));
                        $mVal = round($netWt * ($purityPct / 100.0) * ($base100 / 10.0), 2);
                    }

                    $rkNum = !empty($g['rk_number']) ? trim($g['rk_number']) : RackManager::getNextAvailableSlot();
                    $itemStmt->execute([
                        ':loan_id'      => $loanId,
                        ':name'         => $g['item_name'],
                        ':qty'          => intval($g['quantity'] ?? 1),
                        ':gross'        => floatval($g['gross_weight'] ?? 0),
                        ':stone'        => floatval($g['stone_weight'] ?? 0),
                        ':net'          => $netWt,
                        ':preset'       => $g['purity_preset'] ?? '24K',
                        ':purity'       => $purityPct,
                        ':market_val'   => $mVal,
                        ':override_val' => !empty($g['manual_market_value_override']) ? floatval($g['manual_market_value_override']) : null,
                        ':loan_val'     => floatval($g['loan_value'] ?? 0),
                        ':rk'           => $rkNum,
                        ':remarks'      => $g['remarks'] ?? null
                    ]);
                }
            }

            // 3. Insert Silver Collateral Items
            if (!empty($silverItems)) {
                $itemSql = "INSERT INTO collateral_items (
                                loan_id, item_type, item_name, quantity, gross_weight,
                                stone_weight, net_weight, purity_preset, purity_percentage,
                                market_value, manual_market_value_override, loan_value,
                                rk_number, remarks, created_at
                            ) VALUES (
                                :loan_id, 'SILVER', :name, :qty, :gross, 0.000, :net,
                                :preset, :purity, :market_val, :override_val, :loan_val,
                                :rk, :remarks, NOW()
                            )";
                $itemStmt = $db->prepare($itemSql);

                foreach ($silverItems as $s) {
                    if (empty($s['item_name'])) continue;
                    $netWt = floatval($s['net_weight'] ?? 0);
                    if ($netWt <= 0 && !empty($s['gross_weight'])) {
                        $netWt = floatval($s['gross_weight']);
                    }
                    $purityPct = floatval($s['purity_percentage'] ?? 100.00);
                    $mVal = floatval($s['market_value'] ?? 0);
                    if ($mVal <= 0 && $netWt > 0) {
                        $silverRate = RateModel::getLatestSilverRate();
                        $base100 = floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 90) / 0.999, 2));
                        $mVal = round($netWt * ($purityPct / 100.0) * $base100, 2);
                    }

                    $rkNum = !empty($s['rk_number']) ? trim($s['rk_number']) : RackManager::getNextAvailableSlot();
                    $itemStmt->execute([
                        ':loan_id'      => $loanId,
                        ':name'         => $s['item_name'],
                        ':qty'          => intval($s['quantity'] ?? 1),
                        ':gross'        => floatval($s['gross_weight'] ?? 0),
                        ':net'          => $netWt,
                        ':preset'       => $s['purity_preset'] ?? '100% (Pure)',
                        ':purity'       => $purityPct,
                        ':market_val'   => $mVal,
                        ':override_val' => !empty($s['manual_market_value_override']) ? floatval($s['manual_market_value_override']) : null,
                        ':loan_val'     => floatval($s['loan_value'] ?? 0),
                        ':rk'           => $rkNum,
                        ':remarks'      => $s['remarks'] ?? null
                    ]);
                }
            }

            // 4. Insert Guarantor Record (if provided)
            if (!empty($guarantorData) && !empty($guarantorData['guarantor_name'])) {
                $gSql = "INSERT INTO loan_guarantors (
                            loan_id, guarantor_name, guarantor_mobile, guarantor_address,
                            relationship, photo, aadhaar, pan, remarks, created_at
                         ) VALUES (
                            :loan_id, :gname, :gmobile, :gaddr, :rel, :photo, :aadhaar, :pan, :remarks, NOW()
                         )";
                $gStmt = $db->prepare($gSql);
                $gStmt->execute([
                    ':loan_id' => $loanId,
                    ':gname'   => $guarantorData['guarantor_name'],
                    ':gmobile' => $guarantorData['guarantor_mobile'] ?? null,
                    ':gaddr'   => $guarantorData['guarantor_address'] ?? null,
                    ':rel'     => $guarantorData['relationship'] ?? null,
                    ':photo'   => $guarantorData['photo'] ?? null,
                    ':aadhaar' => $guarantorData['aadhaar'] ?? null,
                    ':pan'     => $guarantorData['pan'] ?? null,
                    ':remarks' => $guarantorData['remarks'] ?? null
                ]);
            }

            // 5. Create Initial Ledger Entry: Loan Issued with 1st Cycle Interest
            $principal = floatval($loanData['principal_amount']);
            $rate = floatval($loanData['interest_rate']);
            $cycleStr = $loanData['interest_cycle'] ?? '15 Days';
            $isHalfMonthly = !str_contains(strtolower($cycleStr), '30') && strtolower($cycleStr) !== 'monthly';

            $firstCycleInterest = round($principal * (($rate / ($isHalfMonthly ? 2.0 : 1.0)) / 100.0), 2);
            $initialTotalBalance = round($principal + $firstCycleInterest, 2);
            $loanRemarks = !empty($loanData['remarks']) ? trim($loanData['remarks']) : null;

            $ledgerSql = "INSERT INTO loan_ledger (
                            loan_id, entry_date, entry_type, description, remarks, debit, credit, balance, created_at
                          ) VALUES (
                            :loan_id, :entry_date, 'Loan Issued', :description, :remarks, :debit, 0.00, :balance, NOW()
                          )";
            $ledgerStmt = $db->prepare($ledgerSql);
            $ledgerStmt->execute([
                ':loan_id'     => $loanId,
                ':entry_date'  => $loanData['loan_date'],
                ':description' => 'Initial loan disbursement (Principal: ₹' . number_format($principal, 2) . ' + 1st Cycle Int: ₹' . number_format($firstCycleInterest, 2) . ')',
                ':remarks'     => $loanRemarks,
                ':debit'       => $initialTotalBalance,
                ':balance'     => $initialTotalBalance
            ]);

            if ($db->inTransaction()) {
                $db->commit();
            }
            return $loanId;

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to create loan: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if a loan is eligible to be deleted.
     * Returns an array with boolean 'can_delete' and human-readable 'reason'.
     */
    public static function canDelete(int $loanId): array {
        $paymentCount = intval(Database::fetchOne("SELECT COUNT(*) as c FROM payments WHERE loan_id = :id", [':id' => $loanId])['c'] ?? 0);
        
        $repaymentLedgerCount = intval(Database::fetchOne("
            SELECT COUNT(*) as c FROM loan_ledger 
            WHERE loan_id = :id 
              AND (credit > 0 OR payment_id IS NOT NULL OR entry_type IN ('Payment Received', 'Discount / Concession', 'Full Settlement', 'Principal Paid', 'Interest Paid', 'Partial Principal', 'Closing Entry'))
        ", [':id' => $loanId])['c'] ?? 0);

        if ($paymentCount > 0 || $repaymentLedgerCount > 0) {
            $count = max($paymentCount, $repaymentLedgerCount);
            return [
                'can_delete'      => false,
                'reason'          => "This loan has {$count} recorded repayment/reviewed transaction(s). Loans with repayment history cannot be deleted.",
                'payment_count'   => $paymentCount,
                'repayment_count' => $repaymentLedgerCount
            ];
        }

        return [
            'can_delete'      => true,
            'reason'          => '',
            'payment_count'   => 0,
            'repayment_count' => 0
        ];
    }

    /**
     * Delete a loan account and all associated child records (collateral, rack slots, ledger, guarantors).
     * Strictly blocked if any repayments exist.
     */
    public static function deleteLoan(int $loanId): bool {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Loan account not found.");
            }

            // Strict backend validation
            $check = self::canDelete($loanId);
            if (!$check['can_delete']) {
                throw new \Exception($check['reason']);
            }

            // 1. Delete collateral item photos and clean up uploaded files
            $items = Database::fetchAll("SELECT id FROM collateral_items WHERE loan_id = :lid", [':lid' => $loanId]);
            foreach ($items as $it) {
                $photos = Database::fetchAll("SELECT file_path FROM collateral_item_photos WHERE item_id = :item_id", [':item_id' => $it['id']]);
                foreach ($photos as $ph) {
                    if (!empty($ph['file_path'])) {
                        $fpath = __DIR__ . '/../../public/' . ltrim($ph['file_path'], '/');
                        if (file_exists($fpath)) {
                            @unlink($fpath);
                        }
                    }
                }
                $db->prepare("DELETE FROM collateral_item_photos WHERE item_id = :item_id")->execute([':item_id' => $it['id']]);
            }

            // 2. Delete collateral items (this automatically frees rack slots)
            $db->prepare("DELETE FROM collateral_items WHERE loan_id = :lid")->execute([':lid' => $loanId]);

            // 3. Delete loan guarantors
            $db->prepare("DELETE FROM loan_guarantors WHERE loan_id = :lid")->execute([':lid' => $loanId]);

            // 4. Delete interest history
            $db->prepare("DELETE FROM interest_history WHERE loan_id = :lid")->execute([':lid' => $loanId]);

            // 5. Delete loan top-ups
            $db->prepare("DELETE FROM loan_topups WHERE loan_id = :lid")->execute([':lid' => $loanId]);

            // 6. Delete loan ledger entries
            $db->prepare("DELETE FROM loan_ledger WHERE loan_id = :lid")->execute([':lid' => $loanId]);

            // 7. Delete customer document if video proof linked
            $db->prepare("DELETE FROM customer_documents WHERE customer_id = :cid AND document_type LIKE :dtype")
               ->execute([
                   ':cid'   => $loan['customer_id'],
                   ':dtype' => '%#' . $loan['loan_number'] . '%'
               ]);

            // 8. Delete the loan record itself
            $db->prepare("DELETE FROM loans WHERE id = :id")->execute([':id' => $loanId]);

            // 9. Audit log the deletion
            AuditLogger::log('Loan Deleted', Session::get('admin_id'), null, [
                'loan_id'          => $loanId,
                'loan_number'      => $loan['loan_number'],
                'customer_id'      => $loan['customer_id'],
                'principal_amount' => $loan['principal_amount']
            ], 'Deleted loan account with no repayment history');

            $db->commit();
            return true;

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to delete loan: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing loan, its collateral items, guarantor, initial disbursement ledger entry,
     * and recalculate the running balances.
     */
    public static function updateLoan(int $loanId, array $loanData, array $goldItems = [], array $silverItems = [], ?array $guarantorData = null): bool {
        self::ensureSchema();
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Loan account not found.");
            }

            $secType = $loanData['security_type'] ?? $loan['security_type'];
            if (!str_contains($secType, 'Gold')) {
                $goldItems = [];
            }
            if (!str_contains($secType, 'Silver')) {
                $silverItems = [];
            }
            if (!str_contains($secType, 'Guarantor')) {
                $guarantorData = null;
            }

            $principal = floatval($loanData['principal_amount'] ?? $loan['principal_amount']);
            if ($principal <= 0) {
                throw new \Exception("Principal amount must be greater than zero.");
            }

            $rate = floatval($loanData['interest_rate'] ?? $loan['interest_rate']);
            $cycleStr = $loanData['interest_cycle'] ?? $loan['interest_cycle'] ?? '15 Days';
            $isHalfMonthly = !str_contains(strtolower($cycleStr), '30') && strtolower($cycleStr) !== 'monthly';

            $firstCycleInterest = round($principal * (($rate / ($isHalfMonthly ? 2.0 : 1.0)) / 100.0), 2);
            $initialTotalDisbursement = round($principal + $firstCycleInterest, 2);

            // 1. Update loans master record
            $sql = "UPDATE loans SET
                        customer_id         = :customer_id,
                        loan_date           = :loan_date,
                        security_type       = :security_type,
                        principal_amount    = :principal_amount,
                        interest_rate       = :interest_rate,
                        interest_cycle      = :interest_cycle,
                        interest_method     = :interest_method,
                        compound_frequency  = :compound_frequency,
                        return_date         = :return_date,
                        interest_due_date   = :interest_due_date,
                        remarks             = :remarks,
                        total_payable_amount= :total_payable_amount
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':customer_id'         => intval($loanData['customer_id'] ?? $loan['customer_id']),
                ':loan_date'           => $loanData['loan_date'] ?? $loan['loan_date'],
                ':security_type'       => $secType,
                ':principal_amount'    => $principal,
                ':interest_rate'       => $rate,
                ':interest_cycle'      => $cycleStr,
                ':interest_method'     => $loanData['interest_method'] ?? $loan['interest_method'] ?? 'Compound',
                ':compound_frequency'  => $loanData['compound_frequency'] ?? $loan['compound_frequency'] ?? 'Yearly',
                ':return_date'         => !empty($loanData['return_date']) ? $loanData['return_date'] : null,
                ':interest_due_date'   => !empty($loanData['interest_due_date']) ? $loanData['interest_due_date'] : null,
                ':remarks'             => !empty($loanData['remarks']) ? trim($loanData['remarks']) : null,
                ':total_payable_amount'=> $initialTotalDisbursement,
                ':id'                  => $loanId
            ]);

            // 2. Synchronize Collateral Items
            $existingCollateral = Database::fetchAll("SELECT id, item_type FROM collateral_items WHERE loan_id = :lid", [':lid' => $loanId]);
            $existingIds = array_column($existingCollateral, 'id');
            $keptIds = [];

            // Process Gold Items
            if (!empty($goldItems)) {
                $goldInsertSql = "INSERT INTO collateral_items (
                                    loan_id, item_type, item_name, quantity, gross_weight,
                                    stone_weight, net_weight, purity_preset, purity_percentage,
                                    market_value, manual_market_value_override, loan_value,
                                    rk_number, remarks, created_at
                                ) VALUES (
                                    :loan_id, 'GOLD', :name, :qty, :gross, :stone, :net,
                                    :preset, :purity, :market_val, :override_val, :loan_val,
                                    :rk, :remarks, NOW()
                                )";
                $goldUpdateSql = "UPDATE collateral_items SET
                                    item_name = :name, quantity = :qty, gross_weight = :gross,
                                    stone_weight = :stone, net_weight = :net, purity_preset = :preset,
                                    purity_percentage = :purity, market_value = :market_val,
                                    manual_market_value_override = :override_val, loan_value = :loan_val,
                                    rk_number = :rk, remarks = :remarks, updated_at = NOW()
                                  WHERE id = :id AND loan_id = :loan_id";
                $gInsStmt = $db->prepare($goldInsertSql);
                $gUpdStmt = $db->prepare($goldUpdateSql);

                foreach ($goldItems as $g) {
                    if (empty($g['item_name'])) continue;
                    $netWt = floatval($g['net_weight'] ?? 0);
                    if ($netWt <= 0 && !empty($g['gross_weight'])) {
                        $netWt = floatval($g['gross_weight']) - floatval($g['stone_weight'] ?? 0);
                    }
                    $purityPct = floatval($g['purity_percentage'] ?? 100.00);
                    $mVal = floatval($g['market_value'] ?? 0);
                    if ($mVal <= 0 && $netWt > 0) {
                        $goldRate = RateModel::getLatestGoldRate();
                        $base100 = floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2));
                        $mVal = round($netWt * ($purityPct / 100.0) * ($base100 / 10.0), 2);
                    }
                    $rkNum = !empty($g['rk_number']) ? trim($g['rk_number']) : RackManager::getNextAvailableSlot();

                    if (!empty($g['id']) && in_array(intval($g['id']), $existingIds)) {
                        $itemId = intval($g['id']);
                        $keptIds[] = $itemId;
                        $gUpdStmt->execute([
                            ':name'         => $g['item_name'],
                            ':qty'          => intval($g['quantity'] ?? 1),
                            ':gross'        => floatval($g['gross_weight'] ?? 0),
                            ':stone'        => floatval($g['stone_weight'] ?? 0),
                            ':net'          => $netWt,
                            ':preset'       => $g['purity_preset'] ?? '24K',
                            ':purity'       => $purityPct,
                            ':market_val'   => $mVal,
                            ':override_val' => !empty($g['manual_market_value_override']) ? floatval($g['manual_market_value_override']) : null,
                            ':loan_val'     => floatval($g['loan_value'] ?? 0),
                            ':rk'           => $rkNum,
                            ':remarks'      => $g['remarks'] ?? null,
                            ':id'           => $itemId,
                            ':loan_id'      => $loanId
                        ]);
                    } else {
                        $gInsStmt->execute([
                            ':loan_id'      => $loanId,
                            ':name'         => $g['item_name'],
                            ':qty'          => intval($g['quantity'] ?? 1),
                            ':gross'        => floatval($g['gross_weight'] ?? 0),
                            ':stone'        => floatval($g['stone_weight'] ?? 0),
                            ':net'          => $netWt,
                            ':preset'       => $g['purity_preset'] ?? '24K',
                            ':purity'       => $purityPct,
                            ':market_val'   => $mVal,
                            ':override_val' => !empty($g['manual_market_value_override']) ? floatval($g['manual_market_value_override']) : null,
                            ':loan_val'     => floatval($g['loan_value'] ?? 0),
                            ':rk'           => $rkNum,
                            ':remarks'      => $g['remarks'] ?? null
                        ]);
                        $keptIds[] = intval($db->lastInsertId());
                    }
                }
            }

            // Process Silver Items
            if (!empty($silverItems)) {
                $silverInsertSql = "INSERT INTO collateral_items (
                                    loan_id, item_type, item_name, quantity, gross_weight,
                                    stone_weight, net_weight, purity_preset, purity_percentage,
                                    market_value, manual_market_value_override, loan_value,
                                    rk_number, remarks, created_at
                                ) VALUES (
                                    :loan_id, 'SILVER', :name, :qty, :gross, 0.000, :net,
                                    :preset, :purity, :market_val, :override_val, :loan_val,
                                    :rk, :remarks, NOW()
                                )";
                $silverUpdateSql = "UPDATE collateral_items SET
                                    item_name = :name, quantity = :qty, gross_weight = :gross,
                                    stone_weight = 0.000, net_weight = :net, purity_preset = :preset,
                                    purity_percentage = :purity, market_value = :market_val,
                                    manual_market_value_override = :override_val, loan_value = :loan_val,
                                    rk_number = :rk, remarks = :remarks, updated_at = NOW()
                                  WHERE id = :id AND loan_id = :loan_id";
                $sInsStmt = $db->prepare($silverInsertSql);
                $sUpdStmt = $db->prepare($silverUpdateSql);

                foreach ($silverItems as $s) {
                    if (empty($s['item_name'])) continue;
                    $netWt = floatval($s['net_weight'] ?? 0);
                    if ($netWt <= 0 && !empty($s['gross_weight'])) {
                        $netWt = floatval($s['gross_weight']);
                    }
                    $purityPct = floatval($s['purity_percentage'] ?? 100.00);
                    $mVal = floatval($s['market_value'] ?? 0);
                    if ($mVal <= 0 && $netWt > 0) {
                        $silverRate = RateModel::getLatestSilverRate();
                        $base100 = floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 90) / 0.999, 2));
                        $mVal = round($netWt * ($purityPct / 100.0) * $base100, 2);
                    }
                    $rkNum = !empty($s['rk_number']) ? trim($s['rk_number']) : RackManager::getNextAvailableSlot();

                    if (!empty($s['id']) && in_array(intval($s['id']), $existingIds)) {
                        $itemId = intval($s['id']);
                        $keptIds[] = $itemId;
                        $sUpdStmt->execute([
                            ':name'         => $s['item_name'],
                            ':qty'          => intval($s['quantity'] ?? 1),
                            ':gross'        => floatval($s['gross_weight'] ?? 0),
                            ':net'          => $netWt,
                            ':preset'       => $s['purity_preset'] ?? '100% (Pure)',
                            ':purity'       => $purityPct,
                            ':market_val'   => $mVal,
                            ':override_val' => !empty($s['manual_market_value_override']) ? floatval($s['manual_market_value_override']) : null,
                            ':loan_val'     => floatval($s['loan_value'] ?? 0),
                            ':rk'           => $rkNum,
                            ':remarks'      => $s['remarks'] ?? null,
                            ':id'           => $itemId,
                            ':loan_id'      => $loanId
                        ]);
                    } else {
                        $sInsStmt->execute([
                            ':loan_id'      => $loanId,
                            ':name'         => $s['item_name'],
                            ':qty'          => intval($s['quantity'] ?? 1),
                            ':gross'        => floatval($s['gross_weight'] ?? 0),
                            ':net'          => $netWt,
                            ':preset'       => $s['purity_preset'] ?? '100% (Pure)',
                            ':purity'       => $purityPct,
                            ':market_val'   => $mVal,
                            ':override_val' => !empty($s['manual_market_value_override']) ? floatval($s['manual_market_value_override']) : null,
                            ':loan_val'     => floatval($s['loan_value'] ?? 0),
                            ':rk'           => $rkNum,
                            ':remarks'      => $s['remarks'] ?? null
                        ]);
                        $keptIds[] = intval($db->lastInsertId());
                    }
                }
            }

            // Remove any old items that were removed in the edit form
            $toDeleteIds = array_diff($existingIds, $keptIds);
            if (!empty($toDeleteIds)) {
                $inClause = implode(',', array_map('intval', $toDeleteIds));
                $db->exec("DELETE FROM collateral_item_photos WHERE item_id IN ($inClause)");
                $db->exec("DELETE FROM collateral_items WHERE id IN ($inClause) AND loan_id = $loanId");
            }

            // 3. Synchronize Guarantor Data
            if (!empty($guarantorData) && !empty($guarantorData['guarantor_name'])) {
                $existingG = Database::fetchOne("SELECT id FROM loan_guarantors WHERE loan_id = :lid LIMIT 1", [':lid' => $loanId]);
                if ($existingG) {
                    $gSql = "UPDATE loan_guarantors SET
                                guarantor_name = :gname, guarantor_mobile = :gmobile,
                                guarantor_address = :gaddr, relationship = :rel,
                                aadhaar = :aadhaar, pan = :pan, remarks = :remarks,
                                updated_at = NOW()
                             WHERE id = :gid";
                    $db->prepare($gSql)->execute([
                        ':gname'   => trim($guarantorData['guarantor_name']),
                        ':gmobile' => trim($guarantorData['guarantor_mobile'] ?? ''),
                        ':gaddr'   => trim($guarantorData['guarantor_address'] ?? ''),
                        ':rel'     => trim($guarantorData['relationship'] ?? ''),
                        ':aadhaar' => trim($guarantorData['aadhaar'] ?? ''),
                        ':pan'     => strtoupper(trim($guarantorData['pan'] ?? '')),
                        ':remarks' => trim($guarantorData['remarks'] ?? ''),
                        ':gid'     => $existingG['id']
                    ]);
                } else {
                    $gSql = "INSERT INTO loan_guarantors (
                                loan_id, guarantor_name, guarantor_mobile, guarantor_address,
                                relationship, aadhaar, pan, remarks, created_at
                             ) VALUES (
                                :loan_id, :gname, :gmobile, :gaddr, :rel, :aadhaar, :pan, :remarks, NOW()
                             )";
                    $db->prepare($gSql)->execute([
                        ':loan_id' => $loanId,
                        ':gname'   => trim($guarantorData['guarantor_name']),
                        ':gmobile' => trim($guarantorData['guarantor_mobile'] ?? ''),
                        ':gaddr'   => trim($guarantorData['guarantor_address'] ?? ''),
                        ':rel'     => trim($guarantorData['relationship'] ?? ''),
                        ':aadhaar' => trim($guarantorData['aadhaar'] ?? ''),
                        ':pan'     => strtoupper(trim($guarantorData['pan'] ?? '')),
                        ':remarks' => trim($guarantorData['remarks'] ?? '')
                    ]);
                }
            } else {
                $db->prepare("DELETE FROM loan_guarantors WHERE loan_id = :lid")->execute([':lid' => $loanId]);
            }

            // 4. Synchronize Initial "Loan Issued" Ledger Entry
            $loanRemarks = !empty($loanData['remarks']) ? trim($loanData['remarks']) : null;
            $loanDate = $loanData['loan_date'] ?? $loan['loan_date'];
            $desc = 'Initial loan disbursement (Principal: ₹' . number_format($principal, 2) . ' + 1st Cycle Int: ₹' . number_format($firstCycleInterest, 2) . ')';

            $issuedEntry = Database::fetchOne("SELECT id FROM loan_ledger WHERE loan_id = :lid AND entry_type = 'Loan Issued' ORDER BY id ASC LIMIT 1", [':lid' => $loanId]);
            if ($issuedEntry) {
                $db->prepare("
                    UPDATE loan_ledger
                    SET entry_date = :edate,
                        debit = :debit,
                        description = :desc,
                        remarks = :remarks
                    WHERE id = :id
                ")->execute([
                    ':edate'   => $loanDate,
                    ':debit'   => $initialTotalDisbursement,
                    ':desc'    => $desc,
                    ':remarks' => $loanRemarks,
                    ':id'      => $issuedEntry['id']
                ]);
            } else {
                $db->prepare("
                    INSERT INTO loan_ledger (loan_id, entry_date, entry_type, description, remarks, debit, credit, balance, created_at)
                    VALUES (:lid, :edate, 'Loan Issued', :desc, :remarks, :debit, 0.00, :debit, NOW())
                ")->execute([
                    ':lid'     => $loanId,
                    ':edate'   => $loanDate,
                    ':desc'    => $desc,
                    ':remarks' => $loanRemarks,
                    ':debit'   => $initialTotalDisbursement
                ]);
            }

            // 5. Recalculate all chronological ledger balances & update loan remaining_balance & status
            PaymentModel::recalculateLedgerAndBalance($loanId, $db);

            // 6. Audit Log
            AuditLogger::log('Loan Updated', Session::get('admin_id'), null, [
                'loan_id'          => $loanId,
                'loan_number'      => $loan['loan_number'],
                'customer_id'      => $loanData['customer_id'] ?? $loan['customer_id'],
                'principal_amount' => $principal,
                'security_type'    => $secType
            ], 'Edited loan details and synchronized collateral and ledger balances');

            $db->commit();
            return true;

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to update loan: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getCollateralItems(int $loanId): array {
        return Database::fetchAll("
            SELECT * FROM collateral_items WHERE loan_id = :lid ORDER BY id ASC
        ", [':lid' => $loanId]);
    }

    public static function getGuarantor(int $loanId): ?array {
        return Database::fetchOne("
            SELECT * FROM loan_guarantors WHERE loan_id = :lid LIMIT 1
        ", [':lid' => $loanId]);
    }

    public static function getLedger(int $loanId): array {
        $rows = Database::fetchAll("
            SELECT ll.*,
                   COALESCE(p.id, p2.id) as payment_id,
                   COALESCE(p.receipt_number, p2.receipt_number) as payment_receipt_no,
                   COALESCE(p.payment_date, p2.payment_date, ll.entry_date) as payment_date,
                   COALESCE(p.payment_mode, p2.payment_mode, 'Cash') as payment_mode,
                   COALESCE(p.reference_number, p2.reference_number) as payment_ref_no,
                   COALESCE(NULLIF(ll.remarks, ''), p.remarks, p2.remarks, (CASE WHEN ll.entry_type = 'Loan Issued' THEN l.remarks ELSE NULL END)) as payment_remarks,
                   COALESCE(p.total_amount, p2.total_amount, ll.credit) as payment_total_amount
            FROM loan_ledger ll
            JOIN loans l ON ll.loan_id = l.id
            LEFT JOIN payments p ON ll.payment_id = p.id
            LEFT JOIN payments p2 ON (p.id IS NULL AND p2.loan_id = ll.loan_id AND ll.description LIKE CONCAT('%', p2.receipt_number, '%'))
            WHERE ll.loan_id = :lid
            ORDER BY ll.id ASC
        ", [':lid' => $loanId]);

        $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
        if ($loan && ($loan['status'] === 'Active' || $loan['status'] === 'Running')) {
            $dueInfo = \App\Models\DueModel::calculateLoanDues($loan);
            $totalPayable = floatval($dueInfo['total_payable'] ?? 0);
            $accruedInterest = floatval($dueInfo['accrued_interest'] ?? 0);

            $totDebits = 0.0;
            $totCredits = 0.0;
            foreach ($rows as $r) {
                $totDebits += floatval($r['debit'] ?? 0);
                $totCredits += floatval($r['credit'] ?? 0);
            }

            // Expected total debits = principal + total accrued interest
            $expectedDebits = floatval($loan['principal_amount']) + $accruedInterest;
            $interestDiff = round($expectedDebits - $totDebits, 2);

            if ($interestDiff > 0) {
                $cycleText = !empty($dueInfo['cycles_count']) ? $dueInfo['cycles_count'] . ' cycles' : '';
                $yrText = !empty($dueInfo['full_years_completed']) && $dueInfo['full_years_completed'] >= 1 ? ', Yr ' . ($dueInfo['full_years_completed'] + 1) . ' Compounded' : '';
                $desc = 'Accrued Interest to Date' . ($cycleText ? ' (' . $cycleText . $yrText . ')' : '');

                $finalBalance = round($totDebits + $interestDiff - $totCredits, 2);

                $rows[] = [
                    'id'                   => 'accrued_' . $loanId,
                    'loan_id'              => $loanId,
                    'payment_id'           => null,
                    'entry_date'           => date('Y-m-d'),
                    'entry_type'           => 'Interest Accrued',
                    'description'          => $desc,
                    'remarks'              => 'Live auto-calculated ongoing interest dues',
                    'debit'                => $interestDiff,
                    'credit'               => 0.00,
                    'balance'              => $finalBalance,
                    'payment_receipt_no'   => '',
                    'payment_date'         => date('Y-m-d'),
                    'payment_mode'         => '',
                    'payment_ref_no'       => '',
                    'payment_remarks'      => '',
                    'payment_total_amount' => 0.00,
                    'is_virtual'           => true
                ];
            }
        }

        return $rows;
    }

    public static function getPayments(int $loanId): array {
        return Database::fetchAll("
            SELECT * FROM payments WHERE loan_id = :lid ORDER BY id DESC
        ", [':lid' => $loanId]);
    }

    public static function applyTopUp(int $loanId, float $amount, string $date, string $reason, ?int $adminId): int {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            // 1. Fetch current loan
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Loan account not found.");
            }
            if ($loan['status'] !== 'Active' && $loan['status'] !== 'Running') {
                throw new \Exception("Top-up can only be applied to Active loan accounts.");
            }

            $oldPrincipal = floatval($loan['principal_amount']);
            $newPrincipal = $oldPrincipal + $amount;

            $rate = floatval($loan['interest_rate']);
            $cycleStr = $loan['interest_cycle'] ?? '15 Days';
            $isHalfMonthly = !str_contains(strtolower($cycleStr), '30') && strtolower($cycleStr) !== 'monthly';

            $topupInterest = round($amount * (($rate / ($isHalfMonthly ? 2.0 : 1.0)) / 100.0), 2);
            $topupDebit = round($amount + $topupInterest, 2);

            $oldTotalPayable = floatval($loan['total_payable_amount'] ?? $oldPrincipal);
            $newTotalPayable = round($oldTotalPayable + $topupDebit, 2);

            $oldRemaining = floatval($loan['remaining_balance'] ?? $oldTotalPayable);
            $newRemaining = round($oldRemaining + $topupDebit, 2);

            // 2. Update principal_amount, total_payable_amount, and remaining_balance on the loan
            $db->prepare("UPDATE loans SET principal_amount = :np, total_payable_amount = :tp, remaining_balance = :rb WHERE id = :id")
               ->execute([
                   ':np' => $newPrincipal,
                   ':tp' => $newTotalPayable,
                   ':rb' => $newRemaining,
                   ':id' => $loanId
               ]);

            // 3. Get latest ledger balance to build on top
            $newBalance = $newRemaining;

            // 4. Insert ledger debit entry for the top-up
            $db->prepare("INSERT INTO loan_ledger (loan_id, entry_date, entry_type, description, debit, credit, balance, created_at)
                          VALUES (:loan_id, :entry_date, :entry_type, :description, :debit, 0.00, :balance, NOW())")
               ->execute([
                   ':loan_id'     => $loanId,
                   ':entry_date'  => $date,
                   ':entry_type'  => 'Loan Top-Up',
                   ':description' => 'Additional disbursement (Top-Up: ₹' . number_format($amount, 2) . ' + Int: ₹' . number_format($topupInterest, 2) . ')' . (!empty($reason) ? ' — ' . $reason : ''),
                   ':debit'       => $topupDebit,
                   ':balance'     => $newBalance,
               ]);

            // 5. Insert into loan_topups audit table
            $stmt = $db->prepare("INSERT INTO loan_topups (loan_id, topup_date, topup_amount, new_principal, reason, approved_by, created_at)
                                   VALUES (:loan_id, :topup_date, :topup_amount, :new_principal, :reason, :approved_by, NOW())");
            $stmt->execute([
                ':loan_id'       => $loanId,
                ':topup_date'    => $date,
                ':topup_amount'  => $amount,
                ':new_principal' => $newPrincipal,
                ':reason'        => $reason ?: null,
                ':approved_by'   => $adminId,
            ]);
            $topupId = intval($db->lastInsertId());

            if ($db->inTransaction()) {
                $db->commit();
            }
            return $topupId;

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to apply top-up: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getTopUps(int $loanId): array {
        return Database::fetchAll("
            SELECT t.*, a.username as approved_by_name
            FROM loan_topups t
            LEFT JOIN admin a ON t.approved_by = a.id
            WHERE t.loan_id = :lid
            ORDER BY t.id ASC
        ", [':lid' => $loanId]);
    }

    public static function getLoanValuationSummary(int $loanId): array {
        $row = Database::fetchOne("
            SELECT 
                COUNT(CASE WHEN item_type = 'GOLD' THEN 1 END) as gold_count,
                COALESCE(SUM(CASE WHEN item_type = 'GOLD' THEN COALESCE(manual_market_value_override, market_value) ELSE 0 END), 0) as gold_market_value,
                COUNT(CASE WHEN item_type = 'SILVER' THEN 1 END) as silver_count,
                COALESCE(SUM(CASE WHEN item_type = 'SILVER' THEN COALESCE(manual_market_value_override, market_value) ELSE 0 END), 0) as silver_market_value
            FROM collateral_items
            WHERE loan_id = :lid
        ", [':lid' => $loanId]);

        $goldVal = floatval($row['gold_market_value'] ?? 0);
        $silverVal = floatval($row['silver_market_value'] ?? 0);
        $totalVal = $goldVal + $silverVal;

        return [
            'gold_count'          => intval($row['gold_count'] ?? 0),
            'gold_market_value'   => $goldVal,
            'silver_count'        => intval($row['silver_count'] ?? 0),
            'silver_market_value' => $silverVal,
            'total_market_value'  => $totalVal
        ];
    }

    public static function deliverJewellery(int $loanId, string $remarks = '', string $haste = 'Customer Self'): bool {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Loan account not found.");
            }

            // Update loan delivery status and timestamp
            $stmt = $db->prepare("
                UPDATE loans 
                SET delivered = 'Yes', 
                    delivery_date = CURDATE(), 
                    delivery_remarks = :remarks, 
                    haste = :haste 
                WHERE id = :id
            ");
            $stmt->execute([
                ':remarks' => !empty($remarks) ? trim($remarks) : ($loan['delivery_remarks'] ?? 'Jewellery delivered to customer upon settlement'),
                ':haste'   => !empty($haste) ? trim($haste) : ($loan['haste'] ?? 'Customer Self'),
                ':id'      => $loanId
            ]);

            // Physical rack reference is preserved on collateral items;
            // Slot availability is dynamically managed by RackManager (delivered items release slots for reuse).

            // Log in audit log
            \App\Helpers\AuditLogger::log('Jewellery Delivered', \App\Helpers\Session::get('admin_id'), null, [
                'loan_id'     => $loanId,
                'loan_number' => $loan['loan_number'],
                'haste'       => $haste,
                'remarks'     => $remarks
            ], 'Pledged jewellery marked delivered & rack slots released');

            if ($db->inTransaction()) {
                $db->commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to deliver jewellery: " . $e->getMessage());
            throw $e;
        }
    }

    public static function updateLedgerEntry(int $ledgerId, array $data): bool {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $entry = Database::fetchOne("SELECT * FROM loan_ledger WHERE id = :id LIMIT 1", [':id' => $ledgerId]);
            if (!$entry) {
                throw new \Exception("Ledger entry not found.");
            }

            $loanId = intval($entry['loan_id']);
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Associated loan account not found.");
            }

            $entryDate = !empty($data['entry_date']) ? trim($data['entry_date']) : $entry['entry_date'];
            
            // Validate and lock 'Loan Issued' transaction type
            if ($entry['entry_type'] === 'Loan Issued') {
                if (isset($data['entry_type']) && $data['entry_type'] !== '' && $data['entry_type'] !== 'Loan Issued') {
                    throw new \Exception("The transaction type for 'Loan Issued' is fixed and cannot be modified.");
                }
                $entryType = 'Loan Issued';
            } else {
                if (isset($data['entry_type']) && $data['entry_type'] === 'Loan Issued') {
                    throw new \Exception("Cannot change transaction type to 'Loan Issued'.");
                }
                $entryType = !empty($data['entry_type']) ? trim($data['entry_type']) : $entry['entry_type'];
            }

            $description = isset($data['description']) ? trim($data['description']) : $entry['description'];
            $debit = isset($data['debit']) ? max(0.00, round(floatval($data['debit']), 2)) : floatval($entry['debit']);
            $credit = isset($data['credit']) ? max(0.00, round(floatval($data['credit']), 2)) : floatval($entry['credit']);

            // Closing Entry defaults: debit = 0, credit = 0 unless explicitly specified
            if ($entryType === 'Closing Entry' && !isset($data['debit']) && !isset($data['credit'])) {
                $debit = 0.00;
                $credit = 0.00;
            }

            $remarks = isset($data['remarks']) ? trim($data['remarks']) : ($entry['remarks'] ?? null);

            // 1. Update loan_ledger
            $stmt = $db->prepare("
                UPDATE loan_ledger
                SET entry_date = :entry_date,
                    entry_type = :entry_type,
                    description = :description,
                    remarks = :remarks,
                    debit = :debit,
                    credit = :credit
                WHERE id = :id
            ");
            $stmt->execute([
                ':entry_date'  => $entryDate,
                ':entry_type'  => $entryType,
                ':description' => $description,
                ':remarks'     => !empty($remarks) ? $remarks : null,
                ':debit'       => $debit,
                ':credit'      => $credit,
                ':id'          => $ledgerId
            ]);

            // 2. Synchronize "Loan Issued" entry with loans table
            if ($entryType === 'Loan Issued') {
                $db->prepare("
                    UPDATE loans
                    SET loan_date = :ldate,
                        total_payable_amount = :tp,
                        remarks = :remarks
                    WHERE id = :id
                ")->execute([
                    ':ldate'   => $entryDate,
                    ':tp'      => $debit,
                    ':remarks' => !empty($remarks) ? $remarks : null,
                    ':id'      => $loanId
                ]);
            }

            // 3. Synchronize Payment details if linked to payment_id
            if (!empty($entry['payment_id'])) {
                $paymentId = intval($entry['payment_id']);
                if ($entryType === 'Discount / Concession') {
                    $db->prepare("
                        UPDATE payments
                        SET discount = :disc,
                            payment_date = :pdate
                        WHERE id = :id
                    ")->execute([
                        ':disc'  => $credit,
                        ':pdate' => $entryDate,
                        ':id'    => $paymentId
                    ]);
                } elseif ($entryType === 'Payment Received') {
                    $pMode = !empty($data['payment_mode']) ? trim($data['payment_mode']) : 'Cash';
                    $pRef  = isset($data['reference_number']) ? trim($data['reference_number']) : null;
                    $pRem  = isset($data['remarks']) ? trim($data['remarks']) : null;

                    $db->prepare("
                        UPDATE payments
                        SET total_amount = :amt,
                            interest_component = :amt,
                            payment_date = :pdate,
                            payment_mode = :pmode,
                            reference_number = :pref,
                            remarks = :premarks
                        WHERE id = :id
                    ")->execute([
                        ':amt'      => $credit,
                        ':pdate'    => $entryDate,
                        ':pmode'    => $pMode,
                        ':pref'     => !empty($pRef) ? $pRef : null,
                        ':premarks' => !empty($pRem) ? $pRem : null,
                        ':id'       => $paymentId
                    ]);
                }
            }

            // 4. Recalculate all chronological ledger balances & update loan remaining_balance & status
            PaymentModel::recalculateLedgerAndBalance($loanId, $db);

            // 5. Audit Log
            AuditLogger::log('Ledger Entry Updated', Session::get('admin_id'), null, [
                'ledger_id'   => $ledgerId,
                'loan_id'     => $loanId,
                'loan_number' => $loan['loan_number'],
                'entry_type'  => $entryType,
                'entry_date'  => $entryDate,
                'debit'       => $debit,
                'credit'      => $credit,
                'description' => $description
            ], 'Updated loan ledger transaction entry #' . $ledgerId);

            if ($db->inTransaction()) {
                $db->commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to update ledger entry: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve all loan ledger entries across all loans with filtering
     */
    public static function getAllLedgerEntries(array $filters = []): array {
        $sql = "
            SELECT ll.*,
                   l.loan_number,
                   l.principal_amount,
                   l.interest_rate,
                   l.status as loan_status,
                   l.security_type,
                   c.id as customer_id,
                   c.customer_id as cust_code,
                   c.account_number as customer_account_number,
                   c.full_name as customer_name,
                   c.mobile as customer_mobile,
                   COALESCE(p.id, p2.id) as payment_id,
                   COALESCE(p.receipt_number, p2.receipt_number) as payment_receipt_no,
                   COALESCE(p.payment_date, p2.payment_date, ll.entry_date) as payment_date,
                   COALESCE(p.payment_mode, p2.payment_mode, 'Cash') as payment_mode,
                   COALESCE(p.reference_number, p2.reference_number) as payment_ref_no,
                   COALESCE(NULLIF(ll.remarks, ''), p.remarks, p2.remarks, (CASE WHEN ll.entry_type = 'Loan Issued' THEN l.remarks ELSE NULL END)) as payment_remarks,
                   COALESCE(p.total_amount, p2.total_amount, ll.credit) as payment_total_amount
            FROM loan_ledger ll
            JOIN loans l ON ll.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            LEFT JOIN payments p ON ll.payment_id = p.id
            LEFT JOIN payments p2 ON (p.id IS NULL AND p2.loan_id = ll.loan_id AND ll.description LIKE CONCAT('%', p2.receipt_number, '%'))
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND ll.entry_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND ll.entry_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['flow_type'])) {
            if ($filters['flow_type'] === 'debit' || $filters['flow_type'] === 'outflow') {
                $sql .= " AND ll.debit > 0";
            } elseif ($filters['flow_type'] === 'credit' || $filters['flow_type'] === 'inflow') {
                $sql .= " AND ll.credit > 0";
            }
        }

        if (!empty($filters['entry_type'])) {
            $sql .= " AND ll.entry_type = :entry_type";
            $params[':entry_type'] = $filters['entry_type'];
        }

        if (!empty($filters['payment_mode'])) {
            $sql .= " AND COALESCE(p.payment_mode, p2.payment_mode, 'Cash') = :payment_mode";
            $params[':payment_mode'] = $filters['payment_mode'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                l.loan_number LIKE :s1
                OR c.full_name LIKE :s2
                OR c.mobile LIKE :s3
                OR c.customer_id LIKE :s4
                OR c.account_number LIKE :s5
                OR p.receipt_number LIKE :s6
                OR p2.receipt_number LIKE :s7
                OR ll.description LIKE :s8
                OR ll.remarks LIKE :s9
            )";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
            $params[':s6'] = $term;
            $params[':s7'] = $term;
            $params[':s8'] = $term;
            $params[':s9'] = $term;
        }

        $sql .= " ORDER BY ll.entry_date DESC, ll.id DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Compute summary statistics for filtered ledger entries
     */
    public static function getLedgerSummary(array $filters = []): array {
        $sql = "
            SELECT 
                COALESCE(SUM(ll.debit), 0) as total_debit,
                COALESCE(SUM(ll.credit), 0) as total_credit,
                COUNT(ll.id) as total_count,
                COUNT(DISTINCT ll.loan_id) as total_loans
            FROM loan_ledger ll
            JOIN loans l ON ll.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            LEFT JOIN payments p ON ll.payment_id = p.id
            LEFT JOIN payments p2 ON (p.id IS NULL AND p2.loan_id = ll.loan_id AND ll.description LIKE CONCAT('%', p2.receipt_number, '%'))
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND ll.entry_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND ll.entry_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['flow_type'])) {
            if ($filters['flow_type'] === 'debit' || $filters['flow_type'] === 'outflow') {
                $sql .= " AND ll.debit > 0";
            } elseif ($filters['flow_type'] === 'credit' || $filters['flow_type'] === 'inflow') {
                $sql .= " AND ll.credit > 0";
            }
        }

        if (!empty($filters['entry_type'])) {
            $sql .= " AND ll.entry_type = :entry_type";
            $params[':entry_type'] = $filters['entry_type'];
        }

        if (!empty($filters['payment_mode'])) {
            $sql .= " AND COALESCE(p.payment_mode, p2.payment_mode, 'Cash') = :payment_mode";
            $params[':payment_mode'] = $filters['payment_mode'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                l.loan_number LIKE :s1
                OR c.full_name LIKE :s2
                OR c.mobile LIKE :s3
                OR c.customer_id LIKE :s4
                OR c.account_number LIKE :s5
                OR p.receipt_number LIKE :s6
                OR p2.receipt_number LIKE :s7
                OR ll.description LIKE :s8
                OR ll.remarks LIKE :s9
            )";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
            $params[':s6'] = $term;
            $params[':s7'] = $term;
            $params[':s8'] = $term;
            $params[':s9'] = $term;
        }

        $row = Database::fetchOne($sql, $params);
        $totalDebit  = floatval($row['total_debit'] ?? 0);
        $totalCredit = floatval($row['total_credit'] ?? 0);
        $netFlow     = $totalCredit - $totalDebit;

        return [
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'net_flow'     => $netFlow,
            'total_count'  => intval($row['total_count'] ?? 0),
            'total_loans'  => intval($row['total_loans'] ?? 0)
        ];
    }
}
