<?php
$pageTitle = 'Billing & Invoicing System';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ deleteModal: false, deleteBillId: null, deleteBillNumber: '' }">

    <!-- Top Action Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-file-invoice-dollar text-amber-500"></i>
                Billing System
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Create, print, and store multi-product customer bills & invoices.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= $baseUrl ?>/bills/create" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:from-amber-400 hover:to-amber-500 transition-all">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Generate New Bill</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Bills -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Invoices</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($stats['total_count']) ?></p>
            <span class="text-[11px] text-slate-400">Stored in system</span>
        </div>

        <!-- Card 2: Total Revenue -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Billed</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">₹<?= number_format($stats['total_revenue'], 2) ?></p>
            <span class="text-[11px] text-slate-400">Lifetime total sales</span>
        </div>

        <!-- Card 3: Today's Bills -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Today's Invoices</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($stats['today_count']) ?></p>
            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold">₹<?= number_format($stats['today_revenue'], 2) ?></span>
        </div>

        <!-- Card 4: Month's Revenue -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">This Month</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-2">₹<?= number_format($stats['month_revenue'], 2) ?></p>
            <span class="text-[11px] text-slate-400"><?= number_format($stats['month_count']) ?> bills this month</span>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs">
        <form action="<?= $baseUrl ?>/bills" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Search Bills</label>
                <div class="relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Bill #, Customer, Mobile, Company..." 
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">From Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">To Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-slate-900 dark:bg-slate-100 py-2 px-4 text-xs font-bold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition">
                    <i class="fa-solid fa-filter mr-1.5"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($startDate) || !empty($endDate)): ?>
                    <a href="<?= $baseUrl ?>/bills" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Bills Directory Table -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-list text-amber-500"></i>
                All Invoices (<?= count($bills) ?>)
            </h2>
            <span class="text-xs text-slate-400">Stored in unified single table</span>
        </div>

        <?php if (empty($bills)): ?>
            <div class="py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                    <i class="fa-solid fa-receipt text-2xl"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Bills Found</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">No invoices match your search or date criteria. Generate a new bill to begin tracking sales.</p>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/bills/create" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
                        <i class="fa-solid fa-circle-plus"></i> Create First Bill
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Bill No / Date</th>
                            <th class="py-3 px-4">Company Name</th>
                            <th class="py-3 px-4">Customer Details</th>
                            <th class="py-3 px-4">Items Summary</th>
                            <th class="py-3 px-4">Payment Mode</th>
                            <th class="py-3 px-4 text-right">Total Amount</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                        <?php foreach ($bills as $b): ?>
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4">
                                    <a href="<?= $baseUrl ?>/bills/<?= $b['id'] ?>" class="font-mono font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                        <?= htmlspecialchars($b['bill_number']) ?>
                                    </a>
                                    <span class="block text-[10px] text-slate-400 font-mono mt-0.5">
                                        <?= date('d M Y', strtotime($b['bill_date'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($b['company_name']) ?>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($b['customer_name']) ?></p>
                                    <div class="flex items-center gap-2 flex-wrap text-[11px] font-mono text-slate-400">
                                        <?php if (!empty($b['customer_mobile'])): ?>
                                            <span><?= htmlspecialchars($b['customer_mobile']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['gst_number'])): ?>
                                            <span class="rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                                                GST: <?= htmlspecialchars($b['gst_number']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="max-w-xs truncate text-[11px]">
                                        <span class="rounded-md bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-600 dark:text-slate-300 mr-1">
                                            <?= $b['item_count'] ?> <?= $b['item_count'] === 1 ? 'item' : 'items' ?>
                                        </span>
                                        <?php
                                            $itemNames = array_map(function($it) {
                                                return htmlspecialchars($it['product_name'] ?? '') . ' (x' . ($it['quantity'] ?? 1) . ')';
                                            }, $b['items_decoded']);
                                            echo implode(', ', array_slice($itemNames, 0, 3));
                                            if (count($itemNames) > 3) echo '...';
                                        ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($b['payment_mode']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                        ₹<?= number_format(floatval($b['total_amount']), 2) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="<?= $baseUrl ?>/bills/<?= $b['id'] ?>" 
                                           class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-1.5 text-slate-600 dark:text-slate-300 hover:text-amber-500 hover:border-amber-500 transition shadow-2xs" 
                                           title="View & Print Bill">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <button type="button" 
                                                @click="deleteModal = true; deleteBillId = <?= $b['id'] ?>; deleteBillNumber = '<?= htmlspecialchars($b['bill_number']) ?>'"
                                                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-1.5 text-slate-400 hover:text-rose-500 hover:border-rose-500 transition shadow-2xs" 
                                                title="Delete Bill">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Bill Confirmation Modal -->
    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div @click.away="deleteModal = false" class="w-full max-w-sm rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-500">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Invoice?</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Are you sure you want to delete bill <strong class="text-slate-800 dark:text-slate-200" x-text="deleteBillNumber"></strong>? This record will be permanently deleted.
                </p>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="deleteModal = false" class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Cancel
                </button>
                <form :action="'<?= $baseUrl ?>/bills/' + deleteBillId + '/delete'" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-rose-500 transition">
                        Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
