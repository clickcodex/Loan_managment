<?php
$meta    = $report['meta'];
$columns = $report['columns'];
$rows    = $report['rows'];
$totals  = $report['totals'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .no-print { display: none !important; }
            @page { margin: 15mm; size: A4 landscape; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6">

    <!-- Print / Close Bar -->
    <div class="no-print max-w-6xl mx-auto mb-4 flex items-center justify-between bg-slate-900 text-white p-4 rounded-xl shadow">
        <div class="text-xs">
            <span class="font-bold text-amber-400"><?= htmlspecialchars($meta['name']) ?></span> &bull; Printable Formal Report
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow">
                <i class="fa-solid fa-print"></i> Print Report Now
            </button>
            <button onclick="window.close()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold px-3 py-2 rounded-lg text-xs">
                Close Window
            </button>
        </div>
    </div>

    <!-- Printable Report Container -->
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-xl shadow-lg border border-slate-200 space-y-6">

        <!-- Company Header -->
        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">GOLDEN TRUST FINANCE CO.</h1>
                <p class="text-xs text-slate-600 font-medium">123 Main Financial Street, Capital City &bull; Phone: +91 98765 43210</p>
                <p class="text-[11px] text-slate-500 font-mono">LMS v2.0 Unified Loan & Ledger Management System</p>
            </div>
            <div class="text-right font-mono">
                <span class="block text-base font-extrabold text-slate-900 uppercase"><?= htmlspecialchars($meta['name']) ?></span>
                <span class="block text-xs text-slate-600">Generated: <?= date('d M Y - h:i A') ?></span>
            </div>
        </div>

        <!-- Summary Totals Cards -->
        <?php if (!empty($totals)): ?>
            <div class="grid grid-cols-4 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-300 text-xs font-mono">
                <?php foreach ($totals as $lbl => $v): ?>
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase block font-bold"><?= htmlspecialchars($lbl) ?></span>
                        <strong class="text-slate-900 text-sm font-extrabold"><?= htmlspecialchars($v) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse border border-slate-300">
                <thead>
                    <tr class="bg-slate-900 text-white font-bold text-[10px] uppercase">
                        <th class="border border-slate-400 p-2.5 w-10 text-center">#</th>
                        <?php foreach ($columns as $col): ?>
                            <th class="border border-slate-400 p-2.5"><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?= count($columns) + 1 ?>" class="p-6 text-center text-slate-500">No records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sn = 1; foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="border border-slate-300 p-2 text-center font-mono text-slate-500"><?= $sn++ ?></td>
                                <?php foreach ($r as $val): ?>
                                    <td class="border border-slate-300 p-2 font-medium text-slate-900">
                                        <?= htmlspecialchars($val) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="pt-10 grid grid-cols-2 gap-8 text-xs font-semibold text-slate-700">
            <div>
                <div class="border-t border-slate-400 pt-2 w-48 text-center">Prepared By (Auditor)</div>
            </div>
            <div class="text-right">
                <div class="border-t border-slate-400 pt-2 w-48 text-center ml-auto">Authorized Manager Signature</div>
            </div>
        </div>

    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => { window.print(); }, 500);
        });
    </script>
</body>
</html>
