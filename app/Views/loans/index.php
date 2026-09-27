<?php
$pageTitle = 'Loan Accounts Directory';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Page Header & Action -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-hand-holding-dollar text-emerald-500"></i> Loan Accounts Directory
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Unified Loan Model &bull; One Loan &bull; One Ledger &bull; Mixed Collateral</p>
        </div>
        <a href="<?= $baseUrl ?>/loans/create" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
            <i class="fa-solid fa-plus-circle text-sm"></i> Issue New Loan
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
        
        <!-- Status Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <a href="<?= $baseUrl ?>/loans<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= empty($status) ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                All Loans
            </a>
            <a href="<?= $baseUrl ?>/loans?status=Active<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= ($status === 'Active' || $status === 'Running') ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1"></span> Active Loans
            </a>
            <a href="<?= $baseUrl ?>/loans?status=Closed<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $status === 'Closed' ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Closed Loans
            </a>
            <a href="<?= $baseUrl ?>/loans?status=Overdue<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $status === 'Overdue' ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Overdue Loans
            </a>
        </div>

        <!-- Search Input -->
        <form action="<?= $baseUrl ?>/loans" method="GET" class="flex items-center gap-2">
            <?php if (!empty($status)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <?php endif; ?>
            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search loan#, customer name, mobile..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
            </div>
            <button type="submit" class="rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/loans" class="text-xs text-slate-500 hover:underline">Clear</a>
            <?php endif; ?>
        </form>

    </div>

    <!-- Loan Directory Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($loans)): ?>
            <div class="py-16 text-center text-slate-500">
                <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No loan accounts found</p>
                <p class="text-xs mt-1 text-slate-500">Try adjusting search parameters or create a new loan.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[900px] lg:min-w-full">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-3">Loan #</th>
                            <th class="py-3 px-3">Customer</th>
                            <th class="py-3 px-2.5">Disbursed</th>
                            <th class="py-3 px-3">Collateral Item</th>
                            <th class="py-3 px-2.5 text-right">Principal</th>
                            <th class="py-3 px-2.5 text-right">Total Payable</th>
                            <th class="py-3 px-2.5 text-right">Received</th>
                            <th class="py-3 px-2.5 text-right">Balance</th>
                            <th class="py-3 px-2.5 text-right hidden xl:table-cell">Market Value</th>
                            <th class="py-3 px-2 text-center hidden lg:table-cell">LTV %</th>
                            <th class="py-3 px-2.5 text-center">Status</th>
                            <th class="py-3 px-3 text-right sticky right-0 bg-slate-100 dark:bg-slate-950 shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($loans as $l): ?>
                            <?php 
                                $principal = floatval($l['principal_amount']);
                                $totalPayable = floatval($l['total_payable'] ?? $l['total_payable_amount'] ?? $principal);
                                $totalReceived = floatval($l['total_received'] ?? $l['total_paid'] ?? 0);
                                $remainingBal = floatval($l['total_payable'] ?? $l['running_balance'] ?? $l['remaining_balance'] ?? $principal);
                                $collateralVal = floatval($l['current_collateral_value'] ?? $l['total_collateral_value'] ?? 0);
                                $ltv = floatval($l['live_ltv_ratio'] ?? ($collateralVal > 0 ? ($principal / $collateralVal) * 100 : 0));
                                $isActive = ($l['status'] === 'Active' || $l['status'] === 'Running');
                                $isClosed = ($l['status'] === 'Closed');
                                $colName = !empty($l['collateral_names']) ? $l['collateral_names'] : (!empty($l['collateral_items_summary']) ? $l['collateral_items_summary'] : $l['security_type']);
                            ?>
                            <tr class="transition group <?= $isActive ? 'border-l-4 border-l-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/25 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/35' : ($isClosed ? 'bg-slate-50/30 dark:bg-slate-900/30 text-slate-500 opacity-80 hover:bg-slate-100/40' : 'hover:bg-slate-50 dark:hover:bg-slate-800/40') ?>">
                                <!-- Loan Number -->
                                <td class="py-2.5 px-3 font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($l['loan_number']) ?>
                                    </a>
                                </td>

                                <!-- Customer Info -->
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white leading-tight truncate max-w-[130px]"><?= htmlspecialchars($l['customer_name']) ?></div>
                                    <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($l['customer_mobile']) ?></span>
                                </td>

                                <!-- Loan Date -->
                                <td class="py-2.5 px-2.5 text-slate-600 dark:text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($l['loan_date']) ?>
                                </td>

                                <!-- Collateral / Security Item -->
                                <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid <?= str_contains($l['security_type'] ?? '', 'Silver') ? 'fa-ring text-slate-400' : 'fa-gem text-amber-500' ?> text-xs shrink-0"></i>
                                        <span class="truncate max-w-[140px] lg:max-w-[180px]" title="<?= htmlspecialchars($colName) ?>"><?= htmlspecialchars($colName) ?></span>
                                    </div>
                                    <?php if (!empty($l['collateral_items_summary']) && $l['collateral_items_summary'] !== $colName): ?>
                                        <span class="text-[10px] text-slate-400 font-normal block truncate max-w-[140px] lg:max-w-[180px]"><?= htmlspecialchars($l['collateral_items_summary']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Principal -->
                                <td class="py-2.5 px-2.5 text-right font-extrabold text-slate-900 dark:text-white font-mono whitespace-nowrap">
                                    ₹<?= number_format($principal, 2) ?>
                                </td>

                                <!-- Total Payable -->
                                <td class="py-2.5 px-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400 font-mono whitespace-nowrap">
                                    ₹<?= number_format($totalPayable, 2) ?>
                                </td>

                                <!-- Received Amount -->
                                <td class="py-2.5 px-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400 font-mono whitespace-nowrap">
                                    ₹<?= number_format($totalReceived, 2) ?>
                                </td>

                                <!-- Remaining Balance -->
                                <td class="py-2.5 px-2.5 text-right font-bold text-amber-600 dark:text-amber-400 font-mono whitespace-nowrap">
                                    ₹<?= number_format($remainingBal, 2) ?>
                                </td>

                                <!-- Collateral Market Value -->
                                <td class="py-2.5 px-2.5 text-right font-bold text-amber-600 dark:text-yellow-400 font-mono whitespace-nowrap hidden xl:table-cell">
                                    ₹<?= number_format($collateralVal, 2) ?>
                                </td>

                                <!-- LTV Ratio -->
                                <td class="py-2.5 px-2 text-center hidden lg:table-cell">
                                    <span class="font-mono text-[11px] font-bold <?= $ltv > 80 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                        <?= number_format($ltv, 1) ?>%
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-2.5 px-2.5 text-center whitespace-nowrap">
                                    <?php if ($isActive): ?>
                                        <span class="rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider">Active</span>
                                    <?php elseif ($isClosed): ?>
                                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[9px] font-bold text-slate-500 dark:text-slate-400">Closed</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[9px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/20">Overdue</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions (Sticky Right Column) -->
                                <td class="py-2.5 px-3 text-right whitespace-nowrap sticky right-0 bg-inherit shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="<?= $baseUrl ?>/receipts/disbursement/<?= $l['id'] ?>" target="_blank" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-1 text-slate-500 hover:text-amber-500 transition shadow-xs" title="Print Disbursement Slip (RK Details)">
                                            <i class="fa-solid fa-receipt text-xs"></i>
                                        </a>
                                        <?php if ($isClosed): ?>
                                            <a href="<?= $baseUrl ?>/receipts/closure/<?= $l['id'] ?>" target="_blank" class="rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 p-1 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 transition shadow-xs" title="Print Closure Receipt">
                                                <i class="fa-solid fa-file-circle-check text-xs"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-xs">
                                            View Loan <i class="fa-solid fa-arrow-right ml-0.5 text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
