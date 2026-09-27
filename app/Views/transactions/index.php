<?php
$pageTitle = 'Daily Credit & Debit Cashbook - ' . date('d M Y', strtotime($date));
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-scale-balanced text-emerald-500"></i> Daily Credit & Debit Cashbook
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Real-time Daybook &bull; Complete Inflow (Credit) & Outflow (Debit) Transactions Audit
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= $baseUrl ?>/transactions/export-pdf?date=<?= urlencode($date) ?>" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-4 py-2.5 text-xs font-bold shadow-md hover:bg-slate-800 dark:hover:bg-slate-100 transition">
                <i class="fa-solid fa-file-pdf text-rose-500 text-sm"></i> Download / Print PDF Report
            </a>
            <a href="<?= $baseUrl ?>/loans/create" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                <i class="fa-solid fa-plus text-xs"></i> New Loan
            </a>
        </div>
    </div>

    <!-- Date Navigation & Summary Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
        
        <!-- Date Selector Form -->
        <form action="<?= $baseUrl ?>/transactions" method="GET" class="flex flex-wrap items-center gap-2.5">
            <?php if (!empty($type) && $type !== 'all'): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            <?php endif; ?>
            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Select Date:</label>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()"
                   class="rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3.5 py-2 text-xs font-mono font-bold text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
            
            <!-- Quick Date Shortcut Buttons -->
            <a href="<?= $baseUrl ?>/transactions?date=<?= date('Y-m-d') ?><?= !empty($type) && $type !== 'all' ? '&type=' . urlencode($type) : '' ?>" 
               class="rounded-xl px-3 py-2 text-xs font-bold border transition <?= $date === date('Y-m-d') ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200' ?>">
                Today
            </a>
            <a href="<?= $baseUrl ?>/transactions?date=<?= date('Y-m-d', strtotime('-1 day')) ?><?= !empty($type) && $type !== 'all' ? '&type=' . urlencode($type) : '' ?>" 
               class="rounded-xl px-3 py-2 text-xs font-bold border transition <?= $date === date('Y-m-d', strtotime('-1 day')) ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-200' ?>">
                Yesterday
            </a>
        </form>

        <!-- Search Form -->
        <form action="<?= $baseUrl ?>/transactions" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
            <?php if (!empty($type) && $type !== 'all'): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            <?php endif; ?>
            <div class="relative w-full sm:w-60">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search loan#, customer, mobile..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
            </div>
            <button type="submit" class="rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/transactions?date=<?= urlencode($date) ?><?= !empty($type) && $type !== 'all' ? '&type=' . urlencode($type) : '' ?>" class="text-xs text-slate-500 hover:underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Credits (Inflow) -->
        <div class="rounded-2xl border border-emerald-300 dark:border-emerald-500/30 bg-gradient-to-br from-emerald-50/80 dark:from-emerald-950/40 via-white dark:via-slate-900 to-white dark:to-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Total Credit (Inflow)</span>
                <div class="h-8 w-8 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-arrow-down-left text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2 font-mono">₹<?= number_format($summary['total_credit'], 2) ?></p>
            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                <span><?= $summary['credit_count'] ?> Collections</span>
                <span>Cash: ₹<?= number_format($summary['cash_credit'], 2) ?></span>
            </div>
        </div>

        <!-- Total Debits (Outflow) -->
        <div class="rounded-2xl border border-rose-300 dark:border-rose-500/30 bg-gradient-to-br from-rose-50/80 dark:from-rose-950/40 via-white dark:via-slate-900 to-white dark:to-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-800 dark:text-rose-300 uppercase tracking-wider">Total Debit (Outflow)</span>
                <div class="h-8 w-8 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-arrow-up-right text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-2 font-mono">₹<?= number_format($summary['total_debit'], 2) ?></p>
            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                <span><?= $summary['debit_count'] ?> Disbursements</span>
                <span>Cash: ₹<?= number_format($summary['cash_debit'], 2) ?></span>
            </div>
        </div>

        <!-- Net Cash Flow Position -->
        <div class="rounded-2xl border border-blue-300 dark:border-blue-500/30 bg-gradient-to-br from-blue-50/80 dark:from-blue-950/40 via-white dark:via-slate-900 to-white dark:to-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider">Net Cash Position</span>
                <div class="h-8 w-8 rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-scale-unbalanced text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black <?= $summary['net_cash_flow'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?> mt-2 font-mono">
                <?= $summary['net_cash_flow'] >= 0 ? '+' : '' ?>₹<?= number_format($summary['net_cash_flow'], 2) ?>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                <?= $summary['net_cash_flow'] >= 0 ? 'Net positive liquidity surplus' : 'Net disbursement deficit' ?>
            </p>
        </div>

        <!-- Total Entries Count & Mode Split -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Transactions</span>
                <div class="h-8 w-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-list-check text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono"><?= $summary['total_count'] ?> Entries</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                <?= $summary['credit_count'] ?> Credits &bull; <?= $summary['debit_count'] ?> Debits
            </p>
        </div>
    </div>

    <!-- Type Filters -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
        <a href="<?= $baseUrl ?>/transactions?date=<?= urlencode($date) ?>&type=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
           class="rounded-xl px-4 py-2 text-xs font-bold transition <?= ($type === 'all' || empty($type)) ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-950 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            All Transactions (<?= $summary['total_count'] ?>)
        </a>
        <a href="<?= $baseUrl ?>/transactions?date=<?= urlencode($date) ?>&type=credit<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
           class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 <?= $type === 'credit' ? 'bg-emerald-500 text-slate-950 shadow-sm' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' ?>">
            <i class="fa-solid fa-arrow-down-left text-xs"></i> Credit Only (<?= $summary['credit_count'] ?>)
        </a>
        <a href="<?= $baseUrl ?>/transactions?date=<?= urlencode($date) ?>&type=debit<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
           class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 <?= $type === 'debit' ? 'bg-rose-500 text-white shadow-sm' : 'text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40' ?>">
            <i class="fa-solid fa-arrow-up-right text-xs"></i> Debit Only (<?= $summary['debit_count'] ?>)
        </a>
    </div>

    <!-- Transactions List Table -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($transactions)): ?>
            <div class="py-16 text-center text-slate-500">
                <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-receipt text-2xl text-slate-400"></i>
                </div>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No transactions recorded for <?= date('d M Y', strtotime($date)) ?></p>
                <p class="text-xs mt-1 text-slate-500">No credit collections or debit disbursements match the selected filter.</p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <a href="<?= $baseUrl ?>/loans/create" class="rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400">
                        <i class="fa-solid fa-plus-circle mr-1"></i> Issue Loan
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Desktop Layout: Detailed Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[850px] lg:min-w-full">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-3">Type</th>
                            <th class="py-3 px-3">Loan / Customer</th>
                            <th class="py-3 px-3">Details</th>
                            <th class="py-3 px-3">Collateral</th>
                            <th class="py-3 px-2.5">Mode</th>
                            <th class="py-3 px-2.5 text-right">Debit (₹)</th>
                            <th class="py-3 px-2.5 text-right">Credit (₹)</th>
                            <th class="py-3 px-2.5 text-right">Balance (₹)</th>
                            <th class="py-3 px-3 text-right sticky right-0 bg-slate-100 dark:bg-slate-950 shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($transactions as $t): ?>
                            <?php 
                                $isCredit = $t['category'] === 'CREDIT';
                            ?>
                            <tr class="transition group <?= $isCredit ? 'border-l-4 border-l-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/30' : 'border-l-4 border-l-rose-500 bg-rose-50/50 dark:bg-rose-950/20 hover:bg-rose-100/50 dark:hover:bg-rose-900/30' ?>">
                                
                                <!-- 1. Type Badge -->
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <?php if ($isCredit): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider">
                                            <i class="fa-solid fa-arrow-down-left"></i> Credit
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider">
                                            <i class="fa-solid fa-arrow-up-right"></i> Debit
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- 2. Loan & Customer -->
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= $baseUrl ?>/loans/<?= $t['loan_id'] ?>" class="font-mono font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                            <?= htmlspecialchars($t['loan_number']) ?>
                                        </a>
                                    </div>
                                    <div class="font-bold text-slate-900 dark:text-white leading-tight truncate max-w-[130px]"><?= htmlspecialchars($t['customer_name']) ?></div>
                                    <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($t['customer_mobile']) ?></span>
                                </td>

                                <!-- 3. Transaction Details & Remarks -->
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white leading-tight"><?= htmlspecialchars($t['title']) ?></div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400"><?= htmlspecialchars($t['sub_title']) ?></div>
                                    <?php if (!empty($t['remarks']) && $t['remarks'] !== '—'): ?>
                                        <div class="text-[10px] text-slate-400 italic mt-0.5 truncate max-w-[160px]" title="<?= htmlspecialchars($t['remarks']) ?>">
                                            "<?= htmlspecialchars($t['remarks']) ?>"
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- 4. Collateral Item -->
                                <td class="py-2.5 px-3">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[130px]" title="<?= htmlspecialchars($t['collateral_names']) ?>">
                                        <?= htmlspecialchars($t['collateral_names']) ?>
                                    </div>
                                </td>

                                <!-- 5. Mode & Reference -->
                                <td class="py-2.5 px-2.5 whitespace-nowrap">
                                    <span class="inline-block rounded-md bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($t['payment_mode']) ?>
                                    </span>
                                    <?php if (!empty($t['receipt_number']) && $t['receipt_number'] !== '—'): ?>
                                        <div class="text-[9px] font-mono text-slate-500 mt-0.5">#<?= htmlspecialchars($t['receipt_number']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- 6. Debit Amount -->
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    <?= $t['debit'] > 0 ? '₹' . number_format($t['debit'], 2) : '—' ?>
                                </td>

                                <!-- 7. Credit Amount -->
                                <td class="py-2.5 px-2.5 text-right font-mono font-extrabold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    <?= $t['credit'] > 0 ? '₹' . number_format($t['credit'], 2) : '—' ?>
                                </td>

                                <!-- 8. Closing Balance -->
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    ₹<?= number_format($t['balance'], 2) ?>
                                </td>

                                <!-- 9. Actions (Sticky Right Column) -->
                                <td class="py-2.5 px-3 text-right whitespace-nowrap sticky right-0 bg-inherit shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">
                                    <a href="<?= $baseUrl ?>/loans/<?= $t['loan_id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-xs">
                                        View <i class="fa-solid fa-arrow-right ml-0.5 text-[9px]"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-100 dark:bg-slate-950 font-mono font-black text-xs text-slate-900 dark:text-white border-t-2 border-slate-300 dark:border-slate-700">
                        <tr>
                            <td colspan="5" class="py-3 px-4 uppercase">Daily Cashbook Totals (<?= count($transactions) ?> Entries)</td>
                            <td class="py-3 px-4 text-right text-rose-600 dark:text-rose-400 font-extrabold">₹<?= number_format($summary['total_debit'], 2) ?></td>
                            <td class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400 font-extrabold">₹<?= number_format($summary['total_credit'], 2) ?></td>
                            <td colspan="2" class="py-3 px-4 text-right font-sans font-bold text-[11px] <?= $summary['net_cash_flow'] >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                                Net Flow: <?= $summary['net_cash_flow'] >= 0 ? '+' : '' ?>₹<?= number_format($summary['net_cash_flow'], 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Mobile Layout: Card Grid -->
            <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($transactions as $t): ?>
                    <?php $isCredit = $t['category'] === 'CREDIT'; ?>
                    <div class="p-4 space-y-3 transition <?= $isCredit ? 'border-l-4 border-l-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20' : 'border-l-4 border-l-rose-500 bg-rose-50/50 dark:bg-rose-950/20' ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <?php if ($isCredit): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-2 py-0.5 text-[9px] font-extrabold uppercase">
                                        <i class="fa-solid fa-arrow-down-left"></i> Credit
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30 px-2 py-0.5 text-[9px] font-extrabold uppercase">
                                        <i class="fa-solid fa-arrow-up-right"></i> Debit
                                    </span>
                                <?php endif; ?>
                                <a href="<?= $baseUrl ?>/loans/<?= $t['loan_id'] ?>" class="font-mono text-xs font-bold text-amber-600 dark:text-amber-400">
                                    <?= htmlspecialchars($t['loan_number']) ?>
                                </a>
                            </div>
                            <span class="font-mono font-black text-sm <?= $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                                <?= $isCredit ? '+₹' . number_format($t['credit'], 2) : '-₹' . number_format($t['debit'], 2) ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs border-t border-slate-100 dark:border-slate-800/60 pt-2">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Customer</span>
                                <span class="font-bold text-slate-900 dark:text-white truncate block"><?= htmlspecialchars($t['customer_name']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Mode & Receipt</span>
                                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($t['payment_mode']) ?> <?= $t['receipt_number'] !== '—' ? '(# ' . htmlspecialchars($t['receipt_number']) . ')' : '' ?></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Description</span>
                                <span class="text-slate-700 dark:text-slate-300 font-medium"><?= htmlspecialchars($t['title']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Closing Balance</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹<?= number_format($t['balance'], 2) ?></span>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800/60 pt-2 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 truncate max-w-[200px]">Item: <?= htmlspecialchars($t['collateral_names']) ?></span>
                            <a href="<?= $baseUrl ?>/loans/<?= $t['loan_id'] ?>" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-100">
                                Details <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
