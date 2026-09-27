<?php
$pageTitle = 'Audit Logs & Security Trail';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ diffModal: false, selectedLog: null }">

    <!-- Page Header & Title -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-orange-400"></i> Audit Logs & Security Trail
            </h1>
            <p class="text-xs text-slate-400 mt-1">Field-level change tracking, security context, and 13 action categories</p>
        </div>

        <span class="rounded-full bg-slate-900 border border-slate-800 px-3.5 py-1.5 text-xs font-mono font-bold text-amber-400">
            <i class="fa-solid fa-lock mr-1"></i> Immutable Security Logs
        </span>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Operations Logged</span>
            <div class="text-2xl font-black text-amber-400 font-mono"><?= number_format($kpis['total_logs']) ?></div>
            <span class="text-[10px] text-slate-500 block">Lifetime audit records</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Today's System Actions</span>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= number_format($kpis['today_logs']) ?></div>
            <span class="text-[10px] text-slate-500 block">Operations recorded today</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Unique IP Addresses</span>
            <div class="text-2xl font-black text-purple-400 font-mono"><?= number_format($kpis['unique_ips']) ?></div>
            <span class="text-[10px] text-slate-500 block">Security client IPs</span>
        </div>
    </div>

    <!-- Category Filter Tabs & Search Bar Card -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-4 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <!-- Category Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto text-xs">
                <?php
                    $cats = [
                        'all'        => ['label' => 'All Actions', 'icon' => 'fa-layer-group'],
                        'auth'       => ['label' => 'Auth', 'icon' => 'fa-key'],
                        'customers'  => ['label' => 'Customers', 'icon' => 'fa-users'],
                        'loans'      => ['label' => 'Loans', 'icon' => 'fa-hand-holding-dollar'],
                        'collateral' => ['label' => 'Collateral', 'icon' => 'fa-gem'],
                        'payments'   => ['label' => 'Payments', 'icon' => 'fa-receipt'],
                        'rates'      => ['label' => 'Rates', 'icon' => 'fa-chart-line'],
                        'system'     => ['label' => 'System', 'icon' => 'fa-gear']
                    ];
                ?>
                <?php foreach ($cats as $key => $meta): ?>
                    <a href="<?= $baseUrl ?>/audit-logs?category=<?= $key ?>&search=<?= urlencode($search) ?>" 
                       class="rounded-xl px-3 py-1.5 font-bold transition flex items-center gap-1.5 whitespace-nowrap <?= $category === $key ? 'bg-amber-500 text-slate-950 shadow' : 'bg-slate-955 text-slate-400 hover:text-white border border-slate-800' ?>">
                        <i class="fa-solid <?= $meta['icon'] ?> text-xs"></i>
                        <span><?= $meta['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Form -->
            <form action="<?= $baseUrl ?>/audit-logs" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                <div class="relative w-64">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search action, user, IP..."
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-1.5 pl-8 pr-3 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-slate-500 text-xs"></i>
                </div>
                <button type="submit" class="rounded-xl bg-slate-800 border border-slate-700 px-3 py-1.5 text-xs font-bold text-slate-200 hover:bg-slate-700">Filter</button>
            </form>
        </div>

        <!-- Audit Trail Directory Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Field Changes</th>
                        <th class="py-3 px-4 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                No audit log records found matching your filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-400 whitespace-nowrap">
                                    <?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-white whitespace-nowrap">
                                    <i class="fa-solid fa-user-shield text-amber-400 mr-1 text-[11px]"></i>
                                    <?= htmlspecialchars($l['admin_name'] ?: ($l['admin_username'] ?: 'Admin ID ' . $l['user_id'])) ?>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    <?= htmlspecialchars($l['action']) ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        <?= htmlspecialchars($l['category']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($l['diffs'])): ?>
                                        <span class="rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[10px] font-bold text-amber-400 border border-amber-500/30">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> <?= count($l['diffs']) ?> fields changed
                                        </span>
                                    <?php elseif (!empty($l['old_values']) || !empty($l['new_values'])): ?>
                                        <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-400">
                                            Payload Recorded
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-600 text-[11px]">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" @click="fetch('<?= $baseUrl ?>/audit-logs/' + <?= $l['id'] ?>).then(r=>r.json()).then(d=>{ selectedLog = d.log; diffModal = true; })"
                                            class="rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1 text-[11px] font-semibold text-amber-400 hover:bg-slate-700 transition">
                                        <i class="fa-solid fa-eye mr-1"></i> View Diff
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between border-t border-slate-800 pt-4 text-xs text-slate-400">
                <span>Page <?= $page ?> of <?= $totalPages ?> (Total <?= number_format($totalLogs) ?> entries)</span>
                <div class="flex items-center gap-1.5">
                    <?php if ($page > 1): ?>
                        <a href="<?= $baseUrl ?>/audit-logs?page=<?= $page - 1 ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1 text-slate-200 hover:bg-slate-700">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= $baseUrl ?>/audit-logs?page=<?= $page + 1 ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>"
                           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1 text-slate-200 hover:bg-slate-700">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Field-Level Change Diff Inspector Modal -->
    <div x-show="diffModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm">
        <div class="w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="diffModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-amber-400"></i> Field-Level Change Diff Inspector
                </h3>
                <button @click="diffModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <template x-if="selectedLog">
                <div class="space-y-4 text-xs">
                    <!-- Log Meta Header -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800 font-mono text-[11px]">
                        <div><span class="text-slate-500 block">Action</span><strong class="text-white" x-text="selectedLog.action"></strong></div>
                        <div><span class="text-slate-500 block">User</span><strong class="text-amber-400" x-text="selectedLog.admin_name || selectedLog.admin_username"></strong></div>
                        <div><span class="text-slate-500 block">IP Address</span><strong class="text-slate-200" x-text="selectedLog.ip_address"></strong></div>
                        <div><span class="text-slate-500 block">Timestamp</span><strong class="text-slate-300" x-text="selectedLog.created_at"></strong></div>
                    </div>

                    <!-- Remarks / Details -->
                    <template x-if="selectedLog.details">
                        <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800 text-slate-300">
                            <span class="text-slate-500 font-bold block mb-0.5">Operation Details:</span>
                            <span x-text="selectedLog.details"></span>
                        </div>
                    </template>

                    <!-- Changed Fields Diff Table -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-400 mb-2">Changed Fields (Old ➔ New)</h4>

                        <template x-if="selectedLog.diffs && selectedLog.diffs.length > 0">
                            <div class="rounded-xl border border-slate-800 overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-950 text-slate-400 font-bold">
                                        <tr>
                                            <th class="py-2 px-3">Field Name</th>
                                            <th class="py-2 px-3 text-rose-400">Old Value</th>
                                            <th class="py-2 px-3 text-emerald-400">New Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800 bg-slate-950/60 font-mono">
                                        <template x-for="d in selectedLog.diffs" :key="d.field">
                                            <tr>
                                                <td class="py-2 px-3 font-semibold text-white" x-text="d.label"></td>
                                                <td class="py-2 px-3 text-rose-400 bg-rose-500/10" x-text="d.old"></td>
                                                <td class="py-2 px-3 text-emerald-400 bg-emerald-500/10" x-text="d.new"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!selectedLog.diffs || selectedLog.diffs.length === 0">
                            <div class="p-4 rounded-xl bg-slate-950 text-slate-500 text-center font-mono">
                                No field-level diffs recorded (Action logged as single operation payload).
                            </div>
                        </template>
                    </div>

                    <!-- Raw JSON Payload Accordion -->
                    <div x-data="{ showRaw: false }" class="space-y-2">
                        <button type="button" @click="showRaw = !showRaw" class="text-[11px] text-amber-400 font-semibold hover:underline">
                            <span x-text="showRaw ? 'Hide Raw JSON Payload' : 'Show Raw JSON Payload'"></span>
                        </button>
                        <div x-show="showRaw" x-cloak class="p-3 bg-slate-950 rounded-xl font-mono text-[10px] text-slate-400 max-h-40 overflow-y-auto">
                            <pre x-text="JSON.stringify(selectedLog, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex justify-end pt-2 border-t border-slate-800">
                <button type="button" @click="diffModal = false" class="rounded-xl bg-slate-800 border border-slate-700 px-4 py-2 text-xs font-bold text-slate-200 hover:bg-slate-700">
                    Close Inspector
                </button>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
