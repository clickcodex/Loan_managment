<?php
$pageTitle = 'All Loans Ledger - Master Transaction Register';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$activePreset = $preset ?? '';
?>

<div class="space-y-6 pb-12">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5 no-print">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-slate-950 font-black shadow-md shadow-amber-500/20">
                    <i class="fa-solid fa-book-bookmark text-base"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        <span>All Loans Ledger</span>
                        <span class="rounded-full bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                            <?= number_format(count($entries)) ?> Records
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Consolidated master transaction register of all loans — track payments coming in and going out with date filters
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="exportLedgerCsv()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 shadow-xs transition">
                <i class="fa-solid fa-file-csv text-emerald-500 text-sm"></i> Export CSV
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-md shadow-amber-500/20 transition">
                <i class="fa-solid fa-print text-sm"></i> Print Register
            </button>
        </div>
    </div>

    <!-- Quick Date Preset Pills (No Print) -->
    <div class="flex flex-wrap items-center gap-2 no-print">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mr-1">Quick Filters:</span>
        <?php 
            $presets = [
                'all'        => 'All Time',
                'today'      => 'Today',
                'yesterday'  => 'Yesterday',
                'this_week'  => 'This Week',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_year'  => 'This Year'
            ];
            foreach ($presets as $pKey => $pLabel): 
                $isActive = ($activePreset === $pKey) || (empty($activePreset) && empty($startDate) && empty($endDate) && $pKey === 'all');
        ?>
            <a href="<?= $baseUrl ?>/loan-ledger?preset=<?= $pKey ?>&flow_type=<?= urlencode($flowType ?? 'all') ?>&entry_type=<?= urlencode($entryType ?? '') ?>&search=<?= urlencode($search ?? '') ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $isActive ? 'bg-amber-500 text-slate-950 shadow-sm shadow-amber-500/20' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:border-amber-500/50 hover:text-slate-900 dark:hover:text-white' ?>">
                <?= $pLabel ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1: Total Money Out (Debits) -->
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/60 dark:bg-rose-950/20 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-rose-700 dark:text-rose-400">Money Out (Debits)</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 mt-2 font-mono">
                ₹<?= number_format($summary['total_debit'], 2) ?>
            </p>
            <p class="text-[11px] text-rose-800/80 dark:text-rose-300/70 mt-1 font-medium">Loans Disbursed & Top-Ups</p>
        </div>

        <!-- 2: Total Money In (Credits) -->
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/20 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Money In (Credits)</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2 font-mono">
                ₹<?= number_format($summary['total_credit'], 2) ?>
            </p>
            <p class="text-[11px] text-emerald-800/80 dark:text-emerald-300/70 mt-1 font-medium">Repayments & Settlements</p>
        </div>

        <!-- 3: Net Cash Flow -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Net Cash Flow</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg <?= $summary['net_flow'] >= 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500' ?> text-xs">
                    <i class="fa-solid fa-scale-balanced"></i>
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-black <?= $summary['net_flow'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?> mt-2 font-mono">
                <?= $summary['net_flow'] >= 0 ? '+' : '' ?>₹<?= number_format($summary['net_flow'], 2) ?>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">Credits minus Debits</p>
        </div>

        <!-- 4: Total Entries & Loans -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Transactions</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 text-xs">
                    <i class="fa-solid fa-receipt"></i>
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono">
                <?= number_format($summary['total_count']) ?>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                Across <strong class="font-mono text-slate-700 dark:text-slate-300"><?= number_format($summary['total_loans']) ?></strong> unique loans
            </p>
        </div>
    </div>

    <!-- Filter & Search Toolbar Card (No Print) -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs no-print">
        <form method="GET" action="<?= $baseUrl ?>/loan-ledger" class="space-y-4">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                
                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">
                        From Date
                    </label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-xs font-medium text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">
                        To Date
                    </label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-xs font-medium text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Flow Type Filter -->
                <div>
                    <label for="flow_type" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">
                        Money Flow
                    </label>
                    <select id="flow_type" name="flow_type" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-xs font-medium text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                        <option value="all" <?= ($flowType === 'all' || empty($flowType)) ? 'selected' : '' ?>>All Transactions</option>
                        <option value="debit" <?= $flowType === 'debit' ? 'selected' : '' ?>>🔴 Money Out (Debits)</option>
                        <option value="credit" <?= $flowType === 'credit' ? 'selected' : '' ?>>🟢 Money In (Credits)</option>
                    </select>
                </div>

                <!-- Transaction Type Filter -->
                <div>
                    <label for="entry_type" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">
                        Entry Type
                    </label>
                    <select id="entry_type" name="entry_type" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-xs font-medium text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                        <option value="">All Types</option>
                        <option value="Loan Issued" <?= $entryType === 'Loan Issued' ? 'selected' : '' ?>>Loan Issued</option>
                        <option value="Loan Top-Up" <?= $entryType === 'Loan Top-Up' ? 'selected' : '' ?>>Loan Top-Up</option>
                        <option value="Payment Received" <?= $entryType === 'Payment Received' ? 'selected' : '' ?>>Payment Received</option>
                        <option value="Full Settlement" <?= $entryType === 'Full Settlement' ? 'selected' : '' ?>>Full Settlement</option>
                        <option value="Interest Paid" <?= $entryType === 'Interest Paid' ? 'selected' : '' ?>>Interest Paid</option>
                        <option value="Principal Paid" <?= $entryType === 'Principal Paid' ? 'selected' : '' ?>>Principal Paid</option>
                        <option value="Discount / Concession" <?= $entryType === 'Discount / Concession' ? 'selected' : '' ?>>Discount / Concession</option>
                        <option value="Closing Entry" <?= $entryType === 'Closing Entry' ? 'selected' : '' ?>>Closing Entry</option>
                    </select>
                </div>

                <!-- Payment Mode Filter -->
                <div>
                    <label for="payment_mode" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">
                        Payment Mode
                    </label>
                    <select id="payment_mode" name="payment_mode" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-xs font-medium text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                        <option value="">All Modes</option>
                        <option value="Cash" <?= $paymentMode === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="UPI" <?= $paymentMode === 'UPI' ? 'selected' : '' ?>>UPI</option>
                        <option value="Bank Transfer" <?= $paymentMode === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        <option value="Cheque" <?= $paymentMode === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label for="search" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">
                        Search
                    </label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Customer, Loan#, Receipt#..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-xs font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800 pt-3">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    <?php if (!empty($startDate) || !empty($endDate) || !empty($search) || !empty($entryType) || $flowType !== 'all'): ?>
                        Active Filters: 
                        <span class="font-bold text-slate-800 dark:text-slate-200">
                            <?= !empty($startDate) ? 'From: ' . htmlspecialchars($startDate) : '' ?>
                            <?= !empty($endDate) ? ' To: ' . htmlspecialchars($endDate) : '' ?>
                            <?= !empty($flowType) && $flowType !== 'all' ? ' | Flow: ' . ucfirst($flowType) : '' ?>
                            <?= !empty($search) ? ' | Search: "' . htmlspecialchars($search) . '"' : '' ?>
                        </span>
                    <?php else: ?>
                        Showing all loan ledger entries
                    <?php endif; ?>
                </span>

                <div class="flex items-center gap-2">
                    <a href="<?= $baseUrl ?>/loan-ledger" class="rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        Reset Filters
                    </a>
                    <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-black text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-filter text-xs"></i> Apply Filters
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Print Header (Visible only when printing) -->
    <div class="hidden print:block mb-4 border-b-2 border-slate-900 pb-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black text-slate-900">Golden Trust Finance Co.</h1>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-700">All Loans Master Transaction Ledger</p>
            </div>
            <div class="text-right text-xs">
                <p>Printed: <strong><?= date('d-m-Y H:i') ?></strong></p>
                <p>Period: <strong><?= !empty($startDate) ? $startDate : 'Beginning' ?></strong> to <strong><?= !empty($endDate) ? $endDate : 'Current' ?></strong></p>
            </div>
        </div>
        <div class="mt-2 flex gap-4 text-xs font-bold">
            <span>Total Debits: ₹<?= number_format($summary['total_debit'], 2) ?></span>
            <span>Total Credits: ₹<?= number_format($summary['total_credit'], 2) ?></span>
            <span>Net Flow: ₹<?= number_format($summary['net_flow'], 2) ?></span>
            <span>Records: <?= number_format($summary['total_count']) ?></span>
        </div>
    </div>

    <!-- Master Ledger Transactions Table Card -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between no-print">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-table-list text-amber-500"></i> Ledger Transaction Entries (<?= count($entries) ?>)
            </h2>
            <span class="text-xs text-slate-400">Chronological list with debit/credit balance tracking</span>
        </div>

        <?php if (empty($entries)): ?>
            <div class="py-16 text-center space-y-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto text-xl">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No ledger entries found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Try clearing or widening your date filter to view transactions across all loans.
                </p>
                <div class="pt-2">
                    <a href="<?= $baseUrl ?>/loan-ledger" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
                        View All Entries
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table id="ledgerTable" class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px] border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-3.5 whitespace-nowrap"># / Date</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Loan Account</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Customer</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Transaction Type</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Flow</th>
                            <th class="py-3 px-3.5 text-right whitespace-nowrap">Debit (Out)</th>
                            <th class="py-3 px-3.5 text-right whitespace-nowrap">Credit (In)</th>
                            <th class="py-3 px-3.5 text-right whitespace-nowrap">Loan Balance</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Mode / Receipt</th>
                            <th class="py-3 px-3.5 text-center whitespace-nowrap no-print">Receipt / Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-800 dark:text-slate-200 font-sans">
                        <?php foreach ($entries as $idx => $entry): 
                            $debit = floatval($entry['debit'] ?? 0);
                            $credit = floatval($entry['credit'] ?? 0);
                            $isDebit = $debit > 0;
                            $isCredit = $credit > 0;
                            $hasPaymentReceipt = !empty($entry['payment_id']);
                            $isLoanIssued = ($entry['entry_type'] ?? '') === 'Loan Issued';
                        ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                
                                <!-- 1. Date -->
                                <td class="py-3 px-3.5 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars(date('d-m-Y', strtotime($entry['entry_date']))) ?></div>
                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars(date('D', strtotime($entry['entry_date']))) ?></span>
                                </td>

                                <!-- 2. Loan Account -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <a href="<?= $baseUrl ?>/loans/<?= $entry['loan_id'] ?>" class="font-mono font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1.5" title="Open Loan Account">
                                        <i class="fa-solid fa-file-invoice-dollar text-[11px]"></i>
                                        <?= htmlspecialchars($entry['loan_number']) ?>
                                    </a>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        <?= htmlspecialchars($entry['security_type'] ?? 'Secured') ?> &bull; 
                                        <span class="font-semibold text-slate-500 dark:text-slate-400"><?= htmlspecialchars($entry['loan_status'] ?? '') ?></span>
                                    </div>
                                </td>

                                <!-- 3. Customer -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <a href="<?= $baseUrl ?>/customers/<?= $entry['customer_id'] ?>" class="font-bold text-slate-900 dark:text-white hover:underline block truncate max-w-[140px]" title="<?= htmlspecialchars($entry['customer_name']) ?>">
                                        <?= htmlspecialchars($entry['customer_name']) ?>
                                    </a>
                                    <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-[9px] text-slate-400"></i>
                                        <?= htmlspecialchars($entry['customer_mobile'] ?? 'N/A') ?>
                                    </div>
                                </td>

                                <!-- 4. Transaction Type -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-bold 
                                        <?php if ($isLoanIssued): ?>
                                            bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20
                                        <?php elseif (($entry['entry_type'] ?? '') === 'Loan Top-Up'): ?>
                                            bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20
                                        <?php elseif (($entry['entry_type'] ?? '') === 'Full Settlement'): ?>
                                            bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20
                                        <?php elseif ($isCredit): ?>
                                            bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20
                                        <?php else: ?>
                                            bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700
                                        <?php endif; ?>">
                                        <?= htmlspecialchars($entry['entry_type'] ?: 'Ledger Entry') ?>
                                    </span>
                                </td>

                                <!-- 5. Flow Direction Badge -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <?php if ($isDebit): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-black text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                            <i class="fa-solid fa-arrow-down text-[9px]"></i> Outflow
                                        </span>
                                    <?php elseif ($isCredit): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-black text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                            <i class="fa-solid fa-arrow-up text-[9px]"></i> Inflow
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[10px]">Adjustment</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 6. Debit (Outflow) -->
                                <td class="py-3 px-3.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    <?= $debit > 0 ? '₹' . number_format($debit, 2) : '—' ?>
                                </td>

                                <!-- 7. Credit (Inflow) -->
                                <td class="py-3 px-3.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    <?= $credit > 0 ? '₹' . number_format($credit, 2) : '—' ?>
                                </td>

                                <!-- 8. Loan Running Balance -->
                                <td class="py-3 px-3.5 text-right font-mono font-black text-slate-900 dark:text-white whitespace-nowrap">
                                    ₹<?= number_format(floatval($entry['balance'] ?? 0), 2) ?>
                                </td>

                                <!-- 9. Payment Mode & Receipt -->
                                <td class="py-3 px-3.5 whitespace-nowrap font-mono text-[11px]">
                                    <?php if (!empty($entry['payment_receipt_no'])): ?>
                                        <div class="font-bold text-amber-600 dark:text-amber-400">
                                            <?= htmlspecialchars($entry['payment_receipt_no']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-slate-500 dark:text-slate-400 text-[10px]">
                                        <?= htmlspecialchars($entry['payment_mode'] ?? 'Cash') ?>
                                        <?php if (!empty($entry['payment_ref_no'])): ?>
                                            &bull; <?= htmlspecialchars($entry['payment_ref_no']) ?>
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <!-- 10. Actions & Print Receipt -->
                                <td class="py-3 px-3.5 text-center whitespace-nowrap no-print">
                                    <div class="flex items-center justify-center gap-1.5">
                                        
                                        <?php if ($hasPaymentReceipt): ?>
                                            <a href="<?= $baseUrl ?>/receipts/payment/<?= $entry['payment_id'] ?>" target="_blank" 
                                               class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 shadow-2xs transition"
                                               title="Print Official Payment Receipt">
                                                <i class="fa-solid fa-receipt text-xs"></i> Receipt
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= $baseUrl ?>/loans/<?= $entry['loan_id'] ?>" 
                                           class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                                           title="View Full Loan Profile">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View
                                        </a>

                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-100/80 dark:bg-slate-950 font-bold border-t-2 border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white">
                        <tr>
                            <td colspan="5" class="py-3 px-3.5 text-right uppercase tracking-wider text-[10px] text-slate-500 dark:text-slate-400">
                                Filtered Total Summary:
                            </td>
                            <td class="py-3 px-3.5 text-right font-mono font-black text-rose-600 dark:text-rose-400">
                                ₹<?= number_format($summary['total_debit'], 2) ?>
                            </td>
                            <td class="py-3 px-3.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                ₹<?= number_format($summary['total_credit'], 2) ?>
                            </td>
                            <td class="py-3 px-3.5 text-right font-mono font-black text-slate-900 dark:text-white">
                                Net: <?= $summary['net_flow'] >= 0 ? '+' : '' ?>₹<?= number_format($summary['net_flow'], 2) ?>
                            </td>
                            <td colspan="2" class="py-3 px-3.5 text-slate-400 font-normal text-[11px]">
                                <?= number_format($summary['total_count']) ?> records loaded
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- CSV Export Client-Side Script -->
<script>
function exportLedgerCsv() {
    const table = document.getElementById('ledgerTable');
    if (!table) {
        alert('No data to export.');
        return;
    }

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('th, td');
        
        // Skip last action column in CSV
        const colCount = cols.length > 1 ? cols.length - 1 : cols.length;

        for (let j = 0; j < colCount; j++) {
            // Clean inner text
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
            // Escape double quotes
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }

    const csvString = csv.join('\n');
    const filename = 'All_Loans_Ledger_' + (new Date().toISOString().slice(0, 10)) + '.csv';
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });

    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, filename);
    } else {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    aside, header, nav {
        display: none !important;
    }
    body {
        background: white !important;
        color: #0f172a !important;
        padding: 0 !important;
    }
    table {
        width: 100% !important;
        font-size: 10px !important;
        border-collapse: collapse !important;
    }
    th, td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 6px !important;
        color: #0f172a !important;
    }
    th {
        background: #f1f5f9 !important;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
