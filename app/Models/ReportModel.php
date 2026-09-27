<?php

namespace App\Models;

use App\Config\Database;
use App\Models\DueModel;

class ReportModel {

    public static function getReportMeta(): array {
        return [
            'loan_disbursement' => [
                'name' => '1. Loan Disbursement Register Report',
                'category' => 'Financial & Loans',
                'desc' => 'Comprehensive register of all loans disbursed within date range.',
                'icon' => 'fa-file-invoice-dollar'
            ],
            'payment_collection' => [
                'name' => '2. Payment & Receipt Collection Report',
                'category' => 'Financial & Loans',
                'desc' => 'All payments collected split by payment mode (Cash, UPI, Bank).',
                'icon' => 'fa-receipt'
            ],
            'outstanding_loans' => [
                'name' => '3. Outstanding Loans Balance Summary Report',
                'category' => 'Financial & Loans',
                'desc' => 'Active open loan accounts with principal running balances.',
                'icon' => 'fa-scale-balanced'
            ],
            'interest_dues' => [
                'name' => '4. Interest Dues & Accrual Report',
                'category' => 'Customers & Dues',
                'desc' => 'Accrued interest calculations and total payable amounts.',
                'icon' => 'fa-percent'
            ],
            'overdue_npa' => [
                'name' => '5. Overdue & NPA Warning Report',
                'category' => 'Customers & Dues',
                'desc' => 'Loan accounts overdue past grace period (>15 to 30+ days).',
                'icon' => 'fa-triangle-exclamation'
            ],
            'shortfall_risk' => [
                'name' => '6. Collateral Shortfall Risk Report',
                'category' => 'Customers & Dues',
                'desc' => 'Loans where total payable amount exceeds live collateral value.',
                'icon' => 'fa-shield-exclamation'
            ],
            'gold_inventory' => [
                'name' => '7. Gold Collateral Vault Inventory Report',
                'category' => 'Collateral Vault',
                'desc' => 'Pledged gold items, gross wt, net wt, karat, and market value.',
                'icon' => 'fa-gem'
            ],
            'silver_inventory' => [
                'name' => '8. Silver Collateral Vault Inventory Report',
                'category' => 'Collateral Vault',
                'desc' => 'Pledged silver items, gross wt, net wt, purity, and valuation.',
                'icon' => 'fa-ring'
            ],
            'rack_allocation' => [
                'name' => '9. Physical Storage Rack Allocation Report',
                'category' => 'Collateral Vault',
                'desc' => 'Physical storage mapping by RK Rack reference (RK-G101, RK-S202).',
                'icon' => 'fa-warehouse'
            ],
            'customer_portfolio' => [
                'name' => '10. Customer Portfolio Summary Report',
                'category' => 'Customers & Dues',
                'desc' => 'Customer directory with lifetime loan counts and total balances.',
                'icon' => 'fa-users'
            ],
            'kyc_compliance' => [
                'name' => '11. KYC Documents Compliance Report',
                'category' => 'Customers & Dues',
                'desc' => 'Compliance tracking of uploaded photos, Aadhaar, and PAN cards.',
                'icon' => 'fa-id-card'
            ],
            'bullion_rates' => [
                'name' => '12. Daily Bullion Rates Log Report',
                'category' => 'System & Audit',
                'desc' => 'Historical daily rates log for 24K, 22K Gold and 99.9% Silver.',
                'icon' => 'fa-chart-line'
            ],
            'loan_settlement' => [
                'name' => '13. Loan Closure & Settlement Report',
                'category' => 'Financial & Loans',
                'desc' => 'History of fully settled and closed loan accounts.',
                'icon' => 'fa-check-circle'
            ],
            'audit_trail' => [
                'name' => '14. Audit Trail Security Log Report',
                'category' => 'System & Audit',
                'desc' => 'Complete operations audit trail with IP address and timestamps.',
                'icon' => 'fa-shield-halved'
            ],
            'backup_history' => [
                'name' => '15. Database Backup History Report',
                'category' => 'System & Audit',
                'desc' => 'Database backup dumps history, size, and system safety status.',
                'icon' => 'fa-database'
            ],
            'cashflow_monthly' => [
                'name' => '16. Financial Cashflow Monthly Summary Report',
                'category' => 'Financial & Loans',
                'desc' => 'Monthly disbursed principal vs total payments collected.',
                'icon' => 'fa-chart-column'
            ],
            'guarantor_register' => [
                'name' => '17. Guarantor Security Register Report',
                'category' => 'Collateral Vault',
                'desc' => 'Loans backed by guarantor details, mobile, and address.',
                'icon' => 'fa-user-shield'
            ]
        ];
    }

    public static function generateReport(string $key, ?string $startDate = null, ?string $endDate = null, string $search = ''): array {
        $meta = self::getReportMeta()[$key] ?? [
            'name' => 'System Report',
            'category' => 'General',
            'desc' => 'System report',
            'icon' => 'fa-file'
        ];

        $columns = [];
        $rows    = [];
        $totals  = [];

        switch ($key) {
            case 'loan_disbursement':
                $sql = "SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile
                        FROM loans l JOIN customers c ON l.customer_id = c.id WHERE 1=1";
                $params = [];
                if ($startDate) { $sql .= " AND l.loan_date >= :s"; $params[':s'] = $startDate; }
                if ($endDate)   { $sql .= " AND l.loan_date <= :e"; $params[':e'] = $endDate; }
                if ($search)    { $sql .= " AND (l.loan_number LIKE :st OR c.full_name LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY l.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Loan Number', 'Customer Name', 'Mobile', 'Loan Date', 'Security Type', 'Interest Rate', 'Principal (₹)', 'Status'];
                $sumP = 0;
                foreach ($res as $r) {
                    $sumP += floatval($r['principal_amount']);
                    $rows[] = [
                        $r['loan_number'], $r['customer_name'], $r['customer_mobile'], $r['loan_date'],
                        $r['security_type'], $r['interest_rate'] . '%', '₹' . number_format($r['principal_amount'], 2), $r['status']
                    ];
                }
                $totals = ['Total Loans Disbursed' => count($res), 'Total Principal Amount' => '₹' . number_format($sumP, 2)];
                break;

            case 'payment_collection':
                $sql = "SELECT p.*, l.loan_number, c.full_name as customer_name
                        FROM payments p JOIN loans l ON p.loan_id = l.id JOIN customers c ON l.customer_id = c.id WHERE 1=1";
                $params = [];
                if ($startDate) { $sql .= " AND p.payment_date >= :s"; $params[':s'] = $startDate; }
                if ($endDate)   { $sql .= " AND p.payment_date <= :e"; $params[':e'] = $endDate; }
                if ($search)    { $sql .= " AND (p.receipt_number LIKE :st OR l.loan_number LIKE :st OR c.full_name LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY p.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Receipt Number', 'Payment Date', 'Loan Number', 'Customer Name', 'Payment Mode', 'Reference Number', 'Total Amount (₹)'];
                $sumA = 0;
                foreach ($res as $r) {
                    $sumA += floatval($r['total_amount']);
                    $rows[] = [
                        $r['receipt_number'], $r['payment_date'], $r['loan_number'], $r['customer_name'],
                        $r['payment_mode'], $r['reference_number'] ?: '—', '₹' . number_format($r['total_amount'], 2)
                    ];
                }
                $totals = ['Total Receipts Count' => count($res), 'Total Collection Amount' => '₹' . number_format($sumA, 2)];
                break;

            case 'outstanding_loans':
                $sql = "SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile,
                               (SELECT GROUP_CONCAT(DISTINCT ci.item_name SEPARATOR ', ') FROM collateral_items ci WHERE ci.loan_id = l.id) as collateral_names,
                               (SELECT balance FROM loan_ledger ll WHERE ll.loan_id = l.id ORDER BY id DESC LIMIT 1) as running_balance
                        FROM loans l JOIN customers c ON l.customer_id = c.id WHERE l.status IN ('Active', 'Running')";
                $params = [];
                if ($search) { $sql .= " AND (l.loan_number LIKE :st OR c.full_name LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY l.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Loan Number', 'Customer Name', 'Mobile', 'Disbursement Date', 'Collateral / Security', 'Original Principal (₹)', 'Outstanding Balance (₹)'];
                $sumB = 0;
                foreach ($res as $r) {
                    $bal = floatval($r['running_balance'] ?? $r['principal_amount']);
                    $sumB += $bal;
                    $colName = !empty($r['collateral_names']) ? $r['collateral_names'] : $r['security_type'];
                    $rows[] = [
                        $r['loan_number'], $r['customer_name'], $r['customer_mobile'], $r['loan_date'],
                        $colName, '₹' . number_format($r['principal_amount'], 2), '₹' . number_format($bal, 2)
                    ];
                }
                $totals = ['Total Active Loans' => count($res), 'Total Outstanding Principal' => '₹' . number_format($sumB, 2)];
                break;

            case 'interest_dues':
            case 'overdue_npa':
            case 'shortfall_risk':
                $filterKey = 'all';
                if ($key === 'overdue_npa') $filterKey = 'overdue_1_15';
                if ($key === 'shortfall_risk') $filterKey = 'shortfall_alert';

                $dueSheet = DueModel::getDueSheet($filterKey, $search);
                $columns = ['Loan Number', 'Customer Name', 'Mobile', 'Security Type', 'Principal Balance (₹)', 'Elapsed Days', 'Accrued Interest (₹)', 'Collateral Value (₹)', 'LTV %', 'Total Payable (₹)', 'Status'];
                $sumP = 0; $sumI = 0; $sumTot = 0;
                foreach ($dueSheet as $d) {
                    $sumP += $d['principal_balance'];
                    $sumI += $d['accrued_interest'];
                    $sumTot += $d['total_payable'];
                    $rows[] = [
                        $d['loan_number'], $d['customer_name'], $d['customer_mobile'], $d['security_type'],
                        '₹' . number_format($d['principal_balance'], 2), $d['days_elapsed'] . ' days',
                        '₹' . number_format($d['accrued_interest'], 2), '₹' . number_format($d['current_collateral_value'], 2),
                        $d['live_ltv_ratio'] . '%', '₹' . number_format($d['total_payable'], 2), $d['due_status_label']
                    ];
                }
                $totals = ['Total Loans Listed' => count($dueSheet), 'Total Accrued Interest' => '₹' . number_format($sumI, 2), 'Total Payable Dues' => '₹' . number_format($sumTot, 2)];
                break;

            case 'gold_inventory':
            case 'silver_inventory':
                $type = ($key === 'gold_inventory') ? 'GOLD' : 'SILVER';
                $sql = "SELECT ci.*, l.loan_number, c.full_name as customer_name
                        FROM collateral_items ci JOIN loans l ON ci.loan_id = l.id JOIN customers c ON l.customer_id = c.id
                        WHERE ci.item_type = :t";
                $params = [':t' => $type];
                if ($search) { $sql .= " AND (ci.item_name LIKE :st OR ci.rk_number LIKE :st OR l.loan_number LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY ci.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Item Name', 'Loan Number', 'Customer Name', 'RK Storage Rack#', 'Quantity', 'Gross Wt (g)', 'Stone Wt (g)', 'Net Wt (g)', 'Purity Preset', 'Market Valuation (₹)'];
                $sumGross = 0; $sumNet = 0; $sumVal = 0;
                foreach ($res as $r) {
                    $val = floatval($r['manual_market_value_override'] ?: $r['market_value']);
                    $sumGross += floatval($r['gross_weight']);
                    $sumNet   += floatval($r['net_weight']);
                    $sumVal   += $val;
                    $rows[] = [
                        $r['item_name'], $r['loan_number'], $r['customer_name'], $r['rk_number'] ?: '—',
                        $r['quantity'], number_format($r['gross_weight'], 3) . ' g', number_format($r['stone_weight'], 3) . ' g',
                        number_format($r['net_weight'], 3) . ' g', $r['purity_preset'], '₹' . number_format($val, 2)
                    ];
                }
                $totals = ['Total Items' => count($res), 'Total Net Weight' => number_format($sumNet, 3) . ' g', 'Total Market Valuation' => '₹' . number_format($sumVal, 2)];
                break;

            case 'rack_allocation':
                $sql = "SELECT ci.*, l.loan_number, l.status as loan_status, l.delivered as loan_delivered, c.full_name as customer_name
                        FROM collateral_items ci JOIN loans l ON ci.loan_id = l.id JOIN customers c ON l.customer_id = c.id
                        WHERE ci.rk_number IS NOT NULL AND ci.rk_number != ''";
                $params = [];
                if ($search) { $sql .= " AND (ci.rk_number LIKE :st OR ci.item_name LIKE :st OR l.loan_number LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY ci.rk_number ASC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['RK Storage Rack Reference', 'Item Name', 'Item Type', 'Net Weight', 'Loan Number', 'Customer Name', 'Vault Status', 'Valuation (₹)'];
                foreach ($res as $r) {
                    $val = floatval($r['manual_market_value_override'] ?: $r['market_value']);
                    $isDelivered = (($r['loan_delivered'] ?? 'No') === 'Yes' || ($r['loan_status'] ?? '') === 'Closed');
                    $statusLabel = $isDelivered ? 'Delivered / Released' : 'In Vault (Active)';
                    $rows[] = [
                        $r['rk_number'], $r['item_name'], $r['item_type'], number_format($r['net_weight'], 3) . ' g',
                        $r['loan_number'], $r['customer_name'], $statusLabel, '₹' . number_format($val, 2)
                    ];
                }
                $totals = ['Total Rack Allocations' => count($res)];
                break;

            case 'customer_portfolio':
                $sql = "SELECT c.*, (SELECT COUNT(*) FROM loans l WHERE l.customer_id = c.id) as total_loans
                        FROM customers c WHERE 1=1";
                $params = [];
                if ($search) { $sql .= " AND (c.full_name LIKE :st OR c.mobile LIKE :st OR c.customer_id LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY c.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Customer ID', 'Account Number', 'Full Name', 'Mobile', 'Aadhaar', 'City', 'Status', 'Lifetime Loans'];
                foreach ($res as $r) {
                    $rows[] = [
                        $r['customer_id'], $r['account_number'] ?: '—', $r['full_name'], $r['mobile'],
                        $r['aadhaar'] ?: '—', $r['city'] ?: '—', $r['status'], $r['total_loans']
                    ];
                }
                $totals = ['Total Customers' => count($res)];
                break;

            case 'kyc_compliance':
                $sql = "SELECT c.*, 
                               (SELECT COUNT(*) FROM customer_documents cd WHERE cd.customer_id = c.id AND LOWER(cd.document_type) IN ('customer photo', 'photo')) as has_photo,
                               (SELECT COUNT(*) FROM customer_documents cd WHERE cd.customer_id = c.id AND LOWER(cd.document_type) LIKE '%aadhaar%') as has_aadhaar,
                               (SELECT COUNT(*) FROM customer_documents cd WHERE cd.customer_id = c.id AND LOWER(cd.document_type) LIKE '%pan%') as has_pan
                        FROM customers c WHERE 1=1";
                $params = [];
                if ($search) { $sql .= " AND (c.full_name LIKE :st OR c.customer_id LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY c.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Customer ID', 'Full Name', 'Mobile', 'Aadhaar Field', 'PAN Field', 'Photo Uploaded', 'Aadhaar Doc Uploaded', 'Compliance Status'];
                foreach ($res as $r) {
                    $comp = ($r['has_photo'] > 0 && ($r['has_aadhaar'] > 0 || !empty($r['aadhaar']))) ? 'Compliant' : 'Incomplete KYC';
                    $rows[] = [
                        $r['customer_id'], $r['full_name'], $r['mobile'], $r['aadhaar'] ?: '—', $r['pan'] ?: '—',
                        $r['has_photo'] > 0 ? 'YES' : 'NO', $r['has_aadhaar'] > 0 ? 'YES' : 'NO', $comp
                    ];
                }
                $totals = ['Total Customers Evaluated' => count($res)];
                break;

            case 'bullion_rates':
                $sql = "SELECT * FROM gold_rates ORDER BY rate_date DESC, id DESC LIMIT 50";
                $res = Database::fetchAll($sql);
                $columns = ['Effective Date', '24K Rate (₹/10g)', '22K Rate (₹/10g)', '18K Rate (₹/10g)', '14K Rate (₹/10g)', 'Remarks'];
                foreach ($res as $r) {
                    $rows[] = [
                        $r['rate_date'], '₹' . number_format($r['rate_24k'], 2), '₹' . number_format($r['rate_22k'], 2),
                        '₹' . number_format($r['rate_18k'], 2), '₹' . number_format($r['rate_14k'], 2), $r['remarks'] ?: '—'
                    ];
                }
                $totals = ['Rate Log Entries' => count($res)];
                break;

            case 'loan_settlement':
                $sql = "SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile
                        FROM loans l JOIN customers c ON l.customer_id = c.id WHERE l.status = 'Closed'";
                $params = [];
                if ($search) { $sql .= " AND (l.loan_number LIKE :st OR c.full_name LIKE :st)"; $params[':st'] = "%$search%"; }
                $sql .= " ORDER BY l.id DESC";

                $res = Database::fetchAll($sql, $params);
                $columns = ['Loan Number', 'Customer Name', 'Mobile', 'Loan Date', 'Security Type', 'Principal Amount (₹)', 'Status'];
                foreach ($res as $r) {
                    $rows[] = [
                        $r['loan_number'], $r['customer_name'], $r['customer_mobile'], $r['loan_date'],
                        $r['security_type'], '₹' . number_format($r['principal_amount'], 2), 'Closed & Settled'
                    ];
                }
                $totals = ['Closed Loans Count' => count($res)];
                break;

            case 'audit_trail':
                $sql = "SELECT a.*, adm.username FROM audit_logs a LEFT JOIN admin adm ON a.user_id = adm.id ORDER BY a.id DESC LIMIT 100";
                $res = Database::fetchAll($sql);
                $columns = ['Timestamp', 'User', 'Action', 'IP Address', 'Details'];
                foreach ($res as $r) {
                    $rows[] = [
                        $r['created_at'], $r['username'] ?: 'Admin', $r['action'], $r['ip_address'] ?: '127.0.0.1', $r['details'] ?: '—'
                    ];
                }
                $totals = ['Total Audit Records' => count($res)];
                break;

            case 'backup_history':
                $sql = "SELECT * FROM backups ORDER BY id DESC";
                $res = Database::fetchAll($sql);
                $columns = ['Filename', 'File Size (KB)', 'Backup Type', 'Status', 'Created At'];
                foreach ($res as $r) {
                    $rows[] = [
                        $r['filename'], number_format($r['file_size'] / 1024, 2) . ' KB', $r['backup_type'], $r['status'], $r['created_at']
                    ];
                }
                $totals = ['Total Backups Logged' => count($res)];
                break;

            case 'cashflow_monthly':
                $sql = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as mth, COUNT(*) as cnt, SUM(total_amount) as total_collected
                        FROM payments GROUP BY DATE_FORMAT(payment_date, '%Y-%m') ORDER BY mth DESC";
                $res = Database::fetchAll($sql);
                $columns = ['Year & Month', 'Receipts Count', 'Total Collection Amount (₹)'];
                $sumC = 0;
                foreach ($res as $r) {
                    $sumC += floatval($r['total_collected']);
                    $rows[] = [ $r['mth'], $r['cnt'], '₹' . number_format($r['total_collected'], 2) ];
                }
                $totals = ['Total Collections' => '₹' . number_format($sumC, 2)];
                break;

            case 'guarantor_register':
                $sql = "SELECT g.*, l.loan_number, c.full_name as customer_name
                        FROM loan_guarantors g JOIN loans l ON g.loan_id = l.id JOIN customers c ON l.customer_id = c.id";
                $res = Database::fetchAll($sql);
                $columns = ['Loan Number', 'Borrower Customer', 'Guarantor Name', 'Guarantor Mobile', 'Relationship', 'Guarantor Address'];
                foreach ($res as $r) {
                    $rows[] = [
                        $r['loan_number'], $r['customer_name'], $r['guarantor_name'], $r['guarantor_mobile'] ?: '—',
                        $r['relationship'] ?: '—', $r['guarantor_address'] ?: '—'
                    ];
                }
                $totals = ['Total Guarantors Registered' => count($res)];
                break;

            default:
                break;
        }

        return [
            'key'       => $key,
            'meta'      => $meta,
            'columns'   => $columns,
            'rows'      => $rows,
            'totals'    => $totals,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'search'    => $search
        ];
    }
}
