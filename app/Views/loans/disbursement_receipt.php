<?php
$loanId = $loan['id'];
$loanNumber = $loan['loan_number'];

// Extract clean numeric account / loan representation matching reference slip (e.g. 11178/300)
$custAcc = trim($loan['customer_account_number'] ?? '');
$cleanLoanNo = preg_replace('/^.*?(\d+)$/', '$1', $loanNumber);
if (!empty($custAcc)) {
    $accDisplay = $custAcc . '/' . $cleanLoanNo;
} else {
    $accDisplay = $loanNumber;
}

// Date & Time
$loanDate = !empty($loan['loan_date']) ? date('d-m-Y', strtotime($loan['loan_date'])) : date('d-m-Y');
$loanTime = !empty($loan['created_at']) ? date('H:i:s', strtotime($loan['created_at'])) : date('H:i:s');

// Customer Details
$custName   = trim($loan['customer_name'] ?? '');
$fatherName = trim($loan['father_name'] ?? '');
$aadhaar    = trim($loan['aadhaar'] ?? '');
$mobile     = trim($loan['customer_mobile'] ?? '');
$principal  = number_format(floatval($loan['principal_amount']), 2, '.', '');

// Guarantor Details
$guaName   = !empty($guarantor['guarantor_name']) ? trim($guarantor['guarantor_name']) : (!empty($loan['guarantor_name']) ? trim($loan['guarantor_name']) : '');
$guaMobile = !empty($guarantor['guarantor_mobile']) ? trim($guarantor['guarantor_mobile']) : (!empty($loan['guarantor_mobile']) ? trim($loan['guarantor_mobile']) : '');

// Collateral RK Details
$rkDetailsList = [];
foreach ($collateralItems as $ci) {
    $name = trim($ci['item_name']);
    $wt = floatval($ci['gross_weight'] ?? 0);
    $wtDisplay = ($wt == intval($wt)) ? intval($wt) : number_format($wt, 2);
    $rkDetailsList[] = $name . ' (' . $wtDisplay . ' G)';
}
$rkDetailsDisplay = !empty($rkDetailsList) ? implode(', ', $rkDetailsList) : '—';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Disbursement Receipt - <?= htmlspecialchars($loanNumber) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Exact Slip Styling Matching Provided Voucher Image */
        .rk-slip-container {
            border: 3px double #334155;
            background: #0f172a;
            color: #f8fafc;
            width: 100%;
            max-width: 480px;
            padding: 24px;
            font-family: 'Courier New', Courier, monospace, system-ui, sans-serif;
            border-radius: 4px;
        }

        .rk-header-box {
            border-top: 2px solid #334155;
            border-bottom: 2px solid #334155;
            text-align: center;
            padding: 6px 0;
            margin-bottom: 20px;
            font-weight: 900;
            letter-spacing: 2px;
            font-size: 1.25rem;
        }

        .rk-field-row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 12px;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .rk-label {
            font-weight: 800;
            width: 140px;
            flex-shrink: 0;
            color: #cbd5e1;
        }

        .rk-value {
            flex: 1;
            border-bottom: 1px dotted #64748b;
            padding-bottom: 2px;
            font-weight: 700;
            color: #ffffff;
            min-height: 24px;
        }

        .rk-value-dated {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex: 1;
            border-bottom: 1px dotted #64748b;
            padding-bottom: 2px;
            font-weight: 700;
            color: #ffffff;
        }

        .rk-multiline-value {
            border-bottom: 1px dotted #64748b;
            padding-top: 4px;
            padding-bottom: 4px;
            font-weight: 700;
            color: #ffffff;
            min-height: 40px;
            line-height: 1.5;
        }

        @media print {
            @page {
                size: 80mm auto; /* Ideal for thermal slip or standard page */
                margin: 5mm;
            }
            body {
                background: white !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .rk-slip-container {
                border: 3px double #000000 !important;
                background: white !important;
                color: #000000 !important;
                box-shadow: none !important;
                max-width: 100% !important;
                padding: 15px !important;
            }
            .rk-header-box {
                border-top: 2px solid #000000 !important;
                border-bottom: 2px solid #000000 !important;
                color: #000000 !important;
            }
            .rk-label {
                color: #000000 !important;
            }
            .rk-value, .rk-value-dated, .rk-multiline-value {
                border-bottom: 1px dotted #000000 !important;
                color: #000000 !important;
            }
        }
    </style>
</head>
<body class="h-full font-sans antialiased flex flex-col items-center justify-start p-4 sm:p-6 bg-slate-950 text-slate-100">

    <!-- Top Action Bar (Hidden when printing) -->
    <div class="no-print w-full max-w-lg flex items-center justify-between mb-5">
        <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Loan Account
        </a>
        <div class="flex items-center gap-2">
            <a href="<?= $baseUrl ?>/receipts/sanction/<?= $loan['id'] ?>" class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-semibold text-amber-400 hover:bg-slate-700 transition" title="View Full A4 Sanction Voucher">
                <i class="fa-solid fa-file-lines mr-1"></i> A4 Voucher
            </a>
            <button onclick="window.print()" class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg hover:bg-amber-400 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-print"></i> Print Slip
            </button>
        </div>
    </div>

    <!-- Official Loan Disbursement Receipt (Styled exactly like reference image) -->
    <div class="rk-slip-container shadow-2xl">

        <!-- Slip Header -->
        <div class="rk-header-box">
            RK DETAILS
        </div>

        <!-- Acc. No. : -->
        <div class="rk-field-row">
            <div class="rk-label">Acc. No. :</div>
            <div class="rk-value"><?= htmlspecialchars($accDisplay) ?></div>
        </div>

        <!-- Dated : -->
        <div class="rk-field-row">
            <div class="rk-label">Dated :</div>
            <div class="rk-value-dated">
                <span><?= htmlspecialchars($loanDate) ?></span>
                <span class="font-mono"><?= htmlspecialchars($loanTime) ?></span>
            </div>
        </div>

        <!-- Name : -->
        <div class="rk-field-row">
            <div class="rk-label">Name :</div>
            <div class="rk-value"><?= htmlspecialchars($custName ?: '—') ?></div>
        </div>

        <!-- Father Name : -->
        <div class="rk-field-row">
            <div class="rk-label">Father Name :</div>
            <div class="rk-value"><?= htmlspecialchars($fatherName ?: '—') ?></div>
        </div>

        <!-- Adhaar No. : -->
        <div class="rk-field-row">
            <div class="rk-label">Adhaar No. :</div>
            <div class="rk-value"><?= htmlspecialchars($aadhaar ?: '—') ?></div>
        </div>

        <!-- Mobile : -->
        <div class="rk-field-row">
            <div class="rk-label">Mobile :</div>
            <div class="rk-value"><?= htmlspecialchars($mobile ?: '—') ?></div>
        </div>

        <!-- Amount : -->
        <div class="rk-field-row">
            <div class="rk-label">Amount :</div>
            <div class="rk-value"><?= htmlspecialchars($principal) ?></div>
        </div>

        <!-- Gua. Name : -->
        <div class="rk-field-row">
            <div class="rk-label">Gua. Name :</div>
            <div class="rk-value"><?= htmlspecialchars($guaName ?: '—') ?></div>
        </div>

        <!-- Gua. Mobile : -->
        <div class="rk-field-row">
            <div class="rk-label">Gua. Mobile :</div>
            <div class="rk-value"><?= htmlspecialchars($guaMobile ?: '—') ?></div>
        </div>

        <!-- RK Detail's : -->
        <div class="mt-3">
            <div class="rk-label mb-1">RK Detail's :</div>
            <div class="rk-multiline-value">
                <?php if (empty($collateralItems)): ?>
                    <span><?= htmlspecialchars($loan['security_type'] ?? 'No Physical Ornaments Recorded') ?></span>
                <?php else: ?>
                    <div class="space-y-1">
                        <?php foreach ($collateralItems as $ci): ?>
                            <?php 
                                $wt = floatval($ci['gross_weight'] ?? 0);
                                $wtFmt = ($wt == intval($wt)) ? intval($wt) : number_format($wt, 2);
                            ?>
                            <div><?= htmlspecialchars($ci['item_name']) ?> (<?= $wtFmt ?> G)</div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Optional Auto-print -->
    <?php if (!empty($_GET['print'])): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 300);
            });
        </script>
    <?php endif; ?>

</body>
</html>
