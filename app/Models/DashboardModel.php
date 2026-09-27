<?php

namespace App\Models;

use App\Config\Database;

class DashboardModel {

    public static function getKpiStats(): array {
        // Total Customers & Active Customers
        $customerStats = Database::fetchOne("
            SELECT 
                COUNT(*) as total_customers,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_customers
            FROM customers
        ");

        // Loan Counts & Totals
        $loanStats = Database::fetchOne("
            SELECT 
                COUNT(*) as total_loans,
                SUM(CASE WHEN status IN ('Active', 'Running') THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_loans,
                SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_loans,
                SUM(CASE WHEN loan_date = CURDATE() THEN 1 ELSE 0 END) as todays_new_loans,
                SUM(CASE WHEN security_type LIKE '%Gold%' AND status IN ('Active', 'Running') THEN 1 ELSE 0 END) as gold_loan_count,
                SUM(CASE WHEN security_type LIKE '%Silver%' AND status IN ('Active', 'Running') THEN 1 ELSE 0 END) as silver_loan_count,
                SUM(CASE WHEN security_type = 'Guarantor Secured' AND status IN ('Active', 'Running') THEN 1 ELSE 0 END) as cash_loan_count,
                SUM(CASE WHEN status IN ('Active', 'Running') THEN principal_amount ELSE 0 END) as total_running_principal,
                COALESCE(SUM(principal_amount), 0) as total_disbursed
            FROM loans
        ");

        // Today's Collection Sum
        $todayCollection = Database::fetchOne("
            SELECT COALESCE(SUM(total_amount), 0) as todays_collection 
            FROM payments 
            WHERE payment_date = CURDATE()
        ");

        // Total Collections Lifetime & Total Interest Collected
        $collectionStats = Database::fetchOne("
            SELECT 
                COALESCE(SUM(total_amount), 0) as total_collections,
                COALESCE(SUM(interest_component), 0) as total_interest_collected
            FROM payments
        ");

        // Total Principal Paid across active loans
        $principalPaid = Database::fetchOne("
            SELECT COALESCE(SUM(principal_component), 0) as total_principal_paid 
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            WHERE l.status IN ('Active', 'Running')
        ");

        // Today's Dues Count
        $todaysDue = Database::fetchOne("
            SELECT COUNT(*) as todays_due_count 
            FROM loans 
            WHERE status IN ('Active', 'Running') AND (interest_due_date = CURDATE() OR return_date = CURDATE())
        ");

        // Collateral Items Count & Weights
        $collateralStats = Database::fetchOne("
            SELECT 
                COUNT(*) as total_items,
                COALESCE(SUM(CASE WHEN ci.item_type = 'GOLD' THEN ci.net_weight ELSE 0 END), 0) as gold_weight,
                COALESCE(SUM(CASE WHEN ci.item_type = 'SILVER' THEN ci.net_weight ELSE 0 END), 0) as silver_weight
            FROM collateral_items ci
            JOIN loans l ON ci.loan_id = l.id
            WHERE l.status IN ('Active', 'Running')
        ");

        // Collateral Portfolio Valuation (Gold & Silver)
        $goldValue = Database::fetchOne("
            SELECT COALESCE(SUM(COALESCE(manual_market_value_override, market_value)), 0) as gold_market_val
            FROM collateral_items ci
            JOIN loans l ON ci.loan_id = l.id
            WHERE ci.item_type = 'GOLD' AND l.status IN ('Active', 'Running')
        ");

        $silverValue = Database::fetchOne("
            SELECT COALESCE(SUM(COALESCE(manual_market_value_override, market_value)), 0) as silver_market_val
            FROM collateral_items ci
            JOIN loans l ON ci.loan_id = l.id
            WHERE ci.item_type = 'SILVER' AND l.status IN ('Active', 'Running')
        ");

        // Calculation of Outstanding Principal
        $runningPrincipal = floatval($loanStats['total_running_principal'] ?? 0);
        $paidPrincipal = floatval($principalPaid['total_principal_paid'] ?? 0);
        $outstandingPrincipal = max(0, $runningPrincipal - $paidPrincipal);

        // Calculated accrued interest balance across active loans
        $interestAccrued = Database::fetchOne("
            SELECT COALESCE(SUM(accrued_interest), 0) as total_accrued 
            FROM interest_history ih
            JOIN loans l ON ih.loan_id = l.id
            WHERE l.status IN ('Active', 'Running')
        ");
        $interestPaid = Database::fetchOne("
            SELECT COALESCE(SUM(interest_component), 0) as total_interest_paid 
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            WHERE l.status IN ('Active', 'Running')
        ");

        $accrued = floatval($interestAccrued['total_accrued'] ?? 0);
        $paidInt = floatval($interestPaid['total_interest_paid'] ?? 0);
        $outstandingInterest = max(0, $accrued - $paidInt);

        $goldVal   = floatval($goldValue['gold_market_val'] ?? 0);
        $silverVal = floatval($silverValue['silver_market_val'] ?? 0);
        $totalValuation = $goldVal + $silverVal;
        $avgLtv = $totalValuation > 0 ? ($outstandingPrincipal / $totalValuation) * 100 : 0;
        $activeLoans = intval($loanStats['active_loans'] ?? 0);

        return [
            'total_customers'          => intval($customerStats['total_customers'] ?? 0),
            'active_customers'         => intval($customerStats['active_customers'] ?? 0),
            'total_loans'              => intval($loanStats['total_loans'] ?? 0),
            'active_loans'             => $activeLoans,
            'running_loans'            => $activeLoans,
            'closed_loans'             => intval($loanStats['closed_loans'] ?? 0),
            'overdue_loans'            => intval($loanStats['overdue_loans'] ?? 0),
            'todays_new_loans'         => intval($loanStats['todays_new_loans'] ?? 0),
            'todays_collection'        => floatval($todayCollection['todays_collection'] ?? 0),
            'today_collection'         => floatval($todayCollection['todays_collection'] ?? 0),
            'total_disbursed'          => floatval($loanStats['total_disbursed'] ?? 0),
            'active_principal_balance' => $outstandingPrincipal,
            'outstanding_principal'    => $outstandingPrincipal,
            'outstanding_interest'     => $outstandingInterest,
            'todays_due'               => intval($todaysDue['todays_due_count'] ?? 0),
            'gold_loan_count'          => intval($loanStats['gold_loan_count'] ?? 0),
            'silver_loan_count'        => intval($loanStats['silver_loan_count'] ?? 0),
            'cash_loan_count'          => intval($loanStats['cash_loan_count'] ?? 0),
            'gold_market_value'        => $goldVal,
            'silver_market_value'      => $silverVal,
            'total_collateral_items'   => intval($collateralStats['total_items'] ?? 0),
            'total_gold_weight'        => floatval($collateralStats['gold_weight'] ?? 0),
            'total_silver_weight'      => floatval($collateralStats['silver_weight'] ?? 0),
            'total_market_valuation'   => $totalValuation,
            'average_ltv'              => $avgLtv,
            'total_interest_collected' => floatval($collectionStats['total_interest_collected'] ?? 0),
            'total_collections'        => floatval($collectionStats['total_collections'] ?? 0)
        ];
    }

    public static function getWidgetsData(): array {
        // 1. Today's collections live list
        $todaysCollections = Database::fetchAll("
            SELECT p.*, l.loan_number, c.full_name as customer_name
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            WHERE p.payment_date = CURDATE()
            ORDER BY p.id DESC
            LIMIT 10
        ");

        // 2. Recent payments (latest 10)
        $recentPayments = Database::fetchAll("
            SELECT p.*, l.loan_number, c.full_name as customer_name
            FROM payments p
            JOIN loans l ON p.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            ORDER BY p.id DESC
            LIMIT 10
        ");

        // 3. Recent loans
        $recentLoans = Database::fetchAll("
            SELECT l.*, c.full_name as customer_name, c.mobile
            FROM loans l
            JOIN customers c ON l.customer_id = c.id
            ORDER BY l.id DESC
            LIMIT 8
        ");

        // 4. Upcoming dues (next 7 days)
        $upcomingDues = Database::fetchAll("
            SELECT l.*, c.full_name as customer_name, c.mobile
            FROM loans l
            JOIN customers c ON l.customer_id = c.id
            WHERE l.status IN ('Active', 'Running') 
              AND l.interest_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY l.interest_due_date ASC
            LIMIT 8
        ");

        // 5. Current Gold Rate
        $goldRate = Database::fetchOne("
            SELECT * FROM gold_rates ORDER BY rate_date DESC, id DESC LIMIT 1
        ");

        // 6. Current Silver Rate
        $silverRate = Database::fetchOne("
            SELECT * FROM silver_rates ORDER BY rate_date DESC, id DESC LIMIT 1
        ");

        return [
            'todaysCollections' => $todaysCollections,
            'recentPayments'    => $recentPayments,
            'recentLoans'       => $recentLoans,
            'upcomingDues'      => $upcomingDues,
            'goldRate'          => $goldRate,
            'silverRate'        => $silverRate
        ];
    }
}
