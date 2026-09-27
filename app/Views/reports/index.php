<?php
$pageTitle = '17 System Reports Directory Hub';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Group reports by category
$categories = [
    'Financial & Loans' => [],
    'Collateral Vault'  => [],
    'Customers & Dues'  => [],
    'System & Audit'    => []
];

foreach ($reportsMeta as $k => $meta) {
    $categories[$meta['category']][$k] = $meta;
}
?>

<div class="space-y-8">

    <!-- Page Header Banner -->
    <div class="rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 p-6 shadow-xl space-y-2">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold text-white flex items-center gap-2.5">
                    <i class="fa-solid fa-file-invoice text-cyan-400"></i> 17 System Reports Directory Hub
                </h1>
                <p class="text-xs text-slate-400 mt-1">Complete reporting suite with date range filters, CSV export, and formal print layouts</p>
            </div>
            <span class="rounded-full bg-cyan-500/20 px-3.5 py-1.5 text-xs font-mono font-bold text-cyan-300 border border-cyan-500/30">
                17 Active Reports
            </span>
        </div>
    </div>

    <!-- Categorized Reports Grid -->
    <?php foreach ($categories as $catName => $reports): ?>
        <div class="space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-400 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($catName) ?> (<?= count($reports) ?> Reports)
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($reports as $key => $meta): ?>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-4 shadow-lg flex flex-col justify-between hover:border-slate-700 transition group">
                        
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-800 text-amber-400 border border-slate-700/60 group-hover:bg-amber-500 group-hover:text-slate-950 transition">
                                    <i class="fa-solid <?= $meta['icon'] ?> text-lg"></i>
                                </div>
                                <span class="text-[10px] font-mono text-slate-500 uppercase"><?= htmlspecialchars($key) ?></span>
                            </div>

                            <h3 class="text-sm font-bold text-white group-hover:text-amber-400 transition">
                                <?= htmlspecialchars($meta['name']) ?>
                            </h3>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                <?= htmlspecialchars($meta['desc']) ?>
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between border-t border-slate-800/80 pt-3 text-xs">
                            <a href="<?= $baseUrl ?>/reports/<?= $key ?>"
                               class="rounded-xl bg-amber-500/20 border border-amber-500/30 px-3 py-1.5 text-xs font-bold text-amber-400 hover:bg-amber-500/30 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-eye text-xs"></i> View Report
                            </a>

                            <div class="flex items-center gap-1.5">
                                <a href="<?= $baseUrl ?>/reports/<?= $key ?>/export"
                                   class="rounded-lg bg-slate-800 p-1.5 text-slate-400 hover:text-emerald-400 transition" title="Export CSV">
                                    <i class="fa-solid fa-file-csv text-base"></i>
                                </a>

                                <a href="<?= $baseUrl ?>/reports/<?= $key ?>/print" target="_blank"
                                   class="rounded-lg bg-slate-800 p-1.5 text-slate-400 hover:text-amber-400 transition" title="Print Report">
                                    <i class="fa-solid fa-print text-base"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
