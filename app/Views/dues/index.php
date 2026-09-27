<?php
$pageTitle = 'Due Sheet & Overdue Alerts Engine';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ 
    waModal: false, 
    selectedWaItem: null,
    collectModal: false,
    selectedLoanItem: null,
    receiveAmount: '',
    discount: '',
    paymentMode: 'Cash',
    paymentDate: '<?= date('Y-m-d') ?>',
    receiptNumber: '<?= htmlspecialchars($nextReceiptNumber ?? '') ?>',
    referenceNumber: '',
    remarks: '',
    get currentBalance() {
        return this.selectedLoanItem ? parseFloat(this.selectedLoanItem.principal_balance || 0) : 0;
    },
    get accruedInterest() {
        return this.selectedLoanItem ? parseFloat(this.selectedLoanItem.accrued_interest || 0) : 0;
    },
    get totalPayable() {
        return this.selectedLoanItem ? parseFloat(this.selectedLoanItem.total_payable || 0) : 0;
    },
    get parsedDiscount() {
        const val = parseFloat(this.discount);
        return isNaN(val) ? 0 : Math.max(0, val);
    },
    get netPayableAfterDiscount() {
        return Math.max(0, this.totalPayable - this.parsedDiscount);
    },
    get parsedAmount() {
        const v = parseFloat(this.receiveAmount);
        return isNaN(v) ? 0 : v;
    },
    get remainingBalance() {
        return Math.max(0, this.netPayableAfterDiscount - this.parsedAmount);
    },
    get isFullSettlement() {
        return this.netPayableAfterDiscount > 0 && this.parsedAmount >= this.netPayableAfterDiscount;
    },
    payCompleteLoan() {
        this.receiveAmount = this.netPayableAfterDiscount.toFixed(2);
    },
    onDiscountChange() {
        if (this.receiveAmount !== '' && parseFloat(this.receiveAmount) > this.netPayableAfterDiscount) {
            this.receiveAmount = this.netPayableAfterDiscount.toFixed(2);
        }
    },
    openCollect(item) {
        this.selectedLoanItem = item;
        this.receiveAmount = '';
        this.discount = '';
        this.paymentMode = 'Cash';
        this.paymentDate = '<?= date('Y-m-d') ?>';
        this.referenceNumber = '';
        this.remarks = '';
        this.collectModal = true;
    },
    setAmount(val) {
        this.receiveAmount = parseFloat(val).toFixed(2);
    }
}">

    <!-- Page Header & Title -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2.5">
                <i class="fa-solid fa-calendar-check text-rose-400"></i> Due Sheet & Overdue Alerts Engine
            </h1>
            <p class="text-xs text-slate-400 mt-1">Real-time interest accrual, collateral shortfall deficit alerts, and WhatsApp margin call notices</p>
        </div>

        <a href="<?= $baseUrl ?>/dues/print?filter=<?= htmlspecialchars($filter) ?>&search=<?= urlencode($search) ?>" target="_blank"
           class="inline-flex items-center gap-2 rounded-xl bg-slate-800 border border-slate-700 px-4 py-2.5 text-xs font-bold text-slate-200 hover:bg-slate-700 transition shadow">
            <i class="fa-solid fa-print text-amber-400 text-sm"></i> Print Field Collection Sheet
        </a>
    </div>

    <!-- Collateral Deficit Risk Shortfall Alert Banner (If Any Loan is in Deficit) -->
    <?php if (!empty($kpis['shortfall_count']) && $kpis['shortfall_count'] > 0): ?>
        <div class="rounded-2xl border-2 border-rose-500/60 bg-gradient-to-r from-rose-950/60 via-slate-900 to-slate-900 p-5 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-400 font-black text-2xl border border-rose-500/40 shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-black text-white uppercase tracking-wider">COLLATERAL SHORTFALL RISK ALERT</h3>
                        <span class="rounded bg-rose-500/30 text-rose-300 px-2 py-0.5 text-[10px] font-mono font-bold animate-pulse border border-rose-500/40">
                            <?= $kpis['shortfall_count'] ?> LOANS IN DEFICIT
                        </span>
                    </div>
                    <p class="text-xs text-rose-200/90 mt-1">
                        Total loan payable amount (principal + accrued interest) has <strong>EXCEEDED</strong> current live market value of pledged collateral!
                        Total Shortfall Deficit: <strong class="font-mono text-amber-400">₹<?= number_format($kpis['shortfall_total_sum'], 2) ?></strong>
                    </p>
                </div>
            </div>

            <a href="<?= $baseUrl ?>/dues?filter=shortfall_alert"
               class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-extrabold text-white hover:bg-rose-500 shadow-xl shadow-rose-600/30 transition whitespace-nowrap self-start sm:self-auto">
                <i class="fa-solid fa-shield-exclamation"></i> View Deficit Loans (<?= $kpis['shortfall_count'] ?>)
            </a>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Accrued Interest</span>
            <div class="text-xl font-black text-amber-400 font-mono">₹<?= number_format($kpis['accrued_interest_sum'], 2) ?></div>
            <span class="text-[10px] text-slate-500 block">Accumulated dues</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Dues Payable</span>
            <div class="text-xl font-black text-white font-mono">₹<?= number_format($kpis['total_payable_sum'], 2) ?></div>
            <span class="text-[10px] text-slate-500 block">Principal + Interest</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Collateral Deficits</span>
            <div class="text-xl font-black text-rose-400 font-mono"><?= number_format($kpis['shortfall_count']) ?></div>
            <span class="text-[10px] text-rose-400/80 block font-semibold">Payable > Collateral</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Due Today</span>
            <div class="text-xl font-black text-yellow-400 font-mono"><?= number_format($kpis['due_today_count']) ?></div>
            <span class="text-[10px] text-slate-500 block">Due date falls today</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">NPA Alerts (>30 Days)</span>
            <div class="text-xl font-black text-rose-400 font-mono"><?= number_format($kpis['npa_count']) ?></div>
            <span class="text-[10px] text-slate-500 block">Overdue without pay</span>
        </div>
    </div>

    <!-- Category Filter Tabs & Search Bar Card -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-4 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto text-xs">
                <?php
                    $tabs = [
                        'all'             => 'All Active Dues',
                        'shortfall_alert' => '🚨 Collateral Deficit Risk (' . ($kpis['shortfall_count'] ?? 0) . ')',
                        'due_today'       => 'Due Today',
                        'overdue_1_15'    => '1-15 Days Overdue',
                        'overdue_15_30'   => '15-30 Days Overdue',
                        'critical_npa'    => '30+ Days Critical'
                    ];
                ?>
                <?php foreach ($tabs as $k => $lbl): ?>
                    <a href="<?= $baseUrl ?>/dues?filter=<?= $k ?>&search=<?= urlencode($search) ?>"
                       class="rounded-xl px-3 py-1.5 font-bold transition whitespace-nowrap <?= $filter === $k ? ($k === 'shortfall_alert' ? 'bg-rose-600 text-white shadow' : 'bg-rose-500 text-white shadow') : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800' ?>">
                        <?= $lbl ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Form -->
            <form action="<?= $baseUrl ?>/dues" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <div class="relative w-64">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search loan#, customer, mobile..."
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-1.5 pl-8 pr-3 text-xs text-white placeholder-slate-500 focus:border-rose-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-slate-500 text-xs"></i>
                </div>
                <button type="submit" class="rounded-xl bg-slate-800 border border-slate-700 px-3 py-1.5 text-xs font-bold text-slate-200 hover:bg-slate-700">Filter</button>
            </form>
        </div>

        <!-- Dues Directory Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Loan Number</th>
                        <th class="py-3 px-4">Principal Bal</th>
                        <th class="py-3 px-4">Accrued Interest</th>
                        <th class="py-3 px-4">Total Payable</th>
                        <th class="py-3 px-4 text-right">Collateral Value</th>
                        <th class="py-3 px-4 text-center">LTV %</th>
                        <th class="py-3 px-4">Status & Risk Alert</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    <?php if (empty($dueSheet)): ?>
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500">
                                No loan accounts found matching the selected filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dueSheet as $item): ?>
                            <tr class="hover:bg-slate-800/40 transition <?= $item['has_collateral_shortfall'] ? 'bg-rose-950/20' : '' ?>">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <?php if (!empty($item['customer_photo'])): ?>
                                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['customer_photo']) ?>" alt="Photo" class="h-8 w-8 rounded-lg object-cover border border-amber-500/30">
                                        <?php else: ?>
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/20 text-amber-400 font-bold text-xs">
                                                <?= strtoupper(substr($item['customer_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= $baseUrl ?>/customers/<?= $item['customer_id'] ?>" class="font-bold text-white hover:text-amber-400">
                                                <?= htmlspecialchars($item['customer_name']) ?>
                                            </a>
                                            <span class="block text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($item['customer_mobile']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-400">
                                    <a href="<?= $baseUrl ?>/loans/<?= $item['id'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($item['loan_number']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-white">₹<?= number_format($item['principal_balance'], 2) ?></td>
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-400">
                                    ₹<?= number_format($item['accrued_interest'], 2) ?>
                                    <span class="block text-[9px] text-slate-500 font-normal"><?= $item['days_elapsed'] ?> days</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-black text-white text-sm">
                                    ₹<?= number_format($item['total_payable'], 2) ?>
                                    <?php if (!empty($item['total_received']) && $item['total_received'] > 0): ?>
                                        <span class="block text-[9px] text-emerald-400 font-normal">Recv: ₹<?= number_format($item['total_received'], 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-yellow-400">
                                    ₹<?= number_format($item['current_collateral_value'], 2) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[10px] <?= $item['live_ltv_ratio'] > 100 ? 'bg-rose-500/20 text-rose-400 font-black' : 'bg-slate-800 text-slate-300' ?>">
                                        <?= number_format($item['live_ltv_ratio'], 1) ?>%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold border block w-fit <?= $item['badge_class'] ?>">
                                            <?= $item['due_status_label'] ?>
                                        </span>
                                        <?php if ($item['has_collateral_shortfall']): ?>
                                            <span class="block text-[10px] font-bold text-rose-400 font-mono">
                                                Deficit: ₹<?= number_format($item['shortfall_amount'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                    <!-- Collect Payment Button -->
                                    <button type="button" @click="openCollect(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)"
                                       class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 px-2.5 py-1 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition shadow">
                                        <i class="fa-solid fa-receipt"></i> Collect
                                    </button>

                                    <!-- WhatsApp Notice Button -->
                                    <button type="button" @click="selectedWaItem = <?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>; waModal = true"
                                            class="inline-flex items-center gap-1 rounded-lg <?= $item['has_collateral_shortfall'] ? 'bg-rose-600 text-white font-black' : 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30' ?> px-2.5 py-1 text-xs font-bold hover:bg-emerald-500 transition">
                                        <i class="fa-brands fa-whatsapp text-sm"></i> <?= $item['has_collateral_shortfall'] ? 'Margin Call' : 'Notice' ?>
                                    </button>

                                    <!-- View Loan -->
                                    <a href="<?= $baseUrl ?>/loans/<?= $item['id'] ?>" class="rounded-lg border border-slate-700 bg-slate-800 p-1 px-2 text-slate-400 hover:text-white">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- WhatsApp Overdue / Margin Call Notice Modal -->
    <div x-show="waModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-3xl border border-emerald-500/40 bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="waModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-emerald-400 flex items-center gap-2">
                    <i class="fa-brands fa-whatsapp text-lg"></i>
                    <span x-text="selectedWaItem && selectedWaItem.has_collateral_shortfall ? 'WhatsApp Margin Call Deficit Notice' : 'WhatsApp Interest Due Notice'"></span>
                </h3>
                <button @click="waModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <template x-if="selectedWaItem">
                <div class="space-y-4 text-xs">
                    <!-- Customer & Loan Header -->
                    <div class="flex items-center justify-between bg-slate-950 p-3 rounded-2xl border border-slate-800">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Recipient Customer</span>
                            <strong class="text-white text-sm" x-text="selectedWaItem.customer_name"></strong>
                            <span class="text-slate-500 text-[10px] block font-mono" x-text="'Mobile: ' + selectedWaItem.customer_mobile"></span>
                        </div>
                        <div class="text-right font-mono">
                            <span class="text-slate-400 block text-[10px]">Loan Number</span>
                            <strong class="text-amber-400 text-sm" x-text="selectedWaItem.loan_number"></strong>
                        </div>
                    </div>

                    <!-- Deficit Alert Callout in Modal -->
                    <template x-if="selectedWaItem.has_collateral_shortfall">
                        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300">
                            <span class="font-bold block text-xs">⚠️ SHORTFALL DEFICIT: ₹<span x-text="selectedWaItem.shortfall_amount.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                            <span class="text-[11px] block mt-0.5">Total Payable Amount (₹<span x-text="selectedWaItem.total_payable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>) exceeds live collateral value (₹<span x-text="selectedWaItem.current_collateral_value.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>).</span>
                        </div>
                    </template>

                    <!-- Notice Text Container -->
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Generated WhatsApp Message:</label>
                        <textarea readonly rows="7" x-text="selectedWaItem.whatsapp_msg"
                                  class="w-full rounded-2xl border border-slate-700 bg-slate-950 p-3 text-xs text-emerald-300 font-mono leading-relaxed focus:outline-none"></textarea>
                    </div>

                    <!-- Open WhatsApp Web Action Button -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="waModal = false" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400">Close</button>
                        <a :href="selectedWaItem.whatsapp_link" target="_blank"
                           class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 transition">
                            <i class="fa-brands fa-whatsapp text-base"></i> Send Margin Call Notice via WhatsApp
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Receive Payment Modal for Selected Due Item -->
    <div x-show="collectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 p-2 sm:p-4 md:p-6 flex min-h-full items-start justify-center">
        <div class="w-full max-w-xl rounded-2xl sm:rounded-3xl border border-emerald-300 dark:border-emerald-500/30 bg-white dark:bg-slate-900 p-3.5 sm:p-5 shadow-2xl space-y-3 sm:space-y-4 my-auto sm:my-6" @click.away="collectModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm shrink-0">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight">Receive Loan Payment</h3>
                        <p class="text-[10px] text-slate-500 leading-tight">Collect overdue / running loan payment & generate receipt</p>
                    </div>
                </div>
                <button type="button" @click="collectModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Loan Dues Summary Grid -->
            <template x-if="selectedLoanItem">
                <div class="rounded-xl sm:rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-50/50 to-orange-50/20 dark:from-amber-950/20 dark:to-slate-900 p-2.5 sm:p-3 space-y-2 shadow-xs">
                    <div class="flex items-center justify-between text-xs border-b border-amber-500/20 pb-1.5">
                        <span class="rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 px-2 py-0.5 font-mono font-bold text-[11px]" x-text="selectedLoanItem.loan_number"></span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs truncate max-w-[200px]" x-text="selectedLoanItem.customer_name"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/10">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-slate-400">Principal Bal</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-slate-800 dark:text-slate-200 mt-0.5">
                                ₹<span x-text="currentBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/10">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-slate-400">Accrued Int</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-slate-800 dark:text-slate-200 mt-0.5">
                                ₹<span x-text="accruedInterest.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-emerald-500/20">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Total Dues</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                                ₹<span x-text="totalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/10">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-yellow-600 dark:text-yellow-400">Collateral Val</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-yellow-600 dark:text-yellow-400 mt-0.5">
                                ₹<span x-text="parseFloat(selectedLoanItem.current_collateral_value || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Quick Amount Fill Shortcuts -->
                    <div class="flex items-center flex-wrap gap-1.5 pt-1.5 border-t border-amber-500/20 text-[10px] sm:text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400 font-bold flex items-center gap-1 shrink-0">
                            <i class="fa-solid fa-bolt text-amber-500 text-[10px]"></i> Quick:
                        </span>
                        <button type="button" @click="payCompleteLoan()" class="inline-flex items-center gap-1 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 px-2.5 py-1 font-black shadow-xs hover:brightness-110 border border-emerald-400/60 transition cursor-pointer">
                            <i class="fa-solid fa-crown text-[10px]"></i> Pay Complete (<span x-text="'₹' + netPayableAfterDiscount.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>)
                        </button>
                        <button type="button" @click="setAmount(currentBalance)" class="inline-flex items-center gap-1 rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-300 px-2 py-1 font-bold hover:bg-amber-500/25 border border-amber-500/30 transition cursor-pointer">
                            Principal (<span x-text="'₹' + currentBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>)
                        </button>
                        <template x-if="accruedInterest > 0">
                            <button type="button" @click="setAmount(accruedInterest)" class="inline-flex items-center gap-1 rounded-lg bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 px-2 py-1 font-bold hover:bg-indigo-500/25 border border-indigo-500/30 transition cursor-pointer">
                                Interest (<span x-text="'₹' + accruedInterest.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>)
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <form action="<?= $baseUrl ?>/payments/store" method="POST" class="space-y-3 sm:space-y-3.5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="loan_id" :value="selectedLoanItem ? selectedLoanItem.id : ''">
                <input type="hidden" name="is_full_settlement" :value="isFullSettlement ? '1' : '0'">

                <!-- Amount Received & Discount Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Amount Received (₹) -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-emerald-400 dark:border-emerald-500/50 bg-emerald-50/70 dark:bg-emerald-950/30 p-2.5 space-y-1">
                        <label for="due_modal_receive_amount" class="block text-[10px] sm:text-[11px] font-extrabold uppercase text-emerald-900 dark:text-emerald-300">
                            Amount Received (₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-emerald-600 dark:text-emerald-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="due_modal_receive_amount" name="total_amount" x-model="receiveAmount" required placeholder="0.00" autofocus
                                   class="w-full rounded-lg sm:rounded-xl border border-emerald-400 dark:border-emerald-500/50 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Discount / Waiver (₹) -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-amber-300 dark:border-amber-500/40 bg-amber-50/60 dark:bg-amber-950/20 p-2.5 space-y-1">
                        <label for="due_modal_discount" class="flex items-center justify-between text-[10px] sm:text-[11px] font-extrabold uppercase text-amber-900 dark:text-amber-300">
                            <span>Discount / Waiver (₹)</span>
                            <span class="text-[9px] font-normal text-amber-600 dark:text-amber-400 normal-case">Optional</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-amber-600 dark:text-amber-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="due_modal_discount" name="discount" x-model="discount" @input="onDiscountChange()" placeholder="0.00"
                                   class="w-full rounded-lg sm:rounded-xl border border-amber-300 dark:border-amber-500/40 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Live Payment Summary & Balance Preview -->
                <div x-show="selectedLoanItem" class="rounded-xl sm:rounded-2xl bg-slate-50 dark:bg-slate-950/80 p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 space-y-1.5 text-xs">
                    <!-- Total Dues / Net Payable Row -->
                    <div class="flex items-center justify-between font-bold">
                        <span class="text-slate-600 dark:text-slate-400 text-[11px] sm:text-xs" x-text="parsedDiscount > 0 ? 'Total Dues (Gross):' : 'Total Dues Payable:'">Total Dues Payable:</span>
                        <span class="font-mono font-black text-slate-900 dark:text-slate-100 text-xs sm:text-sm">
                            ₹<span x-text="totalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>

                    <!-- Discount / Waiver Row (Shown only if discount entered) -->
                    <template x-if="parsedDiscount > 0">
                        <div class="space-y-1 pt-1 border-t border-slate-200 dark:border-slate-800">
                            <div class="flex items-center justify-between text-amber-700 dark:text-amber-400 font-bold text-[11px]">
                                <span class="flex items-center gap-1"><i class="fa-solid fa-tag text-[9px]"></i> Less Discount / Waiver:</span>
                                <span class="font-mono font-black">-₹<span x-text="parsedDiscount.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                            </div>
                            <div class="flex items-center justify-between font-bold text-emerald-700 dark:text-emerald-400 text-xs">
                                <span>Net Payable After Discount:</span>
                                <span class="font-mono font-black">₹<span x-text="netPayableAfterDiscount.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                            </div>
                        </div>
                    </template>

                    <!-- Remaining Balance Row -->
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200 dark:border-slate-800 font-mono">
                        <span class="text-slate-600 dark:text-slate-400 font-sans font-bold text-[11px] sm:text-xs">Remaining Balance After Payment:</span>
                        <span class="font-black text-xs sm:text-sm" :class="isFullSettlement ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                            ₹<span x-text="remainingBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>

                    <!-- Full Settlement Banner -->
                    <template x-if="isFullSettlement">
                        <div class="mt-1 flex items-center gap-1.5 rounded-lg bg-emerald-500/15 p-2 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-xs shrink-0"></i>
                            <span>Full Settlement! Loan account will be marked <strong>CLOSED</strong> & jewellery automatically marked as <strong>DELIVERED</strong>.</span>
                        </div>
                    </template>
                </div>

                <!-- Payment Mode, Date & Receipt # Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                        <label for="due_modal_payment_mode" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Mode</label>
                        <select id="due_modal_payment_mode" name="payment_mode" x-model="paymentMode" required
                                class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-bold text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                            <option value="Cash">💵 Cash</option>
                            <option value="UPI">📱 UPI / QR</option>
                            <option value="Bank Transfer">🏦 Bank Transfer</option>
                            <option value="Cheque">📄 Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label for="due_modal_payment_date" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Date</label>
                        <input type="date" id="due_modal_payment_date" name="payment_date" x-model="paymentDate" required
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="due_modal_receipt_number" class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Receipt#</label>
                        <input type="text" id="due_modal_receipt_number" name="receipt_number" :value="receiptNumber" readonly
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 cursor-not-allowed">
                    </div>
                </div>

                <!-- Reference & Remarks Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label for="due_modal_reference_number" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Ref / UTR / Cheque# (Optional)
                        </label>
                        <input type="text" id="due_modal_reference_number" name="reference_number" x-model="referenceNumber" placeholder="Transaction ref#"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-2.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label for="due_modal_remarks" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Remarks / Notes (Optional)
                        </label>
                        <input type="text" id="due_modal_remarks" name="remarks" x-model="remarks" placeholder="Optional notes..."
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-2.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="collectModal = false" class="rounded-lg px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-black text-white hover:bg-emerald-500 shadow-sm shadow-emerald-600/30 transition uppercase tracking-wider">
                        <i class="fa-solid fa-check"></i>
                        <span>Save Payment & Print Receipt</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
