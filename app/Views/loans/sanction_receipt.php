<?php
$loanId = $loan['id'];
$loanNumber = $loan['loan_number'];
$sanctionDate = !empty($loan['loan_date']) ? date('d-m-Y', strtotime($loan['loan_date'])) : date('d-m-Y');
$sanctionReceiptNo = 'SAN-' . date('Y', strtotime($loan['loan_date'] ?? 'now')) . '-' . sprintf('%04d', $loan['id']);

$customerName = $loan['customer_name'] ?? 'N/A';
$accountNumber = !empty($loan['customer_account_number']) 
    ? $loan['customer_account_number'] . ' (' . $loanNumber . ')' 
    : $loanNumber;

// Rack numbers list
$rackNumbers = [];
foreach ($collateralItems as $ci) {
    $rk = trim((string)($ci['rk_number'] ?? ''));
    if ($rk !== '' && !in_array($rk, $rackNumbers, true)) {
        $rackNumbers[] = $rk;
    }
}
$rackDisplay = !empty($rackNumbers) ? implode(', ', $rackNumbers) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Sanction Receipt - <?= htmlspecialchars($loanNumber) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 12mm 15mm;
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
<body class="h-full font-sans antialiased flex flex-col items-center justify-start p-4 sm:p-6 bg-slate-950 text-slate-100">

    <!-- Top Action Bar (Hidden when printing) -->
    <div class="no-print w-full max-w-3xl flex items-center justify-between mb-4">
        <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Loan Account
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 shadow-lg hover:bg-amber-400 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-print"></i> Print Sanction Receipt
            </button>
        </div>
    </div>

    <!-- Printable Sanction Receipt Card -->
    <div class="print-card w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-2xl space-y-6">

        <!-- Receipt Header -->
        <div class="flex items-start justify-between border-b border-slate-800 pb-5 print-header">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight print-text-dark">Golden Trust Finance Co.</h1>
                <p class="text-xs text-amber-400 font-bold uppercase tracking-wider mt-0.5 print-text-dark">
                    Loan Sanction & Disbursal Voucher / Collateral Pledge Note
                </p>
                <p class="text-[11px] text-slate-400 mt-1 print-text-muted">
                    Regd. Office: Main Market, Gold & Bullion Center &bull; Phone: +91 98765 43210
                </p>
            </div>
            <div class="text-right">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider print-text-muted">Sanction Voucher #</span>
                <span class="font-mono font-extrabold text-amber-400 text-sm print-text-dark"><?= htmlspecialchars($sanctionReceiptNo) ?></span>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2 print-text-muted">Sanction Date</span>
                <span class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($sanctionDate) ?></span>
            </div>
        </div>

        <!-- Loan & Customer Key Information Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            
            <!-- Loan Account # -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Loan Number</span>
                <p class="font-mono font-bold text-amber-400 text-sm print-text-dark"><?= htmlspecialchars($loanNumber) ?></p>
                <span class="text-[10px] text-slate-500 font-mono">Sec: <?= htmlspecialchars($loan['security_type']) ?></span>
            </div>

            <!-- Customer Account # -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Account Number</span>
                <p class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($loan['customer_account_number'] ?? 'N/A') ?></p>
                <span class="text-[10px] text-slate-500 font-mono">ID: <?= htmlspecialchars($loan['cust_code'] ?? 'CUST-' . $loan['customer_id']) ?></span>
            </div>

            <!-- Sanctioned Principal Amount -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Principal Amount</span>
                <p class="font-mono font-black text-emerald-400 text-base print-text-dark">
                    ₹<?= number_format(floatval($loan['principal_amount']), 2) ?>
                </p>
                <span class="text-[10px] text-slate-500">Disbursed via Cash/Transfer</span>
            </div>

            <!-- Interest Rate & Terms -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3 space-y-1 print-bg-light print-section-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Interest Rate</span>
                <p class="font-mono font-black text-amber-400 text-base print-text-dark">
                    <?= number_format(floatval($loan['interest_rate']), 2) ?>% <span class="text-[11px] font-bold">p.m.</span>
                </p>
                <span class="text-[10px] text-slate-500"><?= htmlspecialchars($loan['interest_method'] ?? 'Monthly Cycle') ?></span>
            </div>

        </div>

        <!-- Customer Identity Details Box -->
        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 print-bg-light print-section-border text-xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2 print-section-border">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-300 print-text-dark">
                    Borrower / Customer Profile
                </span>
                <span class="text-[11px] font-mono text-slate-400 print-text-muted">
                    Vault Rack Location: <strong class="text-amber-400 print-text-dark"><?= htmlspecialchars($rackDisplay) ?></strong>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 print-text-muted uppercase">Customer Full Name</span>
                    <p class="font-bold text-white print-text-dark"><?= htmlspecialchars($customerName) ?></p>
                    <?php if (!empty($loan['father_name'])): ?>
                        <p class="text-[11px] text-slate-400 print-text-muted">S/o, D/o, W/o: <?= htmlspecialchars($loan['father_name']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="block text-[10px] font-bold text-slate-400 print-text-muted uppercase">Contact Phone</span>
                    <p class="font-mono font-bold text-white print-text-dark"><?= htmlspecialchars($loan['customer_mobile'] ?? 'N/A') ?></p>
                    <?php if (!empty($loan['alt_mobile'])): ?>
                        <p class="text-[11px] font-mono text-slate-400 print-text-muted">Alt: <?= htmlspecialchars($loan['alt_mobile']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="block text-[10px] font-bold text-slate-400 print-text-muted uppercase">Address & KYC</span>
                    <p class="text-[11px] text-slate-300 print-text-dark truncate" title="<?= htmlspecialchars($loan['customer_address'] ?? '') ?>">
                        <?= htmlspecialchars($loan['customer_address'] ?: 'Address on file') ?>
                    </p>
                    <p class="text-[10px] font-mono text-slate-400 print-text-muted">
                        <?= !empty($loan['aadhaar']) ? 'Aadhaar: ' . htmlspecialchars($loan['aadhaar']) : '' ?>
                        <?= !empty($loan['pan']) ? ' &bull; PAN: ' . htmlspecialchars($loan['pan']) : '' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Pledged Collateral Table -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-300 print-text-dark">
                    Pledged Collateral Ornaments & Security Details
                </span>
                <span class="text-[10px] font-mono text-slate-400 print-text-muted">
                    Items: <?= count($collateralItems) ?>
                </span>
            </div>

            <div class="rounded-2xl border border-slate-800 overflow-hidden print-section-border">
                <?php if (empty($collateralItems)): ?>
                    <div class="p-4 text-center text-slate-500 text-xs print-text-muted">
                        No physical collateral items recorded for this loan account.
                    </div>
                <?php else: ?>
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] print-bg-light print-text-muted border-b border-slate-800 print-section-border">
                            <tr>
                                <th class="py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">Item Description</th>
                                <th class="py-2.5 px-3">Purity (%)</th>
                                <th class="py-2.5 px-3">Rack & Slot</th>
                                <th class="py-2.5 px-3 text-right">Gross Wt</th>
                                <th class="py-2.5 px-3 text-right">Net Wt</th>
                                <th class="py-2.5 px-3 text-right">Valuation (₹)</th>
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
                                        <?php
                                            $purityPct = floatval($ci['purity_percentage'] ?? 0);
                                            echo $purityPct > 0 ? number_format($purityPct, 2) . '%' : htmlspecialchars($ci['purity_preset'] ?: '—');
                                        ?>
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
                                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-200 print-text-dark">
                                        ₹<?= number_format(floatval($ci['market_value'] ?? 0), 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-950/80 font-bold border-t-2 border-slate-800 print-bg-light print-section-border print-text-dark">
                            <tr>
                                <td colspan="4" class="py-2.5 px-3 uppercase text-[10px] tracking-wider text-slate-400 print-text-muted">
                                    Totals & LTV: <span class="font-mono text-amber-400 print-text-dark"><?= number_format($ltv, 1) ?>% LTV</span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-200 print-text-dark">
                                    <?= number_format($totalGross, 3) ?> g
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-extrabold text-amber-400 print-text-dark">
                                    <?= number_format($totalNet, 3) ?> g
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-400 print-text-dark">
                                    ₹<?= number_format($totalValuation, 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Guarantor Details (if any) -->
        <?php if (!empty($guarantor)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/40 p-3 print-bg-light print-section-border text-xs">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Guarantor Verification</span>
                <p class="text-white font-bold print-text-dark mt-0.5">
                    <?= htmlspecialchars($guarantor['guarantor_name'] ?? 'N/A') ?> 
                    <span class="font-mono font-normal text-slate-400 print-text-muted">| Phone: <?= htmlspecialchars($guarantor['guarantor_mobile'] ?? 'N/A') ?></span>
                    <?php if (!empty($guarantor['relationship'])): ?>
                        <span class="text-slate-400 print-text-muted">| Rel: <?= htmlspecialchars($guarantor['relationship']) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Terms & Conditions Acknowledgement -->
        <div class="border-t border-slate-800 pt-4 print-section-border text-[10px] text-slate-400 print-text-muted space-y-1">
            <p><strong>Declaration:</strong> The borrower hereby acknowledges receipt of the net principal amount stated above against pledge of the listed gold/silver ornaments in good condition. The borrower agrees to the specified monthly interest rate, repayment cycle, and terms of loan custody.</p>
        </div>

        <!-- Signatures Row -->
        <div class="pt-8 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-slate-700 print-border-dark w-48 mx-auto mb-1"></div>
                <p class="font-bold text-white print-text-dark">Borrower's Signature</p>
                <p class="text-[10px] text-slate-500 print-text-muted"><?= htmlspecialchars($customerName) ?></p>
            </div>
            <div>
                <div class="border-b border-slate-700 print-border-dark w-48 mx-auto mb-1"></div>
                <p class="font-bold text-white print-text-dark">Authorized Signatory</p>
                <p class="text-[10px] text-slate-500 print-text-muted">Golden Trust Finance Co.</p>
            </div>
        </div>

    </div>

</body>
</html>
