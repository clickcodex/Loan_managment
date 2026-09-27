<?php
$pageTitle = 'Add Collateral Item - ' . htmlspecialchars($loan['loan_number']);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$goldCustomRates   = !empty($goldRate['custom_rates']) ? json_decode($goldRate['custom_rates'], true) : [];
$silverCustomRates = !empty($silverRate['custom_rates']) ? json_decode($silverRate['custom_rates'], true) : [];
?>

<script>
function collateralAddEngine() {
    return {
        itemType: 'GOLD',
        customPurityPct: 100.00,
        base100Gold: <?= floatval($goldRate['rate_100'] ?? (floatval($goldRate['rate_24k'] ?? 79920) / 0.999)) ?>,
        base100Silver: <?= floatval($silverRate['rate_100'] ?? (floatval($silverRate['rate_999'] ?? 90) / 0.999)) ?>,

        get applicableRatePerGram() {
            let pct = parseFloat(this.customPurityPct);
            if (isNaN(pct) || pct <= 0) pct = 100;
            if (this.itemType === 'GOLD') {
                return ((this.base100Gold / 10.0) * (pct / 100.0)).toFixed(2);
            } else {
                return (this.base100Silver * (pct / 100.0)).toFixed(2);
            }
        },

        get applicableRatePer10Gram() {
            let pct = parseFloat(this.customPurityPct);
            if (isNaN(pct) || pct <= 0) pct = 100;
            return (this.base100Gold * (pct / 100.0)).toFixed(2);
        }
    };
}
</script>

<div class="max-w-4xl mx-auto space-y-6" x-data="collateralAddEngine()">

    <!-- Header Card -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-xl flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-white flex items-center gap-2">
                <i class="fa-solid fa-gem text-amber-500"></i> Add Collateral Item
            </h1>
            <p class="text-xs text-slate-400 mt-1">Valuation benchmarked against 100% pure base rate</p>
        </div>
        <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>" class="rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-300 hover:bg-slate-700 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to Loan
        </a>
    </div>

    <!-- Collateral Form -->
    <form action="<?= $baseUrl ?>/collateral/store" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
        <input type="hidden" name="purity_preset" :value="itemType === 'GOLD' ? '24K' : '100% (Pure)'">

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 space-y-5">
            
            <!-- Type & Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="item_type" class="block text-xs font-semibold text-slate-300 mb-1">Metal / Collateral Type <span class="text-rose-500">*</span></label>
                    <select id="item_type" name="item_type" x-model="itemType" required class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 font-bold focus:border-amber-500">
                        <option value="GOLD">Gold Jewellery (100% Base Rate: ₹<?= number_format(floatval($goldRate['rate_100'] ?? 140000), 2) ?> / 10g)</option>
                        <option value="SILVER">Silver Articles (100% Base Rate: ₹<?= number_format(floatval($silverRate['rate_100'] ?? 100), 2) ?> / g)</option>
                        <option value="OTHER">Other / Diamond / Platinum</option>
                    </select>
                </div>

                <div>
                    <label for="item_name" class="block text-xs font-semibold text-slate-300 mb-1">Item Name / Description <span class="text-rose-500">*</span></label>
                    <input type="text" id="item_name" name="item_name" placeholder="e.g. Gold Necklace, 2 Bangles, Silver Payal" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 font-bold focus:border-amber-500">
                </div>
            </div>

            <!-- Weights & Counts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="quantity" class="block text-xs font-semibold text-slate-300 mb-1">Quantity / Piece Count <span class="text-rose-500">*</span></label>
                    <input type="number" min="1" id="quantity" name="quantity" value="1" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 focus:border-amber-500">
                </div>

                <div>
                    <label for="gross_weight" class="block text-xs font-semibold text-slate-300 mb-1">Weight (g) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.001" min="0.001" id="gross_weight" name="gross_weight" placeholder="0.000" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 font-mono font-bold focus:border-amber-500">
                    <input type="hidden" id="stone_weight" name="stone_weight" value="0.000">
                </div>
            </div>

            <!-- Custom Purity Input & Live 100% Rate Calculation -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="purity_percentage" class="block text-xs font-semibold text-slate-300">
                        Custom Purity Percentage (%) <span class="text-amber-500">*</span>
                    </label>
                    <span class="text-xs font-mono font-bold text-amber-400" 
                          x-text="itemType === 'GOLD' ? ('Applicable Rate: ₹' + applicableRatePerGram + ' / g (₹' + applicableRatePer10Gram + ' / 10g)') : ('Applicable Rate: ₹' + applicableRatePerGram + ' / g')"></span>
                </div>
                <div class="relative">
                    <input type="number" step="0.01" min="1" max="100" id="purity_percentage" name="purity_percentage" x-model="customPurityPct" placeholder="e.g. 80, 91.67, 75, 100" required
                           class="w-full rounded-xl border border-amber-500/70 bg-slate-950 py-2.5 pl-3.5 pr-8 text-xs text-amber-400 font-mono font-black focus:border-amber-500">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-amber-500 font-bold">%</span>
                </div>
            </div>

            <!-- RK Storage & Manual Valuation Override -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="rk_number" class="block text-xs font-semibold text-slate-300 mb-1">RK Storage Rack Reference</label>
                    <input type="text" id="rk_number" name="rk_number" placeholder="e.g. RK-G102"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 focus:border-amber-500">
                </div>

                <div>
                    <label for="manual_market_value_override" class="block text-xs font-semibold text-slate-300 mb-1">Manual Valuation Override (₹)</label>
                    <input type="number" step="0.01" id="manual_market_value_override" name="manual_market_value_override" placeholder="Optional manual rupee valuation"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 focus:border-amber-500">
                </div>
            </div>

            <!-- Photo Upload / Live Camera -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Item Photo (Upload or Live Camera Capture)</label>
                <div class="flex items-center gap-2">
                    <input type="file" id="item_photo_input" name="item_photo" accept="image/jpeg,image/png,image/webp"
                           onchange="const h = document.getElementById('collateral_create_cam_base64'); if (h && this.files && this.files.length) h.value = '';"
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-950">
                    
                    <button type="button" 
                            onclick="window.openCameraModal({ fileInput: 'item_photo_input', hiddenInput: 'collateral_create_cam_base64', previewImg: 'collateral_create_preview', onCapture: () => document.getElementById('collateral_create_preview_box').classList.remove('hidden') })"
                            class="shrink-0 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-slate-950 px-3.5 py-2 text-xs font-bold shadow transition flex items-center gap-1.5"
                            title="Capture Live Photo with Device Camera">
                        <i class="fa-solid fa-camera"></i>
                        <span>Live Camera</span>
                    </button>
                </div>
                
                <input type="hidden" id="collateral_create_cam_base64" name="camera_photo_base64" value="">

                <div id="collateral_create_preview_box" class="mt-2.5 hidden flex items-center gap-3 p-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <img id="collateral_create_preview" src="" alt="Photo Preview" class="h-16 w-16 object-cover rounded-xl border border-amber-400">
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-circle-check mr-1"></i> Live Item Photo Ready</span>
                </div>
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-xs font-semibold text-slate-300 mb-1">Item Condition / Hallmark Remarks</label>
                <textarea id="remarks" name="remarks" rows="2" placeholder="Condition, custom purity markings, physical details"
                          class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 px-3.5 text-xs text-slate-100 focus:border-amber-500"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= $baseUrl ?>/loans/<?= $loan['id'] ?>" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">Cancel</a>
            <button type="submit" class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-lg hover:bg-amber-400">
                Add Collateral Item
            </button>
        </div>
    </form>

</div>

<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/camera-capture.js?v=<?= time() ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
