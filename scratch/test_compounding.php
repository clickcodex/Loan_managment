<?php

function testCompounding($loanDateStr, $todayStr, $principal, $monthlyRate, $cycleType, $interestMethod = 'Compound') {
    $loanDate = new DateTime($loanDateStr);
    $today = new DateTime($todayStr);

    $isHalfMonthly = !str_contains(strtolower($cycleType), '30') && strtolower($cycleType) !== 'monthly';
    $ratePerCycle = $isHalfMonthly ? ($monthlyRate / 2.0) : $monthlyRate;
    $cyclesPerYear = $isHalfMonthly ? 24 : 12;

    $daysElapsed = max(0, $today->diff($loanDate)->days);

    if ($interestMethod === 'Simple') {
        $cycleDays = $isHalfMonthly ? 15 : 30;
        $totalCycles = ($daysElapsed <= 0) ? 1 : max(1, (int)ceil($daysElapsed / (float)$cycleDays));
        $accruedInterest = round($principal * ($ratePerCycle / 100.0) * $totalCycles, 2);
        return [
            'method' => 'Simple',
            'principal' => $principal,
            'accrued_interest' => $accruedInterest,
            'total_payable' => $principal + $accruedInterest,
            'compounded_base' => $principal,
            'full_years' => 0,
            'current_year_cycles' => $totalCycles
        ];
    }

    // Compounding Method (Yearly compounding)
    // Determine full completed years
    $diff = $today->diff($loanDate);
    $fullYears = $diff->y;

    // Start with base principal
    $compoundedBase = $principal;
    $yearBreakdown = [];

    // Compound for each completed full year
    for ($yr = 1; $yr <= $fullYears; $yr++) {
        $yearInt = round($compoundedBase * ($ratePerCycle / 100.0) * $cyclesPerYear, 2);
        $newBase = round($compoundedBase + $yearInt, 2);
        $yearBreakdown[] = [
            'year' => $yr,
            'starting_principal' => $compoundedBase,
            'cycles' => $cyclesPerYear,
            'interest' => $yearInt,
            'ending_balance' => $newBase
        ];
        $compoundedBase = $newBase;
    }

    // For current ongoing year: calculate remaining cycles since last anniversary
    $lastAnniversary = clone $loanDate;
    if ($fullYears > 0) {
        $lastAnniversary->modify("+{$fullYears} years");
    }
    $daysInCurrentYear = max(0, $today->diff($lastAnniversary)->days);
    $cycleDays = $isHalfMonthly ? 15 : 30;

    if ($fullYears == 0) {
        $currentYearCycles = ($daysInCurrentYear <= 0) ? 1 : max(1, (int)ceil($daysInCurrentYear / (float)$cycleDays));
    } else {
        // In subsequent years, if exactly on anniversary (0 days), currentYearCycles = 0 (year just ended and compounded)
        $currentYearCycles = ($daysInCurrentYear <= 0) ? 0 : (int)ceil($daysInCurrentYear / (float)$cycleDays);
    }
    // Cap current year cycles to max in year
    $currentYearCycles = min($cyclesPerYear, $currentYearCycles);

    $currentYearInterest = round($compoundedBase * ($ratePerCycle / 100.0) * $currentYearCycles, 2);
    $totalAccruedInterest = round(($compoundedBase + $currentYearInterest) - $principal, 2);
    $totalPayable = round($compoundedBase + $currentYearInterest, 2);

    return [
        'method' => 'Compound',
        'original_principal' => $principal,
        'full_years' => $fullYears,
        'current_base' => $compoundedBase,
        'current_year_cycles' => $currentYearCycles,
        'current_year_interest' => $currentYearInterest,
        'total_accrued_interest' => $totalAccruedInterest,
        'total_payable' => $totalPayable,
        'year_breakdown' => $yearBreakdown
    ];
}

// Let's test user's exact scenario:
// Loan: 50,000 on 2024-01-01 at 2% monthly rate
echo "=== TEST 1: Exactly 1 Year Later (2025-01-01) ===\n";
print_r(testCompounding('2024-01-01', '2025-01-01', 50000, 2.0, '30 Days (Monthly)'));

echo "\n=== TEST 2: 1 Month into Year 2 (2025-02-01) ===\n";
print_r(testCompounding('2024-01-01', '2025-02-01', 50000, 2.0, '30 Days (Monthly)'));

echo "\n=== TEST 3: Exactly 2 Years Later (2026-01-01) ===\n";
print_r(testCompounding('2024-01-01', '2026-01-01', 50000, 2.0, '30 Days (Monthly)'));
