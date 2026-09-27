<?php
$pageTitle = $report['meta']['name'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$key       = $report['key'];
$meta      = $report['meta'];
$columns   = $report['columns'];
$rows      = $report['rows'];
$totals    = $report['totals'];
$startDate = $report['startDate'];
$endDate   = $report['endDate'];
$search    = $report['search'];
?>

<div class="space-y-6">

    <!-- Page Header & Action Controls -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2.5">
                <i class="fa-solid <?= $meta['icon'] ?> text-amber-400"></i> <?= htmlspecialchars($meta['name']) ?>
            </h1>
            <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($meta['desc']) ?></p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= $baseUrl ?>/reports/<?= $key ?>/export?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&search=<?= urlencode($search) ?>"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-500/20 border border-emerald-500/30 px-3.5 py-2 text-xs font-bold text-emerald-400 hover:bg-emerald-500/30 transition">
                <i class="fa-solid fa-file-csv text-base"></i> Export to CSV
            </a>

            <a href="<?= $baseUrl ?>/reports/<?= $key ?>/print?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&search=<?= urlencode($search) ?>" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-800 border border-slate-700 px-3.5 py-2 text-xs font-bold text-amber-400 hover:bg-slate-700 transition">
                <i class="fa-solid fa-print text-sm"></i> Print Report
            </a>

            <a href="<?= $baseUrl ?>/reports" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Directory
            </a>
        </div>
    </div>

    <!-- Date Range & Search Filter Bar -->
    <form action="<?= $baseUrl ?>/reports/<?= $key ?>" method="GET" class="rounded-2xl border border-slate-800 bg-slate-900 p-4 shadow-xl flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <div>
                <label class="block text-[10px] text-slate-400 font-semibold mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                       class="rounded-xl border border-slate-700 bg-slate-950 py-1.5 px-3 text-xs text-white">
            </div>

            <div>
                <label class="block text-[10px] text-slate-400 font-semibold mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                       class="rounded-xl border border-slate-700 bg-slate-950 py-1.5 px-3 text-xs text-white">
            </div>

            <div class="w-64">
                <label class="block text-[10px] text-slate-400 font-semibold mb-1">Search Keyword</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Filter rows..."
                       class="w-full rounded-xl border border-slate-700 bg-slate-950 py-1.5 px-3 text-xs text-white focus:border-amber-500">
            </div>
        </div>

        <div class="flex items-center gap-2 self-end">
            <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow transition">
                Apply Filters
            </button>
            <a href="<?= $baseUrl ?>/reports/<?= $key ?>" class="rounded-xl bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                Reset
            </a>
        </div>
    </form>

    <!-- Report Totals KPI Cards -->
    <?php if (!empty($totals)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-<?= min(count($totals), 4) ?> gap-4">
            <?php foreach ($totals as $label => $val): ?>
                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1 shadow">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider"><?= htmlspecialchars($label) ?></span>
                    <div class="text-xl font-black text-amber-400 font-mono"><?= htmlspecialchars($val) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Report Table Card -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px]">
                    <tr>
                        <th class="py-3 px-4 w-12">#</th>
                        <?php foreach ($columns as $col): ?>
                            <th class="py-3 px-4"><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?= count($columns) + 1 ?>" class="py-12 text-center text-slate-500 space-y-2">
                                <i class="fa-solid fa-folder-open text-3xl text-slate-700"></i>
                                <p class="text-xs font-bold text-slate-300">No records found matching your report criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $idx = 1; foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 font-mono text-slate-500"><?= $idx++ ?></td>
                                <?php foreach ($r as $val): ?>
                                    <td class="py-3 px-4 font-semibold text-slate-200">
                                        <?= htmlspecialchars($val) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
