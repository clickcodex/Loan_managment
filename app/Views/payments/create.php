<?php
$pageTitle = 'Receive Loan Payment';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="max-w-3xl mx-auto space-y-6" 
     x-data="{
         loans: <?= htmlspecialchars(json_encode($loans)) ?>,
         selectedLoanId: '<?= $selectedLoan['id'] ?? '' ?>',
         amountReceived: '',
         get selectedLoan() {
             return this.loans.find(l => l.id == this.selectedLoanId) || null;
         },
         get currentBalance() {
             return this.selectedLoan ? parseFloat(this.selectedLoan.total_payable || this.selectedLoan.principal_balance || this.selectedLoan.principal_amount || 0) : 0;
         },
         get accruedInterest() {
             return this.selectedLoan ? parseFloat(this.selectedLoan.accrued_interest || 0) : 0;
         },
         get totalPayable() {
             return this.selectedLoan ? parseFloat(this.selectedLoan.total_payable || 0) : 0;
         },
         get parsedAmount() {
             const val = parseFloat(this.amountReceived);
             return isNaN(val) ? 0 : val;
         },
         get remainingBalance() {
             return Math.max(0, this.currentBalance - this.parsedAmount);
         },
         get isFullSettlement() {
             return this.selectedLoan && this.parsedAmount >= this.currentBalance && this.currentBalance > 0;
         }
     }">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-receipt text-emerald-600 dark:text-emerald-400"></i> Receive Loan Payment
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Record payment received from customer with automated receipt number & live remaining balance</p>
        </div>
        <a href="<?= $baseUrl ?>/payments" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Directory
        </a>
    </div>

    <!-- Collection Form Card -->
    <form action="<?= $baseUrl ?>/payments/store" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 space-y-6 shadow-sm">
            
            <!-- Loan Selection -->
            <div>
                <label for="loan_id" class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-1.5">
                    Select Loan Account <span class="text-rose-500">*</span>
                </label>
                <select id="loan_id" name="loan_id" x-model="selectedLoanId" required 
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-3 px-4 text-sm text-amber-600 dark:text-amber-400 font-bold focus:border-amber-500 focus:outline-none">
                    <option value="">-- Choose Loan Account --</option>
                    <template x-for="l in loans" :key="l.id">
                        <option :value="l.id" 
                                :selected="l.id == selectedLoanId"
                                x-text="l.loan_number + ' • ' + l.customer_name + ' (Balance: ₹' + (parseFloat(l.principal_balance || l.principal_amount)).toLocaleString('en-IN', {minimumFractionDigits: 2}) + ')'">
                        </option>
                    </template>
                </select>
            </div>

            <!-- LIVE LOAN CALCULATION BREAKDOWN CARD (Visible when a loan is selected) -->
            <div x-show="selectedLoan" x-cloak class="rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-50/50 to-orange-50/20 dark:from-amber-950/20 dark:to-slate-900 p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-amber-500/20 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-amber-500/20 text-amber-700 dark:text-amber-300 px-2.5 py-1 text-xs font-mono font-bold" x-text="selectedLoan ? selectedLoan.loan_number : ''"></span>
                        <h3 class="text-xs font-extrabold text-slate-900 dark:text-white" x-text="selectedLoan ? selectedLoan.customer_name : ''"></h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 text-[10px] font-mono font-bold" x-text="selectedLoan ? 'Rate: ' + selectedLoan.interest_rate + '%/mo | Cycle: ' + (selectedLoan.interest_cycle || '15 Days') : ''"></span>
                        <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400" x-text="selectedLoan ? 'Mobile: ' + selectedLoan.customer_mobile : ''"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- Original Principal -->
                    <div class="rounded-xl bg-white dark:bg-slate-950/60 p-3 border border-amber-500/10">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Original Principal</span>
                        <span class="block text-sm font-black font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                            ₹<span x-text="selectedLoan ? parseFloat(selectedLoan.principal_amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '0.00'"></span>
                        </span>
                    </div>

                    <!-- Current Running Balance -->
                    <div class="rounded-xl bg-white dark:bg-slate-950/60 p-3 border border-amber-500/20">
                        <span class="block text-[10px] uppercase font-bold text-amber-600 dark:text-amber-400">Current Balance</span>
                        <span class="block text-sm font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5">
                            ₹<span x-text="currentBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>

                    <!-- Cycle & Accrued Interest -->
                    <div class="rounded-xl bg-white dark:bg-slate-950/60 p-3 border border-amber-500/10">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">
                            Interest (<span x-text="selectedLoan ? selectedLoan.cycles_count || 1 : 1"></span> cycle / <span x-text="selectedLoan ? selectedLoan.days_elapsed : 0"></span> days)
                        </span>
                        <span class="block text-sm font-black font-mono text-slate-900 dark:text-slate-100 mt-0.5">
                            ₹<span x-text="accruedInterest.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>

                    <!-- Total Estimated Dues -->
                    <div class="rounded-xl bg-white dark:bg-slate-950/60 p-3 border border-emerald-500/20">
                        <span class="block text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Total Est. Dues</span>
                        <span class="block text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                            ₹<span x-text="totalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Single Prominent Amount Received Input -->
            <div class="rounded-2xl border-2 border-emerald-400 dark:border-emerald-500/50 bg-emerald-50/80 dark:bg-emerald-950/30 p-5 space-y-3">
                <label for="total_amount" class="block text-xs font-extrabold uppercase tracking-wider text-emerald-900 dark:text-emerald-300">
                    Amount Received (₹) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-600 dark:text-emerald-400 text-xl font-bold">₹</div>
                    <input type="number" step="0.01" id="total_amount" name="total_amount" x-model="amountReceived" required placeholder="Enter total cash / online amount received" autofocus
                           class="w-full rounded-xl border border-emerald-400 dark:border-emerald-500/50 bg-white dark:bg-slate-950 py-3.5 pl-10 pr-4 text-xl font-black text-emerald-600 dark:text-emerald-400 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <!-- REAL-TIME LIVE PAYMENT & REMAINING CALCULATION PREVIEW -->
                <div x-show="selectedLoan && parsedAmount > 0" x-cloak class="rounded-xl bg-white dark:bg-slate-900 p-4 border border-emerald-300 dark:border-emerald-500/30 space-y-2 shadow-xs">
                    <p class="text-[10px] font-extrabold uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">Live Payment & Remaining Calculation</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Total Dues (with Interest)</span>
                            <span class="font-extrabold font-mono text-slate-800 dark:text-slate-200">
                                ₹<span x-text="currentBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Amount Received</span>
                            <span class="font-extrabold font-mono text-emerald-600 dark:text-emerald-400">
                                - ₹<span x-text="parsedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Remaining Balance</span>
                            <span class="font-black font-mono text-base" :class="isFullSettlement ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                ₹<span x-text="remainingBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Settlement Status Alert -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <template x-if="isFullSettlement">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i> Full Settlement! Loan account will be fully paid off & status changed to CLOSED.
                            </span>
                        </template>
                        <template x-if="!isFullSettlement">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 dark:text-amber-400">
                                <i class="fa-solid fa-circle-info"></i> Partial Payment Recorded. Remaining principal balance: ₹<span x-text="remainingBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Payment Mode, Payment Date & Automated Non-Editable Receipt Number -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Payment Mode -->
                <div>
                    <label for="payment_mode" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Payment Mode</label>
                    <select id="payment_mode" name="payment_mode" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-3 px-3.5 text-xs font-bold text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                        <option value="Cash" selected>💵 Cash</option>
                        <option value="UPI">📱 UPI / QR Code</option>
                        <option value="Bank Transfer">🏦 Bank Transfer (NEFT/RTGS/IMPS)</option>
                        <option value="Cheque">📄 Cheque</option>
                    </select>
                </div>

                <!-- Payment Date -->
                <div>
                    <label for="payment_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Payment Date</label>
                    <input type="date" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Automated & Non-Editable Receipt Number -->
                <div>
                    <label for="receipt_number" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5">Receipt Number</label>
                    <input type="text" id="receipt_number" name="receipt_number" value="<?= htmlspecialchars($nextReceiptNumber) ?>" readonly
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-emerald-600 dark:text-emerald-400 font-mono font-bold cursor-not-allowed">
                    <span class="text-[10px] text-slate-500">Auto-generated system reference</span>
                </div>
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Remarks / Notes (Optional)</label>
                <textarea id="remarks" name="remarks" rows="2" placeholder="Simple notes if any..."
                          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3">
            <a href="<?= $baseUrl ?>/payments" class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-8 py-3.5 text-xs font-black text-white uppercase tracking-wider shadow-md hover:bg-emerald-500 transition">
                <i class="fa-solid fa-check mr-2 text-sm"></i> Save Payment & Print Receipt
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
