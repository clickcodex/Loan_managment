<?php
$loanId = $loan['id'];
$loanNumber = $loan['loan_number'];
$closureDate = !empty($loan['delivery_date']) ? date('d-m-Y', strtotime($loan['delivery_date'])) : date('d-m-Y');
$sanctionDate = !empty($loan['loan_date']) ? date('d-m-Y', strtotime($loan['loan_date'])) : date('d-m-Y');
$closureReceiptNo = 'CLS-' . date('Y', strtotime($loan['delivery_date'] ?? 'now')) . '-' . sprintf('%04d', $loan['id']);

$customerName = $loan['customer_name'] ?? 'N/A';
$accountNumber = !empty($loan['customer_account_number']) 
    ? $loan['customer_account_number'] . ' (' . $loanNumber . ')' 
    : $loanNumber;

// Payment aggregation
$payments = $payments ?? [];
$totalPaid = array_sum(array_column($payments, 'total_amount'));
$totalDiscount = array_sum(array_column($payments, 'discount'));

// Final payment mode & reference
$lastPayment = !empty($payments) ? end($payments) : null;
$finalPaymentMode = $lastPayment['payment_mode'] ?? 'Cash';
$finalPaymentDate = !empty($lastPayment['payment_date']) ? date('d-m-Y', strtotime($lastPayment['payment_date'])) : $closureDate;

// Delivery / Haste
$deliveredTo = $loan['haste'] ?? 'Customer Self';
$deliveryRemarks = $loan['delivery_remarks'] ?? 'Full settlement completed & pledged collateral released';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Closure Receipt & Certificate - <?= htmlspecialchars($loanNumber) ?></title>
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
            .print-closure-card { 
                border: 2px solid #0f172a !important; 
                background: white !important; 
                color: #0f172a !important; 
                box-shadow: none !important;
                border-radius: 12px !important;
                padding: 24px !important;
                max-width: 100% !important;
            }
            .print-border-b {
                border-bottom: 2px solid #0f172a !important;
            }
            .print-border {
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
            .print-badge-closed {
                background: #0f172a !important;
                color: white !important;
                border: 1px solid #0f172a !important;
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
                <i class="fa-solid fa-print"></i> Print Closure Receipt
            </button>
        </div>
    </div>

    <!-- Printable Closure Certificate Card -->
    <div class="print-closure-card w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-2xl space-y-6">

        <!-- Receipt Header -->
        <div class="flex items-start justify-between border-b border-slate-800 pb-5 print-border-b">
            <div>
                <h1 class="text-2xl font-black text-emerald-400 tracking-wider uppercase print-text-dark">Loan Closure Receipt</h1>
            </div>
            <div class="text-right">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider print-text-muted">Closure Voucher #</span>
                <span class="font-mono font-extrabold text-emerald-400 text-sm print-text-dark"><?= htmlspecialchars($closureReceiptNo) ?></span>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2 print-text-muted">Closure Date</span>
                <span class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($closureDate) ?></span>
            </div>
        </div>

        <!-- Status & Loan Highlights Bar -->
        <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4 print-bg-light print-border flex flex-wrap items-center justify-between gap-4 text-xs">
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Loan Account</span>
                <p class="font-mono font-bold text-amber-400 text-base print-text-dark"><?= htmlspecialchars($loanNumber) ?></p>
                <span class="text-[10px] text-slate-400 font-mono print-text-muted">Cust Acc: <?= htmlspecialchars($loan['customer_account_number'] ?? 'N/A') ?></span>
            </div>

            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Original Sanction Date</span>
                <p class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($sanctionDate) ?></p>
                <span class="text-[10px] text-slate-400 print-text-muted">Security: <?= htmlspecialchars($loan['security_type']) ?></span>
            </div>

            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Sanctioned Principal</span>
                <p class="font-mono font-black text-white text-base print-text-dark">₹<?= number_format(floatval($loan['principal_amount']), 2) ?></p>
                <span class="text-[10px] text-slate-400 print-text-muted">Rate: <?= floatval($loan['interest_rate']) ?>% p.m.</span>
            </div>

            <div class="text-right">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Account Status</span>
                <span class="inline-block rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-1 font-bold text-xs uppercase tracking-wider print-badge-closed">
                    FULLY CLOSED
                </span>
                <p class="text-[10px] font-mono text-emerald-400 font-bold mt-1 print-text-dark">Balance: ₹0.00</p>
            </div>
        </div>

        <!-- Customer Identity Details Box -->
        <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4 print-bg-light print-border text-xs space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-300 print-text-dark block border-b border-slate-800 pb-2 print-border">
                Borrower / Customer Profile
            </span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 print-text-muted uppercase">Customer Name</span>
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
                    <p class="text-[11px] text-slate-300 print-text-dark"><?= htmlspecialchars($loan['customer_address'] ?: 'On record') ?></p>
                    <p class="text-[10px] font-mono text-slate-400 print-text-muted">
                        <?= !empty($loan['aadhaar']) ? 'Aadhaar: ' . htmlspecialchars($loan['aadhaar']) : '' ?>
                        <?= !empty($loan['pan']) ? ' | PAN: ' . htmlspecialchars($loan['pan']) : '' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Settlement & Repayment Financial Breakdown -->
        <div class="space-y-2">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-300 print-text-dark block">
                Settlement & Payment Summary
            </span>

            <div class="rounded-2xl border border-slate-800 overflow-hidden print-border text-xs">
                <table class="w-full text-left">
                    <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] print-bg-light print-text-muted border-b border-slate-800 print-border">
                        <tr>
                            <th class="py-2.5 px-3">Principal Cleared</th>
                            <th class="py-2.5 px-3">Total Amount Paid</th>
                            <th class="py-2.5 px-3">Discount / Waiver</th>
                            <th class="py-2.5 px-3">Final Mode</th>
                            <th class="py-2.5 px-3 text-right">Outstanding Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300 print-border print-text-dark">
                        <tr class="print-bg-light">
                            <td class="py-3 px-3 font-mono font-bold text-white print-text-dark">
                                ₹<?= number_format(floatval($loan['principal_amount']), 2) ?>
                            </td>
                            <td class="py-3 px-3 font-mono font-black text-emerald-400 print-text-dark">
                                ₹<?= number_format($totalPaid, 2) ?>
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-amber-400 print-text-dark">
                                ₹<?= number_format($totalDiscount, 2) ?>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-white print-text-dark"><?= htmlspecialchars($finalPaymentMode) ?></span>
                                <span class="block text-[10px] font-mono text-slate-400 print-text-muted"><?= htmlspecialchars($finalPaymentDate) ?></span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-emerald-400 print-text-dark text-sm">
                                ₹0.00 (NIL)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collateral Release & Physical Handover Details -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-300 print-text-dark">
                    Pledged Collateral Discharge & Handover Record
                </span>
                <span class="text-[10px] font-mono text-slate-400 print-text-muted">
                    Items: <?= count($collateralItems) ?> &bull; Status: <strong class="text-emerald-400 print-text-dark">DELIVERED</strong>
                </span>
            </div>

            <div class="rounded-2xl border border-slate-800 overflow-hidden print-border text-xs">
                <?php if (empty($collateralItems)): ?>
                    <div class="p-4 text-center text-slate-400 text-xs print-text-muted">
                        No physical collateral items were pledged under this loan.
                    </div>
                <?php else: ?>
                    <table class="w-full text-left">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] print-bg-light print-text-muted border-b border-slate-800 print-border">
                            <tr>
                                <th class="py-2 px-3">#</th>
                                <th class="py-2 px-3">Item Description</th>
                                <th class="py-2 px-3 text-right">Gross Wt</th>
                                <th class="py-2 px-3 text-right">Net Wt</th>
                                <th class="py-2 px-3 text-center">Handover Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300 print-border print-text-dark">
                            <?php foreach ($collateralItems as $idx => $ci): ?>
                                <tr class="hover:bg-slate-800/30 print-bg-light">
                                    <td class="py-2 px-3 font-mono text-slate-500 print-text-muted"><?= $idx + 1 ?></td>
                                    <td class="py-2 px-3 font-bold text-white print-text-dark">
                                        <?= htmlspecialchars($ci['item_name']) ?>
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono text-slate-300 print-text-dark">
                                        <?= number_format(floatval($ci['gross_weight'] ?? 0), 3) ?> g
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-amber-400 print-text-dark">
                                        <?= number_format(floatval($ci['net_weight'] ?? 0), 3) ?> g
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="font-bold text-emerald-400 print-text-dark text-[11px]">
                                            <i class="fa-solid fa-check-double mr-1"></i> Returned
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Handover info -->
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3 print-bg-light print-border text-xs flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="text-slate-400 print-text-muted">Delivered / Handed over to:</span>
                    <strong class="text-white print-text-dark ml-1"><?= htmlspecialchars($deliveredTo) ?></strong>
                </div>
                <div>
                    <span class="text-slate-400 print-text-muted">Handover Date:</span>
                    <strong class="text-white print-text-dark ml-1"><?= htmlspecialchars($closureDate) ?></strong>
                </div>
                <div>
                    <span class="text-slate-400 print-text-muted">Remarks:</span>
                    <span class="text-slate-300 print-text-dark ml-1"><?= htmlspecialchars($deliveryRemarks) ?></span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
