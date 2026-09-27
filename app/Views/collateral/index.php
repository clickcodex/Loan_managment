<?php
$pageTitle = 'Collateral Vault Directory';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-gem text-amber-500"></i> Collateral Vault & Inventory Directory
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Master catalog of all pledged Gold and Silver items across all loan accounts</p>
        </div>

        <div class="flex items-center gap-3">
            <?php if (!empty($unassignedCount) && $unassignedCount > 0): ?>
                <a href="<?= $baseUrl ?>/collateral/auto-assign" class="rounded-xl bg-purple-600 hover:bg-purple-700 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Assign (<?= $unassignedCount ?>)
                </a>
            <?php endif; ?>

            <a href="<?= $baseUrl ?>/collateral/racks" class="rounded-xl bg-amber-500 hover:bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-cubes"></i> 10-Rack Storage Visualizer
            </a>
        </div>
    </div>

    <?php if (!empty($unassignedCount) && $unassignedCount > 0): ?>
        <div class="rounded-2xl border border-purple-300 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/40 p-4 text-xs font-bold text-purple-900 dark:text-purple-300 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-exclamation text-purple-600 dark:text-purple-400 text-sm"></i>
                <span>There are <strong><?= $unassignedCount ?></strong> pledged collateral items without an assigned rack slot.</span>
            </div>
            <a href="<?= $baseUrl ?>/collateral/auto-assign" class="rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 text-xs font-bold transition">
                Auto-Assign Now
            </a>
        </div>
    <?php endif; ?>

    <!-- 4 Portfolio KPI Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Gold Net Weight -->
        <div class="rounded-2xl border border-amber-300 dark:border-yellow-500/30 bg-amber-50 dark:bg-yellow-500/5 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-800 dark:text-yellow-300">Gold Pledged Weight</span>
                <i class="fa-solid fa-scale-balanced text-amber-600 dark:text-yellow-400 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-amber-600 dark:text-yellow-400 mt-1 font-mono"><?= number_format($stats['gold_net_weight'], 3) ?> g</p>
            <span class="text-[11px] text-amber-800/80 dark:text-yellow-500/80 font-bold"><?= number_format($stats['gold_items_count']) ?> active items</span>
        </div>

        <!-- Gold Portfolio Value -->
        <div class="rounded-2xl border border-amber-300 dark:border-yellow-500/30 bg-amber-50 dark:bg-yellow-500/5 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-800 dark:text-yellow-300">Gold Portfolio Value</span>
                <i class="fa-solid fa-coins text-amber-600 dark:text-yellow-400 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-amber-600 dark:text-yellow-400 mt-1 font-mono">₹<?= number_format($stats['gold_market_value'], 2) ?></p>
            <span class="text-[11px] text-amber-800/80 dark:text-yellow-500/80 font-bold">Market valuation</span>
        </div>

        <!-- Silver Net Weight -->
        <div class="rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/40 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Silver Pledged Weight</span>
                <i class="fa-solid fa-ring text-slate-600 dark:text-slate-300 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-slate-800 dark:text-slate-200 mt-1 font-mono"><?= number_format($stats['silver_net_weight'], 3) ?> g</p>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold"><?= number_format($stats['silver_items_count']) ?> active items</span>
        </div>

        <!-- Silver Portfolio Value -->
        <div class="rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800/40 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Silver Portfolio Value</span>
                <i class="fa-solid fa-vault text-slate-600 dark:text-slate-300 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-slate-800 dark:text-slate-200 mt-1 font-mono">₹<?= number_format($stats['silver_market_value'], 2) ?></p>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold">Market valuation</span>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
        
        <!-- Type Filters -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <a href="<?= $baseUrl ?>/collateral<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= empty($itemType) ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                All Items
            </a>
            <a href="<?= $baseUrl ?>/collateral?type=GOLD<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $itemType === 'GOLD' ? 'bg-amber-500/20 text-amber-600 dark:text-yellow-400 border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Gold Only
            </a>
            <a href="<?= $baseUrl ?>/collateral?type=SILVER<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold transition <?= $itemType === 'SILVER' ? 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Silver Only
            </a>
        </div>

        <!-- RK Storage & Multi-Search Input -->
        <form action="<?= $baseUrl ?>/collateral" method="GET" class="flex items-center gap-2">
            <?php if (!empty($itemType)): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($itemType) ?>">
            <?php endif; ?>
            <div class="relative w-full md:w-72">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search RK#, item name, loan#, customer..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
            </div>
            <button type="submit" class="rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/collateral" class="text-xs text-slate-500 hover:underline">Clear</a>
            <?php endif; ?>
        </form>

    </div>

    <!-- Master Items Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($items)): ?>
            <div class="py-16 text-center text-slate-500">
                <i class="fa-solid fa-vault text-4xl mb-3 text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No collateral items found</p>
                <p class="text-xs mt-1 text-slate-500">Try adjusting search parameters or filter options.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Item Name</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Loan Number</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Gross Wt</th>
                            <th class="py-3 px-4">Net Wt</th>
                            <th class="py-3 px-4">Purity (%)</th>
                            <th class="py-3 px-4">RK Storage#</th>
                            <th class="py-3 px-4 text-right">Market Value</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($items as $item): ?>
                            <?php $marketVal = floatval($item['manual_market_value_override'] ?: $item['market_value']); ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <!-- Item Name & Thumbnail -->
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($item['primary_photo'])): ?>
                                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['primary_photo']) ?>" alt="Photo" class="h-8 w-8 rounded-lg object-cover border border-slate-300 dark:border-slate-700">
                                        <?php else: ?>
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                <i class="fa-solid <?= $item['item_type'] === 'GOLD' ? 'fa-gem text-amber-500' : 'fa-ring text-slate-500' ?> text-xs"></i>
                                            </div>
                                        <?php endif; ?>
                                        <a href="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>" class="hover:text-amber-600 dark:hover:text-amber-400 transition">
                                            <?= htmlspecialchars($item['item_name']) ?>
                                        </a>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="py-3.5 px-4">
                                    <?php if ($item['item_type'] === 'GOLD'): ?>
                                        <span class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-yellow-400 border border-amber-500/30">GOLD</span>
                                    <?php else: ?>
                                        <span class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">SILVER</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Loan Number -->
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-600 dark:text-amber-400">
                                    <a href="<?= $baseUrl ?>/loans/<?= $item['loan_id'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($item['loan_number']) ?>
                                    </a>
                                </td>

                                <!-- Customer -->
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-slate-200">
                                    <?= htmlspecialchars($item['customer_name']) ?>
                                </td>

                                <!-- Gross Wt -->
                                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400">
                                    <?= number_format($item['gross_weight'], 3) ?> g
                                </td>

                                <!-- Net Wt -->
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                    <?= number_format($item['net_weight'], 3) ?> g
                                </td>

                                <!-- Purity (%) -->
                                <td class="py-3.5 px-4 font-bold text-amber-600 dark:text-amber-400 font-mono">
                                    <?php
                                        $purityPct = floatval($item['purity_percentage'] ?? 0);
                                        echo $purityPct > 0 ? number_format($purityPct, 2) . '%' : htmlspecialchars($item['purity_preset'] ?? '—');
                                    ?>
                                </td>

                                <!-- RK Storage Number -->
                                <td class="py-3.5 px-4 font-mono font-bold text-purple-600 dark:text-purple-300">
                                    <?= htmlspecialchars($item['rk_number'] ?? '—') ?>
                                </td>

                                <!-- Market Value -->
                                <td class="py-3.5 px-4 text-right font-extrabold text-amber-600 dark:text-yellow-400 font-mono">
                                    ₹<?= number_format($marketVal, 2) ?>
                                </td>

                                <!-- Action -->
                                <td class="py-3.5 px-4 text-right">
                                    <a href="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50 transition">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
