<?php
$billNumber = $bill['bill_number'];
$companyName = $bill['company_name'];
$customerName = $bill['customer_name'];
$billDate = date('d-m-Y', strtotime($bill['bill_date']));
$items = $bill['items_decoded'] ?? [];
$subtotal = floatval($bill['subtotal']);
$discount = floatval($bill['discount_amount']);
$totalAmount = floatval($bill['total_amount']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill / Invoice - <?= htmlspecialchars($billNumber) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 12mm 15mm;
            }
            body { 
                background: white !important; 
                color: #0f172a !important; 
                padding: 0 !important;
            }
            .no-print { 
                display: none !important; 
            }
            .print-invoice-card { 
                border: 1.5px solid #0f172a !important; 
                background: white !important; 
                color: #0f172a !important; 
                box-shadow: none !important;
                border-radius: 12px !important;
                padding: 24px !important;
                max-width: 100% !important;
            }
            .print-border-b {
                border-bottom: 2px solid #0f172a !important;
            }
            .print-border {
                border-color: #cbd5e1 !important;
            }
            .print-bg-light { 
                background: #f8fafc !important; 
            }
            .print-text-dark { 
                color: #0f172a !important; 
            }
            .print-text-muted {
                color: #475569 !important;
            }
        }
    </style>
</head>
<body class="h-full font-sans antialiased flex flex-col items-center justify-start p-4 sm:p-6 bg-slate-950 text-slate-100">

    <!-- Top Action Bar (Hidden in Print) -->
    <div class="no-print w-full max-w-3xl flex items-center justify-between mb-4">
        <a href="<?= $baseUrl ?>/bills" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Invoices
        </a>
        <div class="flex items-center gap-2">
            <a href="<?= $baseUrl ?>/bills/create" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-amber-400 hover:bg-slate-700 transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i> New Bill
            </a>
            <button onclick="window.print()" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 shadow-lg hover:bg-amber-400 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-print"></i> Print Bill
            </button>
        </div>
    </div>

    <!-- Printable Invoice Container -->
    <div class="print-invoice-card w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-2xl space-y-6">

        <!-- Header: Company & Bill Meta -->
        <div class="flex items-start justify-between border-b border-slate-800 pb-5 print-border-b">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight print-text-dark"><?= htmlspecialchars($companyName) ?></h1>
                <p class="text-xs text-amber-400 font-bold uppercase tracking-wider mt-0.5 print-text-dark">Retail & Merchandise Invoice / Bill</p>
                <p class="text-[11px] text-slate-400 mt-1 print-text-muted">
                    Official Customer Receipt & Payment Acknowledgement
                </p>
            </div>
            <div class="text-right">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider print-text-muted">Invoice #</span>
                <span class="font-mono font-extrabold text-amber-400 text-sm print-text-dark"><?= htmlspecialchars($billNumber) ?></span>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2 print-text-muted">Date</span>
                <span class="font-mono font-bold text-white text-sm print-text-dark"><?= htmlspecialchars($billDate) ?></span>
            </div>
        </div>

        <!-- Billed To & Payment Mode Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-4 space-y-1 print-bg-light print-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Billed To (Customer)</span>
                <p class="text-sm font-bold text-white print-text-dark"><?= htmlspecialchars($customerName) ?></p>
                <?php if (!empty($bill['customer_mobile'])): ?>
                    <p class="font-mono text-slate-300 print-text-dark">Phone: <?= htmlspecialchars($bill['customer_mobile']) ?></p>
                <?php endif; ?>
                <?php if (!empty($bill['gst_number'])): ?>
                    <p class="font-mono text-amber-400 print-text-dark font-semibold">GSTIN: <?= htmlspecialchars($bill['gst_number']) ?></p>
                <?php endif; ?>
                <?php if (!empty($bill['customer_address'])): ?>
                    <p class="text-slate-400 print-text-muted"><?= htmlspecialchars($bill['customer_address']) ?></p>
                <?php endif; ?>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-4 space-y-1 print-bg-light print-border">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Payment Information</span>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-400 print-text-muted">Payment Mode:</span>
                    <span class="font-bold text-white print-text-dark"><?= htmlspecialchars($bill['payment_mode']) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print-text-muted">Invoice Status:</span>
                    <span class="font-bold text-emerald-400 print-text-dark">Paid & Stored</span>
                </div>
            </div>
        </div>

        <!-- Multi-Product Line Items Table -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-300 print-text-dark">
                    Product Particulars
                </span>
                <span class="text-[10px] font-mono text-slate-400 print-text-muted">
                    Total Items: <?= count($items) ?>
                </span>
            </div>

            <div class="rounded-2xl border border-slate-800 overflow-hidden print-border">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] print-bg-light print-text-muted border-b border-slate-800 print-border">
                        <tr>
                            <th class="py-2.5 px-3 w-10 text-center">#</th>
                            <th class="py-2.5 px-3">Product Name</th>
                            <th class="py-2.5 px-3 text-right w-24">Quantity</th>
                            <th class="py-2.5 px-3 text-right w-32">Unit Price (₹)</th>
                            <th class="py-2.5 px-3 text-right w-36">Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300 print-border print-text-dark">
                        <?php foreach ($items as $idx => $it): ?>
                            <tr class="hover:bg-slate-800/30 print-bg-light">
                                <td class="py-2.5 px-3 text-center font-mono text-slate-500 print-text-muted"><?= $idx + 1 ?></td>
                                <td class="py-2.5 px-3 font-bold text-white print-text-dark">
                                    <?= htmlspecialchars($it['product_name']) ?>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-300 print-text-dark">
                                    <?= floatval($it['quantity']) ?>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-300 print-text-dark">
                                    ₹<?= number_format(floatval($it['price']), 2) ?>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-amber-400 print-text-dark">
                                    ₹<?= number_format(floatval($it['total']), 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals & Calculation Summary -->
        <div class="flex flex-col sm:flex-row justify-between gap-6 pt-2">
            <!-- Notes -->
            <div class="sm:w-1/2 text-xs space-y-1">
                <?php if (!empty($bill['notes'])): ?>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 print-text-muted">Notes / Terms:</span>
                    <p class="text-slate-300 print-text-dark bg-slate-950/40 print-bg-light p-3 rounded-xl border border-slate-800 print-border">
                        <?= nl2br(htmlspecialchars($bill['notes'])) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Financial Totals -->
            <div class="sm:w-5/12 space-y-2 text-xs">
                <div class="flex justify-between text-slate-400 print-text-muted">
                    <span>Subtotal:</span>
                    <span class="font-mono font-bold text-white print-text-dark">₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <?php if ($discount > 0): ?>
                    <div class="flex justify-between text-rose-400">
                        <span>Discount:</span>
                        <span class="font-mono font-bold">-₹<?= number_format($discount, 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="border-t border-slate-800 print-border pt-2 flex justify-between items-baseline">
                    <span class="text-sm font-black text-white uppercase print-text-dark">Grand Total:</span>
                    <span class="font-mono font-black text-xl text-emerald-400 print-text-dark">
                        ₹<?= number_format($totalAmount, 2) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Signatures & Authorization -->
        <div class="pt-10 grid grid-cols-2 gap-8 text-center text-xs border-t border-slate-800 print-border">
            <div>
                <div class="border-b border-slate-700 print-border w-44 mx-auto mb-1"></div>
                <p class="font-bold text-white print-text-dark">Customer's Signature</p>
                <p class="text-[10px] text-slate-500 print-text-muted"><?= htmlspecialchars($customerName) ?></p>
            </div>
            <div>
                <div class="border-b border-slate-700 print-border w-44 mx-auto mb-1"></div>
                <p class="font-bold text-white print-text-dark">Authorized Signatory</p>
                <p class="text-[10px] text-slate-500 print-text-muted"><?= htmlspecialchars($companyName) ?></p>
            </div>
        </div>

    </div>

</body>
</html>
