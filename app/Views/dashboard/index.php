<?php
$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Top Banner & Quick Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-amber-500/30 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-indigo-500/10 p-6 shadow-sm backdrop-blur">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Dashboard</h1>
                <span class="rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                    Live Operations
                </span>
            </div>
            <p class="mt-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                Loan Management System &bull; Version 2.0 Unified Loan & Ledger Model
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="<?= $baseUrl ?>/loans/create" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                <i class="fa-solid fa-plus-circle text-sm"></i> New Loan
            </a>
            <a href="<?= $baseUrl ?>/payments/create" class="inline-flex items-center gap-2 rounded-xl border border-purple-300 dark:border-purple-500/30 bg-purple-50 dark:bg-purple-500/10 px-4 py-2.5 text-xs font-bold text-purple-700 dark:text-purple-300 hover:bg-purple-100 transition">
                <i class="fa-solid fa-receipt text-sm"></i> Quick Payment
            </a>
            <a href="<?= $baseUrl ?>/rates" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-200 hover:bg-slate-50 transition">
                <i class="fa-solid fa-chart-line text-amber-500 text-sm"></i> Rates
            </a>
        </div>
    </div>

    <!-- Dashboard Backup Reminder Card -->
    <?php if (!empty($backupDue)): ?>
        <div class="rounded-2xl border border-rose-300 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/40 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 font-bold text-lg border border-rose-500/30 shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-rose-900 dark:text-white uppercase tracking-wider flex flex-wrap items-center gap-2">
                        <span>Database Backup Reminder</span>
                        <span class="rounded bg-rose-500/20 text-rose-700 dark:text-rose-400 px-2 py-0.5 text-[9px] font-mono">ACTION REQUIRED</span>
                    </h3>
                    <p class="text-xs text-rose-800 dark:text-slate-300 mt-0.5">
                        <?= !empty($lastBackupDate) ? 'Last backup was generated on ' . date('M d, Y', strtotime($lastBackupDate)) . ' (Over 7 days ago).' : 'No database backup has been generated yet for this system.' ?>
                    </p>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/backups" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 shadow-md transition whitespace-nowrap self-start sm:self-auto">
                <i class="fa-solid fa-database"></i> Backup Database Now
            </a>
        </div>
    <?php endif; ?>

    <!-- Live 100% Pure Gold & Silver Market Rates Banner Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Gold Rate Card (100% Pure Gold) -->
        <div class="rounded-2xl border border-amber-300 dark:border-yellow-500/20 bg-gradient-to-br from-amber-50/50 via-white to-white dark:from-yellow-950/20 dark:via-slate-900 dark:to-slate-900 p-5 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/20 text-amber-600 dark:text-yellow-400">
                        <i class="fa-solid fa-gem text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">100% Pure Gold Base Rate</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Effective: <?= htmlspecialchars($goldRate['rate_date'] ?? date('Y-m-d')) ?></p>
                    </div>
                </div>
                <a href="<?= $baseUrl ?>/rates" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-700 dark:text-yellow-400 hover:bg-amber-500/20 transition">
                    Update Rate
                </a>
            </div>
            
            <div class="pt-4 flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                <div>
                    <span class="text-2xl sm:text-3xl font-black font-mono text-amber-600 dark:text-yellow-400">
                        ₹<?= number_format(floatval($goldRate['rate_100'] ?? 140000), 2) ?>
                    </span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">/ 10 Grams</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 px-3 py-1.5 text-xs font-mono font-bold text-amber-800 dark:text-yellow-300">
                    <i class="fa-solid fa-scale-balanced text-xs"></i>
                    <span>₹<?= number_format((floatval($goldRate['rate_100'] ?? 140000)) / 10, 2) ?> / gram</span>
                </div>
            </div>
        </div>

        <!-- Silver Rate Card (100% Pure Silver) -->
        <div class="rounded-2xl border border-slate-300 dark:border-slate-800 bg-gradient-to-br from-slate-50 via-white to-white dark:from-slate-800/30 dark:via-slate-900 dark:to-slate-900 p-5 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <i class="fa-solid fa-ring text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">100% Pure Silver Base Rate</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Effective: <?= htmlspecialchars($silverRate['rate_date'] ?? date('Y-m-d')) ?></p>
                    </div>
                </div>
                <a href="<?= $baseUrl ?>/rates" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    Update Rate
                </a>
            </div>
            
            <div class="pt-4 flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                <div>
                    <span class="text-2xl sm:text-3xl font-black font-mono text-slate-900 dark:text-white">
                        ₹<?= number_format(floatval($silverRate['rate_100'] ?? 100), 2) ?>
                    </span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">/ gram</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs font-mono font-bold text-slate-700 dark:text-slate-300">
                    <i class="fa-solid fa-scale-balanced text-xs"></i>
                    <span>₹<?= number_format((floatval($silverRate['rate_100'] ?? 100)) * 10, 2) ?> / 10g &bull; ₹<?= number_format((floatval($silverRate['rate_100'] ?? 100)) * 1000, 2) ?> / kg</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 16 KPI Overview Cards Grid -->
    <div>
        <h2 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">16 Operational KPI Metrics</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Total Customers -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Customers</span>
                    <div class="h-8 w-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="fa-solid fa-users text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($kpis['total_customers']) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Registered profiles</p>
            </div>

            <!-- Card 2: Active Customers -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Active Customers</span>
                    <div class="h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-user-check text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?= number_format($kpis['active_customers']) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">With active loans</p>
            </div>

            <!-- Card 3: Total Loans -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Loans</span>
                    <div class="h-8 w-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <i class="fa-solid fa-folder-closed text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($kpis['total_loans']) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">All created loans</p>
            </div>

            <!-- Card 4: Active Loans -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Active Loans</span>
                    <div class="h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?= number_format($kpis['active_loans'] ?? $kpis['running_loans']) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Currently active</p>
            </div>

            <!-- Card 5: Closed Loans -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Closed Loans</span>
                    <div class="h-8 w-8 rounded-lg bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-teal-600 dark:text-teal-400 mt-2"><?= number_format($kpis['closed_loans']) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Fully settled</p>
            </div>

            <!-- Card 6: Today's Collection -->
            <div class="rounded-2xl border border-emerald-300 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/5 p-4 shadow-sm hover:border-emerald-400 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Today's Collection</span>
                    <div class="h-8 w-8 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-calendar-day text-sm"></i>
                    </div>
                </div>
                <p class="text-lg sm:text-xl lg:text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-2">₹<?= number_format($kpis['today_collection'], 2) ?></p>
                <p class="text-[11px] text-emerald-800/80 dark:text-emerald-400/80 mt-0.5">Collected today</p>
            </div>

            <!-- Card 7: Total Disbursed Amount -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Disbursed</span>
                    <div class="h-8 w-8 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-wave text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono">₹<?= number_format($kpis['total_disbursed'], 2) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Lifetime principal</p>
            </div>

            <!-- Card 8: Active Principal Balance -->
            <div class="rounded-2xl border border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/5 p-4 shadow-sm hover:border-amber-400 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-800 dark:text-amber-300">Active Balance</span>
                    <div class="h-8 w-8 rounded-lg bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-scale-balanced text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-amber-700 dark:text-amber-400 mt-2 font-mono">₹<?= number_format($kpis['active_principal_balance'], 2) ?></p>
                <p class="text-[11px] text-amber-800/80 dark:text-amber-400/80 mt-0.5">Running portfolio</p>
            </div>

            <!-- Card 9: Total Pledged Collaterals -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Collateral Items</span>
                    <div class="h-8 w-8 rounded-lg bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 flex items-center justify-center">
                        <i class="fa-solid fa-gem text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-amber-600 dark:text-yellow-400 mt-2"><?= number_format($kpis['total_collateral_items']) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Items in vault</p>
            </div>

            <!-- Card 10: Total Gold Weight -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Gold Net Wt</span>
                    <div class="h-8 w-8 rounded-lg bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 flex items-center justify-center">
                        <i class="fa-solid fa-coins text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-amber-600 dark:text-yellow-400 mt-2 font-mono"><?= number_format($kpis['total_gold_weight'], 2) ?> g</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Vault gold stock</p>
            </div>

            <!-- Card 11: Total Silver Weight -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Silver Net Wt</span>
                    <div class="h-8 w-8 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                        <i class="fa-solid fa-ring text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-slate-800 dark:text-slate-200 mt-2 font-mono"><?= number_format($kpis['total_silver_weight'], 2) ?> g</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Vault silver stock</p>
            </div>

            <!-- Card 12: Collateral Market Valuation -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Vault Valuation</span>
                    <div class="h-8 w-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-vault text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono">₹<?= number_format($kpis['total_market_valuation'], 2) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Market asset value</p>
            </div>

            <!-- Card 13: Average LTV Ratio -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Average LTV %</span>
                    <div class="h-8 w-8 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <i class="fa-solid fa-chart-pie text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-cyan-600 dark:text-cyan-400 mt-2 font-mono"><?= number_format($kpis['average_ltv'], 1) ?>%</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Portfolio risk ratio</p>
            </div>

            <!-- Card 14: Total Interest Collected -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Interest Income</span>
                    <div class="h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2 font-mono">₹<?= number_format($kpis['total_interest_collected'], 2) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Collected interest</p>
            </div>

            <!-- Card 15: Total Collections -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Collections</span>
                    <div class="h-8 w-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="fa-solid fa-cash-register text-sm"></i>
                    </div>
                </div>
                <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono">₹<?= number_format($kpis['total_collections'], 2) ?></p>
                <p class="text-[11px] text-slate-500 mt-0.5">Lifetime receipts</p>
            </div>

            <!-- Card 16: Overdue Loans -->
            <div class="rounded-2xl border border-rose-300 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/40 p-4 shadow-sm hover:border-rose-400 transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-rose-800 dark:text-rose-300">Overdue Loans</span>
                    <div class="h-8 w-8 rounded-lg bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <i class="fa-solid fa-circle-exclamation text-sm"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-400 mt-2"><?= number_format($kpis['overdue_loans']) ?></p>
                <p class="text-[11px] text-rose-800/80 dark:text-rose-400/80 mt-0.5">Requires collection</p>
            </div>

        </div>
    </div>

    <!-- Recent Activity Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Payments Table & Card List -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-purple-500"></i> Recent Payment Receipts
                </h2>
                <a href="<?= $baseUrl ?>/payments" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">View All</a>
            </div>
            
            <!-- Desktop Layout: Standard Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Receipt#</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Loan#</th>
                            <th class="py-2.5 px-3">Mode</th>
                            <th class="py-2.5 px-3 text-right">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                        <?php if (empty($recentPayments)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No payment receipts collected yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $p): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 px-3 font-mono font-bold text-amber-600 dark:text-amber-400">
                                        <a href="<?= $baseUrl ?>/receipts/payment/<?= $p['id'] ?>" target="_blank" class="hover:underline">
                                            <?= htmlspecialchars($p['receipt_number']) ?>
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($p['customer_name']) ?></td>
                                    <td class="py-2.5 px-3 font-mono text-slate-600 dark:text-slate-400"><?= htmlspecialchars($p['loan_number']) ?></td>
                                    <td class="py-2.5 px-3">
                                        <span class="rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-2 py-0.5 text-[10px] font-bold">
                                            <?= htmlspecialchars($p['payment_mode']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-extrabold text-emerald-600 dark:text-emerald-400">
                                        ₹<?= number_format($p['total_amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Layout: Card View (Hidden on desktop) -->
            <div class="block md:hidden space-y-3">
                <?php if (empty($recentPayments)): ?>
                    <p class="py-6 text-center text-slate-400 text-xs">No payment receipts collected yet.</p>
                <?php else: ?>
                    <?php foreach ($recentPayments as $p): ?>
                        <div class="p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <a href="<?= $baseUrl ?>/receipts/payment/<?= $p['id'] ?>" target="_blank" class="font-mono text-xs font-extrabold text-amber-600 dark:text-amber-400 hover:underline">
                                    <?= htmlspecialchars($p['receipt_number']) ?>
                                </a>
                                <span class="rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-300">
                                    <?= htmlspecialchars($p['payment_mode']) ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs border-t border-slate-100 dark:border-slate-800 pt-2.5">
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-slate-500">Customer</span>
                                    <span class="font-bold text-slate-900 dark:text-white block truncate"><?= htmlspecialchars($p['customer_name']) ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] uppercase font-bold text-slate-500">Loan Number</span>
                                    <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold"><?= htmlspecialchars($p['loan_number']) ?></span>
                                </div>
                            </div>
                            <div class="border-t border-slate-100 dark:border-slate-800 pt-2 flex items-center justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-500">Amount Paid</span>
                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                    ₹<?= number_format($p['total_amount'], 2) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Loans Table & Card List -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-500"></i> Active Loans
                </h2>
                <a href="<?= $baseUrl ?>/loans" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">View All</a>
            </div>
            
            <!-- Desktop Layout: Standard Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Loan#</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Security</th>
                            <th class="py-2.5 px-3 text-right">Principal</th>
                            <th class="py-2.5 px-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                        <?php if (empty($recentLoans)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No active loans issued yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLoans as $l): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 px-3 font-mono font-bold text-amber-600 dark:text-amber-400">
                                        <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="hover:underline">
                                            <?= htmlspecialchars($l['loan_number']) ?>
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($l['customer_name']) ?></td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($l['security_type']) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold">₹<?= number_format($l['principal_amount'], 2) ?></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                        ₹<?= number_format($l['running_balance'] ?? $l['principal_amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Layout: Card View (Hidden on desktop) -->
            <div class="block md:hidden space-y-3">
                <?php if (empty($recentLoans)): ?>
                    <p class="py-6 text-center text-slate-400 text-xs">No active loans issued yet.</p>
                <?php else: ?>
                    <?php foreach ($recentLoans as $l): ?>
                        <div class="p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="font-mono text-xs font-extrabold text-amber-600 dark:text-amber-400 hover:underline">
                                    <?= htmlspecialchars($l['loan_number']) ?>
                                </a>
                                <span class="rounded bg-amber-500/10 dark:bg-amber-500/5 border border-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                    <?= htmlspecialchars($l['security_type']) ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs border-t border-slate-100 dark:border-slate-800 pt-2.5">
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-slate-500">Customer</span>
                                    <span class="font-bold text-slate-900 dark:text-white block truncate"><?= htmlspecialchars($l['customer_name']) ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] uppercase font-bold text-slate-500">Principal Amt</span>
                                    <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">₹<?= number_format($l['principal_amount'], 2) ?></span>
                                </div>
                            </div>
                            <div class="border-t border-slate-100 dark:border-slate-800 pt-2 flex items-center justify-between">
                                <span class="text-[10px] uppercase font-bold text-slate-500">Running Balance</span>
                                <span class="font-mono font-black text-amber-600 dark:text-amber-400 text-sm">
                                    ₹<?= number_format($l['running_balance'] ?? $l['remaining_balance'] ?? $l['principal_amount'], 2) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>