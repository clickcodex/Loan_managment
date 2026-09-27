<?php
$pageTitle = 'Payments & Receipts Directory';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Compute quick stats
$totalCollected = array_reduce($payments, fn($sum, $p) => $sum + floatval($p['total_amount']), 0);
$todayDate = date('Y-m-d');
$todayCollected = array_reduce($payments, fn($sum, $p) => $sum + ($p['payment_date'] === $todayDate ? floatval($p['total_amount']) : 0), 0);
?>

<div class="space-y-6" x-data="{
    collectModal: false,
    loans: <?= htmlspecialchars(json_encode($loans ?? [])) ?>,
    selectedLoanId: '',
    amountReceived: '',
    discount: '',
    paymentMode: 'Cash',
    paymentDate: '<?= date('Y-m-d') ?>',
    receiptNumber: '<?= htmlspecialchars($nextReceiptNumber ?? '') ?>',
    referenceNumber: '',
    remarks: '',
    get selectedLoan() {
        return this.loans.find(l => l.id == this.selectedLoanId) || null;
    },
    get currentBalance() {
        return this.selectedLoan ? parseFloat(this.selectedLoan.principal_balance || this.selectedLoan.principal_amount || 0) : 0;
    },
    get accruedInterest() {
        return this.selectedLoan ? parseFloat(this.selectedLoan.accrued_interest || 0) : 0;
    },
    get totalPayable() {
        return this.selectedLoan ? parseFloat(this.selectedLoan.total_payable || 0) : 0;
    },
    get parsedDiscount() {
        const val = parseFloat(this.discount);
        return isNaN(val) ? 0 : Math.max(0, val);
    },
    get netPayableAfterDiscount() {
        return Math.max(0, this.totalPayable - this.parsedDiscount);
    },
    get parsedAmount() {
        const val = parseFloat(this.amountReceived);
        return isNaN(val) ? 0 : val;
    },
    get remainingBalance() {
        return Math.max(0, this.netPayableAfterDiscount - this.parsedAmount);
    },
    get isFullSettlement() {
        return this.selectedLoan && this.netPayableAfterDiscount > 0 && this.parsedAmount >= this.netPayableAfterDiscount;
    },
    payCompleteLoan() {
        this.amountReceived = this.netPayableAfterDiscount.toFixed(2);
    },
    onDiscountChange() {
        if (this.amountReceived !== '' && parseFloat(this.amountReceived) > this.netPayableAfterDiscount) {
            this.amountReceived = this.netPayableAfterDiscount.toFixed(2);
        }
    },
    openCollectModal(loanId = '') {
        this.selectedLoanId = loanId;
        this.amountReceived = '';
        this.discount = '';
        this.paymentMode = 'Cash';
        this.paymentDate = '<?= date('Y-m-d') ?>';
        this.referenceNumber = '';
        this.remarks = '';
        this.collectModal = true;
    },
    setAmount(val) {
        this.amountReceived = parseFloat(val).toFixed(2);
    }
}">

    <!-- Page Header & Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-receipt text-purple-500"></i> Payments & Receipts Directory
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Collect payments, record ledger credits, and print formal receipts</p>
        </div>
        <button type="button" @click="openCollectModal()" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-500 transition">
            <i class="fa-solid fa-plus-circle text-sm"></i> Collect New Payment
        </button>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-emerald-300 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/5 p-4 shadow-sm">
            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Total Amount Collected</span>
            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1 font-mono">₹<?= number_format($totalCollected, 2) ?></p>
            <span class="text-[11px] text-emerald-800/80 dark:text-emerald-500/80 font-bold">Lifetime collection</span>
        </div>

        <div class="rounded-2xl border border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/5 p-4 shadow-sm">
            <span class="text-xs font-bold text-amber-800 dark:text-amber-300">Today's Collections</span>
            <p class="text-2xl font-black text-amber-700 dark:text-amber-400 mt-1 font-mono">₹<?= number_format($todayCollected, 2) ?></p>
            <span class="text-[11px] text-amber-800/80 dark:text-amber-500/80 font-bold">Collected on <?= date('d M Y') ?></span>
        </div>

        <div class="rounded-2xl border border-purple-300 dark:border-purple-500/30 bg-purple-50 dark:bg-purple-500/5 p-4 shadow-sm">
            <span class="text-xs font-bold text-purple-800 dark:text-purple-300">Total Receipt Transactions</span>
            <p class="text-2xl font-black text-purple-700 dark:text-purple-300 mt-1 font-mono"><?= number_format(count($payments)) ?></p>
            <span class="text-[11px] text-purple-800/80 dark:text-purple-400/80 font-bold">Recorded payment receipts</span>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
        
        <!-- Mode Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <a href="<?= $baseUrl ?>/payments<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= empty($paymentMode) ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                All Modes
            </a>
            <a href="<?= $baseUrl ?>/payments?mode=Cash<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $paymentMode === 'Cash' ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Cash
            </a>
            <a href="<?= $baseUrl ?>/payments?mode=UPI<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $paymentMode === 'UPI' ? 'bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                UPI
            </a>
            <a href="<?= $baseUrl ?>/payments?mode=Bank Transfer<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $paymentMode === 'Bank Transfer' ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Bank Transfer
            </a>
            <a href="<?= $baseUrl ?>/payments?mode=Cheque<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $paymentMode === 'Cheque' ? 'bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Cheque
            </a>
        </div>

        <!-- Search Input -->
        <form action="<?= $baseUrl ?>/payments" method="GET" class="flex items-center gap-2">
            <?php if (!empty($paymentMode)): ?>
                <input type="hidden" name="mode" value="<?= htmlspecialchars($paymentMode) ?>">
            <?php endif; ?>
            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Receipt#, loan#, customer, UTR..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
            </div>
            <button type="submit" class="rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/payments" class="text-xs text-slate-500 hover:underline">Clear</a>
            <?php endif; ?>
        </form>

    </div>

    <!-- Payments Directory Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($payments)): ?>
            <div class="py-16 text-center text-slate-500">
                <i class="fa-solid fa-receipt text-4xl mb-3 text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No payment records found</p>
                <p class="text-xs mt-1 text-slate-500">Try adjusting search query or collect a new payment.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Receipt#</th>
                            <th class="py-3 px-4">Loan Number</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Mode / Ref</th>
                            <th class="py-3 px-4 text-right">Total Amount</th>
                            <th class="py-3 px-4 text-right">Print</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($payments as $p): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <!-- Receipt Number -->
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    <a href="<?= $baseUrl ?>/receipts/payment/<?= $p['id'] ?>" target="_blank" class="hover:underline">
                                        <?= htmlspecialchars($p['receipt_number']) ?>
                                    </a>
                                </td>

                                <!-- Loan Number -->
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-600 dark:text-amber-400">
                                    <a href="<?= $baseUrl ?>/loans/<?= $p['loan_id'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($p['loan_number']) ?>
                                    </a>
                                </td>

                                <!-- Customer -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($p['customer_name']) ?></div>
                                    <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($p['customer_mobile']) ?></span>
                                </td>

                                <!-- Payment Date -->
                                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400">
                                    <?= htmlspecialchars($p['payment_date']) ?>
                                </td>

                                <!-- Mode & Ref -->
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($p['payment_mode']) ?></span>
                                    <?php if (!empty($p['reference_number'])): ?>
                                        <span class="block text-[10px] font-mono text-slate-500 dark:text-slate-400">Ref: <?= htmlspecialchars($p['reference_number']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Total Amount -->
                                <td class="py-3.5 px-4 text-right font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">
                                    ₹<?= number_format($p['total_amount'], 2) ?>
                                </td>

                                <!-- Print Action -->
                                <td class="py-3.5 px-4 text-right">
                                    <a href="<?= $baseUrl ?>/receipts/payment/<?= $p['id'] ?>" target="_blank" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50 transition">
                                        <i class="fa-solid fa-print mr-1"></i> Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </    <!-- Collect / Receive Payment Popup Modal -->
    <div x-show="collectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 p-2 sm:p-4 md:p-6 flex min-h-full items-start justify-center">
        <div class="w-full max-w-xl rounded-2xl sm:rounded-3xl border border-emerald-300 dark:border-emerald-500/30 bg-white dark:bg-slate-900 p-3.5 sm:p-5 shadow-2xl space-y-3 sm:space-y-4 my-auto sm:my-6" @click.away="collectModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm shrink-0">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight">Receive Loan Payment</h3>
                        <p class="text-[10px] text-slate-500 leading-tight">Live dues calculation & printable receipt</p>
                    </div>
                </div>
                <button type="button" @click="collectModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form action="<?= $baseUrl ?>/payments/store" method="POST" class="space-y-3 sm:space-y-3.5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="is_full_settlement" :value="isFullSettlement ? '1' : '0'">

                <!-- Loan Account Selection -->
                <div>
                    <label for="modal_loan_id" class="block text-[10px] sm:text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                        Select Loan Account <span class="text-rose-500">*</span>
                    </label>
                    <select id="modal_loan_id" name="loan_id" x-model="selectedLoanId" required 
                            class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-amber-600 dark:text-amber-400 font-bold focus:border-amber-500 focus:outline-none">
                        <option value="">-- Choose Active Loan Account --</option>
                        <template x-for="l in loans" :key="l.id">
                            <option :value="l.id" 
                                    x-text="l.loan_number + ' • ' + l.customer_name + ' (Balance: ₹' + (parseFloat(l.principal_balance || l.principal_amount)).toLocaleString('en-IN', {minimumFractionDigits: 2}) + ')'">
                            </option>
                        </template>
                    </select>
                </div>

                <!-- Selected Loan Dynamic Dues Info Card -->
                <div x-show="selectedLoan" x-cloak class="rounded-xl sm:rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-50/50 to-orange-50/20 dark:from-amber-950/20 dark:to-slate-900 p-2.5 sm:p-3 space-y-2 shadow-xs">
                    <div class="flex items-center justify-between text-xs border-b border-amber-500/20 pb-1.5">
                        <span class="rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 px-2 py-0.5 font-mono font-bold text-[11px]" x-text="selectedLoan ? selectedLoan.loan_number : ''"></span>
                        <span class="font-bold text-slate-900 dark:text-white text-xs truncate max-w-[200px]" x-text="selectedLoan ? selectedLoan.customer_name : ''"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-slate-200 dark:border-slate-800">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-slate-400">Principal</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-slate-800 dark:text-slate-200 mt-0.5">
                                ₹<span x-text="selectedLoan ? parseFloat(selectedLoan.principal_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '0.00'"></span>
                            </span>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/20">
                            <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-amber-600 dark:text-amber-400">Balance</span>
                            <span class="block text-[11px] sm:text-xs font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5">
                                ₹<span x-text="currentBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-slate-200 dark:border-slate-800">
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

                <!-- Amount Received & Discount Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Amount Received Input -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-emerald-400 dark:border-emerald-500/50 bg-emerald-50/70 dark:bg-emerald-950/30 p-2.5 space-y-1">
                        <label for="collect_modal_amount" class="block text-[10px] sm:text-[11px] font-extrabold uppercase text-emerald-900 dark:text-emerald-300">
                            Amount Received (₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-emerald-600 dark:text-emerald-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="collect_modal_amount" name="total_amount" x-model="amountReceived" required placeholder="0.00"
                                   class="w-full rounded-lg sm:rounded-xl border border-emerald-400 dark:border-emerald-500/50 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Discount / Waiver (₹) -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-amber-300 dark:border-amber-500/40 bg-amber-50/60 dark:bg-amber-950/20 p-2.5 space-y-1">
                        <label for="collect_modal_discount" class="flex items-center justify-between text-[10px] sm:text-[11px] font-extrabold uppercase text-amber-900 dark:text-amber-300">
                            <span>Discount / Waiver (₹)</span>
                            <span class="text-[9px] font-normal text-amber-600 dark:text-amber-400 normal-case">Optional</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-amber-600 dark:text-amber-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="collect_modal_discount" name="discount" x-model="discount" @input="onDiscountChange()" placeholder="0.00"
                                   class="w-full rounded-lg sm:rounded-xl border border-amber-300 dark:border-amber-500/40 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Live Payment Summary & Balance Preview -->
                <div x-show="selectedLoan" class="rounded-xl sm:rounded-2xl bg-slate-50 dark:bg-slate-950/80 p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 space-y-1.5 text-xs">
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
                        <label for="collect_modal_mode" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Payment Mode</label>
                        <select id="collect_modal_mode" name="payment_mode" x-model="paymentMode" required
                                class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-bold text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                            <option value="Cash">💵 Cash</option>
                            <option value="UPI">📱 UPI / QR Code</option>
                            <option value="Bank Transfer">🏦 Bank Transfer</option>
                            <option value="Cheque">📄 Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label for="collect_modal_date" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Payment Date</label>
                        <input type="date" id="collect_modal_date" name="payment_date" x-model="paymentDate" required
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="collect_modal_receipt" class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Receipt Number</label>
                        <input type="text" id="collect_modal_receipt" name="receipt_number" :value="receiptNumber" readonly
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 cursor-not-allowed">
                    </div>
                </div>

                <!-- Reference & Remarks Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label for="collect_modal_ref" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Ref / UTR / Cheque# (Optional)
                        </label>
                        <input type="text" id="collect_modal_ref" name="reference_number" x-model="referenceNumber" placeholder="Optional transaction ref#"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-2.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label for="collect_modal_remarks" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Remarks / Notes (Optional)
                        </label>
                        <input type="text" id="collect_modal_remarks" name="remarks" x-model="remarks" placeholder="Simple notes if any..."
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
