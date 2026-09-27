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
            @page { margin: 15mm; size: A4 portrait; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6">

    <!-- Print / Close Controls -->
    <div class="no-print max-w-5xl mx-auto mb-4 flex items-center justify-between bg-slate-900 text-white p-4 rounded-xl shadow">
        <div class="text-xs">
            <span class="font-bold text-amber-400">Printable Collection Due Sheet</span> &bull; Status Filter: <span class="uppercase font-mono"><?= htmlspecialchars($filter) ?></span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow">
                <i class="fa-solid fa-print"></i> Print Due Sheet Now
            </button>
            <button onclick="window.close()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold px-3 py-2 rounded-lg text-xs">
                Close Window
            </button>
        </div>
    </div>

    <!-- Main Printable Sheet Container -->
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg border border-slate-200 space-y-6">

        <!-- Company Header -->
        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">GOLDEN TRUST FINANCE CO.</h1>
                <p class="text-xs text-slate-600 font-medium">123 Main Financial Street, Capital City &bull; Phone: +91 98765 43210</p>
                <p class="text-[11px] text-slate-500 font-mono">LMS v2.0 Unified Loan & Ledger Management System</p>
            </div>
            <div class="text-right font-mono">
                <span class="block text-base font-extrabold text-slate-900 uppercase">FIELD COLLECTION DUE SHEET</span>
                <span class="block text-xs text-slate-600">Generated: <?= date('d M Y - h:i A') ?></span>
            </div>
        </div>

        <!-- Directory Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse border border-slate-300">
                <thead>
                    <tr class="bg-slate-900 text-white font-bold text-[10px] uppercase">
                        <th class="border border-slate-400 p-2.5">S.No</th>
                        <th class="border border-slate-400 p-2.5">Customer Name & Contact</th>
                        <th class="border border-slate-400 p-2.5">Loan#</th>
                        <th class="border border-slate-400 p-2.5">Security</th>
                        <th class="border border-slate-400 p-2.5 text-right">Principal Bal (₹)</th>
                        <th class="border border-slate-400 p-2.5 text-center">Days</th>
                        <th class="border border-slate-400 p-2.5 text-right">Accrued Interest (₹)</th>
                        <th class="border border-slate-400 p-2.5 text-right">Total Payable (₹)</th>
                        <th class="border border-slate-400 p-2.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    <?php if (empty($dueSheet)): ?>
                        <tr>
                            <td colspan="9" class="p-6 text-center text-slate-500">No dues recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                            $sn = 1; 
                            $totalP = 0; 
                            $totalI = 0; 
                            $totalTot = 0;
                        ?>
                        <?php foreach ($dueSheet as $item): ?>
                            <?php 
                                $totalP += $item['principal_balance'];
                                $totalI += $item['accrued_interest'];
                                $totalTot += $item['total_payable'];
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="border border-slate-300 p-2 text-center font-mono"><?= $sn++ ?></td>
                                <td class="border border-slate-300 p-2">
                                    <strong class="text-slate-900 block"><?= htmlspecialchars($item['customer_name']) ?></strong>
                                    <span class="text-[10px] text-slate-600 font-mono">Mobile: <?= htmlspecialchars($item['customer_mobile']) ?></span>
                                    <?php if (!empty($item['customer_address'])): ?>
                                        <span class="block text-[10px] text-slate-500 truncate max-w-xs"><?= htmlspecialchars($item['customer_address']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-slate-300 p-2 font-mono font-bold text-slate-900"><?= htmlspecialchars($item['loan_number']) ?></td>
                                <td class="border border-slate-300 p-2 text-[11px]"><?= htmlspecialchars($item['security_type']) ?></td>
                                <td class="border border-slate-300 p-2 text-right font-mono font-bold">₹<?= number_format($item['principal_balance'], 2) ?></td>
                                <td class="border border-slate-300 p-2 text-center font-mono"><?= $item['days_elapsed'] ?></td>
                                <td class="border border-slate-300 p-2 text-right font-mono font-bold text-amber-700">₹<?= number_format($item['accrued_interest'], 2) ?></td>
                                <td class="border border-slate-300 p-2 text-right font-mono font-black text-slate-900">₹<?= number_format($item['total_payable'], 2) ?></td>
                                <td class="border border-slate-300 p-2 text-center font-semibold text-[10px]"><?= htmlspecialchars($item['due_status_label']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-200 font-bold text-xs font-mono">
                        <td colspan="4" class="border border-slate-400 p-2.5 text-right uppercase">Grand Total Dues:</td>
                        <td class="border border-slate-400 p-2.5 text-right text-slate-900">₹<?= number_format($totalP ?? 0, 2) ?></td>
                        <td class="border border-slate-400 p-2.5"></td>
                        <td class="border border-slate-400 p-2.5 text-right text-amber-800">₹<?= number_format($totalI ?? 0, 2) ?></td>
                        <td class="border border-slate-400 p-2.5 text-right text-slate-900 font-black">₹<?= number_format($totalTot ?? 0, 2) ?></td>
                        <td class="border border-slate-400 p-2.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Field Signatures -->
        <div class="pt-12 grid grid-cols-2 gap-8 text-xs font-semibold text-slate-700">
            <div>
                <div class="border-t border-slate-400 pt-2 w-48 text-center">Collection Agent Signature</div>
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
