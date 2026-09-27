<?php
// Fallback to fetch collateral items if not already passed
if (!isset($collateralItems)) {
    $collateralItems = \App\Models\Loan::getCollateralItems(intval($payment['loan_id'] ?? 0));
}

// 1. Account No.
$accountNumber = !empty($payment['customer_account_number']) 
    ? $payment['customer_account_number'] . ' (' . $payment['loan_number'] . ')' 
    : ($payment['loan_number'] ?? 'N/A');

// 2. Rack No.
$rackNumbers = [];
foreach ($collateralItems as $ci) {
    $rk = trim((string)($ci['rk_number'] ?? ''));
    if ($rk !== '' && !in_array($rk, $rackNumbers, true)) {
        $rackNumbers[] = $rk;
    }
}
$rackDisplay = !empty($rackNumbers) ? implode(', ', $rackNumbers) : 'N/A';

// 3. Customer Name
$customerName = $payment['customer_name'] ?? 'N/A';

// 4. Date
$receiptDate = !empty($payment['payment_date']) ? date('d-m-Y', strtotime($payment['payment_date'])) : date('d-m-Y');

// 5. Loan Amount
$loanAmount = floatval($payment['principal_amount'] ?? 0);

// 6. Collateral Items with Weight
$totalGrossWeight = 0;
$totalNetWeight = 0;
foreach ($collateralItems as $item) {
    $totalGrossWeight += floatval($item['gross_weight'] ?? 0);
    $totalNetWeight += floatval($item['net_weight'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= htmlspecialchars($payment['receipt_number'] ?? $accountNumber) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            @page {
                size: auto;
                margin: 15mm;
            }
            body { 
                background: white !important; 
                color: #0f172a !important; 
                padding: 0 !important;
            }
            .no-print { 
                display: none !important; 
            }
            .print-card { 
                border: 1.5px solid #0f172a !important; 
                background: white !important; 
                color: #0f172a !important; 
                box-shadow: none !important;
                border-radius: 12px !important;
                padding: 24px !important;
                max-width: 100% !important;
            }
            .print-header {
                border-bottom: 2px solid #0f172a !important;
            }
            .print-section-border {
                border-color: #cbd5e1 !important;
            }
            .print-bg-light { 
                background: #f8fafc !important; 
            }
            .print-text-dark { 
                color: #0f172a !important; 
            }
            .print-text-muted {
                color: #475569 !important;
            }
            .print-border-dark {
                border-color: #0f172a !important;
            }
        }
    </style>
</head>
<body class="h-full font-sans antialiased flex flex-col items-center justify-center p-4 bg-slate-950">

    <!-- Top Action Bar (Hidden when printing) -->
    <div class="no-print w-full max-w-2xl flex items-center justify-between mb-4">
        <a href="<?= $baseUrl ?>/payments" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Payments
        </a>
        <div class="flex items-center gap-2">
            <a href="<?= $baseUrl ?>/loans/<?= htmlspecialchars($payment['loan_id'] ?? '') ?>" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-amber-400 hover:bg-slate-700 transition">
                View Loan Account
            </a>
            <button onclick="window.print()" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 shadow-lg hover:bg-amber-400 transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print Receipt
            </button>
        </div>
    </div>

    <!-- Printable Receipt Card -->
    <div class="print-card w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-2xl space-y-6">

        <!-- Receipt Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-5 print-header">
            <div>
                <h1 class="text-2xl font-black text-amber-400 tracking-wider uppercase print-text-dark">Payment Receipt</h1>
            </div>
            <div class="text-right">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider print-text-muted">Receipt Date</span>
                <span class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($receiptDate) ?></span>
            </div>
        </div>

        <!-- 5 Key Details Grid: Account No, Rack No, Customer Name, Date, Loan Amount -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
            
            <!-- Account No. -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Account No.</span>
                <p class="font-mono font-bold text-amber-400 text-sm print-text-dark break-all"><?= htmlspecialchars($accountNumber) ?></p>
            </div>

            <!-- Rack No. -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Rack No.</span>
                <p class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($rackDisplay) ?></p>
            </div>

            <!-- Customer Name -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5 space-y-1 print-bg-light print-section-border col-span-2 sm:col-span-1">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Customer Name</span>
                <p class="font-bold text-white text-sm print-text-dark truncate" title="<?= htmlspecialchars($customerName) ?>">
                    <?= htmlspecialchars($customerName) ?>
                </p>
            </div>

            <!-- Date -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Date</span>
                <p class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($receiptDate) ?></p>
            </div>

            <!-- Loan Amount -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5 space-y-1 print-bg-light print-section-border col-span-2 sm:col-span-2">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Loan Amount</span>
                <p class="font-mono font-extrabold text-emerald-400 text-base print-text-dark">₹<?= number_format($loanAmount, 2) ?></p>
            </div>

        </div>

        <!-- Collateral Items with Weight Section -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-300 print-text-dark">
                    Collateral Items with Weight
                </span>
                <span class="text-[10px] font-mono text-slate-400 print-text-muted">
                    Total Items: <?= count($collateralItems) ?>
                </span>
            </div>

            <div class="rounded-2xl border border-slate-800 overflow-hidden print-section-border">
                <?php if (empty($collateralItems)): ?>
                    <div class="p-4 text-center text-slate-500 text-xs print-text-muted">
                        No collateral items recorded for this loan account.
                    </div>
                <?php else: ?>
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] print-bg-light print-text-muted border-b border-slate-800 print-section-border">
                            <tr>
                                <th class="py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">Collateral Item</th>
                                <th class="py-2.5 px-3">Rack</th>
                                <th class="py-2.5 px-3 text-right">Gross Weight</th>
                                <th class="py-2.5 px-3 text-right">Net Weight</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300 print-section-border print-text-dark">
                            <?php foreach ($collateralItems as $idx => $ci): ?>
                                <tr class="hover:bg-slate-800/30 print-bg-light">
                                    <td class="py-2 px-3 font-mono text-slate-500 print-text-muted"><?= $idx + 1 ?></td>
                                    <td class="py-2 px-3 font-bold text-white print-text-dark">
                                        <?= htmlspecialchars($ci['item_name']) ?>
                                        <?php if (!empty($ci['quantity']) && intval($ci['quantity']) > 1): ?>
                                            <span class="text-[10px] text-slate-400 font-normal print-text-muted">(Qty: <?= intval($ci['quantity']) ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-3 font-mono text-slate-400 print-text-muted">
                                        <?= htmlspecialchars($ci['rk_number'] ?: '—') ?>
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono text-slate-300 print-text-dark">
                                        <?= number_format(floatval($ci['gross_weight'] ?? 0), 3) ?> g
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-amber-400 print-text-dark">
                                        <?= number_format(floatval($ci['net_weight'] ?? 0), 3) ?> g
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-950/80 font-bold border-t-2 border-slate-800 print-bg-light print-section-border print-text-dark">
                            <tr>
                                <td colspan="3" class="py-2.5 px-3 uppercase text-[10px] tracking-wider text-slate-400 print-text-muted">
                                    Total Weight
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-200 print-text-dark">
                                    <?= number_format($totalGrossWeight, 3) ?> g
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-extrabold text-amber-400 print-text-dark">
                                    <?= number_format($totalNetWeight, 3) ?> g
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

</body>
</html>
