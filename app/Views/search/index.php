<?php
$pageTitle = !empty($query) ? 'Search: "' . htmlspecialchars($query) . '"' : 'Global Search Engine';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$customers = $results['customers'] ?? [];
$loans     = $results['loans'] ?? [];
$items     = $results['items'] ?? [];
$payments  = $results['payments'] ?? [];
$total     = $results['total_count'] ?? 0;
?>

<div class="space-y-6" x-data="{ activeTab: 'all' }">

    <!-- Top Search Banner -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-amber-500"></i> Global Search Engine
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cross-entity search across Customers, Loans, Collateral Vault, and Payments</p>
        </div>

        <!-- Search Form Input -->
        <form action="<?= $baseUrl ?>/search" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative w-full">
                <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" autofocus required
                       placeholder="Search by Name, Mobile, Aadhaar, Account#, Loan#, RK Rack#, Guarantor, Receipt#, Item Name, Loan Remarks..."
                       class="w-full rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-3.5 pl-12 pr-4 text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:border-amber-500 focus:outline-none shadow-xs">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-4 text-amber-500 text-base"></i>
            </div>
            <button type="submit" class="rounded-2xl bg-amber-500 px-6 py-3 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition whitespace-nowrap">
                Search All Records
            </button>
        </form>

        <!-- 10 Criteria Chips -->
        <div class="flex flex-wrap items-center gap-1.5 pt-1 text-[11px] text-slate-600 dark:text-slate-400">
            <span class="font-bold text-slate-500 uppercase tracking-wider mr-1">Search Criteria:</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">👤 Name</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">📱 Mobile</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">🪪 Aadhaar</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">💳 Account#</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">📜 Loan#</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">💬 Loan Remarks</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">🏛️ RK Storage#</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">🛡️ Guarantor</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">🧾 Receipt#</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">💎 Item Name</span>
            <span class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 border border-slate-200 dark:border-slate-700 font-bold">📑 PAN / Address</span>
        </div>
    </div>

    <?php if (!empty($query)): ?>

        <!-- Categorized Result Tab Pills -->
        <div class="flex items-center gap-2 overflow-x-auto border-b border-slate-200 dark:border-slate-800 pb-3">
            <button @click="activeTab = 'all'" 
                    class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-2"
                    :class="activeTab === 'all' ? 'bg-amber-500 text-slate-950 shadow' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'">
                <span>All Results</span>
                <span class="rounded-full bg-slate-950/20 px-2 py-0.5 text-[10px]" x-text="'<?= $total ?>'"></span>
            </button>

            <button @click="activeTab = 'customers'" 
                    class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-2"
                    :class="activeTab === 'customers' ? 'bg-blue-600 text-white shadow' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'">
                <span>Customers</span>
                <span class="rounded-full bg-slate-950/20 px-2 py-0.5 text-[10px]"><?= count($customers) ?></span>
            </button>

            <button @click="activeTab = 'loans'" 
                    class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-2"
                    :class="activeTab === 'loans' ? 'bg-amber-500 text-slate-950 shadow' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'">
                <span>Loans</span>
                <span class="rounded-full bg-slate-950/20 px-2 py-0.5 text-[10px]"><?= count($loans) ?></span>
            </button>

            <button @click="activeTab = 'items'" 
                    class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-2"
                    :class="activeTab === 'items' ? 'bg-purple-600 text-white shadow' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'">
                <span>Collateral Vault</span>
                <span class="rounded-full bg-slate-950/20 px-2 py-0.5 text-[10px]"><?= count($items) ?></span>
            </button>

            <button @click="activeTab = 'payments'" 
                    class="rounded-xl px-4 py-2 text-xs font-bold transition flex items-center gap-2"
                    :class="activeTab === 'payments' ? 'bg-emerald-600 text-white shadow' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'">
                <span>Receipts</span>
                <span class="rounded-full bg-slate-950/20 px-2 py-0.5 text-[10px]"><?= count($payments) ?></span>
            </button>
        </div>

        <?php if ($total === 0): ?>
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-16 text-center text-slate-500 space-y-2 shadow-sm">
                <i class="fa-solid fa-magnifying-glass text-4xl text-slate-400"></i>
                <p class="text-base font-bold text-slate-700 dark:text-slate-300">No matching records found</p>
                <p class="text-xs text-slate-500">No results found for "<?= htmlspecialchars($query) ?>". Try searching by customer name, mobile, loan#, or RK#.</p>
            </div>
        <?php else: ?>

            <!-- RESULTS SECTIONS -->
            <div class="space-y-6">

                <!-- 1. CUSTOMERS MATCH SECTION -->
                <div x-show="activeTab === 'all' || activeTab === 'customers'" x-cloak class="space-y-3">
                    <?php if (!empty($customers)): ?>
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-2">
                                <i class="fa-solid fa-users"></i> Customers Found (<?= count($customers) ?>)
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($customers as $c): ?>
                                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-3 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <?php if (!empty($c['photo'])): ?>
                                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($c['photo']) ?>" alt="Photo" class="h-10 w-10 rounded-xl object-cover border border-amber-500/30">
                                            <?php else: ?>
                                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold text-base">
                                                    <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition text-sm">
                                                    <?= htmlspecialchars($c['full_name']) ?>
                                                </a>
                                                <span class="block text-[10px] text-slate-500 font-mono">Mobile: <?= htmlspecialchars($c['mobile']) ?> &bull; Code: <?= htmlspecialchars($c['customer_id']) ?></span>
                                            </div>
                                        </div>

                                        <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50">
                                            View Profile
                                        </a>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px] bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                                        <div><span class="text-slate-500">Account#:</span> <?= htmlspecialchars($c['account_number'] ?? '—') ?></div>
                                        <div><span class="text-slate-500">Aadhaar:</span> <?= htmlspecialchars($c['aadhaar'] ?? '—') ?></div>
                                        <div><span class="text-slate-500">Guarantor:</span> <?= htmlspecialchars($c['guarantor_name'] ?? '—') ?></div>
                                    </div>

                                    <?php if (!empty($c['matched_loan_remark'])): ?>
                                        <div class="flex items-start gap-1.5 text-[11px] bg-amber-50 dark:bg-amber-950/30 text-amber-900 dark:text-amber-300 p-2 rounded-xl border border-amber-200 dark:border-amber-800/50">
                                            <i class="fa-solid fa-comment-dots text-amber-500 mt-0.5 shrink-0"></i>
                                            <span><strong class="font-bold">Loan Note:</strong> <?= htmlspecialchars($c['matched_loan_remark']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. LOANS MATCH SECTION -->
                <div x-show="activeTab === 'all' || activeTab === 'loans'" x-cloak class="space-y-3">
                    <?php if (!empty($loans)): ?>
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-2">
                                <i class="fa-solid fa-file-invoice"></i> Loans Found (<?= count($loans) ?>)
                            </h2>
                        </div>

                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                                        <tr>
                                            <th class="py-3 px-4">Loan Number</th>
                                            <th class="py-3 px-4">Customer</th>
                                            <th class="py-3 px-4">Security Type</th>
                                            <th class="py-3 px-4">Principal</th>
                                            <th class="py-3 px-4">Status</th>
                                            <th class="py-3 px-4 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                                        <?php foreach ($loans as $l): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-3.5 px-4 font-mono font-bold text-amber-600 dark:text-amber-400">
                                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="hover:underline">
                                                        <?= htmlspecialchars($l['loan_number']) ?>
                                                    </a>
                                                    <?php if (!empty($l['remarks'])): ?>
                                                        <div class="text-[10px] font-sans font-normal text-slate-500 dark:text-slate-400 truncate max-w-[200px] mt-0.5" title="<?= htmlspecialchars($l['remarks']) ?>">
                                                            <i class="fa-solid fa-comment-dots text-amber-500 mr-1"></i><?= htmlspecialchars($l['remarks']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                                    <a href="<?= $baseUrl ?>/customers/<?= $l['customer_id'] ?>" class="hover:underline">
                                                        <?= htmlspecialchars($l['customer_name']) ?>
                                                    </a>
                                                </td>
                                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($l['security_type']) ?></td>
                                                <td class="py-3.5 px-4 font-extrabold text-slate-900 dark:text-white font-mono">₹<?= number_format($l['principal_amount'], 2) ?></td>
                                                <td class="py-3.5 px-4">
                                                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold <?= $l['status'] === 'Running' ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' ?>">
                                                        <?= htmlspecialchars($l['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <a href="<?= $baseUrl ?>/loans/<?= $l['id'] ?>" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50">
                                                        View Loan
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. COLLATERAL VAULT ITEMS SECTION -->
                <div x-show="activeTab === 'all' || activeTab === 'items'" x-cloak class="space-y-3">
                    <?php if (!empty($items)): ?>
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-purple-600 dark:text-purple-400 flex items-center gap-2">
                                <i class="fa-solid fa-gem"></i> Collateral Vault Items Found (<?= count($items) ?>)
                            </h2>
                        </div>

                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                                        <tr>
                                            <th class="py-3 px-4">Item Name</th>
                                            <th class="py-3 px-4">Type</th>
                                            <th class="py-3 px-4">RK Storage#</th>
                                            <th class="py-3 px-4">Net Wt</th>
                                            <th class="py-3 px-4">Loan Number</th>
                                            <th class="py-3 px-4">Customer</th>
                                            <th class="py-3 px-4 text-right">Valuation</th>
                                            <th class="py-3 px-4 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                                        <?php foreach ($items as $ci): ?>
                                            <?php $val = floatval($ci['manual_market_value_override'] ?: $ci['market_value']); ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                                    <a href="<?= $baseUrl ?>/collateral/<?= $ci['id'] ?>" class="hover:text-amber-600 dark:hover:text-amber-400">
                                                        <?= htmlspecialchars($ci['item_name']) ?>
                                                    </a>
                                                </td>
                                                <td class="py-3.5 px-4">
                                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold <?= $ci['item_type'] === 'GOLD' ? 'bg-amber-500/20 text-amber-600 dark:text-yellow-400 border border-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' ?>">
                                                        <?= htmlspecialchars($ci['item_type']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3.5 px-4 font-mono font-bold text-purple-600 dark:text-purple-300"><?= htmlspecialchars($ci['rk_number'] ?? '—') ?></td>
                                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white"><?= number_format($ci['net_weight'], 3) ?> g</td>
                                                <td class="py-3.5 px-4 font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($ci['loan_number']) ?></td>
                                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($ci['customer_name']) ?></td>
                                                <td class="py-3.5 px-4 text-right font-extrabold text-amber-600 dark:text-yellow-400 font-mono">₹<?= number_format($val, 2) ?></td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <a href="<?= $baseUrl ?>/collateral/<?= $ci['id'] ?>" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50">
                                                        View Spec
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 4. PAYMENTS & RECEIPTS SECTION -->
                <div x-show="activeTab === 'all' || activeTab === 'payments'" x-cloak class="space-y-3">
                    <?php if (!empty($payments)): ?>
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                                <i class="fa-solid fa-receipt"></i> Payment Receipts Found (<?= count($payments) ?>)
                            </h2>
                        </div>

                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
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
                                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                    <a href="<?= $baseUrl ?>/receipts/payment/<?= $p['id'] ?>" target="_blank" class="hover:underline">
                                                        <?= htmlspecialchars($p['receipt_number']) ?>
                                                    </a>
                                                </td>
                                                <td class="py-3.5 px-4 font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($p['loan_number']) ?></td>
                                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($p['customer_name']) ?></td>
                                                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400"><?= htmlspecialchars($p['payment_date']) ?></td>
                                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                                    <?= htmlspecialchars($p['payment_mode']) ?>
                                                    <?= !empty($p['reference_number']) ? ' (' . htmlspecialchars($p['reference_number']) . ')' : '' ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-right font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">₹<?= number_format($p['total_amount'], 2) ?></td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <a href="<?= $baseUrl ?>/receipts/payment/<?= $p['id'] ?>" target="_blank" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-slate-50">
                                                        <i class="fa-solid fa-print mr-1"></i> Print
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        <?php endif; ?>

    <?php else: ?>
        <!-- Idle Search State -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center text-slate-500 space-y-3 shadow-sm">
            <i class="fa-solid fa-magnifying-glass text-5xl text-slate-400"></i>
            <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">Enter Search Term Above</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Type any customer name, mobile, Aadhaar, account number, loan number, RK storage rack reference, guarantor name, receipt ID, or collateral item name.
            </p>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
