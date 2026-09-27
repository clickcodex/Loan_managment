<?php
$pageTitle = 'Customer Profile - ' . htmlspecialchars($customer['full_name']);
$activeLoans = $activeLoans ?? array_values(array_filter($loans, fn($l) => ($l['status'] ?? '') !== 'Closed'));
$closedLoans = $closedLoans ?? array_values(array_filter($loans, fn($l) => ($l['status'] ?? '') === 'Closed'));
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<script>
function customerProfileEngine() {
    return {
        activeTab: 'loans', 
        uploadModal: false,
        collectModal: false,
        addPaymentModal: false,
        selectedLoanItem: null,
        loansList: <?= json_encode($loans, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        
        // Receive Payment Modal state
        receiveAmount: '',
        discount: '',
        paymentMode: 'Cash',
        paymentDate: '<?= date('Y-m-d') ?>',
        receiptNumber: '<?= htmlspecialchars($nextReceiptNumber ?? '') ?>',
        referenceNumber: '',
        remarks: '',
        get currentBalance() {
            return this.selectedLoanItem ? parseFloat(this.selectedLoanItem.running_balance || this.selectedLoanItem.principal_balance || this.selectedLoanItem.principal_amount || 0) : 0;
        },
        get accruedInterest() {
            return this.selectedLoanItem ? parseFloat(this.selectedLoanItem.accrued_interest || 0) : 0;
        },
        get totalPayable() {
            return this.selectedLoanItem ? parseFloat(this.selectedLoanItem.total_payable || this.selectedLoanItem.principal_amount || 0) : 0;
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
        openCollectById(id) {
            const found = this.loansList.find(x => parseInt(x.id) === parseInt(id));
            if (found) {
                this.openCollect(found);
            }
        },
        setAmount(val) {
            this.receiveAmount = parseFloat(val).toFixed(2);
        },

        // Add Payment (Top-Up) Modal state
        topupAmount: '',
        topupDate: '<?= date('Y-m-d') ?>',
        topupReason: '',
        get parsedTopupAmount() {
            const v = parseFloat(this.topupAmount);
            return isNaN(v) ? 0 : v;
        },
        get newBalanceAfterTopup() {
            return this.currentBalance + this.parsedTopupAmount;
        },
        openAddPayment(item) {
            this.selectedLoanItem = item;
            this.topupAmount = '';
            this.topupDate = '<?= date('Y-m-d') ?>';
            this.topupReason = '';
            this.addPaymentModal = true;
        },
        openAddPaymentById(id) {
            const found = this.loansList.find(x => parseInt(x.id) === parseInt(id));
            if (found) {
                this.openAddPayment(found);
            }
        }
    };
}
</script>

<div class="space-y-6" x-data="customerProfileEngine()">

    <!-- Profile Header Banner Card -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-gradient-to-br from-white to-slate-50/50 dark:from-slate-900 dark:to-slate-950/50 p-6 shadow-sm relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <!-- Customer Photo -->
                <?php if (!empty($customer['photo'])): ?>
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($customer['photo']) ?>" alt="Photo" class="h-20 w-20 rounded-2xl object-cover border-2 border-amber-500/40 shadow-md">
                <?php else: ?>
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 via-amber-400 to-yellow-300 text-slate-950 font-black text-3xl shadow-md shrink-0">
                        <?= strtoupper(substr($customer['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <!-- Customer Title & Badges -->
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?= htmlspecialchars($customer['full_name']) ?></h1>
                        <?php if ($customer['status'] === 'Active'): ?>
                            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Active</span>
                        <?php elseif ($customer['status'] === 'Closed'): ?>
                            <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-bold text-slate-600 dark:text-slate-400">Closed</span>
                        <?php else: ?>
                            <span class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 border border-rose-500/20">Blocked</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-600 dark:text-slate-400 mt-2.5 font-mono">
                        <span class="text-amber-600 dark:text-amber-400 font-bold"><i class="fa-solid fa-id-card mr-1 text-slate-400"></i> <?= htmlspecialchars($customer['customer_id']) ?></span>
                        <span class="hidden sm:inline text-slate-300 dark:text-slate-700">&bull;</span>
                        <span><i class="fa-solid fa-credit-card mr-1 text-slate-400"></i> Account: <?= htmlspecialchars($customer['account_number'] ?? 'N/A') ?></span>
                        <span class="hidden sm:inline text-slate-300 dark:text-slate-700">&bull;</span>
                        <span><i class="fa-solid fa-phone mr-1 text-slate-400"></i> <?= htmlspecialchars($customer['mobile']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="<?= $baseUrl ?>/loans/create?customer_id=<?= $customer['id'] ?>" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                    <i class="fa-solid fa-plus-circle text-sm"></i> Issue New Loan
                </a>
                <a href="<?= $baseUrl ?>/customers/<?= $customer['id'] ?>/edit" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
                    <i class="fa-solid fa-pen text-amber-500 text-sm"></i> Edit Profile
                </a>
                
                <!-- Status Toggle Dropdown -->
                <div class="relative" x-data="{ statusOpen: false }">
                    <button @click="statusOpen = !statusOpen" @click.away="statusOpen = false" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Status <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </button>
                    <div x-show="statusOpen" x-cloak class="absolute right-0 mt-2 w-36 origin-top-right rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-1 shadow-2xl z-20">
                        <form action="<?= $baseUrl ?>/customers/<?= $customer['id'] ?>/status" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <button type="submit" name="status" value="Active" class="w-full text-left px-4 py-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-slate-50 dark:hover:bg-slate-800">Set Active</button>
                            <button type="submit" name="status" value="Closed" class="w-full text-left px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800">Set Closed</button>
                            <button type="submit" name="status" value="Blocked" class="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-slate-50 dark:hover:bg-slate-800">Set Blocked</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Active Loans -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm border-l-4 border-l-blue-500">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Loans</span>
            <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1"><?= count($loans) ?></p>
        </div>

        <!-- Total Disbursed -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm border-l-4 border-l-amber-500">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Disbursed</span>
            <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono">₹<?= number_format(array_sum(array_column($loans, 'principal_amount')), 2) ?></p>
        </div>

        <!-- Active Principal Balance -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm border-l-4 border-l-emerald-500">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Active Balance</span>
            <?php 
                $activeBal = 0;
                foreach ($loans as $l) {
                    if ($l['status'] === 'Running') {
                        $activeBal += floatval($l['running_balance'] ?? $l['principal_amount']);
                    }
                }
            ?>
            <p class="text-base sm:text-lg lg:text-xl xl:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">₹<?= number_format($activeBal, 2) ?></p>
        </div>

        <!-- Documents Uploaded -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm border-l-4 border-l-indigo-500">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">KYC Vault Docs</span>
            <p class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1"><?= count($documents) ?></p>
        </div>
    </div>

    <!-- Navigation Tabs (With Horizontal Swipe for Mobile viewports) -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto whitespace-nowrap [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <button @click="activeTab = 'loans'" 
                :class="activeTab === 'loans' ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="rounded-xl px-4 py-2 text-xs font-bold transition border border-transparent whitespace-nowrap">
            <i class="fa-solid fa-hand-holding-dollar mr-1.5"></i> Loans (<?= count($activeLoans) ?>)
        </button>

        <button @click="activeTab = 'closed_loans'" 
                :class="activeTab === 'closed_loans' ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="rounded-xl px-4 py-2 text-xs font-bold transition border border-transparent whitespace-nowrap">
            <i class="fa-solid fa-circle-check mr-1.5"></i> Closed Loans (<?= count($closedLoans) ?>)
        </button>

        <button @click="activeTab = 'documents'" 
                :class="activeTab === 'documents' ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="rounded-xl px-4 py-2 text-xs font-bold transition border border-transparent whitespace-nowrap">
            <i class="fa-solid fa-folder-open mr-1.5"></i> Document Vault (<?= count($documents) ?>)
        </button>

        <button @click="activeTab = 'info'" 
                :class="activeTab === 'info' ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                class="rounded-xl px-4 py-2 text-xs font-bold transition border border-transparent whitespace-nowrap">
            <i class="fa-solid fa-circle-info mr-1.5"></i> Profile Info
        </button>
    </div>

    <!-- Tab 1: Active Loans List -->
    <div x-show="activeTab === 'loans'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($activeLoans)): ?>
            <div class="py-16 text-center text-slate-500 space-y-3">
                <i class="fa-solid fa-hand-holding-dollar text-4xl text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No active loans found for this customer.</p>
                <a href="<?= $baseUrl ?>/loans/create?customer_id=<?= $customer['id'] ?>" class="inline-block rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow hover:bg-amber-400 transition">
                    Issue New Loan
                </a>
            </div>
        <?php else: ?>
            
            <!-- Desktop Layout: Standard Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[760px] lg:min-w-full">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-3">Loan #</th>
                            <th class="py-3 px-2.5">Date</th>
                            <th class="py-3 px-3">Collateral Item</th>
                            <th class="py-3 px-2.5 text-right">Payable</th>
                            <th class="py-3 px-2.5 text-right">Received</th>
                            <th class="py-3 px-2.5 text-right">Principal</th>
                            <th class="py-3 px-2.5 text-right">Balance</th>
                            <th class="py-3 px-2.5 text-center">Status</th>
                            <th class="py-3 px-3 text-right sticky right-0 bg-slate-100 dark:bg-slate-950 shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($activeLoans as $l): ?>
                            <?php 
                                $isActive = ($l['status'] === 'Active' || $l['status'] === 'Running');
                                $isClosed = ($l['status'] === 'Closed');
                                $colName = !empty($l['collateral_names']) ? $l['collateral_names'] : (!empty($l['collateral_items_summary']) ? $l['collateral_items_summary'] : $l['security_type']);
                                $totalReceived = floatval($l['total_received'] ?? $l['total_paid'] ?? 0);
                            ?>
                            <tr class="transition group <?= $isActive ? 'border-l-4 border-l-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/25 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/35' : ($isClosed ? 'bg-slate-50/30 dark:bg-slate-900/30 text-slate-500 opacity-80 hover:bg-slate-100/40' : 'hover:bg-slate-50 dark:hover:bg-slate-800/40') ?>">
                                <td class="py-2.5 px-3 font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($l['loan_number']) ?>
                                    </a>
                                </td>
                                <td class="py-2.5 px-2.5 font-mono text-slate-600 dark:text-slate-400 text-[11px] whitespace-nowrap"><?= htmlspecialchars($l['loan_date']) ?></td>
                                <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid <?= str_contains($l['security_type'] ?? '', 'Silver') ? 'fa-ring text-slate-400' : 'fa-gem text-amber-500' ?> text-xs shrink-0"></i>
                                        <span class="truncate max-w-[160px] lg:max-w-[200px]" title="<?= htmlspecialchars($colName) ?>"><?= htmlspecialchars($colName) ?></span>
                                    </div>
                                    <?php if (!empty($l['collateral_items_summary']) && $l['collateral_items_summary'] !== $colName): ?>
                                        <span class="text-[10px] text-slate-400 font-normal block truncate max-w-[160px] lg:max-w-[200px]"><?= htmlspecialchars($l['collateral_items_summary']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-2.5 text-right font-mono font-extrabold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    ₹<?= number_format($l['total_payable'] ?? $l['principal_amount'], 2) ?>
                                </td>
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    ₹<?= number_format($totalReceived, 2) ?>
                                </td>
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold whitespace-nowrap">₹<?= number_format($l['principal_amount'], 2) ?></td>
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    ₹<?= number_format($l['running_balance'] ?? $l['principal_amount'], 2) ?>
                                </td>
                                <td class="py-2.5 px-2.5 text-center whitespace-nowrap">
                                    <?php if ($isActive): ?>
                                        <span class="rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider">Active</span>
                                    <?php elseif ($isClosed): ?>
                                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[9px] font-bold text-slate-500 dark:text-slate-400">Closed</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[9px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/20">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 text-right space-x-1 whitespace-nowrap sticky right-0 bg-inherit shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">
                                    <?php if ($isActive): ?>
                                        <button type="button" @click="openCollectById(<?= $l['id'] ?>)" 
                                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 px-2.5 py-1 text-[11px] font-bold text-slate-950 hover:bg-emerald-400 transition shadow-xs cursor-pointer">
                                            <i class="fa-solid fa-receipt text-[10px]"></i> Receive
                                        </button>
                                        <button type="button" @click="openAddPaymentById(<?= $l['id'] ?>)" 
                                                class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-indigo-500 transition shadow-xs cursor-pointer">
                                            <i class="fa-solid fa-circle-plus text-[10px]"></i> Top-Up
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50 transition">
                                        Details <i class="fa-solid fa-arrow-right ml-0.5 text-[9px]"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Layout: Card Grid View (Hidden on desktop) -->
            <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
                <?php foreach ($activeLoans as $l): ?>
                    <?php 
                        $isActive = ($l['status'] === 'Active' || $l['status'] === 'Running');
                        $isClosed = ($l['status'] === 'Closed');
                        $colName = !empty($l['collateral_names']) ? $l['collateral_names'] : (!empty($l['collateral_items_summary']) ? $l['collateral_items_summary'] : $l['security_type']);
                    ?>
                    <div class="p-4 space-y-3 transition <?= $isActive ? 'border-l-4 border-l-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/25 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/35' : ($isClosed ? 'bg-slate-50/30 dark:bg-slate-900/30 opacity-80' : 'hover:bg-slate-50') ?>">
                        <div class="flex items-center justify-between">
                            <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="font-mono text-xs font-extrabold text-amber-600 dark:text-amber-400 hover:underline">
                                <?= htmlspecialchars($l['loan_number']) ?>
                            </a>
                            <?php if ($isActive): ?>
                                <span class="rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider">Active</span>
                            <?php elseif ($isClosed): ?>
                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-500 dark:text-slate-400">Closed</span>
                            <?php else: ?>
                                <span class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/20">Overdue</span>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs border-t border-slate-100 dark:border-slate-800/60 pt-2.5">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Collateral Item</span>
                                <span class="font-bold text-slate-900 dark:text-white truncate block" title="<?= htmlspecialchars($colName) ?>"><?= htmlspecialchars($colName) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Loan Date</span>
                                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($l['loan_date']) ?></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Principal</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹<?= number_format($l['principal_amount'], 2) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Running Balance</span>
                                <span class="font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                    ₹<?= number_format($l['total_payable'] ?? $l['running_balance'] ?? $l['principal_amount'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800/60 pt-2 flex items-center justify-end gap-2">
                            <?php if ($isActive): ?>
                                <button type="button" @click="openCollectById(<?= $l['id'] ?>)" 
                                        class="inline-flex items-center gap-1 rounded-xl bg-emerald-500 px-3 py-1.5 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition cursor-pointer">
                                    <i class="fa-solid fa-receipt"></i> Receive
                                </button>
                                <button type="button" @click="openAddPaymentById(<?= $l['id'] ?>)" 
                                        class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-500 transition cursor-pointer">
                                    <i class="fa-solid fa-circle-plus"></i> Top-Up
                                </button>
                            <?php endif; ?>
                            <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                Details <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <!-- Tab 2: Closed Loans List -->
    <div x-show="activeTab === 'closed_loans'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($closedLoans)): ?>
            <div class="py-16 text-center text-slate-500 space-y-3">
                <i class="fa-solid fa-circle-check text-4xl text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No closed/completed loans for this customer yet.</p>
                <p class="text-xs text-slate-400">When loans are fully settled and closed, they will be listed here.</p>
            </div>
        <?php else: ?>
            
            <!-- Desktop Layout: Standard Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[760px] lg:min-w-full">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-3">Loan #</th>
                            <th class="py-3 px-2.5">Loan Date</th>
                            <th class="py-3 px-3">Collateral Item</th>
                            <th class="py-3 px-2.5 text-right">Principal Disbursed</th>
                            <th class="py-3 px-2.5 text-right">Total Received</th>
                            <th class="py-3 px-2.5 text-center">Status</th>
                            <th class="py-3 px-3 text-right sticky right-0 bg-slate-100 dark:bg-slate-950 shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($closedLoans as $l): ?>
                            <?php 
                                $colName = !empty($l['collateral_names']) ? $l['collateral_names'] : (!empty($l['collateral_items_summary']) ? $l['collateral_items_summary'] : $l['security_type']);
                                $totalReceived = floatval($l['total_received'] ?? $l['total_paid'] ?? 0);
                            ?>
                            <tr class="transition group bg-slate-50/40 dark:bg-slate-900/40 hover:bg-slate-100/50 dark:hover:bg-slate-800/40">
                                <td class="py-2.5 px-3 font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($l['loan_number']) ?>
                                    </a>
                                </td>
                                <td class="py-2.5 px-2.5 font-mono text-slate-600 dark:text-slate-400 text-[11px] whitespace-nowrap"><?= htmlspecialchars($l['loan_date']) ?></td>
                                <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-solid <?= str_contains($l['security_type'] ?? '', 'Silver') ? 'fa-ring text-slate-400' : 'fa-gem text-amber-500' ?> text-xs shrink-0"></i>
                                        <span class="truncate max-w-[160px] lg:max-w-[200px]" title="<?= htmlspecialchars($colName) ?>"><?= htmlspecialchars($colName) ?></span>
                                    </div>
                                    <?php if (!empty($l['collateral_items_summary']) && $l['collateral_items_summary'] !== $colName): ?>
                                        <span class="text-[10px] text-slate-400 font-normal block truncate max-w-[160px] lg:max-w-[200px]"><?= htmlspecialchars($l['collateral_items_summary']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold whitespace-nowrap">₹<?= number_format($l['principal_amount'], 2) ?></td>
                                <td class="py-2.5 px-2.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    ₹<?= number_format($totalReceived, 2) ?>
                                </td>
                                <td class="py-2.5 px-2.5 text-center whitespace-nowrap">
                                    <span class="rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider">Closed</span>
                                </td>
                                <td class="py-2.5 px-3 text-right space-x-1 whitespace-nowrap sticky right-0 bg-inherit shadow-[-6px_0_8px_-4px_rgba(0,0,0,0.06)]">
                                    <a href="<?= $baseUrl ?>/receipts/closure/<?= $l['id'] ?>" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 transition shadow-2xs" title="Print Loan Closure Receipt">
                                        <i class="fa-solid fa-file-circle-check text-[10px]"></i> Receipt
                                    </a>
                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50 transition">
                                        View History <i class="fa-solid fa-arrow-right ml-0.5 text-[9px]"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Layout: Card Grid View (Hidden on desktop) -->
            <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
                <?php foreach ($closedLoans as $l): ?>
                    <?php 
                        $colName = !empty($l['collateral_names']) ? $l['collateral_names'] : (!empty($l['collateral_items_summary']) ? $l['collateral_items_summary'] : $l['security_type']);
                        $totalReceived = floatval($l['total_received'] ?? $l['total_paid'] ?? 0);
                    ?>
                    <div class="p-4 space-y-3 transition bg-slate-50/30 dark:bg-slate-900/30 opacity-90 hover:bg-slate-100/50">
                        <div class="flex items-center justify-between">
                            <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="font-mono text-xs font-extrabold text-amber-600 dark:text-amber-400 hover:underline">
                                <?= htmlspecialchars($l['loan_number']) ?>
                            </a>
                            <span class="rounded-full bg-slate-200 dark:bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400">Closed</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs border-t border-slate-100 dark:border-slate-800/60 pt-2.5">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Collateral Item</span>
                                <span class="font-bold text-slate-900 dark:text-white truncate block" title="<?= htmlspecialchars($colName) ?>"><?= htmlspecialchars($colName) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Loan Date</span>
                                <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($l['loan_date']) ?></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Principal</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹<?= number_format($l['principal_amount'], 2) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Total Settled</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">₹<?= number_format($totalReceived, 2) ?></span>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800/60 pt-2 flex items-center justify-end">
                            <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                View History <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <!-- Tab 2: Document Vault -->
    <div x-show="activeTab === 'documents'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Uploaded KYC & Identity Documents</h2>
            <button @click="uploadModal = true" class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow hover:bg-amber-400 transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-upload"></i> Upload Document
            </button>
        </div>

        <?php if (empty($documents)): ?>
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center text-slate-500 space-y-2">
                <i class="fa-solid fa-folder-open text-3xl text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No documents or video proofs uploaded for this customer vault yet.</p>
                <p class="text-xs text-slate-500">Upload Aadhaar scan, PAN card, photo, loan agreements, or loan sanction/closure video proofs.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($documents as $d): ?>
                    <?php 
                        $ext = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
                        $isVid = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']);
                    ?>
                    <div class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-3 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="rounded-lg px-2.5 py-1 text-[10px] font-bold uppercase truncate max-w-[150px] <?= $isVid ? 'bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20' ?>">
                                <?= htmlspecialchars($d['document_type']) ?>
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono"><?= date('M d, Y', strtotime($d['created_at'])) ?></span>
                        </div>

                        <div class="h-28 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden relative">
                            <?php if ($isVid): ?>
                                <video controls class="h-full w-full object-cover">
                                    <source src="<?= $baseUrl ?>/<?= htmlspecialchars($d['file_path']) ?>" type="video/mp4">
                                </video>
                            <?php elseif (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($d['file_path']) ?>" alt="Doc" class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                            <?php else: ?>
                                <i class="fa-solid fa-file-pdf text-3xl text-rose-500"></i>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <span class="font-bold text-slate-900 dark:text-slate-200 truncate max-w-[140px] block" title="<?= htmlspecialchars($d['original_name']) ?>"><?= htmlspecialchars($d['original_name']) ?></span>
                            <form action="<?= $baseUrl ?>/customers/documents/<?= $d['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this document?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                <button type="submit" class="text-slate-400 hover:text-rose-500 p-1.5 rounded-lg hover:bg-rose-500/10 transition-colors">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab 3: Profile Information -->
    <div x-show="activeTab === 'info'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Contact & Address -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-user-tag"></i> Personal & Contact Details
            </h2>

            <div class="space-y-3.5 text-xs">
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Father / Spouse Name:</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($customer['father_name'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Primary Mobile:</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono"><?= htmlspecialchars($customer['mobile']) ?></span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Alternate Mobile:</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono"><?= htmlspecialchars($customer['alt_mobile'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Aadhaar Card:</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono"><?= htmlspecialchars($customer['aadhaar'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">PAN Card:</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono"><?= htmlspecialchars($customer['pan'] ?? 'N/A') ?></span>
                </div>
                <div class="py-1">
                    <span class="text-slate-500 block mb-1.5">Residential Address:</span>
                    <p class="font-semibold text-slate-900 dark:text-slate-200 bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800 leading-relaxed">
                        <?= htmlspecialchars($customer['address'] ?? 'N/A') ?>
                        <?= !empty($customer['village']) ? ', ' . htmlspecialchars($customer['village']) : '' ?>
                        <?= !empty($customer['city']) ? ', ' . htmlspecialchars($customer['city']) : '' ?>
                        <?= !empty($customer['state']) ? ', ' . htmlspecialchars($customer['state']) : '' ?>
                        <?= !empty($customer['pincode']) ? ' - ' . htmlspecialchars($customer['pincode']) : '' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Guarantor & System Info -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-nodes"></i> Guarantor & System Information
            </h2>

            <div class="space-y-3.5 text-xs">
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Guarantor Name:</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($customer['guarantor_name'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Guarantor Mobile:</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono"><?= htmlspecialchars($customer['guarantor_mobile'] ?? 'N/A') ?></span>
                </div>
                <div class="py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500 block mb-1.5">Guarantor Address:</span>
                    <p class="font-semibold text-slate-900 dark:text-slate-200 bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                        <?= htmlspecialchars($customer['guarantor_address'] ?? 'N/A') ?>
                    </p>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100 dark:border-slate-800/60">
                    <span class="text-slate-500">Registration Date:</span>
                    <span class="font-bold text-slate-900 dark:text-slate-300 font-mono"><?= date('M d, Y', strtotime($customer['created_at'])) ?></span>
                </div>
                <div class="py-1">
                    <span class="text-slate-500 block mb-1.5">Account Remarks:</span>
                    <p class="text-slate-600 dark:text-slate-400 italic bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800 leading-relaxed">
                        <?= htmlspecialchars($customer['remarks'] ?: 'No internal remarks recorded.') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Document Modal -->
    <div x-show="uploadModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm" x-cloak>
        
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" 
             @click.away="uploadModal = false"
             x-show="uploadModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95"
             x-transition:enter-end="scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="scale-100"
             x-transition:leave-end="scale-95">
             
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-upload text-amber-500"></i> Upload KYC Document / Video
                </h3>
                <button @click="uploadModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="<?= $baseUrl ?>/customers/<?= $customer['id'] ?>/documents" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label for="document_type" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Document Category</label>
                    <select id="document_type" name="document_type" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                        <option value="Loan Sanction Video Proof (Disbursement)">📹 Loan Sanction Video Proof (Giving Loan / Disbursement)</option>
                        <option value="Loan Closure Video Proof (Collateral Release)">🎬 Loan Closure Video Proof (After Loan Complete / Release)</option>
                        <option value="Customer Photo">Customer Photo</option>
                        <option value="Aadhaar Card" selected>Aadhaar Card</option>
                        <option value="PAN Card">PAN Card</option>
                        <option value="Address Proof">Address Proof</option>
                        <option value="Bank Passbook / Cheque">Bank Passbook / Cheque</option>
                        <option value="Loan Agreement">Loan Agreement</option>
                        <option value="Other KYC Document">Other KYC Document</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Attach Document / Proof File</label>
                    
                    <div class="space-y-2.5">
                        <input type="file" id="document_file" name="document_file" accept="image/*,application/pdf,video/*"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-950">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4.5 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="uploadModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900 transition-colors">Cancel</button>
                    <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow transition-colors">
                        Upload to Vault
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receive Payment Modal for Selected Customer Loan -->
    <div x-show="collectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 p-2 sm:p-4 md:p-6 flex min-h-full items-start justify-center">
        <div class="w-full max-w-xl rounded-2xl sm:rounded-3xl border border-emerald-300 dark:border-emerald-500/30 bg-white dark:bg-slate-900 p-3.5 sm:p-5 shadow-2xl space-y-3 sm:space-y-4 my-auto sm:my-6" @click.away="collectModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm shrink-0">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight">Receive Loan Payment</h3>
                        <p class="text-[10px] text-slate-500 leading-tight">Record customer payment & live balance update</p>
                    </div>
                </div>
                <button type="button" @click="collectModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Loan Summary Grid -->
            <template x-if="selectedLoanItem">
                <div class="rounded-xl sm:rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-50/50 to-orange-50/20 dark:from-amber-950/20 dark:to-slate-900 p-2.5 sm:p-3 space-y-2 shadow-xs">
                    <div class="flex items-center justify-between text-xs border-b border-amber-500/20 pb-1.5">
                        <span class="rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 px-2 py-0.5 font-mono font-bold text-[11px]" x-text="selectedLoanItem.loan_number"></span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs truncate max-w-[200px]"><?= htmlspecialchars($customer['full_name']) ?></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/10">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-slate-400">Principal</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-slate-800 dark:text-slate-200 mt-0.5">
                                ₹<span x-text="parseFloat(selectedLoanItem.principal_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/20">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-amber-600 dark:text-amber-400">Balance</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5">
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
                            <span class="block text-xs font-black font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                                ₹<span x-text="totalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
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
                        <label for="cust_modal_receive_amount" class="block text-[10px] sm:text-[11px] font-extrabold uppercase text-emerald-900 dark:text-emerald-300">
                            Amount Received (₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-emerald-600 dark:text-emerald-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="cust_modal_receive_amount" name="total_amount" x-model="receiveAmount" required placeholder="0.00" autofocus
                                   class="w-full rounded-lg sm:rounded-xl border border-emerald-400 dark:border-emerald-500/50 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Discount / Waiver (₹) -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-amber-300 dark:border-amber-500/40 bg-amber-50/60 dark:bg-amber-950/20 p-2.5 space-y-1">
                        <label for="cust_modal_discount" class="flex items-center justify-between text-[10px] sm:text-[11px] font-extrabold uppercase text-amber-900 dark:text-amber-300">
                            <span>Discount / Waiver (₹)</span>
                            <span class="text-[9px] font-normal text-amber-600 dark:text-amber-400 normal-case">Optional</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-amber-600 dark:text-amber-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="cust_modal_discount" name="discount" x-model="discount" @input="onDiscountChange()" placeholder="0.00"
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
                        <label for="cust_modal_payment_mode" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Mode</label>
                        <select id="cust_modal_payment_mode" name="payment_mode" x-model="paymentMode" required
                                class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-bold text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                            <option value="Cash">💵 Cash</option>
                            <option value="UPI">📱 UPI / QR</option>
                            <option value="Bank Transfer">🏦 Bank Transfer</option>
                            <option value="Cheque">📄 Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label for="cust_modal_payment_date" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Date</label>
                        <input type="date" id="cust_modal_payment_date" name="payment_date" x-model="paymentDate" required
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="cust_modal_receipt_number" class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Receipt#</label>
                        <input type="text" id="cust_modal_receipt_number" name="receipt_number" :value="receiptNumber" readonly
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 cursor-not-allowed">
                    </div>
                </div>

                <!-- Reference & Remarks Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label for="cust_modal_reference_number" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Ref / UTR / Cheque# (Optional)
                        </label>
                        <input type="text" id="cust_modal_reference_number" name="reference_number" x-model="referenceNumber" placeholder="Transaction ref#"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-2.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label for="cust_modal_receive_remarks" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Remarks / Notes (Optional)
                        </label>
                        <input type="text" id="cust_modal_receive_remarks" name="remarks" x-model="remarks" placeholder="Optional notes..."
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

    <!-- Add Payment (Top-Up) Modal for Selected Customer Loan -->
    <div x-show="addPaymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-lg rounded-3xl border border-indigo-200 dark:border-indigo-500/30 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-5 my-8" @click.away="addPaymentModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-bold text-base">
                        <i class="fa-solid fa-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Loan Top-Up (Add Payment)</h3>
                        <p class="text-[11px] text-slate-500">Disburse extra funds on top of existing running loan balance</p>
                    </div>
                </div>
                <button type="button" @click="addPaymentModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Current Loan Summary Banner -->
            <template x-if="selectedLoanItem">
                <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900/50 bg-gradient-to-r from-indigo-50/70 to-slate-50 dark:from-indigo-950/40 dark:to-slate-950 p-4 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedLoanItem.loan_number"></span>
                        <span class="font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($customer['full_name']) ?></span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-indigo-100 dark:border-indigo-900/40 text-[11px]">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Principal</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">₹<span x-text="parseFloat(selectedLoanItem.principal_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Current Balance</span>
                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400">₹<span x-text="currentBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Rate</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300"><span x-text="selectedLoanItem.interest_rate"></span>%</span>
                        </div>
                    </div>
                </div>
            </template>

            <form :action="'<?= $baseUrl ?>/loans/' + (selectedLoanItem ? selectedLoanItem.id : '') + '/topup'" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <!-- Top-Up Amount -->
                <div class="rounded-2xl border-2 border-indigo-400 dark:border-indigo-500/50 bg-indigo-50/50 dark:bg-indigo-950/20 p-4 space-y-2">
                    <label for="cust_modal_topup_amount" class="block text-[11px] font-extrabold uppercase text-indigo-900 dark:text-indigo-300">
                        Additional Disbursement Amount (₹) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-indigo-600 dark:text-indigo-400 font-black text-xl">₹</span>
                        <input type="number" step="0.01" min="1" id="cust_modal_topup_amount" name="topup_amount" x-model="topupAmount" required placeholder="0.00" autofocus
                               class="w-full rounded-xl border border-indigo-400 dark:border-indigo-500/50 bg-white dark:bg-slate-950 py-3 pl-9 pr-4 text-xl font-black text-indigo-600 dark:text-indigo-400 placeholder-slate-400 focus:border-indigo-500 focus:outline-none">
                    </div>

                    <!-- Live Calculation Result -->
                    <div x-show="parsedTopupAmount > 0" class="pt-2 border-t border-indigo-200 dark:border-indigo-800/60 flex items-center justify-between text-xs font-mono">
                        <span class="text-slate-500 dark:text-slate-400 text-[11px]">New Resulting Balance:</span>
                        <span class="text-base font-black text-indigo-600 dark:text-indigo-400">
                            ₹<span x-text="newBalanceAfterTopup.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>
                </div>

                <!-- Disbursement Date -->
                <div>
                    <label for="cust_modal_topup_date" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Disbursement Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="cust_modal_topup_date" name="topup_date" x-model="topupDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-indigo-500 focus:outline-none">
                </div>

                <!-- Reason / Notes -->
                <div>
                    <label for="cust_modal_topup_reason" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Reason / Notes (Optional)
                    </label>
                    <textarea id="cust_modal_topup_reason" name="reason" x-model="topupReason" rows="2" placeholder="e.g. Additional gold ornament pledged, emergency top-up..."
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="addPaymentModal = false" class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-black text-white hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition uppercase tracking-wider">
                        <i class="fa-solid fa-check"></i>
                        <span>Confirm & Disburse Top-Up</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>