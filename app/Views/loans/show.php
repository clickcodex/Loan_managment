<?php
$pageTitle = 'Loan Details - ' . htmlspecialchars($loan['loan_number']);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ 
    videoModal: false,
    editPaymentModal: false,
    editLedgerModal: false,
    addPaymentModal: false,
    receivePaymentModal: false,
    deleteLoanModal: false,

    // Add Payment (Top-Up) State
    topupAmount: '',
    topupDate: '<?= date('Y-m-d') ?>',
    topupReason: '',
    latestBalance: <?= floatval($latestBalance ?? $loan['principal_amount']) ?>,
    get parsedTopupAmount() {
        const val = parseFloat(this.topupAmount);
        return isNaN(val) ? 0 : val;
    },
    get newBalanceAfterTopup() {
        return this.latestBalance + this.parsedTopupAmount;
    },
    openAddPayment() {
        this.topupAmount = '';
        this.topupDate = '<?= date('Y-m-d') ?>';
        this.topupReason = '';
        this.addPaymentModal = true;
    },

    // Receive Payment State
    receivePayment: {
        loan_id: <?= intval($loan['id']) ?>,
        total_amount: '',
        discount: '',
        payment_mode: 'Cash',
        payment_date: '<?= date('Y-m-d') ?>',
        reference_number: '',
        remarks: '',
        receipt_number: '<?= htmlspecialchars($nextReceiptNumber ?? '') ?>'
    },
    totalPayable: <?= floatval($dueInfo['total_payable'] ?? $loan['principal_amount']) ?>,
    accruedInterest: <?= floatval($dueInfo['accrued_interest'] ?? 0) ?>,
    currentPrincipalBalance: <?= floatval($dueInfo['principal_balance'] ?? ($latestBalance ?? $loan['principal_amount'])) ?>,
    originalPrincipal: <?= floatval($loan['principal_amount']) ?>,

    get parsedDiscount() {
        const val = parseFloat(this.receivePayment.discount);
        return isNaN(val) ? 0 : Math.max(0, val);
    },
    get netPayableAfterDiscount() {
        return Math.max(0, this.totalPayable - this.parsedDiscount);
    },
    get parsedReceiveAmount() {
        const val = parseFloat(this.receivePayment.total_amount);
        return isNaN(val) ? 0 : val;
    },
    get remainingReceiveBalance() {
        return Math.max(0, this.netPayableAfterDiscount - this.parsedReceiveAmount);
    },
    get isFullSettlement() {
        return this.netPayableAfterDiscount > 0 && this.parsedReceiveAmount >= this.netPayableAfterDiscount;
    },
    payCompleteLoan() {
        this.receivePayment.total_amount = this.netPayableAfterDiscount.toFixed(2);
    },
    onDiscountChange() {
        if (this.receivePayment.total_amount !== '' && parseFloat(this.receivePayment.total_amount) > this.netPayableAfterDiscount) {
            this.receivePayment.total_amount = this.netPayableAfterDiscount.toFixed(2);
        }
    },
    openReceivePayment() {
        this.receivePayment.total_amount = '';
        this.receivePayment.discount = '';
        this.receivePayment.payment_mode = 'Cash';
        this.receivePayment.payment_date = '<?= date('Y-m-d') ?>';
        this.receivePayment.reference_number = '';
        this.receivePayment.remarks = '';
        this.receivePaymentModal = true;
    },
    setReceiveAmount(val) {
        this.receivePayment.total_amount = parseFloat(val).toFixed(2);
    },

    // Edit Ledger Entry State
    editLedger: {
        id: '',
        loan_id: <?= intval($loan['id']) ?>,
        entry_type: '',
        entry_date: '<?= date('Y-m-d') ?>',
        description: '',
        debit: 0,
        credit: 0,
        balance: 0,
        amount: '',
        payment_id: null,
        payment_receipt_no: '',
        payment_mode: 'Cash',
        payment_ref_no: '',
        remarks: '',
        payment_remarks: ''
    },
    isSubmittingLedger: false,
    ledgerErrorMessage: '',
    ledgerSuccessMessage: '',

    openEditLedger(data) {
        const d = parseFloat(data.debit) || 0;
        const c = parseFloat(data.credit) || 0;
        let amt = '';
        if (data.entry_type === 'Loan Issued' || data.entry_type === 'Loan Top-Up') {
            amt = (d > 0 ? d : c).toFixed(2);
        } else if (data.entry_type === 'Payment Received' || data.entry_type === 'Discount / Concession') {
            amt = (c > 0 ? c : d).toFixed(2);
        } else {
            amt = (d > 0 ? d : c).toFixed(2);
        }

        this.editLedger = {
            id: data.id,
            loan_id: data.loan_id || <?= intval($loan['id']) ?>,
            entry_type: data.entry_type || 'General Entry',
            entry_date: data.entry_date || '<?= date('Y-m-d') ?>',
            description: data.description || '',
            debit: d,
            credit: c,
            balance: parseFloat(data.balance) || 0,
            amount: amt,
            payment_id: data.payment_id || null,
            payment_receipt_no: data.payment_receipt_no || '',
            payment_mode: data.payment_mode || 'Cash',
            payment_ref_no: data.payment_ref_no || '',
            remarks: data.remarks || data.payment_remarks || '',
            payment_remarks: data.remarks || data.payment_remarks || ''
        };
        this.ledgerErrorMessage = '';
        this.ledgerSuccessMessage = '';
        this.isSubmittingLedger = false;
        this.editLedgerModal = true;
    },

    openEditPayment(data) {
        this.openEditLedger(data);
    },

    async submitLedgerEdit() {
        this.isSubmittingLedger = true;
        this.ledgerErrorMessage = '';
        this.ledgerSuccessMessage = '';

        try {
            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrfToken ?>');
            formData.append('entry_date', this.editLedger.entry_date);
            formData.append('entry_type', this.editLedger.entry_type);
            formData.append('description', this.editLedger.description || '');

            if (this.editLedger.entry_type === 'Loan Issued' || this.editLedger.entry_type === 'Loan Top-Up') {
                formData.append('debit', this.editLedger.amount || '0.00');
                formData.append('credit', '0.00');
            } else if (this.editLedger.entry_type === 'Payment Received' || this.editLedger.entry_type === 'Discount / Concession') {
                formData.append('debit', '0.00');
                formData.append('credit', this.editLedger.amount || '0.00');
            } else if (this.editLedger.entry_type === 'Closing Entry') {
                formData.append('debit', '0.00');
                formData.append('credit', '0.00');
            } else {
                formData.append('debit', this.editLedger.debit || '0.00');
                formData.append('credit', this.editLedger.credit || '0.00');
            }

            if (this.editLedger.payment_id || this.editLedger.entry_type === 'Payment Received') {
                formData.append('payment_mode', this.editLedger.payment_mode);
                formData.append('reference_number', this.editLedger.payment_ref_no || '');
            }
            formData.append('remarks', this.editLedger.remarks || this.editLedger.payment_remarks || '');

            const url = '<?= $baseUrl ?>/loans/' + this.editLedger.loan_id + '/ledger/' + this.editLedger.id + '/update';
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            if (result.success) {
                this.ledgerSuccessMessage = result.message || 'Ledger entry updated successfully!';
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                this.ledgerErrorMessage = result.error || 'Failed to update ledger entry.';
                this.isSubmittingLedger = false;
            }
        } catch (err) {
            this.ledgerErrorMessage = 'An error occurred while updating ledger entry. Please try again.';
            this.isSubmittingLedger = false;
        }
    }
}">

    <!-- Header Banner -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 tracking-tight"><?= htmlspecialchars($loan['loan_number']) ?></h1>
                    <?php if ($loan['status'] === 'Active' || $loan['status'] === 'Running'): ?>
                        <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-extrabold text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 uppercase tracking-wider">Active</span>
                    <?php elseif ($loan['status'] === 'Closed'): ?>
                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Closed / Settled</span>
                    <?php else: ?>
                        <span class="rounded-full bg-rose-500/20 px-3 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 border border-rose-500/30">Overdue</span>
                    <?php endif; ?>
                    <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        <?= htmlspecialchars($loan['security_type']) ?>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 dark:text-slate-400 mt-2">
                    <span class="text-slate-900 dark:text-white font-bold"><i class="fa-solid fa-user mr-1 text-slate-400"></i> Customer: <?= htmlspecialchars($loan['customer_name']) ?></span>
                    <span>&bull;</span>
                    <span><i class="fa-solid fa-calendar mr-1 text-slate-400"></i> Date: <?= htmlspecialchars($loan['loan_date']) ?></span>
                    <span>&bull;</span>
                    <?php 
                        $rateVal = floatval($loan['interest_rate']);
                        $isHalfM = !str_contains(strtolower($loan['interest_cycle'] ?? ''), '30') && strtolower($loan['interest_cycle'] ?? '') !== 'monthly';
                        $cycleLabel = $isHalfM ? '15 Days (' . number_format($rateVal / 2, 2) . '% per cycle)' : '30 Days (' . number_format($rateVal, 2) . '% per cycle)';
                    ?>
                    <span><i class="fa-solid fa-percent mr-1 text-slate-400"></i> Rate: <?= htmlspecialchars($loan['interest_rate']) ?>% / Month &bull; <?= $cycleLabel ?></span>
                    <?php if (!empty($loan['remarks'])): ?>
                        <span>&bull;</span>
                        <span class="text-amber-700 dark:text-amber-300 font-bold bg-amber-500/10 dark:bg-amber-500/20 px-2 py-0.5 rounded-lg border border-amber-500/30 inline-flex items-center gap-1">
                            <i class="fa-regular fa-comment-dots text-amber-500"></i> Remark: <?= htmlspecialchars($loan['remarks']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>/edit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-4 py-2.5 text-xs shadow-md shadow-amber-500/20 transition cursor-pointer">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Loan
                </a>

                <?php if ($canDeleteLoan): ?>
                    <button type="button" @click="deleteLoanModal = true" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 px-3.5 py-2.5 text-xs font-bold transition shadow-xs cursor-pointer">
                        <i class="fa-solid fa-trash-can"></i> Delete Loan
                    </button>
                <?php else: ?>
                    <button type="button" disabled title="<?= htmlspecialchars($deleteBlockReason) ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-xs font-bold cursor-not-allowed opacity-60">
                        <i class="fa-solid fa-trash-can"></i> Delete Loan
                    </button>
                <?php endif; ?>

                <button type="button" @click="videoModal = true" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-purple-500 transition">
                    <i class="fa-solid fa-video text-sm"></i> Upload Video Proof
                </button>
                <?php if ($loan['status'] === 'Active' || $loan['status'] === 'Running'): ?>
                <button type="button" @click="openAddPayment()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-500 transition">
                    <i class="fa-solid fa-circle-plus text-sm"></i> Add Payment
                </button>
                <?php endif; ?>
                <button type="button" @click="openReceivePayment()" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-emerald-500/20 hover:bg-emerald-400 transition">
                    <i class="fa-solid fa-receipt text-sm"></i> Receive Payment
                </button>

                <a href="<?= $baseUrl ?>/receipts/disbursement/<?= $loan['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-amber-500 hover:border-amber-500 font-bold px-3.5 py-2.5 text-xs shadow-xs transition" title="Print Loan Disbursement Slip (RK Details)">
                    <i class="fa-solid fa-receipt text-amber-500"></i> RK Details Slip
                </a>

                <?php if ($loan['status'] === 'Closed'): ?>
                    <a href="<?= $baseUrl ?>/receipts/closure/<?= $loan['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-black px-4 py-2.5 text-xs shadow-md hover:from-emerald-500 hover:to-teal-500 transition" title="Print Loan Closure Receipt & Clearance Certificate">
                        <i class="fa-solid fa-file-circle-check"></i> Closure Receipt
                    </a>
                <?php endif; ?>

                <?php if (!empty($collaterals)): ?>
                    <?php if (($loan['delivered'] ?? 'No') === 'Yes' || ($loan['status'] ?? '') === 'Closed'): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3 py-2 text-xs font-bold text-emerald-600 dark:text-emerald-400" title="Delivered: <?= htmlspecialchars($loan['delivery_date'] ?? 'Yes') ?>">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i> Jewellery Delivered
                    </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Collateral Shortfall Deficit Risk Alert Banner -->
    <?php if (!empty($dueInfo['has_collateral_shortfall'])): ?>
        <div class="rounded-2xl border-2 border-rose-500/60 bg-rose-50 dark:bg-rose-950/40 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-600 dark:text-rose-400 font-black text-2xl border border-rose-500/40 shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-rose-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>COLLATERAL SHORTFALL RISK ALERT</span>
                        <span class="rounded bg-rose-500/30 text-rose-700 dark:text-rose-300 px-2 py-0.5 text-[10px] font-mono font-bold animate-pulse">MARGIN CALL REQUIRED</span>
                    </h3>
                    <p class="text-xs text-rose-800 dark:text-rose-200/90 mt-1">
                        Total Loan Payable Amount (<strong class="font-mono text-rose-950 dark:text-white">₹<?= number_format($dueInfo['total_payable'], 2) ?></strong>) has <strong>EXCEEDED</strong> the current live market valuation of pledged collateral (<strong class="font-mono text-amber-600 dark:text-yellow-400">₹<?= number_format($dueInfo['current_collateral_value'], 2) ?></strong>)!
                    </p>
                    <p class="text-[11px] text-rose-700 dark:text-rose-400 font-bold mt-0.5">
                        Deficit Risk Shortfall: ₹<?= number_format(floatval($dueInfo['shortfall_amount'] ?? 0), 2) ?> &bull; Current LTV Ratio: <?= number_format(floatval($dueInfo['current_ltv'] ?? $dueInfo['live_ltv_ratio'] ?? $ltv ?? 0), 1) ?>%
                    </p>
                </div>
            </div>
            <a href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $loan['customer_mobile']) ?>?text=<?= urlencode("🚨 MARGIN CALL NOTICE: Dear " . $loan['customer_name'] . ", your loan account (" . $loan['loan_number'] . ") payable amount (₹" . number_format($dueInfo['total_payable'], 2) . ") has exceeded your pledged collateral market value (₹" . number_format($dueInfo['current_collateral_value'], 2) . "). Deficit Shortfall is ₹" . number_format($dueInfo['shortfall_amount'], 2) . ". Please deposit partial principal immediately.") ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-emerald-500 transition whitespace-nowrap">
                <i class="fa-brands fa-whatsapp text-sm"></i> Send Margin Call
            </a>
        </div>
    <?php endif; ?>

    <!-- 5 Summary KPI Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- 1st: Total Loan Amount with Interest (First) -->
        <div class="rounded-2xl border-2 border-emerald-500/40 bg-emerald-50/60 dark:bg-emerald-950/30 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase text-emerald-800 dark:text-emerald-300 tracking-wider">Loan Amount with Interest</span>
                <span class="rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-bold">Total Dues</span>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                ₹<?= number_format($dueInfo['total_payable'], 2) ?>
            </p>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-bold mt-0.5 space-y-0.5">
                <div>Principal: ₹<?= number_format($loan['principal_amount'], 2) ?> + Int: ₹<?= number_format($dueInfo['accrued_interest'], 2) ?></div>
                <?php 
                    $actualRecv = floatval($totalReceived ?? ($dueInfo['total_received'] ?? 0));
                    if ($actualRecv > 0): 
                ?>
                    <div class="text-emerald-600 dark:text-emerald-400 font-extrabold flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[9px]"></i>
                        <span>Received: ₹<?= number_format($actualRecv, 2) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2nd: Received Amount (Total Paid) -->
        <div class="rounded-2xl border border-emerald-500/30 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Received Amount</span>
                <span class="rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 text-[10px] font-bold">Total Paid</span>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                ₹<?= number_format($actualRecv ?? floatval($totalReceived ?? ($dueInfo['total_received'] ?? 0)), 2) ?>
            </p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold mt-0.5">
                <?= count($payments) ?> Payment<?= count($payments) === 1 ? '' : 's' ?> Collected
            </p>
        </div>

        <!-- 3rd: Principal Amount -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Principal Amount</span>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">₹<?= number_format($loan['principal_amount'], 2) ?></p>
            <?php if (!empty($dueInfo['full_years_completed']) && $dueInfo['full_years_completed'] >= 1): ?>
                <div class="text-[10px] text-amber-600 dark:text-amber-400 font-black mt-0.5" title="Year <?= $dueInfo['full_years_completed'] + 1 ?> interest is calculated on this updated amount">
                    ⚡ Yr <?= $dueInfo['full_years_completed'] + 1 ?> Base: ₹<?= number_format($dueInfo['compounded_principal'], 2) ?>
                </div>
            <?php else: ?>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold mt-0.5">Original Loan Disbursed</p>
            <?php endif; ?>
        </div>

        <!-- 4th: Running Balance (Live Outstanding Dues) -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Running Balance</span>
                <span class="rounded bg-amber-500/20 text-amber-700 dark:text-amber-400 px-2 py-0.5 text-[10px] font-bold">Outstanding</span>
            </div>
            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono">₹<?= number_format($dueInfo['total_payable'], 2) ?></p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold mt-0.5">Current Outstanding Balance</p>
        </div>

        <!-- 5th: Collateral Valuation -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Collateral Valuation</span>
            <p class="text-2xl font-black text-amber-600 dark:text-yellow-400 mt-1 font-mono">₹<?= number_format($valuation['total_market_value'] ?? 0, 2) ?></p>
            <?php 
                $ltv = ($valuation['total_market_value'] ?? 0) > 0 ? ($loan['principal_amount'] / $valuation['total_market_value']) * 100 : 0;
            ?>
            <p class="text-[10px] font-bold <?= $ltv > 80 ? 'text-rose-500' : 'text-slate-500' ?> mt-0.5">Sanction LTV Ratio: <?= number_format($ltv, 1) ?>%</p>
        </div>
    </div>

    <!-- 1st Section: Loan Ledger Transactions Table -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-500"></i> Loan Ledger Account Transactions (<?= count($ledger) ?>)
            </h2>
            <button type="button" @click="openReceivePayment()" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                + Receive Payment
            </button>
        </div>

        <?php if (empty($ledger)): ?>
            <p class="text-xs text-slate-500 text-center py-6">No transaction ledger entries recorded yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Transaction Type</th>
                            <th class="py-2.5 px-3">Receipt# / Ref</th>
                            <th class="py-2.5 px-3 text-right">Debit (₹)</th>
                            <th class="py-2.5 px-3 text-right">Credit (₹)</th>
                            <th class="py-2.5 px-3 text-right">Running Balance (₹)</th>
                            <th class="py-2.5 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                        <?php foreach ($ledger as $entry): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= htmlspecialchars($entry['entry_date'] ?? '') ?></td>
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($entry['entry_type'] ?? '') ?></td>
                                <td class="py-3 px-3 font-mono text-amber-600 dark:text-amber-400">
                                    <div><?= htmlspecialchars($entry['description'] ?? '—') ?></div>
                                    <?php 
                                        $entryRemark = !empty($entry['remarks']) ? $entry['remarks'] : (!empty($entry['payment_remarks']) ? $entry['payment_remarks'] : '');
                                        if (!empty($entryRemark)): 
                                    ?>
                                        <div class="text-[11px] font-sans text-slate-600 dark:text-slate-300 mt-1 flex items-start gap-1 bg-amber-500/10 dark:bg-amber-500/10 p-1.5 rounded-lg border border-amber-500/20">
                                            <i class="fa-regular fa-comment-dots text-amber-500 mt-0.5 shrink-0"></i>
                                            <span class="break-words"><strong class="text-amber-700 dark:text-amber-300 font-bold">Remark:</strong> <?= htmlspecialchars($entryRemark) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                    <?= floatval($entry['debit'] ?? 0) > 0 ? '₹' . number_format($entry['debit'], 2) : '—' ?>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    <?= floatval($entry['credit'] ?? 0) > 0 ? '₹' . number_format($entry['credit'], 2) : '—' ?>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-extrabold text-slate-900 dark:text-white">
                                    ₹<?= number_format($entry['balance'] ?? 0, 2) ?>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <?php if (!empty($entry['payment_id'])): ?>
                                            <a href="<?= $baseUrl ?>/receipts/payment/<?= $entry['payment_id'] ?>" target="_blank" 
                                               class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/50 px-2 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition shadow-2xs" 
                                               title="Print Payment Receipt">
                                                <i class="fa-solid fa-receipt text-xs"></i> Receipt
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($entry['is_virtual']) || ($entry['entry_type'] ?? '') === 'Interest Accrued'): ?>
                                            <span class="rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 px-2 py-0.5 text-[10px] font-bold" title="Live Auto-Calculated Accrued Interest">Auto</span>
                                        <?php elseif (($entry['entry_type'] ?? '') !== 'Loan Issued'): ?>
                                            <button type="button" 
                                                    @click="openEditLedger(<?= htmlspecialchars(json_encode([
                                                        'id'                 => intval($entry['id']),
                                                        'loan_id'            => intval($entry['loan_id']),
                                                        'entry_type'         => $entry['entry_type'] ?? 'General Entry',
                                                        'entry_date'         => $entry['entry_date'] ?? date('Y-m-d'),
                                                        'description'        => $entry['description'] ?? '',
                                                        'remarks'            => $entryRemark,
                                                        'debit'              => floatval($entry['debit'] ?? 0),
                                                        'credit'             => floatval($entry['credit'] ?? 0),
                                                        'balance'            => floatval($entry['balance'] ?? 0),
                                                        'payment_id'         => !empty($entry['payment_id']) ? intval($entry['payment_id']) : null,
                                                        'payment_receipt_no' => $entry['payment_receipt_no'] ?? '',
                                                        'payment_mode'       => $entry['payment_mode'] ?? 'Cash',
                                                        'payment_ref_no'     => $entry['payment_ref_no'] ?? '',
                                                        'payment_remarks'    => $entryRemark
                                                    ])) ?>)"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700/50 px-2 py-1 text-xs font-bold text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition cursor-pointer shadow-2xs"
                                                    title="Edit Transaction Entry">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 dark:text-slate-500 italic px-2 py-1">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2nd Section: Pledged Collateral Items Table -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <i class="fa-solid fa-gem text-amber-500"></i> Pledged Collateral Items (<?= count($collaterals) ?>)
        </h2>

        <?php if (empty($collaterals)): ?>
            <p class="text-xs text-slate-500 text-center py-6">No collateral items pledged for this loan (e.g. Guarantor Secured).</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Metal / Type</th>
                            <th class="py-2.5 px-3">Item Description</th>
                            <th class="py-2.5 px-3 text-center">Qty</th>
                            <th class="py-2.5 px-3 text-right">Gross Wt (g)</th>
                            <th class="py-2.5 px-3 text-center">Rack</th>
                            <th class="py-2.5 px-3 text-right">Net Wt (g)</th>
                            <th class="py-2.5 px-3">Purity (%)</th>
                            <th class="py-2.5 px-3 text-right">Market Valuation (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                        <?php foreach ($collaterals as $ci): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3 font-bold">
                                    <span class="rounded px-2 py-0.5 text-[10px] <?= strtolower($ci['item_type'] ?? '') === 'gold' ? 'bg-amber-500/20 text-amber-600 dark:text-yellow-400 border border-amber-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700' ?>">
                                        <?= htmlspecialchars($ci['item_type'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($ci['item_name']) ?></td>
                                <td class="py-3 px-3 text-center font-mono font-bold"><?= intval($ci['quantity']) ?></td>
                                <td class="py-3 px-3 text-right font-mono"><?= number_format($ci['gross_weight'], 2) ?></td>
                                <td class="py-3 px-3 text-center font-mono">
                                    <span class="rounded px-2.5 py-1 text-xs font-bold <?= !empty($ci['rk_number']) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700' ?>">
                                        <?= htmlspecialchars($ci['rk_number'] ?? '') ?: '—' ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-amber-600 dark:text-amber-400"><?= number_format($ci['net_weight'], 2) ?></td>
                                <td class="py-3 px-3 font-mono font-bold text-slate-700 dark:text-slate-300">
                                    <?php
                                        $purityPct = floatval($ci['purity_percentage'] ?? 0);
                                        $displayPurity = $purityPct > 0 ? number_format($purityPct, 2) . '%' : ($ci['purity_preset'] ?? '—');
                                        $isGold = strtoupper($ci['item_type'] ?? '') === 'GOLD';
                                    ?>
                                    <span class="rounded px-2.5 py-0.5 text-xs font-bold <?= $isGold ? 'bg-amber-500/20 text-amber-700 dark:text-yellow-400 border border-amber-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700' ?>">
                                        <?= htmlspecialchars($displayPurity) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-extrabold text-amber-600 dark:text-yellow-400">
                                    ₹<?= number_format($ci['market_value'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3rd Section: Loan Top-Up & Add Payment History -->
    <div class="rounded-3xl border border-indigo-200 dark:border-indigo-500/30 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-circle-plus text-indigo-500"></i> Loan Top-Up History (Additional Disbursements)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">All additional amounts disbursed on top of the original loan principal</p>
            </div>
            <?php if ($loan['status'] === 'Active' || $loan['status'] === 'Running'): ?>
            <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>/topup" class="rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow hover:bg-indigo-500 transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Payment
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($topups)): ?>
            <div class="py-8 text-center text-slate-400 space-y-2 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                <i class="fa-solid fa-circle-plus text-3xl text-indigo-400/50"></i>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No top-up disbursements on this loan yet.</p>
                <p class="text-[11px] text-slate-500">Use the "Add Payment" button to disburse additional funds to the customer on this loan.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Top-Up Date</th>
                            <th class="py-2.5 px-3 text-right">Amount Disbursed (₹)</th>
                            <th class="py-2.5 px-3 text-right">New Principal Total (₹)</th>
                            <th class="py-2.5 px-3">Reason / Remarks</th>
                            <th class="py-2.5 px-3">Approved By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                        <?php foreach ($topups as $i => $tu): ?>
                            <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition">
                                <td class="py-3 px-3">
                                    <span class="rounded-full bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 px-2.5 py-0.5 text-[10px] font-bold border border-indigo-500/30">
                                        #<?= $i + 1 ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-400"><?= htmlspecialchars($tu['topup_date']) ?></td>
                                <td class="py-3 px-3 text-right font-mono font-extrabold text-indigo-600 dark:text-indigo-400">
                                    +₹<?= number_format($tu['topup_amount'], 2) ?>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ₹<?= number_format($tu['new_principal'], 2) ?>
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($tu['reason'] ?? '—') ?></td>
                                <td class="py-3 px-3 font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($tu['approved_by_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-indigo-50 dark:bg-indigo-950/30">
                            <td colspan="2" class="py-2.5 px-3 text-[10px] font-extrabold text-indigo-700 dark:text-indigo-300 uppercase">Total Top-Up Disbursed</td>
                            <td class="py-2.5 px-3 text-right font-mono font-extrabold text-indigo-700 dark:text-indigo-300">
                                +₹<?= number_format(array_sum(array_column($topups, 'topup_amount')), 2) ?>
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 4th Section: 📹 VIDEO PROOF RECORDINGS SECTION -->
    <div class="rounded-3xl border border-purple-200 dark:border-purple-500/30 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-video text-purple-600 dark:text-purple-400"></i> Video Proof Recordings (Loan Disbursement & Settlement)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Video evidence recorded when giving the loan or after loan completion / collateral release</p>
            </div>
            <button @click="videoModal = true" class="rounded-xl bg-purple-600 px-3.5 py-2 text-xs font-bold text-white shadow hover:bg-purple-500 transition">
                <i class="fa-solid fa-plus mr-1"></i> Upload Video Proof
            </button>
        </div>

        <?php if (empty($videos)): ?>
            <div class="py-8 text-center text-slate-400 space-y-2 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                <i class="fa-solid fa-video-slash text-3xl text-purple-400/60"></i>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No video proof recordings uploaded yet for this loan account.</p>
                <p class="text-[11px] text-slate-500">Upload video proof recorded when giving the loan or after loan completion / releasing pledged collateral.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($videos as $v): ?>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="rounded-lg bg-purple-500/20 px-2.5 py-1 text-[10px] font-bold text-purple-700 dark:text-purple-300 border border-purple-500/30">
                                <?= htmlspecialchars($v['document_type']) ?>
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono"><?= date('M d, Y', strtotime($v['created_at'])) ?></span>
                        </div>

                        <div class="rounded-xl overflow-hidden bg-black border border-slate-300 dark:border-slate-800">
                            <video controls class="w-full h-48 object-cover">
                                <source src="<?= $baseUrl ?>/<?= htmlspecialchars($v['file_path']) ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="font-bold text-slate-900 dark:text-slate-200 truncate max-w-[200px]"><?= htmlspecialchars($v['original_name']) ?></span>
                            <a href="<?= $baseUrl ?>/documents/<?= $v['id'] ?>/download" class="text-amber-600 dark:text-amber-400 font-bold hover:underline">
                                <i class="fa-solid fa-download mr-1"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upload Video Proof Modal -->
    <div x-show="videoModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="videoModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-video text-purple-600 dark:text-purple-400"></i> Upload Video Proof Recording
                </h3>
                <button @click="videoModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>/upload-video" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label for="video_type" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Video Proof Stage / Category</label>
                    <select id="video_type" name="video_type" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-bold focus:border-purple-500 focus:outline-none">
                        <option value="Loan Sanction Video Proof (Disbursement)" <?= ($loan['status'] === 'Active' || $loan['status'] === 'Running') ? 'selected' : '' ?>>
                            📹 Loan Sanction Video Proof (Giving Loan / Disbursement)
                        </option>
                        <option value="Loan Closure Video Proof (Collateral Release)" <?= $loan['status'] === 'Closed' ? 'selected' : '' ?>>
                            🎬 Loan Closure Video Proof (After Loan Complete / Release)
                        </option>
                        <option value="General Collateral Inspection Video">
                            🎥 General Collateral Inspection Video
                        </option>
                    </select>
                </div>

                <div>
                    <label for="video_file" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Video File (MP4, WebM, MOV, AVI, MKV)</label>
                    <input type="file" id="video_file" name="video_file" accept="video/*" required
                           class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 dark:file:bg-slate-800 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 cursor-pointer">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="videoModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-purple-600 px-5 py-2 text-xs font-bold text-white hover:bg-purple-500 shadow">
                        Upload & Link Video
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Ledger Transaction Entry Modal -->
    <div x-show="editLedgerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-lg rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 my-8" @click.away="if (!isSubmittingLedger) editLedgerModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold text-lg">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Edit Ledger Entry</span>
                            <span class="rounded-lg px-2 py-0.5 text-[10px] font-mono font-bold uppercase tracking-wider"
                                  :class="{
                                      'bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30': editLedger.entry_type === 'Loan Issued',
                                      'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30': editLedger.entry_type === 'Payment Received',
                                      'bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30': editLedger.entry_type === 'Discount / Concession',
                                      'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700': editLedger.entry_type === 'Closing Entry',
                                      'bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30': editLedger.entry_type === 'Loan Top-Up',
                                  }"
                                  x-text="editLedger.entry_type || 'Ledger Entry'"></span>
                        </h3>
                        <p class="text-[11px] text-slate-500">Modify transaction details with live ledger balance recalculation</p>
                    </div>
                </div>
                <button type="button" @click="editLedgerModal = false" :disabled="isSubmittingLedger" class="text-slate-400 hover:text-slate-900 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Error / Success Alert Messages -->
            <div x-show="ledgerErrorMessage" x-cloak class="rounded-xl border border-rose-500/30 bg-rose-50 dark:bg-rose-950/30 p-3 text-xs text-rose-700 dark:text-rose-300 font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span x-text="ledgerErrorMessage"></span>
            </div>

            <div x-show="ledgerSuccessMessage" x-cloak class="rounded-xl border border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 p-3 text-xs text-emerald-700 dark:text-emerald-300 font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-check shrink-0"></i>
                <span x-text="ledgerSuccessMessage"></span>
            </div>

            <form @submit.prevent="submitLedgerEdit()" class="space-y-4">
                <!-- Transaction Type & Date Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Transaction Type</label>
                        <template x-if="editLedger.entry_type === 'Loan Issued'">
                            <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border-2 border-indigo-300 dark:border-indigo-700/60 px-3.5 py-2 text-xs font-black text-indigo-700 dark:text-indigo-300 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-lock text-amber-500 text-xs"></i>
                                    <span>Loan Issued</span>
                                </span>
                                <span class="text-[9px] font-extrabold uppercase tracking-widest bg-indigo-200 dark:bg-indigo-800 text-indigo-900 dark:text-indigo-200 px-2 py-0.5 rounded-md">Fixed</span>
                            </div>
                        </template>
                        <template x-if="editLedger.entry_type !== 'Loan Issued'">
                            <div class="rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 px-3.5 py-2 text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                <span x-text="editLedger.entry_type || '—'"></span>
                                <span class="text-[10px] font-mono text-slate-400">#<span x-text="editLedger.id"></span></span>
                            </div>
                        </template>
                        <p class="text-[10px] text-slate-400 mt-1" x-show="editLedger.entry_type === 'Loan Issued'">
                            <i class="fa-solid fa-circle-info text-indigo-500 mr-0.5"></i> "Loan Issued" transaction type cannot be changed.
                        </p>
                    </div>
                    <div>
                        <label for="edit_ledger_date" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Transaction Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="edit_ledger_date" x-model="editLedger.entry_date" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <!-- AMOUNT SECTION: Contextual by entry_type -->
                <!-- Case A: Loan Issued (Debit) -->
                <template x-if="editLedger.entry_type === 'Loan Issued'">
                    <div class="rounded-2xl border-2 border-indigo-400 dark:border-indigo-500/50 bg-indigo-50/50 dark:bg-indigo-950/20 p-3.5 space-y-1">
                        <label for="edit_ledger_amount_loan" class="block text-[11px] font-extrabold uppercase text-indigo-900 dark:text-indigo-300">
                            Disbursement Amount (Debit ₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-indigo-600 dark:text-indigo-400 font-black text-lg">₹</span>
                            <input type="number" step="0.01" min="0" id="edit_ledger_amount_loan" x-model="editLedger.amount" required placeholder="0.00"
                                   class="w-full rounded-xl border border-indigo-400 dark:border-indigo-500/50 bg-white dark:bg-slate-950 py-2.5 pl-8 pr-3 text-lg font-black text-indigo-600 dark:text-indigo-400 focus:border-indigo-500 focus:outline-none font-mono">
                        </div>
                        <span class="text-[10px] text-slate-500 block">Initial loan disbursement. Editing synchronizes the loan start date and total payable amount.</span>
                    </div>
                </template>

                <!-- Case B: Payment Received (Credit) -->
                <template x-if="editLedger.entry_type === 'Payment Received'">
                    <div class="rounded-2xl border-2 border-emerald-400 dark:border-emerald-500/50 bg-emerald-50/50 dark:bg-emerald-950/20 p-3.5 space-y-1">
                        <label for="edit_ledger_amount_payment" class="block text-[11px] font-extrabold uppercase text-emerald-900 dark:text-emerald-300">
                            Amount Received (Credit ₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-600 dark:text-emerald-400 font-black text-lg">₹</span>
                            <input type="number" step="0.01" min="0.01" id="edit_ledger_amount_payment" x-model="editLedger.amount" required placeholder="0.00"
                                   class="w-full rounded-xl border border-emerald-400 dark:border-emerald-500/50 bg-white dark:bg-slate-950 py-2.5 pl-8 pr-3 text-lg font-black text-emerald-600 dark:text-emerald-400 focus:border-emerald-500 focus:outline-none font-mono">
                        </div>
                        <span class="text-[10px] text-slate-500 block">Editing payment credit will update the payment record and recompute all remaining dues.</span>
                    </div>
                </template>

                <!-- Case C: Discount / Concession (Credit) -->
                <template x-if="editLedger.entry_type === 'Discount / Concession'">
                    <div class="rounded-2xl border-2 border-amber-300 dark:border-amber-500/50 bg-amber-50/50 dark:bg-amber-950/20 p-3.5 space-y-1">
                        <label for="edit_ledger_amount_discount" class="block text-[11px] font-extrabold uppercase text-amber-900 dark:text-amber-300">
                            Discount / Waiver Amount (Credit ₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-amber-600 dark:text-amber-400 font-black text-lg">₹</span>
                            <input type="number" step="0.01" min="0" id="edit_ledger_amount_discount" x-model="editLedger.amount" required placeholder="0.00"
                                   class="w-full rounded-xl border border-amber-400 dark:border-amber-500/50 bg-white dark:bg-slate-950 py-2.5 pl-8 pr-3 text-lg font-black text-amber-600 dark:text-amber-400 focus:border-amber-500 focus:outline-none font-mono">
                        </div>
                        <span class="text-[10px] text-slate-500 block">Settlement discount/waiver granted against this loan.</span>
                    </div>
                </template>

                <!-- Case D: Loan Top-Up (Debit) -->
                <template x-if="editLedger.entry_type === 'Loan Top-Up'">
                    <div class="rounded-2xl border-2 border-purple-400 dark:border-purple-500/50 bg-purple-50/50 dark:bg-purple-950/20 p-3.5 space-y-1">
                        <label for="edit_ledger_amount_topup" class="block text-[11px] font-extrabold uppercase text-purple-900 dark:text-purple-300">
                            Top-Up Disbursement (Debit ₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-purple-600 dark:text-purple-400 font-black text-lg">₹</span>
                            <input type="number" step="0.01" min="0" id="edit_ledger_amount_topup" x-model="editLedger.amount" required placeholder="0.00"
                                   class="w-full rounded-xl border border-purple-400 dark:border-purple-500/50 bg-white dark:bg-slate-950 py-2.5 pl-8 pr-3 text-lg font-black text-purple-600 dark:text-purple-400 focus:border-purple-500 focus:outline-none font-mono">
                        </div>
                        <span class="text-[10px] text-slate-500 block">Additional disbursement debit added to loan principal.</span>
                    </div>
                </template>

                <!-- Case E: Closing Entry -->
                <template x-if="editLedger.entry_type === 'Closing Entry'">
                    <div class="rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/50 p-3.5 space-y-1.5">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200">
                            <i class="fa-solid fa-lock text-emerald-500"></i>
                            <span>Account Settlement & Closing Entry (Balance: ₹0.00)</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            This closing entry marks the full settlement of the loan account. You can update the closing settlement date and notes below.
                        </p>
                    </div>
                </template>

                <!-- Payment Details Grid (Shown for Payment Received or Payment-linked entries) -->
                <template x-if="editLedger.payment_id || editLedger.entry_type === 'Payment Received'">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label for="edit_ledger_payment_mode" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Payment Mode</label>
                            <select id="edit_ledger_payment_mode" x-model="editLedger.payment_mode" required
                                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                                <option value="Cash">💵 Cash</option>
                                <option value="UPI">📱 UPI</option>
                                <option value="Bank Transfer">🏦 Bank Transfer</option>
                                <option value="Cheque">📄 Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_ledger_reference" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Reference / UTR #</label>
                            <input type="text" id="edit_ledger_reference" x-model="editLedger.payment_ref_no" placeholder="Optional UTR / Cheque#"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none font-mono">
                        </div>
                    </div>
                </template>

                <!-- Description / Reference Notes -->
                <div>
                    <label for="edit_ledger_description" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Receipt# / Reference / Description <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="edit_ledger_description" x-model="editLedger.description" rows="2" required placeholder="Enter description or reference..."
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none"></textarea>
                </div>

                <!-- Remarks / Notes (Available for ALL entries including Loan Issued!) -->
                <div>
                    <label for="edit_ledger_remarks" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        <span x-text="editLedger.entry_type === 'Loan Issued' ? 'Loan Disbursement Remark / Notes (Optional)' : (editLedger.payment_id ? 'Payment Remarks / Notes (Optional)' : 'Remarks / Notes (Optional)')">Remarks / Notes (Optional)</span>
                    </label>
                    <input type="text" id="edit_ledger_remarks" x-model="editLedger.remarks" placeholder="Enter remarks or notes..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                    <p class="text-[10px] text-slate-400 mt-1" x-show="editLedger.entry_type === 'Loan Issued'">
                        Updating this remark will automatically update both the first ledger record and the loan account remarks.
                    </p>
                </div>

                <!-- Informational Alert -->
                <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-800 dark:text-amber-300 flex items-start gap-2">
                    <i class="fa-solid fa-calculator text-amber-500 mt-0.5 shrink-0"></i>
                    <span><strong>Live Balance Recalculation:</strong> Running balances across all subsequent chronological entries and loan settlement status will recalculate automatically upon saving.</span>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editLedgerModal = false" :disabled="isSubmittingLedger" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmittingLedger" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-md shadow-amber-500/20 transition disabled:opacity-50">
                        <template x-if="isSubmittingLedger">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        </template>
                        <template x-if="!isSubmittingLedger">
                            <i class="fa-solid fa-check"></i>
                        </template>
                        <span>Update Ledger Entry</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Payment (Top-Up / Additional Disbursement) Modal -->
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
            <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900/50 bg-gradient-to-r from-indigo-50/70 to-slate-50 dark:from-indigo-950/40 dark:to-slate-950 p-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($loan['loan_number']) ?></span>
                    <span class="font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($loan['customer_name']) ?></span>
                </div>
                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-indigo-100 dark:border-indigo-900/40 text-[11px]">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Principal</span>
                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300">₹<?= number_format($loan['principal_amount'], 2) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Current Balance</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400">₹<span x-text="latestBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Rate</span>
                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($loan['interest_rate']) ?>% / <?= htmlspecialchars($loan['interest_cycle'] ?? '15 Days') ?></span>
                    </div>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>/topup" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <!-- Top-Up Amount -->
                <div class="rounded-2xl border-2 border-indigo-400 dark:border-indigo-500/50 bg-indigo-50/50 dark:bg-indigo-950/20 p-4 space-y-2">
                    <label for="modal_topup_amount" class="block text-[11px] font-extrabold uppercase text-indigo-900 dark:text-indigo-300">
                        Additional Disbursement Amount (₹) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-indigo-600 dark:text-indigo-400 font-black text-xl">₹</span>
                        <input type="number" step="0.01" min="1" id="modal_topup_amount" name="topup_amount" x-model="topupAmount" required placeholder="0.00" autofocus
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
                    <label for="modal_topup_date" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Disbursement Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="modal_topup_date" name="topup_date" x-model="topupDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-indigo-500 focus:outline-none">
                </div>

                <!-- Reason / Notes -->
                <div>
                    <label for="modal_topup_reason" class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Reason / Notes (Optional)
                    </label>
                    <textarea id="modal_topup_reason" name="reason" x-model="topupReason" rows="2" placeholder="e.g. Additional gold ornament pledged, client requested emergency top-up..."
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

    <!-- Receive Payment Modal -->
    <div x-show="receivePaymentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 p-2 sm:p-4 md:p-6 flex min-h-full items-start justify-center">
        <div class="w-full max-w-xl rounded-2xl sm:rounded-3xl border border-emerald-300 dark:border-emerald-500/30 bg-white dark:bg-slate-900 p-3.5 sm:p-5 shadow-2xl space-y-3 sm:space-y-4 my-auto sm:my-6" @click.away="receivePaymentModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-sm shrink-0">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight">Receive Loan Payment</h3>
                        <p class="text-[10px] text-slate-500 leading-tight">Live ledger balance update & instant receipt</p>
                    </div>
                </div>
                <button type="button" @click="receivePaymentModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Live Loan Dues Summary Grid -->
            <div class="rounded-xl sm:rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-50/50 to-orange-50/20 dark:from-amber-950/20 dark:to-slate-900 p-2.5 sm:p-3 space-y-2 shadow-xs">
                <div class="flex items-center justify-between text-xs border-b border-amber-500/20 pb-1.5">
                    <span class="rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 px-2 py-0.5 font-mono font-bold text-[11px]"><?= htmlspecialchars($loan['loan_number']) ?></span>
                    <span class="font-bold text-slate-900 dark:text-white text-xs truncate max-w-[200px]"><?= htmlspecialchars($loan['customer_name']) ?></span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
                    <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-slate-200 dark:border-slate-800">
                        <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-slate-400">Principal</span>
                        <span class="block text-[11px] sm:text-xs font-black font-mono text-slate-800 dark:text-slate-200 mt-0.5">₹<?= number_format($loan['principal_amount'], 2) ?></span>
                    </div>
                    <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-amber-500/20">
                        <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-amber-600 dark:text-amber-400">Balance</span>
                        <span class="block text-[11px] sm:text-xs font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5">₹<span x-text="currentPrincipalBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                    </div>
                    <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-slate-200 dark:border-slate-800">
                        <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-slate-400">Accrued Int</span>
                        <span class="block text-[11px] sm:text-xs font-black font-mono text-slate-800 dark:text-slate-200 mt-0.5">₹<span x-text="accruedInterest.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                    </div>
                    <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-950 p-1.5 sm:p-2 border border-emerald-500/20">
                        <span class="block text-[8px] sm:text-[9px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Total Dues</span>
                        <span class="block text-[11px] sm:text-xs font-black font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">₹<span x-text="totalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
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
                    <button type="button" @click="setReceiveAmount(currentPrincipalBalance)" class="inline-flex items-center gap-1 rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-300 px-2 py-1 font-bold hover:bg-amber-500/25 border border-amber-500/30 transition cursor-pointer">
                        Principal (<span x-text="'₹' + currentPrincipalBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>)
                    </button>
                    <template x-if="accruedInterest > 0">
                        <button type="button" @click="setReceiveAmount(accruedInterest)" class="inline-flex items-center gap-1 rounded-lg bg-indigo-500/15 text-indigo-800 dark:text-indigo-300 px-2 py-1 font-bold hover:bg-indigo-500/25 border border-indigo-500/30 transition cursor-pointer">
                            Interest (<span x-text="'₹' + accruedInterest.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>)
                        </button>
                    </template>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/payments/store" method="POST" class="space-y-3 sm:space-y-3.5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="loan_id" :value="receivePayment.loan_id">
                <input type="hidden" name="is_full_settlement" :value="isFullSettlement ? '1' : '0'">

                <!-- Amount Received & Discount Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Amount Received (₹) -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-emerald-400 dark:border-emerald-500/50 bg-emerald-50/70 dark:bg-emerald-950/30 p-2.5 space-y-1">
                        <label for="modal_receive_amount" class="block text-[10px] sm:text-[11px] font-extrabold uppercase text-emerald-900 dark:text-emerald-300">
                            Amount Received (₹) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-emerald-600 dark:text-emerald-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="modal_receive_amount" name="total_amount" x-model="receivePayment.total_amount" required placeholder="0.00" autofocus
                                   class="w-full rounded-lg sm:rounded-xl border border-emerald-400 dark:border-emerald-500/50 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Discount / Waiver (₹) -->
                    <div class="rounded-xl sm:rounded-2xl border-2 border-amber-300 dark:border-amber-500/40 bg-amber-50/60 dark:bg-amber-950/20 p-2.5 space-y-1">
                        <label for="modal_discount_amount" class="flex items-center justify-between text-[10px] sm:text-[11px] font-extrabold uppercase text-amber-900 dark:text-amber-300">
                            <span>Discount / Waiver (₹)</span>
                            <span class="text-[9px] font-normal text-amber-600 dark:text-amber-400 normal-case">Optional</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-amber-600 dark:text-amber-400 font-black text-base">₹</span>
                            <input type="number" step="0.01" min="0" id="modal_discount_amount" name="discount" x-model="receivePayment.discount" @input="onDiscountChange()" placeholder="0.00"
                                   class="w-full rounded-lg sm:rounded-xl border border-amber-300 dark:border-amber-500/40 bg-white dark:bg-slate-950 py-1.5 sm:py-2 pl-7 pr-2.5 text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Live Payment Summary & Balance Preview -->
                <div class="rounded-xl sm:rounded-2xl bg-slate-50 dark:bg-slate-950/80 p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 space-y-1.5 text-xs">
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
                            ₹<span x-text="remainingReceiveBalance.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
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
                        <label for="modal_payment_mode" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Mode</label>
                        <select id="modal_payment_mode" name="payment_mode" x-model="receivePayment.payment_mode" required
                                class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-bold text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                            <option value="Cash">💵 Cash</option>
                            <option value="UPI">📱 UPI / QR</option>
                            <option value="Bank Transfer">🏦 Bank Transfer</option>
                            <option value="Cheque">📄 Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label for="modal_payment_date" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">Date</label>
                        <input type="date" id="modal_payment_date" name="payment_date" x-model="receivePayment.payment_date" required
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="modal_receipt_number" class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Receipt#</label>
                        <input type="text" id="modal_receipt_number" name="receipt_number" :value="receivePayment.receipt_number" readonly
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-1.5 sm:py-2 px-2.5 text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 cursor-not-allowed">
                    </div>
                </div>

                <!-- Reference & Remarks Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label for="modal_reference_number" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Ref / UTR / Cheque# (Optional)
                        </label>
                        <input type="text" id="modal_reference_number" name="reference_number" x-model="receivePayment.reference_number" placeholder="Transaction ref#"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-2.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label for="modal_receive_remarks" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase mb-0.5">
                            Remarks / Notes (Optional)
                        </label>
                        <input type="text" id="modal_receive_remarks" name="remarks" x-model="receivePayment.remarks" placeholder="Optional notes..."
                               class="w-full rounded-lg sm:rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-2.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="receivePaymentModal = false" class="rounded-lg px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-black text-white hover:bg-emerald-500 shadow-sm shadow-emerald-600/30 transition uppercase tracking-wider">
                        <i class="fa-solid fa-check"></i>
                        <span>Save Payment & Receipt</span>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Delete Loan Confirmation Modal -->
    <div x-show="deleteLoanModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-rose-200 dark:border-rose-900 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="deleteLoanModal = false">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-600 dark:text-rose-400 font-black text-xl shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Loan Account</h3>
                    <p class="text-xs text-slate-400">Confirm permanent loan deletion</p>
                </div>
            </div>

            <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                <p>Are you sure you want to delete loan <strong class="font-mono text-amber-600 dark:text-amber-400"><?= htmlspecialchars($loan['loan_number']) ?></strong> for <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($loan['customer_name']) ?></strong>?</p>
                
                <div class="rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/50 dark:bg-rose-950/30 p-3 space-y-1.5 text-[11px] text-rose-700 dark:text-rose-300 font-medium">
                    <div class="font-bold flex items-center gap-1.5 text-rose-800 dark:text-rose-200">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>This action cannot be undone!</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-600 dark:text-slate-300">
                        <li>The loan account and disbursement record will be removed.</li>
                        <li>All <?= count($collaterals) ?> pledged collateral item(s) will be deleted.</li>
                        <li>Any assigned physical rack slots will be freed automatically.</li>
                        <li>All related ledger entries will be removed with zero orphan entries.</li>
                    </ul>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>/delete" method="POST" class="pt-2 flex items-center justify-end gap-2.5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="button" @click="deleteLoanModal = false" class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black px-5 py-2 text-xs shadow-md shadow-rose-600/30 transition cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Permanently Delete
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
