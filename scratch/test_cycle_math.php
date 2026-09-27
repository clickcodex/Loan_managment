<?php

function getCycleCount($startDateStr, $targetDateStr, $isHalfMonthly) {
    $start = new DateTime($startDateStr);
    $target = new DateTime($targetDateStr);

    $diff = $target->diff($start);
    $fullYears = $diff->y;

    if ($isHalfMonthly) {
        $cyclesPerYear = 24;
        if ($diff->d > 15) {
            $partial = 2;
        } elseif ($diff->d > 0) {
            $partial = 1;
        } else {
            $partial = 0;
        }
        $currCycles = ($diff->m * 2) + $partial;
    } else {
        $cyclesPerYear = 12;
        $currCycles = $diff->m + ($diff->d > 0 ? 1 : 0);
    }

    if ($fullYears == 0 && $currCycles == 0) {
        $currCycles = 1; // 1st cycle begins on day 1
    }

    return [
        'full_years' => $fullYears,
        'current_year_cycles' => min($cyclesPerYear, $currCycles),
        'cycles_per_year' => $cyclesPerYear
    ];
}

$dates = [
    '2024-01-01' => 'Day 1',
    '2024-01-15' => 'Day 15',
    '2024-01-20' => 'Day 20',
    '2024-02-01' => '1 Month',
    '2024-06-01' => '5 Months',
    '2025-01-01' => '1 Full Year',
    '2025-01-10' => 'Year 2, 10 days',
    '2025-02-01' => 'Year 2, 1 Month',
    '2026-01-01' => '2 Full Years'
];

echo "=== MONTHLY CYCLE (30 Days) ===\n";
foreach ($dates as $d => $label) {
    $res = getCycleCount('2024-01-01', $d, false);
    echo "$label ($d): Full Years = {$res['full_years']}, Current Year Cycles = {$res['current_year_cycles']}\n";
}

echo "\n=== HALF-MONTHLY CYCLE (15 Days) ===\n";
foreach ($dates as $d => $label) {
    $res = getCycleCount('2024-01-01', $d, true);
    echo "$label ($d): Full Years = {$res['full_years']}, Current Year Cycles = {$res['current_year_cycles']}\n";
}
