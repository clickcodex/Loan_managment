<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Credit & Debit Cashbook - <?= htmlspecialchars($date) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #1e293b; background: #fff; line-height: 1.4; padding: 25px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
        .company-title { font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .company-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-badge { background: #0f172a; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 4px; display: inline-block; }
        .doc-date { font-size: 11px; color: #64748b; margin-top: 4px; font-family: monospace; }
        
        .kpi-grid { display: flex; gap: 12px; margin-bottom: 18px; }
        .kpi-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; background: #f8fafc; }
        .kpi-card.credit { border-left: 4px solid #10b981; }
        .kpi-card.debit { border-left: 4px solid #ef4444; }
        .kpi-card.net { border-left: 4px solid #3b82f6; }
        .kpi-card.count { border-left: 4px solid #8b5cf6; }
        .kpi-label { font-size: 10px; text-transform: uppercase; font-weight: 700; color: #64748b; }
        .kpi-value { font-size: 16px; font-weight: 800; margin-top: 3px; font-family: monospace; }
        .kpi-value.credit { color: #059669; }
        .kpi-value.debit { color: #dc2626; }
        
        .section-title { font-size: 13px; font-weight: 700; color: #0f172a; text-transform: uppercase; margin: 15px 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; }
        .section-title span.badge { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px; }
        .badge-credit { background: #d1fae5; color: #065f46; }
        .badge-debit { background: #fee2e2; color: #991b1b; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 11px; }
        th { background: #f1f5f9; color: #334155; font-weight: 700; text-align: left; padding: 6px 8px; border: 1px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Consolas', 'Courier New', monospace; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 800; }
        
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; padding-top: 20px; page-break-inside: avoid; }
        .sig-block { width: 200px; text-align: center; border-top: 1px dashed #64748b; padding-top: 5px; font-size: 11px; font-weight: 600; color: #475569; }
        
        .actions-bar { margin-bottom: 20px; text-align: right; }
        .btn-print { background: #0f172a; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px; }
        .btn-print:hover { background: #334155; }
        .btn-back { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 12px; margin-right: 8px; }

        @media print {
            .actions-bar { display: none; }
            body { padding: 0; }
            @page { size: A4 portrait; margin: 12mm; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="javascript:history.back()" class="btn-back">&larr; Back to Cashbook</a>
        <button onclick="window.print()" class="btn-print">&#128438; Print / Save PDF</button>
    </div>

    <!-- Header -->
    <div class="header">
        <div>
            <div class="company-title">GOLDEN TRUST FINANCE & JEWELLERS</div>
            <div class="company-sub">Gold & Mixed Collateral Lending &bull; Official Daily Cashbook & Daybook Register</div>
        </div>
        <div class="doc-title">
            <span class="doc-badge">DAILY AUDIT STATEMENT</span>
            <div class="doc-date">Date: <strong><?= date('d M Y (l)', strtotime($date)) ?></strong></div>
            <div class="doc-date">Generated: <?= date('d-M-Y H:i A') ?></div>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="kpi-grid">
        <div class="kpi-card credit">
            <div class="kpi-label">Total Credit (Collections)</div>
            <div class="kpi-value credit">₹<?= number_format($summary['total_credit'], 2) ?></div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Cash: ₹<?= number_format($summary['cash_credit'], 2) ?> | UPI: ₹<?= number_format($summary['upi_credit'], 2) ?></div>
        </div>
        <div class="kpi-card debit">
            <div class="kpi-label">Total Debit (Disbursements)</div>
            <div class="kpi-value debit">₹<?= number_format($summary['total_debit'], 2) ?></div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Cash: ₹<?= number_format($summary['cash_debit'], 2) ?></div>
        </div>
        <div class="kpi-card net">
            <div class="kpi-label">Net Daily Cash Position</div>
            <div class="kpi-value <?= $summary['net_cash_flow'] >= 0 ? 'credit' : 'debit' ?>">
                <?= $summary['net_cash_flow'] >= 0 ? '+' : '' ?>₹<?= number_format($summary['net_cash_flow'], 2) ?>
            </div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;"><?= $summary['net_cash_flow'] >= 0 ? 'Cash Surplus' : 'Deficit' ?></div>
        </div>
        <div class="kpi-card count">
            <div class="kpi-label">Total Day Entries</div>
            <div class="kpi-value" style="color: #6d28d9;"><?= $summary['total_count'] ?></div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;"><?= $summary['credit_count'] ?> Cr &bull; <?= $summary['debit_count'] ?> Dr</div>
        </div>
    </div>

    <!-- 1. CREDIT ENTRIES TABLE (Inflow) -->
    <div class="section-title">
        <span>1. Credit Transactions (Inflow / Collections)</span>
        <span class="badge badge-credit"><?= count($credits) ?> Records &bull; Total: ₹<?= number_format($summary['total_credit'], 2) ?></span>
    </div>

    <?php if (empty($credits)): ?>
        <p style="padding: 12px; text-align: center; color: #94a3b8; font-style: italic; border: 1px dashed #cbd5e1; margin-bottom: 15px;">
            No credit collection entries recorded for this date.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 45px;">Sr.</th>
                    <th style="width: 80px;">Receipt #</th>
                    <th style="width: 85px;">Loan #</th>
                    <th>Customer Name & Mobile</th>
                    <th>Collateral Item</th>
                    <th>Description & Remarks</th>
                    <th style="width: 70px;">Mode</th>
                    <th style="width: 90px;" class="text-right">Amount (₹)</th>
                    <th style="width: 90px;" class="text-right">Closing Bal (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($credits as $idx => $c): ?>
                    <tr>
                        <td class="text-center font-mono"><?= $idx + 1 ?></td>
                        <td class="font-mono font-bold"><?= htmlspecialchars($c['receipt_number']) ?></td>
                        <td class="font-mono font-bold"><?= htmlspecialchars($c['loan_number']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($c['customer_name']) ?></strong>
                            <div style="font-size: 10px; color: #64748b; font-family: monospace;"><?= htmlspecialchars($c['customer_mobile']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($c['collateral_names']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($c['title']) ?></div>
                            <?php if (!empty($c['remarks']) && $c['remarks'] !== '—'): ?>
                                <div style="font-size: 10px; color: #64748b; font-style: italic;">"<?= htmlspecialchars($c['remarks']) ?>"</div>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold"><?= htmlspecialchars($c['payment_mode']) ?></td>
                        <td class="text-right font-mono font-bold" style="color: #059669;">₹<?= number_format($c['credit'], 2) ?></td>
                        <td class="text-right font-mono">₹<?= number_format($c['balance'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: bold;">
                    <td colspan="7" class="text-right uppercase">Total Credit Inflow:</td>
                    <td class="text-right font-mono font-black" style="color: #059669;">₹<?= number_format($summary['total_credit'], 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- 2. DEBIT ENTRIES TABLE (Outflow) -->
    <div class="section-title" style="margin-top: 25px;">
        <span>2. Debit Transactions (Outflow / Disbursements)</span>
        <span class="badge badge-debit"><?= count($debits) ?> Records &bull; Total: ₹<?= number_format($summary['total_debit'], 2) ?></span>
    </div>

    <?php if (empty($debits)): ?>
        <p style="padding: 12px; text-align: center; color: #94a3b8; font-style: italic; border: 1px dashed #cbd5e1; margin-bottom: 15px;">
            No debit disbursement entries recorded for this date.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 45px;">Sr.</th>
                    <th style="width: 85px;">Loan #</th>
                    <th>Customer Name & Mobile</th>
                    <th>Collateral Item</th>
                    <th>Type / Purpose</th>
                    <th>Remarks</th>
                    <th style="width: 70px;">Mode</th>
                    <th style="width: 90px;" class="text-right">Debit (₹)</th>
                    <th style="width: 90px;" class="text-right">Closing Bal (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($debits as $idx => $d): ?>
                    <tr>
                        <td class="text-center font-mono"><?= $idx + 1 ?></td>
                        <td class="font-mono font-bold"><?= htmlspecialchars($d['loan_number']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($d['customer_name']) ?></strong>
                            <div style="font-size: 10px; color: #64748b; font-family: monospace;"><?= htmlspecialchars($d['customer_mobile']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($d['collateral_names']) ?></td>
                        <td><strong><?= htmlspecialchars($d['title']) ?></strong></td>
                        <td style="font-size: 10px; color: #64748b;"><?= htmlspecialchars($d['remarks']) ?></td>
                        <td class="font-bold"><?= htmlspecialchars($d['payment_mode']) ?></td>
                        <td class="text-right font-mono font-bold" style="color: #dc2626;">₹<?= number_format($d['debit'], 2) ?></td>
                        <td class="text-right font-mono">₹<?= number_format($d['balance'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: bold;">
                    <td colspan="7" class="text-right uppercase">Total Debit Outflow:</td>
                    <td class="text-right font-mono font-black" style="color: #dc2626;">₹<?= number_format($summary['total_debit'], 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Summary Balance Bar -->
    <table style="margin-top: 15px; border: 2px solid #0f172a;">
        <tr style="background: #0f172a; color: #fff; font-weight: bold; font-size: 12px;">
            <td style="padding: 8px 12px;">DAILY CASHBOOK NET RECONCILIATION SUMMARY</td>
            <td class="text-right" style="padding: 8px 12px; font-family: monospace;">
                Net Flow: <?= $summary['net_cash_flow'] >= 0 ? '+' : '' ?>₹<?= number_format($summary['net_cash_flow'], 2) ?> 
                (Credits: ₹<?= number_format($summary['total_credit'], 2) ?> &mdash; Debits: ₹<?= number_format($summary['total_debit'], 2) ?>)
            </td>
        </tr>
    </table>

    <!-- Signature Sign-off -->
    <div class="signatures">
        <div class="sig-block">
            Cashier / Operator Signature
        </div>
        <div class="sig-block">
            Accounts Officer / Auditor
        </div>
        <div class="sig-block">
            Branch Manager / Proprietor
        </div>
    </div>

</body>
</html>
