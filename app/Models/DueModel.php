<?php

namespace App\Models;

require_once __DIR__ . '/RateModel.php';

use App\Config\Database;
use App\Models\RateModel;

class DueModel {

    public static function getDueSheet(string $filter = 'all', string $search = ''): array {
        $sql = "SELECT l.*, c.full_name as customer_name, c.mobile as customer_mobile, c.customer_id as cust_code, c.address as customer_address,
                       (SELECT file_path FROM customer_documents cd WHERE cd.customer_id = c.id AND LOWER(cd.document_type) IN ('customer photo', 'photo') ORDER BY id DESC LIMIT 1) as customer_photo,
                       (SELECT balance FROM loan_ledger ll WHERE ll.loan_id = l.id ORDER BY ll.id DESC LIMIT 1) as running_balance,
                       (SELECT MAX(payment_date) FROM payments p WHERE p.loan_id = l.id) as last_payment_date
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                WHERE l.status IN ('Active', 'Running')";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (l.loan_number LIKE :s1 OR c.full_name LIKE :s2 OR c.mobile LIKE :s3 OR c.customer_id LIKE :s4)";
            $term = '%' . trim($search) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
        }

        $sql .= " ORDER BY l.id DESC";

        $loans = Database::fetchAll($sql, $params);
        $dueList = [];

        $today      = new \DateTime();
        $latestGold = RateModel::getLatestGoldRate();
        $latestSilv = RateModel::getLatestSilverRate();

        foreach ($loans as $l) {
            $calculated = self::calculateLoanDues($l, $today, $latestGold, $latestSilv);

            // Apply Status Filter
            if ($filter !== 'all') {
                if ($filter === 'due_today' && $calculated['due_status_key'] !== 'due_today') continue;
                if ($filter === 'overdue_1_15' && $calculated['due_status_key'] !== 'overdue_1_15') continue;
                if ($filter === 'overdue_15_30' && $calculated['due_status_key'] !== 'overdue_15_30') continue;
                if ($filter === 'critical_npa' && $calculated['due_status_key'] !== 'critical_npa') continue;
                if ($filter === 'shortfall_alert' && !$calculated['has_collateral_shortfall']) continue;
            }

            $dueList[] = $calculated;
        }

        return $dueList;
    }

    public static function calculateLoanDues(array $l, ?\DateTime $today = null, ?array $goldRate = null, ?array $silverRate = null): array {
        if (!$today) $today = new \DateTime();
        if (!$goldRate) $goldRate = RateModel::getLatestGoldRate();
        if (!$silverRate) $silverRate = RateModel::getLatestSilverRate();

        $principal = floatval($l['principal_amount']);
        $monthlyRate = floatval($l['interest_rate']); // Monthly interest rate e.g. 2% or 10%
        $cycleStr = $l['interest_cycle'] ?? '15 Days';
        $interestMethod = $l['interest_method'] ?? 'Compound';
        $compoundFreq = $l['compound_frequency'] ?? 'Yearly';

        // Check if 15 days half-month cycle or 30 days full month cycle
        $isHalfMonthly = !str_contains(strtolower($cycleStr), '30') && strtolower($cycleStr) !== 'monthly';
        $ratePerCycle = $isHalfMonthly ? ($monthlyRate / 2.0) : $monthlyRate;
        $cycleDays = $isHalfMonthly ? 15 : 30;
        $cyclesPerYear = $isHalfMonthly ? 24 : 12;

        // Determine reference date (loan issue date)
        $loanDateStr = !empty($l['loan_date']) ? $l['loan_date'] : date('Y-m-d');
        $loanDate = new \DateTime($loanDateStr);

        $refDateStr = !empty($l['last_payment_date']) ? $l['last_payment_date'] : $loanDateStr;
        $refDate = new \DateTime($refDateStr);

        $daysElapsed = max(0, $today->diff($loanDate)->days);

        // Fetch payments made on this loan
        $paymentsRow = Database::fetchOne("SELECT COALESCE(SUM(total_amount), 0) as paid_sum FROM payments WHERE loan_id = :lid", [':lid' => $l['id']]);
        $totalPaid = floatval($paymentsRow['paid_sum'] ?? 0);

        $yearBreakdown = [];
        $compoundedPrincipal = $principal;

        if (strtolower($interestMethod) === 'simple') {
            // Simple Interest: calculated linearly across all cycles on original principal
            $cycles = ($daysElapsed <= 0) ? 1 : max(1, (int)ceil($daysElapsed / (float)$cycleDays));
            $accruedInterest = round($principal * ($ratePerCycle / 100.0) * $cycles, 2);
            $fullYears = 0;
            $currentYearCycles = $cycles;
            $currentYearInterest = $accruedInterest;
        } else {
            // Annual Compounding Method:
            // At each completed 1-year anniversary, accrued interest compounds into the principal base.
            // Subsequent years calculate interest on the updated compounded amount (e.g. ₹60,000 instead of ₹50,000).
            $diff = $today->diff($loanDate);
            $fullYears = $diff->y;

            $currentBase = $principal;
            for ($yr = 1; $yr <= $fullYears; $yr++) {
                $yearInt = round($currentBase * ($ratePerCycle / 100.0) * $cyclesPerYear, 2);
                $newBase = round($currentBase + $yearInt, 2);
                $yearBreakdown[] = [
                    'year'               => $yr,
                    'starting_principal' => $currentBase,
                    'cycles'             => $cyclesPerYear,
                    'interest'           => $yearInt,
                    'ending_balance'     => $newBase
                ];
                $currentBase = $newBase;
            }
            $compoundedPrincipal = $currentBase;

            // Current ongoing year cycles since the last completed anniversary
            $lastAnniversary = clone $loanDate;
            if ($fullYears > 0) {
                $lastAnniversary->modify("+{$fullYears} years");
            }
            $currentYearDiff = $today->diff($lastAnniversary);
            if ($isHalfMonthly) {
                $partial = ($currentYearDiff->d > 15) ? 2 : (($currentYearDiff->d > 0) ? 1 : 0);
                $currCycles = ($currentYearDiff->m * 2) + $partial;
            } else {
                $currCycles = $currentYearDiff->m + ($currentYearDiff->d > 0 ? 1 : 0);
            }

            if ($fullYears == 0 && $currCycles == 0) {
                $currCycles = 1; // 1st cycle begins on day 1
            }
            $currentYearCycles = min($cyclesPerYear, $currCycles);

            // In Year 2+, ongoing interest is calculated directly on the compounded base!
            $currentYearInterest = round($compoundedPrincipal * ($ratePerCycle / 100.0) * $currentYearCycles, 2);
            $accruedInterest = round(($compoundedPrincipal + $currentYearInterest) - $principal, 2);
            $cycles = ($fullYears * $cyclesPerYear) + $currentYearCycles;
        }

        $isClosed = (($l['status'] ?? '') === 'Closed');
        $totalPayable = $isClosed ? 0.00 : max(0, round(($principal + $accruedInterest) - $totalPaid, 2));

        // Fetch Collateral Items and Calculate Live Current Market Value
        $collateralItems = Database::fetchAll("SELECT * FROM collateral_items WHERE loan_id = :lid", [':lid' => $l['id']]);
        $currentCollateralValue = 0;

        $goldCustom   = !empty($goldRate['custom_rates']) ? json_decode($goldRate['custom_rates'], true) : [];
        $silverCustom = !empty($silverRate['custom_rates']) ? json_decode($silverRate['custom_rates'], true) : [];

        foreach ($collateralItems as $ci) {
            if (!empty($ci['manual_market_value_override']) && floatval($ci['manual_market_value_override']) > 0) {
                $currentCollateralValue += floatval($ci['manual_market_value_override']);
                continue;
            }

            $netWt = floatval($ci['net_weight']);
            $itemValue = 0;

            if ($ci['item_type'] === 'GOLD') {
                $base100 = floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2));
                $purityPct = floatval($ci['purity_percentage'] ?? 0);
                if ($purityPct <= 0) {
                    if ($ci['purity_preset'] === '24K') $purityPct = 100.00;
                    elseif ($ci['purity_preset'] === '22K') $purityPct = 91.67;
                    elseif ($ci['purity_preset'] === '18K') $purityPct = 75.00;
                    elseif ($ci['purity_preset'] === '14K') $purityPct = 58.33;
                    else $purityPct = 100.00;
                }
                $itemValue = $netWt * ($purityPct / 100.0) * ($base100 / 10.0);
            } else {
                $base100 = floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 90) / 0.999, 2));
                $purityPct = floatval($ci['purity_percentage'] ?? 0);
                if ($purityPct <= 0) {
                    if ($ci['purity_preset'] === '99.9%') $purityPct = 99.90;
                    elseif ($ci['purity_preset'] === '92.5%') $purityPct = 92.50;
                    elseif ($ci['purity_preset'] === '80.0%') $purityPct = 80.00;
                    else $purityPct = 100.00;
                }
                $itemValue = $netWt * ($purityPct / 100.0) * $base100;
            }

            $currentCollateralValue += $itemValue;
        }

        $currentCollateralValue = round($currentCollateralValue, 2);

        // Collateral Deficit / Shortfall Risk Check
        $hasShortfall = false;
        $shortfallAmount = 0;
        $liveLtvRatio = 0;

        if ($currentCollateralValue > 0) {
            $liveLtvRatio = round(($totalPayable / $currentCollateralValue) * 100, 2);
            if ($totalPayable > $currentCollateralValue) {
                $hasShortfall = true;
                $shortfallAmount = round($totalPayable - $currentCollateralValue, 2);
            }
        } elseif ($totalPayable > 0 && !empty($l['security_type']) && str_contains($l['security_type'], 'Guarantor')) {
            $liveLtvRatio = 100;
        }

        // Categorize Overdue Status
        $statusKey = 'current';
        $statusLabel = 'Regular Active';
        $badgeClass = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';

        if ($hasShortfall) {
            $statusKey = 'shortfall_alert';
            $statusLabel = '🚨 Collateral Deficit Risk (Payable > Collateral)';
            $badgeClass = 'bg-rose-500/20 text-rose-400 border-rose-500/30 animate-pulse';
        } elseif ($daysElapsed >= 30 && $daysElapsed < 35) {
            $statusKey = 'due_today';
            $statusLabel = 'Due Today';
            $badgeClass = 'bg-amber-500/20 text-amber-400 border-amber-500/30';
        } elseif ($daysElapsed >= 35 && $daysElapsed < 45) {
            $statusKey = 'overdue_1_15';
            $statusLabel = '1-15 Days Overdue (Grace)';
            $badgeClass = 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
        } elseif ($daysElapsed >= 45 && $daysElapsed < 60) {
            $statusKey = 'overdue_15_30';
            $statusLabel = '15-30 Days Overdue';
            $badgeClass = 'bg-orange-500/20 text-orange-400 border-orange-500/30';
        } elseif ($daysElapsed >= 60) {
            $statusKey = 'critical_npa';
            $statusLabel = '30+ Days Critical NPA Alert';
            $badgeClass = 'bg-rose-500/20 text-rose-400 border-rose-500/30';
        }

        // Safely resolve Customer Name & Mobile for notices and messaging
        $custName = $l['customer_name'] ?? $l['full_name'] ?? '';
        $custMobile = $l['customer_mobile'] ?? $l['mobile'] ?? '';

        if ((empty($custName) || empty($custMobile)) && !empty($l['customer_id'])) {
            $cust = Database::fetchOne("SELECT full_name, mobile FROM customers WHERE id = :id LIMIT 1", [':id' => $l['customer_id']]);
            if ($cust) {
                if (empty($custName)) $custName = $cust['full_name'] ?? '';
                if (empty($custMobile)) $custMobile = $cust['mobile'] ?? '';
            }
        }
        if (empty($custName)) $custName = 'Customer';

        // WhatsApp Notice Text Generator with Margin Call Warning
        $whatsappMsg = "URGENT NOTICE - Golden Trust Finance Co.\n" .
                       "Customer: " . $custName . " | Loan #" . ($l['loan_number'] ?? '') . "\n\n";

        if ($hasShortfall) {
            $whatsappMsg .= "⚠️ MARGIN CALL WARNING: Your total loan payable amount (₹" . number_format($totalPayable, 2) . ") has EXCEEDED the current market valuation of your pledged collateral (₹" . number_format($currentCollateralValue, 2) . ").\n" .
                            "Deficit Shortfall: ₹" . number_format($shortfallAmount, 2) . " (Current LTV: " . $liveLtvRatio . "%)\n" .
                            "Please deposit interest or additional collateral immediately to prevent legal/auction action.";
        } else {
            $whatsappMsg .= "This is a reminder for your interest dues:\n" .
                            "- Principal Balance: ₹" . number_format($principal, 2) . "\n" .
                            "- Accrued Interest: ₹" . number_format($accruedInterest, 2) . " (" . $daysElapsed . " days elapsed)\n" .
                            "- Total Payable: ₹" . number_format($totalPayable, 2) . "\n" .
                            "Kindly clear your dues at the earliest. Thank you!";
        }

        $collateralNames = [];
        $collateralSummary = [];
        foreach ($collateralItems as $ci) {
            $collateralNames[] = $ci['item_name'];
            $collateralSummary[] = $ci['item_name'] . ' (' . round(floatval($ci['net_weight']), 2) . 'g)';
        }
        $collateralNamesStr = !empty($collateralNames) ? implode(', ', array_unique($collateralNames)) : ($l['security_type'] ?? 'Secured Loan');
        $collateralSummaryStr = !empty($collateralSummary) ? implode(', ', $collateralSummary) : $collateralNamesStr;

        $cleanMobile = preg_replace('/[^0-9]/', '', $custMobile ?? '');
        if (strlen($cleanMobile) === 10) $cleanMobile = '91' . $cleanMobile;
        $whatsappLink = "https://wa.me/" . $cleanMobile . "?text=" . urlencode($whatsappMsg);

        return array_merge($l, [
            'customer_name'             => $custName,
            'customer_mobile'           => $custMobile,
            'collateral_names'          => $l['collateral_names'] ?? $collateralNamesStr,
            'collateral_items_summary'  => $l['collateral_items_summary'] ?? $collateralSummaryStr,
            'principal_balance'         => $principal,
            'interest_cycle'            => $cycleStr,
            'cycle_days'                => $cycleDays,
            'cycles_count'              => $cycles,
            'last_ref_date'             => $refDateStr,
            'days_elapsed'              => $daysElapsed,
            'accrued_interest'          => $accruedInterest,
            'interest_method'           => $interestMethod,
            'compounded_principal'      => $compoundedPrincipal,
            'full_years_completed'      => $fullYears,
            'current_year_cycles'       => $currentYearCycles,
            'current_year_interest'     => $currentYearInterest,
            'year_breakdown'            => $yearBreakdown,
            'total_paid'                => $totalPaid,
            'total_received'            => $totalPaid,
            'received_amount'           => $totalPaid,
            'total_payable'             => $totalPayable,
            'remaining_balance'         => $totalPayable,
            'running_balance'           => $totalPayable,
            'current_collateral_value'  => $currentCollateralValue,
            'has_collateral_shortfall'  => $hasShortfall,
            'shortfall_amount'          => $shortfallAmount,
            'live_ltv_ratio'            => $liveLtvRatio,
            'current_ltv'               => $liveLtvRatio,
            'due_status_key'            => $statusKey,
            'due_status_label'          => $statusLabel,
            'badge_class'               => $badgeClass,
            'whatsapp_msg'              => $whatsappMsg,
            'whatsapp_link'             => $whatsappLink
        ]);
    }

    public static function getDueKpis(): array {
        $all = self::getDueSheet('all');

        $totalPayableSum = 0;
        $accruedInterestSum = 0;
        $overdueCount = 0;
        $dueTodayCount = 0;
        $npaCount = 0;
        $shortfallCount = 0;
        $shortfallTotalSum = 0;

        foreach ($all as $item) {
            $totalPayableSum += $item['total_payable'];
            $accruedInterestSum += $item['accrued_interest'];

            if ($item['has_collateral_shortfall']) {
                $shortfallCount++;
                $shortfallTotalSum += $item['shortfall_amount'];
            }

            if (in_array($item['due_status_key'], ['overdue_1_15', 'overdue_15_30', 'critical_npa', 'shortfall_alert'])) {
                $overdueCount++;
            }
            if ($item['due_status_key'] === 'due_today') {
                $dueTodayCount++;
            }
            if ($item['due_status_key'] === 'critical_npa') {
                $npaCount++;
            }
        }

        return [
            'total_active_loans'    => count($all),
            'total_payable_sum'     => $totalPayableSum,
            'accrued_interest_sum'  => $accruedInterestSum,
            'overdue_count'         => $overdueCount,
            'due_today_count'       => $dueTodayCount,
            'npa_count'             => $npaCount,
            'shortfall_count'       => $shortfallCount,
            'shortfall_total_sum'   => $shortfallTotalSum
        ];
    }
}
