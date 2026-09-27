<?php
$pageTitle = 'Edit Collateral - ' . htmlspecialchars($item['item_name']);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$goldCustomRates   = !empty($goldRate['custom_rates']) ? json_decode($goldRate['custom_rates'], true) : [];
$silverCustomRates = !empty($silverRate['custom_rates']) ? json_decode($silverRate['custom_rates'], true) : [];
?>

<div class="max-w-2xl mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-pen text-amber-500"></i> Edit Collateral Specifications
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Item: <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($item['item_name']) ?></span> &bull; Loan: <span class="font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($item['loan_number']) ?></span>
            </p>
        </div>
        <a href="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Item
        </a>
    </div>

    <!-- Edit Form Card -->
    <form action="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>/update" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <!-- Item Name -->
            <div>
                <label for="item_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Item Name <span class="text-rose-500">*</span></label>
                <input type="text" id="item_name" name="item_name" required value="<?= htmlspecialchars($item['item_name']) ?>"
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
            </div>

            <!-- Weights & Quantity -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="quantity" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Quantity</label>
                    <input type="number" min="1" id="quantity" name="quantity" value="<?= intval($item['quantity']) ?>" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label for="gross_weight" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Weight (g) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.001" id="gross_weight" name="gross_weight" value="<?= number_format($item['gross_weight'], 3, '.', '') ?>" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                    <input type="hidden" id="stone_weight" name="stone_weight" value="<?= number_format($item['stone_weight'] ?? 0, 3, '.', '') ?>">
                </div>
            </div>

            <!-- Purity Percentage & RK Storage -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="purity_percentage" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Purity (%) <span class="text-amber-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.01" min="1" max="100" id="purity_percentage" name="purity_percentage" value="<?= number_format($item['purity_percentage'] ?? 91.67, 2, '.', '') ?>" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 pl-3.5 pr-8 text-xs text-amber-600 dark:text-amber-400 font-bold focus:border-amber-500 focus:outline-none">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-slate-400 font-bold">%</span>
                    </div>
                    <input type="hidden" name="purity_preset" value="Custom">
                </div>

                <div>
                    <label for="rk_number" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">RK Storage Rack #</label>
                    <input type="text" id="rk_number" name="rk_number" value="<?= htmlspecialchars($item['rk_number'] ?? '') ?>" placeholder="e.g. RK-A01"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Manual Market Value Override -->
            <div>
                <label for="manual_market_value_override" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Manual Valuation Override (₹)</label>
                <input type="number" step="0.01" id="manual_market_value_override" name="manual_market_value_override" value="<?= !empty($item['manual_market_value_override']) ? number_format($item['manual_market_value_override'], 2, '.', '') : '' ?>" placeholder="Leave blank to use automatic live rate"
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
            </div>

            <!-- Item Remarks -->
            <div>
                <label for="remarks" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Item Remarks & Hallmarks</label>
                <textarea id="remarks" name="remarks" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none"><?= htmlspecialchars($item['remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>" class="rounded-xl px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900">
                Cancel
            </a>
            <button type="submit" class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow hover:bg-amber-400">
                Update Specifications
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
