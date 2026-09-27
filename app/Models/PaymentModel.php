<?php

namespace App\Models;

require_once __DIR__ . '/DueModel.php';

use App\Config\Database;
use App\Models\DueModel;

class PaymentModel {

    public static function getAll(string $search = '', string $paymentMode = '', string $paymentType = ''): array {
        $sql = "SELECT p.*, l.loan_number, l.customer_id, c.full_name as customer_name, c.mobile as customer_mobile
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE 1=1";

        $params = [];

        if (!empty($paymentMode)) {
            $sql .= " AND p.payment_mode = :pmode";
            $params[':pmode'] = $paymentMode;
        }

        if (!empty($search)) {
            $sql .= " AND (p.receipt_number LIKE :p1 
                        OR l.loan_number LIKE :p2 
                        OR c.full_name LIKE :p3 
                        OR p.reference_number LIKE :p4)";
            $searchTerm = '%' . $search . '%';
            $params[':p1'] = $searchTerm;
            $params[':p2'] = $searchTerm;
            $params[':p3'] = $searchTerm;
            $params[':p4'] = $searchTerm;
        }

        $sql .= " ORDER BY p.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("
            SELECT p.*, l.loan_number, l.loan_date, l.principal_amount, l.interest_rate, l.interest_method, l.status as loan_status,
                   c.id as cust_id, c.customer_id as cust_code, c.account_number as customer_account_number, c.full_name as customer_name, c.mobile as customer_mobile, c.address as customer_address
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            WHERE p.id = :id
            LIMIT 1
        ", [':id' => $id]);
    }

    public static function generateNextReceiptNumber(): string {
        $row = Database::fetchOne("SELECT MAX(id) as max_id FROM payments");
        $nextId = (intval($row['max_id'] ?? 0)) + 1;
        $year = date('Y');
        return sprintf("REC-%s-%04d", $year, $nextId);
    }

    public static function recordPayment(array $data): int {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $loanId             = intval($data['loan_id']);
            $totalAmount        = floatval($data['total_amount']);
            $discount           = max(0.00, floatval($data['discount'] ?? 0));
            $isFullSettlement   = !empty($data['is_full_settlement']);
            $jewelleryDelivered = !empty($data['jewellery_delivered']);
            $deliveryRemarks    = trim($data['delivery_remarks'] ?? '');
            $haste              = trim($data['haste'] ?? 'Customer Self');
            $pType              = $data['payment_type'] ?? ($discount > 0 || $isFullSettlement ? 'Full Settlement' : 'Payment Received');
            $pMode              = $data['payment_mode'] ?? 'Cash';
            $pDate              = $data['payment_date'] ?? date('Y-m-d');
            $receiptNo          = !empty($data['receipt_number']) ? $data['receipt_number'] : self::generateNextReceiptNumber();

            // Fetch Loan & Live Calculation Dues (Total payable amount with interest)
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Loan account not found.");
            }

            // Calculate remaining balance after payment and discount deduction using live total dues
            $dueInfo = DueModel::calculateLoanDues($loan);
            $currRemaining = floatval($dueInfo['total_payable'] ?? ($loan['remaining_balance'] ?? $loan['principal_amount']));
            $totalCreditApplied = $totalAmount + $discount;
            $newBalance = max(0.00, round($currRemaining - $totalCreditApplied, 2));

            if ($isFullSettlement || $totalCreditApplied >= $currRemaining) {
                $newBalance = 0.00;
            }

            // 1. Insert Payment Master Record
            $sql = "INSERT INTO payments (
                        receipt_number, loan_id, payment_date, payment_type,
                        total_amount, discount, interest_component, principal_component, penalty_component,
                        payment_mode, reference_number, remarks, created_at
                    ) VALUES (
                        :receipt_number, :loan_id, :payment_date, :payment_type,
                        :total_amount, :discount, :interest_component, 0.00, 0.00,
                        :payment_mode, :reference_number, :remarks, NOW()
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':receipt_number'     => $receiptNo,
                ':loan_id'            => $loanId,
                ':payment_date'       => $pDate,
                ':payment_type'       => $pType,
                ':total_amount'       => $totalAmount,
                ':discount'           => $discount,
                ':interest_component' => $totalAmount,
                ':payment_mode'       => $pMode,
                ':reference_number'   => $data['reference_number'] ?? null,
                ':remarks'            => $data['remarks'] ?? null
            ]);

            $paymentId = intval($db->lastInsertId());

            $ledgerSql = "INSERT INTO loan_ledger (
                            loan_id, payment_id, entry_date, entry_type, description, debit, credit, balance, created_at
                          ) VALUES (
                            :loan_id, :payment_id, :entry_date, :entry_type, :description, :debit, :credit, :balance, NOW()
                          )";
            $ledgerStmt = $db->prepare($ledgerSql);

            // 2. If Discount / Waiver granted, insert synchronized discount ledger credit entry
            if ($discount > 0) {
                $intermediateBalance = max(0.00, round($currRemaining - $discount, 2));
                $ledgerStmt->execute([
                    ':loan_id'     => $loanId,
                    ':payment_id'  => $paymentId,
                    ':entry_date'  => $pDate,
                    ':entry_type'  => 'Discount / Concession',
                    ':description' => 'Settlement discount / concession granted (₹' . number_format($discount, 2) . ')',
                    ':debit'       => 0.00,
                    ':credit'      => $discount,
                    ':balance'     => $intermediateBalance
                ]);
            }

            // 3. Insert Synchronized Payment Received Ledger Credit Row
            $desc = "Payment Received via " . $pMode . " (" . $receiptNo . ")";
            if ($discount > 0) {
                $desc .= " [Discount: ₹" . number_format($discount, 2) . "]";
            }
            if (!empty($data['reference_number'])) {
                $desc .= " Ref: " . $data['reference_number'];
            }

            $ledgerStmt->execute([
                ':loan_id'     => $loanId,
                ':payment_id'  => $paymentId,
                ':entry_date'  => $pDate,
                ':entry_type'  => 'Payment Received',
                ':description' => $desc,
                ':debit'       => 0.00,
                ':credit'      => $totalAmount,
                ':balance'     => $newBalance
            ]);

            // Update remaining_balance on the loan record
            $db->prepare("UPDATE loans SET remaining_balance = :rb WHERE id = :id")
               ->execute([':rb' => $newBalance, ':id' => $loanId]);

            // 4. Auto-close loan if balance reaches zero & automatically mark jewellery as delivered
            if ($newBalance <= 0) {
                $autoRemarks = !empty($deliveryRemarks) ? trim($deliveryRemarks) : 'Jewellery automatically delivered upon loan closure & settlement';
                $autoHaste   = !empty($haste) ? trim($haste) : 'Customer Self';

                $db->prepare("
                    UPDATE loans 
                    SET status = 'Closed',
                        delivered = 'Yes',
                        delivery_date = COALESCE(delivery_date, :pdate),
                        delivery_remarks = COALESCE(delivery_remarks, :dremarks),
                        haste = COALESCE(haste, :haste)
                    WHERE id = :id
                ")->execute([
                    ':pdate'    => $pDate,
                    ':dremarks' => $autoRemarks,
                    ':haste'    => $autoHaste,
                    ':id'       => $loanId
                ]);

                // Closing Ledger Entry
                $ledgerStmt->execute([
                    ':loan_id'     => $loanId,
                    ':payment_id'  => null,
                    ':entry_date'  => $pDate,
                    ':entry_type'  => 'Closing Entry',
                    ':description' => 'Loan account fully settled and closed',
                    ':debit'       => 0.00,
                    ':credit'      => 0.00,
                    ':balance'     => 0.00
                ]);
            }

            $db->commit();
            return $paymentId;

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Failed to record payment: " . $e->getMessage());
            throw $e;
        }
    }

    public static function updatePayment(int $paymentId, array $data): bool {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $payment = self::findById($paymentId);
            if (!$payment) {
                throw new \Exception("Payment record not found.");
            }

            $loanId      = intval($payment['loan_id']);
            $totalAmount = floatval($data['total_amount']);
            $pDate       = !empty($data['payment_date']) ? trim($data['payment_date']) : $payment['payment_date'];
            $pMode       = !empty($data['payment_mode']) ? trim($data['payment_mode']) : $payment['payment_mode'];
            $receiptNo   = $payment['receipt_number'];
            $refNumber   = isset($data['reference_number']) ? trim($data['reference_number']) : $payment['reference_number'];
            $remarks     = isset($data['remarks']) ? trim($data['remarks']) : $payment['remarks'];

            if ($totalAmount <= 0) {
                throw new \Exception("Payment amount must be greater than 0.");
            }

            // 1. Update Payment Record
            $sql = "UPDATE payments SET 
                        total_amount = :total_amount,
                        interest_component = :interest_component,
                        payment_date = :payment_date,
                        payment_mode = :payment_mode,
                        reference_number = :reference_number,
                        remarks = :remarks
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':total_amount'       => $totalAmount,
                ':interest_component' => $totalAmount,
                ':payment_date'       => $pDate,
                ':payment_mode'       => $pMode,
                ':reference_number'   => !empty($refNumber) ? $refNumber : null,
                ':remarks'            => !empty($remarks) ? $remarks : null,
                ':id'                 => $paymentId
            ]);

            // 2. Update Corresponding Loan Ledger Record
            $desc = "Payment Received via " . $pMode . " (" . $receiptNo . ")";
            if (!empty($refNumber)) {
                $desc .= " Ref: " . $refNumber;
            }

            // Try updating by payment_id first, fallback to receipt number in description
            $ledgerUpdateStmt = $db->prepare("
                UPDATE loan_ledger 
                SET entry_date = :entry_date,
                    credit = :credit,
                    description = :description,
                    payment_id = :payment_id
                WHERE (payment_id = :payment_id_where OR (loan_id = :loan_id AND description LIKE :receipt_pattern))
                  AND (entry_type = 'Payment Received' OR entry_type = '' OR entry_type IS NULL)
            ");
            $ledgerUpdateStmt->execute([
                ':entry_date'        => $pDate,
                ':credit'            => $totalAmount,
                ':description'       => $desc,
                ':payment_id'        => $paymentId,
                ':payment_id_where'  => $paymentId,
                ':loan_id'           => $loanId,
                ':receipt_pattern'   => '%' . $receiptNo . '%'
            ]);

            // 3. Recalculate running balances across the entire loan ledger
            self::recalculateLedgerAndBalance($loanId, $db);

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Failed to update payment: " . $e->getMessage());
            throw $e;
        }
    }

    public static function recalculateLedgerAndBalance(int $loanId, ?\PDO $db = null): float {
        $shouldCommit = false;
        if ($db === null) {
            $db = Database::connect();
            $db->beginTransaction();
            $shouldCommit = true;
        }

        try {
            // Fetch loan
            $loan = Database::fetchOne("SELECT * FROM loans WHERE id = :id LIMIT 1", [':id' => $loanId]);
            if (!$loan) {
                throw new \Exception("Loan account not found.");
            }

            // Fetch all non-closing ledger entries in chronological sequence
            $stmt = $db->prepare("
                SELECT id, entry_date, entry_type, debit, credit, balance
                FROM loan_ledger
                WHERE loan_id = :lid AND entry_type != 'Closing Entry'
                ORDER BY id ASC
            ");
            $stmt->execute([':lid' => $loanId]);
            $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $runningBalance = 0.00;
            $updateBalanceStmt = $db->prepare("UPDATE loan_ledger SET balance = :balance WHERE id = :id");

            foreach ($entries as $entry) {
                $debit  = floatval($entry['debit']);
                $credit = floatval($entry['credit']);

                $runningBalance = round($runningBalance + $debit - $credit, 2);
                $runningBalance = max(0.00, $runningBalance);

                $updateBalanceStmt->execute([
                    ':balance' => $runningBalance,
                    ':id'      => $entry['id']
                ]);
            }

            // Check for Closing Entry
            $closingStmt = $db->prepare("SELECT id FROM loan_ledger WHERE loan_id = :lid AND entry_type = 'Closing Entry' LIMIT 1");
            $closingStmt->execute([':lid' => $loanId]);
            $closingEntry = $closingStmt->fetch(\PDO::FETCH_ASSOC);

            // Compute live total dues to ensure remaining_balance reflects true dues
            $dueInfo = DueModel::calculateLoanDues($loan);
            $liveTotalPayable = floatval($dueInfo['total_payable'] ?? 0);

            if ($runningBalance <= 0.00 || !empty($closingEntry)) {
                // Settle and close loan, and automatically mark jewellery as delivered
                $lastDate = !empty($entries) ? end($entries)['entry_date'] : date('Y-m-d');
                $db->prepare("
                    UPDATE loans 
                    SET remaining_balance = 0.00, 
                        status = 'Closed',
                        delivered = 'Yes',
                        delivery_date = COALESCE(delivery_date, :ddate),
                        delivery_remarks = COALESCE(delivery_remarks, 'Jewellery automatically delivered upon loan closure & settlement')
                    WHERE id = :id
                ")->execute([
                    ':ddate' => $lastDate,
                    ':id'    => $loanId
                ]);

                if (!$closingEntry) {
                    $lastDate = !empty($entries) ? end($entries)['entry_date'] : date('Y-m-d');
                    $db->prepare("
                        INSERT INTO loan_ledger (loan_id, entry_date, entry_type, description, debit, credit, balance, created_at)
                        VALUES (:lid, :edate, 'Closing Entry', 'Loan account fully settled and closed', 0.00, 0.00, 0.00, NOW())
                    ")->execute([
                        ':lid'   => $loanId,
                        ':edate' => $lastDate
                    ]);
                } else {
                    $db->prepare("UPDATE loan_ledger SET balance = 0.00, credit = 0.00, debit = 0.00 WHERE id = :id")
                       ->execute([':id' => $closingEntry['id']]);
                }
            } else {
                // Determine the true outstanding balance
                $effectiveRemaining = max($runningBalance, $liveTotalPayable);
                $newStatus = ($loan['status'] === 'Closed') ? 'Running' : $loan['status'];
                $db->prepare("UPDATE loans SET remaining_balance = :rb, status = :st, delivered = 'No', delivery_date = NULL WHERE id = :id")
                   ->execute([
                       ':rb' => $effectiveRemaining,
                       ':st' => $newStatus,
                       ':id' => $loanId
                   ]);

                // Remove Closing Entry if it exists because loan now has an unpaid balance
                if ($closingEntry) {
                    $db->prepare("DELETE FROM loan_ledger WHERE id = :id")->execute([':id' => $closingEntry['id']]);
                }
            }

            if ($shouldCommit) {
                $db->commit();
            }

            return $runningBalance;

        } catch (\Exception $e) {
            if ($shouldCommit) {
                $db->rollBack();
            }
            error_log("Failed to recalculate ledger: " . $e->getMessage());
            throw $e;
        }
    }
}
