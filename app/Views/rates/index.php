<?php
$pageTitle = '100% Pure Gold & Silver Rates Management';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$activeGold100 = floatval($goldRate['rate_100'] ?? 140000);
$activeSilver100 = floatval($silverRate['rate_100'] ?? 100);
?>

<script>
function dailyRateEngine() {
    return {
        // Gold rate model in ₹ / 10g
        goldRate100: <?= $activeGold100 ?>,
        get goldPerGram() {
            let val = parseFloat(this.goldRate100) || 0;
            return (val / 10).toFixed(2);
        },

        // Silver rate model in ₹ / g
        silverRate100: <?= $activeSilver100 ?>,
        get silverPer10g() {
            let val = parseFloat(this.silverRate100) || 0;
            return (val * 10).toFixed(2);
        },
        get silverPerKg() {
            let val = parseFloat(this.silverRate100) || 0;
            return (val * 1000).toFixed(2);
        }
    };
}
</script>

<div class="space-y-6" x-data="dailyRateEngine()">

    <!-- Page Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-chart-line text-amber-500"></i> Daily 100% Purity Base Rates Management
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Standard 100% benchmark rates used for automated custom purity valuation across all loan issuances & collaterals.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <form action="<?= $baseUrl ?>/rates/sync-collaterals" method="POST" onsubmit="return confirm('Re-evaluate and synchronize all pledged collateral market values against current gold & silver rates?');">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="rounded-xl border border-amber-500/40 bg-amber-500/10 hover:bg-amber-500/20 px-3.5 py-1.5 text-xs font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5 transition cursor-pointer" title="Synchronize all collateral values with current rates">
                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                    <span>Sync Collaterals</span>
                </button>
            </form>
            <span class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-check text-xs"></i> 100% Purity Valuation Active
            </span>
        </div>
    </div>

    <!-- Active 100% Base Rates Display Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Active 100% Pure Gold Rate Card -->
        <div class="rounded-3xl border border-amber-300 dark:border-yellow-500/30 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white dark:via-slate-900 dark:to-slate-900 p-6 shadow-md space-y-4">
            <div class="flex items-center justify-between border-b border-amber-500/20 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-600 dark:text-yellow-400 shadow-xs">
                        <i class="fa-solid fa-gem text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Active 100% Pure Gold Base Rate</h2>
                        <span class="text-[11px] text-amber-700 dark:text-yellow-500 font-mono">
                            Effective Date: <?= htmlspecialchars($goldRate['rate_date'] ?? date('Y-m-d')) ?>
                        </span>
                    </div>
                </div>
                <span class="rounded-full bg-amber-500/20 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-yellow-300 border border-amber-500/30">
                    100% Fine Gold
                </span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-3 pt-2">
                <div>
                    <span class="text-3xl sm:text-4xl font-black font-mono text-amber-600 dark:text-yellow-400">
                        ₹<?= number_format($activeGold100, 2) ?>
                    </span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1.5">/ 10 Grams</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 px-3.5 py-2 text-xs font-mono font-bold text-amber-900 dark:text-yellow-200">
                    <i class="fa-solid fa-scale-balanced text-xs"></i>
                    <span>Unit Rate: ₹<?= number_format($activeGold100 / 10, 2) ?> / gram</span>
                </div>
            </div>

            <?php if (!empty($goldRate['remarks'])): ?>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 italic pt-1 border-t border-amber-500/10">
                    <i class="fa-solid fa-quote-left text-[9px] mr-1 text-amber-400"></i><?= htmlspecialchars($goldRate['remarks']) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Active 100% Pure Silver Base Rate Card -->
        <div class="rounded-3xl border border-slate-300 dark:border-slate-700 bg-gradient-to-br from-slate-200/50 via-slate-50 to-white dark:from-slate-800/40 dark:via-slate-900 dark:to-slate-900 p-6 shadow-md space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 shadow-xs">
                        <i class="fa-solid fa-ring text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Active 100% Pure Silver Base Rate</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                            Effective Date: <?= htmlspecialchars($silverRate['rate_date'] ?? date('Y-m-d')) ?>
                        </span>
                    </div>
                </div>
                <span class="rounded-full bg-slate-200 dark:bg-slate-800 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                    100% Fine Silver
                </span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-3 pt-2">
                <div>
                    <span class="text-3xl sm:text-4xl font-black font-mono text-slate-900 dark:text-white">
                        ₹<?= number_format($activeSilver100, 2) ?>
                    </span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1.5">/ Gram</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-mono font-bold text-slate-700 dark:text-slate-300">
                    <i class="fa-solid fa-scale-balanced text-xs"></i>
                    <span>₹<?= number_format($activeSilver100 * 10, 2) ?> / 10g &bull; ₹<?= number_format($activeSilver100 * 1000, 2) ?> / kg</span>
                </div>
            </div>

            <?php if (!empty($silverRate['remarks'])): ?>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 italic pt-1 border-t border-slate-200 dark:border-slate-800">
                    <i class="fa-solid fa-quote-left text-[9px] mr-1 text-slate-400"></i><?= htmlspecialchars($silverRate['remarks']) ?>
                </p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Automated Collateral Valuation Info Banner -->
    <div class="rounded-2xl border border-blue-500/30 bg-blue-500/10 dark:bg-blue-950/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400">
                <i class="fa-solid fa-bolt text-sm"></i>
            </div>
            <div>
                <p class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    Automated Collateral Valuation Synchronization
                    <span class="rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-extrabold uppercase">Live Auto-Sync</span>
                </p>
                <p class="text-slate-600 dark:text-slate-400 text-[11px] mt-0.5">
                    When you update the Gold or Silver rate below, the market valuation of all pledged collateral items is automatically updated based on their individual net weight and purity percentage.
                </p>
            </div>
        </div>
        <form action="<?= $baseUrl ?>/rates/sync-collaterals" method="POST" class="shrink-0" onsubmit="return confirm('Re-evaluate and synchronize all pledged collateral market values against current gold & silver rates?');">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <button type="submit" class="rounded-xl bg-blue-600 hover:bg-blue-500 px-3.5 py-2 text-xs font-bold text-white shadow transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-arrows-rotate text-xs"></i>
                <span>Sync All Valuations Now</span>
            </button>
        </form>
    </div>

    <!-- Update 100% Purity Base Rates Forms Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Form 1: Update 100% Pure Gold Base Rate -->
        <form action="<?= $baseUrl ?>/rates/gold" method="POST" class="rounded-3xl border border-amber-300/60 dark:border-yellow-500/20 bg-white dark:bg-slate-900 p-6 space-y-5 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-500"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Update 100% Gold Base Rate</h3>
                </div>
                <span class="text-[11px] text-amber-600 dark:text-amber-400 font-mono font-semibold">Standard ₹ / 10g</span>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label for="rate_100_gold" class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        100% Pure Gold Base Rate (₹ / 10 Grams) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-slate-400 font-mono font-bold">₹</span>
                        <input type="number" step="0.01" id="rate_100_gold" name="rate_100" x-model="goldRate100" required placeholder="e.g. 140000.00"
                               class="w-full rounded-2xl border border-amber-300 dark:border-yellow-500/40 bg-slate-50 dark:bg-slate-950 py-3 pl-8 pr-4 text-base font-black text-amber-600 dark:text-yellow-400 focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <!-- Live Computed Preview -->
                <div class="rounded-2xl border border-amber-200 dark:border-yellow-500/20 bg-amber-50/60 dark:bg-yellow-950/20 p-3.5 flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-900 dark:text-yellow-300">Live Computed Rate per Gram:</span>
                    <span class="font-mono font-extrabold text-sm text-amber-700 dark:text-yellow-400">
                        ₹<span x-text="goldPerGram"></span> / g
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="gold_rate_date" class="block text-slate-600 dark:text-slate-400 text-[11px] font-bold mb-1">Effective Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="gold_rate_date" name="rate_date" value="<?= date('Y-m-d') ?>" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="gold_remarks" class="block text-slate-600 dark:text-slate-400 text-[11px] font-bold mb-1">Remarks (Optional)</label>
                        <input type="text" id="gold_remarks" name="remarks" placeholder="e.g. Morning Market Update"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-amber-500 py-3 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Update 100% Gold Rate
            </button>
        </form>

        <!-- Form 2: Update 100% Pure Silver Base Rate -->
        <form action="<?= $baseUrl ?>/rates/silver" method="POST" class="rounded-3xl border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-5 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-slate-600 dark:text-slate-400"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Update 100% Silver Base Rate</h3>
                </div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono font-semibold">Standard ₹ / Gram</span>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label for="rate_100_silver" class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        100% Pure Silver Base Rate (₹ / Gram) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-slate-400 font-mono font-bold">₹</span>
                        <input type="number" step="0.01" id="rate_100_silver" name="rate_100" x-model="silverRate100" required placeholder="e.g. 100.00"
                               class="w-full rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-3 pl-8 pr-4 text-base font-black text-slate-900 dark:text-white focus:border-slate-500 focus:outline-none">
                    </div>
                </div>

                <!-- Live Computed Preview -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 p-3.5 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Live Equivalent Rate:</span>
                    <span class="font-mono font-extrabold text-xs text-slate-900 dark:text-slate-100">
                        ₹<span x-text="silverPer10g"></span> / 10g &bull; ₹<span x-text="silverPerKg"></span> / kg
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="silver_rate_date" class="block text-slate-600 dark:text-slate-400 text-[11px] font-bold mb-1">Effective Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="silver_rate_date" name="rate_date" value="<?= date('Y-m-d') ?>" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="silver_remarks" class="block text-slate-600 dark:text-slate-400 text-[11px] font-bold mb-1">Remarks (Optional)</label>
                        <input type="text" id="silver_remarks" name="remarks" placeholder="e.g. Daily Closing Fix"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-slate-900 dark:bg-slate-800 border border-slate-700 py-3 text-xs font-bold text-white hover:bg-slate-800 dark:hover:bg-slate-700 transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Update 100% Silver Rate
            </button>
        </form>

    </div>

    <!-- Recent 100% Rate Update History Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Gold Rate History -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Gold Rate History
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-500 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3 text-right">100% Rate (₹/10g)</th>
                            <th class="py-2.5 px-3 text-right">Per Gram</th>
                            <th class="py-2.5 px-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300 font-mono">
                        <?php if (empty($goldHistory)): ?>
                            <tr><td colspan="4" class="py-4 text-center text-slate-400 font-sans">No history recorded yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($goldHistory as $gh): ?>
                                <?php $g100 = floatval($gh['rate_100'] ?? (floatval($gh['rate_24k']) / 0.999)); ?>
                                <tr>
                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($gh['rate_date']) ?></td>
                                    <td class="py-2 px-3 text-right font-bold text-amber-600 dark:text-yellow-400">₹<?= number_format($g100, 2) ?></td>
                                    <td class="py-2 px-3 text-right font-medium text-slate-700 dark:text-slate-300">₹<?= number_format($g100 / 10, 2) ?></td>
                                    <td class="py-2 px-3 font-sans text-[11px] text-slate-500 truncate max-w-[120px]"><?= htmlspecialchars($gh['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Silver Rate History -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-400"></i> Silver Rate History
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-500 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3 text-right">100% Rate (₹/g)</th>
                            <th class="py-2.5 px-3 text-right">Per 10g</th>
                            <th class="py-2.5 px-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300 font-mono">
                        <?php if (empty($silverHistory)): ?>
                            <tr><td colspan="4" class="py-4 text-center text-slate-400 font-sans">No history recorded yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($silverHistory as $sh): ?>
                                <?php $s100 = floatval($sh['rate_100'] ?? (floatval($sh['rate_999']) / 0.999)); ?>
                                <tr>
                                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($sh['rate_date']) ?></td>
                                    <td class="py-2 px-3 text-right font-bold text-slate-900 dark:text-white">₹<?= number_format($s100, 2) ?></td>
                                    <td class="py-2 px-3 text-right font-medium text-slate-700 dark:text-slate-300">₹<?= number_format($s100 * 10, 2) ?></td>
                                    <td class="py-2 px-3 font-sans text-[11px] text-slate-500 truncate max-w-[120px]"><?= htmlspecialchars($sh['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
