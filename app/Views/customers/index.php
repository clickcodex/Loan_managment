<?php
$pageTitle = 'Customer Directory';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6">

    <!-- Page Header & Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-users text-blue-500"></i> Customer Directory
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage customer profiles, KYC information, and loan accounts</p>
        </div>
        <a href="<?= $baseUrl ?>/customers/create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
            <i class="fa-solid fa-user-plus text-sm"></i> Register New Customer
        </a>
    </div>

    <!-- Search & Filter Controls -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
        
        <!-- Status Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
            <a href="<?= $baseUrl ?>/customers<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold whitespace-nowrap transition <?= empty($status) ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                All Customers
            </a>
            <a href="<?= $baseUrl ?>/customers?status=Active<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold whitespace-nowrap transition <?= $status === 'Active' ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Active
            </a>
            <a href="<?= $baseUrl ?>/customers?status=Closed<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold whitespace-nowrap transition <?= $status === 'Closed' ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Closed
            </a>
            <a href="<?= $baseUrl ?>/customers?status=Blocked<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="rounded-xl px-3 py-1.5 text-xs font-bold whitespace-nowrap transition <?= $status === 'Blocked' ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                Blocked
            </a>
        </div>

        <!-- Multi-Criteria Search Input -->
        <form action="<?= $baseUrl ?>/customers" method="GET" class="flex flex-wrap items-center gap-2">
            <?php if (!empty($status)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <?php endif; ?>
            <div class="relative flex-1 min-w-[200px] md:w-72">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search name, mobile, Aadhaar..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
            </div>
            <button type="submit" class="rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/customers" class="text-xs text-slate-500 hover:underline">Clear</a>
            <?php endif; ?>
        </form>

    </div>

    <!-- Customer Directory List & Table Wrapper -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <?php if (empty($customers)): ?>
            <div class="py-16 text-center text-slate-500">
                <i class="fa-solid fa-users-slash text-4xl mb-3 text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No customers found</p>
                <p class="text-xs mt-1 text-slate-500">Try refining your search query or clear filters.</p>
            </div>
        <?php else: ?>
            
            <!-- Desktop Layout: Standard Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Account#</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">KYC / Aadhaar</th>
                            <th class="py-3 px-4">Guarantor</th>
                            <th class="py-3 px-4 text-center">Loans</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-300">
                        <?php foreach ($customers as $c): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <!-- Customer Info -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($c['photo'])): ?>
                                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($c['photo']) ?>" alt="Photo" class="h-9 w-9 rounded-full object-cover border border-slate-300 dark:border-slate-700">
                                        <?php else: ?>
                                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold border border-amber-500/30">
                                                <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition">
                                                <?= htmlspecialchars($c['full_name']) ?>
                                            </a>
                                            <span class="block text-[10px] font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($c['customer_id']) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Account Number -->
                                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400">
                                    <?= htmlspecialchars($c['account_number'] ?? '—') ?>
                                </td>

                                <!-- Contact Info -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-slate-200"><?= htmlspecialchars($c['mobile']) ?></div>
                                    <?php if (!empty($c['city'])): ?>
                                        <span class="text-[10px] text-slate-500"><?= htmlspecialchars($c['city']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- KYC Info -->
                                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400">
                                    <?php 
                                        $aadhaar = $c['aadhaar'] ?? '';
                                        if (strlen($aadhaar) >= 4) {
                                            echo 'XXXX-XXXX-' . substr($aadhaar, -4);
                                        } else {
                                            echo 'Not provided';
                                        }
                                    ?>
                                </td>

                                <!-- Guarantor -->
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                    <?= htmlspecialchars($c['guarantor_name'] ?? '—') ?>
                                </td>

                                <!-- Loan Counts -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 font-bold text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                        <i class="fa-solid fa-file-invoice text-[10px] text-amber-500"></i>
                                        <?= intval($c['total_loans']) ?>
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <?php if ($c['status'] === 'Active'): ?>
                                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Active</span>
                                    <?php elseif ($c['status'] === 'Closed'): ?>
                                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:text-slate-400">Closed</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/20">Blocked</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>" title="View Profile" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>/edit" title="Edit Profile" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Layout: Card Grid View (Hidden on desktop) -->
            <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
                <?php foreach ($customers as $c): ?>
                    <div class="p-4 space-y-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/20 transition">
                        
                        <!-- Top Details Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($c['photo'])): ?>
                                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($c['photo']) ?>" alt="Photo" class="h-11 w-11 rounded-full object-cover border border-slate-300 dark:border-slate-700 shrink-0">
                                <?php else: ?>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold border border-amber-500/30 text-sm shrink-0">
                                        <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>" class="font-bold text-sm text-slate-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition line-clamp-1">
                                        <?= htmlspecialchars($c['full_name']) ?>
                                    </a>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($c['customer_id']) ?></span>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-600">•</span>
                                        <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400">Acct: <?= htmlspecialchars($c['account_number'] ?? '—') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <?php if ($c['status'] === 'Active'): ?>
                                    <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Active</span>
                                <?php elseif ($c['status'] === 'Closed'): ?>
                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400">Closed</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/20">Blocked</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info Metadata Row -->
                        <div class="grid grid-cols-2 gap-3.5 text-xs border-t border-slate-100 dark:border-slate-800/60 pt-3">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Contact</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($c['mobile']) ?></span>
                                <?php if (!empty($c['city'])): ?>
                                    <span class="block text-[10px] text-slate-500 mt-0.5"><?= htmlspecialchars($c['city']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">KYC / Aadhaar</span>
                                <span class="font-mono text-slate-700 dark:text-slate-300 block">
                                    <?php 
                                        $aadhaar = $c['aadhaar'] ?? '';
                                        if (strlen($aadhaar) >= 4) {
                                            echo 'XXXX-XXXX-' . substr($aadhaar, -4);
                                        } else {
                                            echo 'Not provided';
                                        }
                                    ?>
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Guarantor</span>
                                <span class="text-slate-700 dark:text-slate-300 block truncate"><?= htmlspecialchars($c['guarantor_name'] ?? '—') ?></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">Active Loans</span>
                                <span class="inline-flex items-center gap-1 font-bold text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800 px-2 py-0.5 mt-0.5 rounded-lg border border-slate-200 dark:border-slate-700 text-[11px]">
                                    <i class="fa-solid fa-file-invoice text-[10px] text-amber-500"></i>
                                    <?= intval($c['total_loans']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Touch-optimized mobile actions row -->
                        <div class="border-t border-slate-100 dark:border-slate-800/60 pt-2.5 flex items-center justify-end gap-2">
                            <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <i class="fa-solid fa-eye text-[11px]"></i> View Profile
                            </a>
                            <a href="<?= $baseUrl ?>/customers/<?= $c['id'] ?>/edit" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition">
                                <i class="fa-solid fa-pen text-[11px]"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>