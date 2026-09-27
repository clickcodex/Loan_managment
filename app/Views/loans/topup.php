<?php
$pageTitle = 'Loan Top-Up - ' . htmlspecialchars($loan['loan_number']);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6 max-w-2xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center gap-4">
        <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>"
           class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition shadow-sm">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-circle-plus text-indigo-500"></i>
                Loan Top-Up - Additional Disbursement
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Disburse extra funds on top of the existing active loan balance</p>
        </div>
    </div>

    <!-- Current Loan Summary Card -->
    <div class="rounded-3xl border border-indigo-200 dark:border-indigo-500/30 bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-950/30 dark:to-slate-900 p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <p class="text-[10px] font-bold uppercase text-indigo-500 dark:text-indigo-400 tracking-widest mb-1">Loan Account</p>
                <h2 class="text-xl font-black font-mono text-indigo-700 dark:text-indigo-300"><?= htmlspecialchars($loan['loan_number']) ?></h2>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 font-medium">
                    <i class="fa-solid fa-user mr-1 text-slate-400"></i> <?= htmlspecialchars($loan['customer_name']) ?>
                </p>
            </div>
            <span class="rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 px-3 py-1.5 text-xs font-extrabold uppercase tracking-wider shrink-0">
                Active
            </span>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Original Principal</p>
                <p class="text-lg font-black font-mono text-slate-900 dark:text-white mt-1">
                    <?php echo '&#8377;' . number_format($loan['principal_amount'], 2); ?>
                </p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Current Balance</p>
                <p class="text-lg font-black font-mono text-amber-600 dark:text-amber-400 mt-1">
                    <?php echo '&#8377;' . number_format($latestBalance, 2); ?>
                </p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Interest Rate</p>
                <p class="text-lg font-black font-mono text-slate-900 dark:text-white mt-1">
                    <?= htmlspecialchars($loan['interest_rate']) ?>%
                    <span class="text-[10px] font-bold text-slate-400"><?= htmlspecialchars($loan['interest_method']) ?></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Top-Up Form -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-5 flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <i class="fa-solid fa-indian-rupee-sign text-indigo-500"></i>
            Top-Up Request Details
        </h3>

        <form action="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>/topup" method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Amount -->
            <div>
                <label for="topup_amount" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Additional Disbursement Amount (&#8377;) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-indigo-500">&#8377;</span>
                    <input
                        type="number"
                        id="topup_amount"
                        name="topup_amount"
                        min="1"
                        step="0.01"
                        required
                        placeholder="Enter top-up amount..."
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 pl-9 pr-4 py-3 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition"
                        oninput="updatePreview()"
                    >
                </div>
                <p class="text-[11px] text-slate-500 mt-1">This amount will be added to the current loan balance and recorded as a new ledger debit entry.</p>
            </div>

            <!-- Live Preview -->
            <div id="preview-card" class="hidden rounded-2xl border border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 p-4">
                <p class="text-[10px] font-extrabold uppercase text-indigo-600 dark:text-indigo-400 tracking-widest mb-2">After Top-Up Preview</p>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Current Balance</span>
                        <p class="font-extrabold font-mono text-slate-800 dark:text-slate-200">&#8377;<?= number_format($latestBalance, 2) ?></p>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">New Balance (After Top-Up)</span>
                        <p id="preview-balance" class="font-extrabold font-mono text-indigo-600 dark:text-indigo-400">&#8212;</p>
                    </div>
                </div>
            </div>

            <!-- Date -->
            <div>
                <label for="topup_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Disbursement Date <span class="text-rose-500">*</span>
                </label>
                <input
                    type="date"
                    id="topup_date"
                    name="topup_date"
                    value="<?= date('Y-m-d') ?>"
                    required
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-4 py-3 text-sm font-bold text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition"
                >
            </div>

            <!-- Reason -->
            <div>
                <label for="reason" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Reason / Remarks <span class="text-slate-400 font-medium">(optional)</span>
                </label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="3"
                    placeholder="e.g. Customer requested additional funds for medical expenses..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition resize-none"
                ></textarea>
            </div>

            <!-- Info Box -->
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-500/30 p-4 flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-amber-500 mt-0.5 shrink-0"></i>
                <div class="text-xs text-amber-800 dark:text-amber-200 space-y-1">
                    <p class="font-bold">What happens when you submit:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-amber-700 dark:text-amber-300">
                        <li>The top-up amount is added to the loan principal</li>
                        <li>A new <strong>Loan Top-Up</strong> debit entry is recorded in the ledger</li>
                        <li>The transaction is logged in the Audit Trail</li>
                        <li>The customer running balance increases by the top-up amount</li>
                    </ul>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>"
                   class="rounded-xl px-5 py-2.5 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-extrabold text-white shadow-md shadow-indigo-500/25 hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/40 transition">
                    <i class="fa-solid fa-circle-plus"></i>
                    Add Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const currentBalance = <?= json_encode($latestBalance) ?>;

function updatePreview() {
    const amountInput = document.getElementById('topup_amount');
    const previewCard  = document.getElementById('preview-card');
    const previewBal   = document.getElementById('preview-balance');
    const amount = parseFloat(amountInput.value);

    if (!isNaN(amount) && amount > 0) {
        const newBal = currentBalance + amount;
        previewBal.textContent = '\u20b9' + newBal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        previewCard.classList.remove('hidden');
    } else {
        previewCard.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
