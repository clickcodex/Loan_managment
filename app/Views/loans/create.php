<?php
$pageTitle = 'Issue New Loan (Unified Model)';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$goldCustomRates   = !empty($goldRate['custom_rates']) ? json_decode($goldRate['custom_rates'], true) : [];
$silverCustomRates = !empty($silverRate['custom_rates']) ? json_decode($silverRate['custom_rates'], true) : [];
?>

<!-- Alpine.js Loan Form Engine Component -->
<script>
function loanFormEngine() {
    return {
        securityType: 'Gold Secured',
        principalAmount: 10000,
        interestRate: 10,
        interestCycle: '15 Days',

        get cycleDays() {
            return this.interestCycle.includes('30') ? 30 : 15;
        },

        get monthlyInterest() {
            const p = parseFloat(this.principalAmount) || 0;
            const r = parseFloat(this.interestRate) || 0;
            return Math.round(p * (r / 100) * 100) / 100;
        },

        get oneCycleInterest() {
            if (this.cycleDays === 15) {
                return Math.round((this.monthlyInterest / 2) * 100) / 100;
            }
            return this.monthlyInterest;
        },

        get initialTotalPayable() {
            const p = parseFloat(this.principalAmount) || 0;
            return Math.round((p + this.oneCycleInterest) * 100) / 100;
        },
        
        // 100% Pure Base Rates
        base100Gold: <?= floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2)) ?>,
        base100Silver: <?= floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 88) / 0.999, 2)) ?>,
        base24k: <?= floatval($goldRate['rate_24k'] ?? 79920) ?>,
        base999: <?= floatval($silverRate['rate_999'] ?? 88) ?>,

        goldPresetsList: <?= json_encode($goldPresets) ?>,
        silverPresetsList: <?= json_encode($silverPresets) ?>,

        // Latest Gold Rates per Karat (₹ / 10 Grams)
        goldRates: {
            '100% (Fine)': <?= floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2)) ?>,
            '100%': <?= floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2)) ?>,
            '24K': <?= floatval($goldRate['rate_24k'] ?? 79920) ?>,
            '22K': <?= floatval($goldRate['rate_22k'] ?? 73336) ?>,
            '18K': <?= floatval($goldRate['rate_18k'] ?? 60000) ?>,
            '14K': <?= floatval($goldRate['rate_14k'] ?? 46664) ?>,
            <?php foreach ($goldPresets as $gp): ?>
                '<?= htmlspecialchars($gp['name']) ?>': <?= floatval($goldCustomRates[$gp['name']] ?? round(($goldRate['rate_100'] ?? 80000) * ($gp['purity_percentage'] / 100), 2)) ?>,
            <?php endforeach; ?>
        },
        
        // Latest Silver Rates per Purity
        silverRates: {
            '100% (Pure)': <?= floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 88) / 0.999, 2)) ?>,
            '100%': <?= floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 88) / 0.999, 2)) ?>,
            '99.9%': <?= floatval($silverRate['rate_999'] ?? 88) ?>,
            '92.5%': <?= floatval($silverRate['rate_925'] ?? 81.4) ?>,
            '80.0%': <?= floatval($silverRate['rate_800'] ?? 70.4) ?>,
            <?php foreach ($silverPresets as $sp): ?>
                '<?= htmlspecialchars($sp['name']) ?>': <?= floatval($silverCustomRates[$sp['name']] ?? round(($silverRate['rate_100'] ?? 90) * ($sp['purity_percentage'] / 100), 2)) ?>,
            <?php endforeach; ?>
        },

        // Dynamic Gold Items Array
        goldItems: [
            { item_name: 'Gold Ring', quantity: 1, gross_weight: 10, stone_weight: 0, net_weight: 10, purity_preset: '24K', purity_percentage: 100.00, market_value: 0, manual_market_value_override: '', rk_number: '', remarks: '' }
        ],

        // Dynamic Silver Items Array
        silverItems: [
            { item_name: 'Silver Chain', quantity: 1, gross_weight: 50, net_weight: 50, purity_preset: '100% (Pure)', purity_percentage: 100.00, market_value: 0, manual_market_value_override: '', rk_number: '', remarks: '' }
        ],

        // Helper: Is Gold Active?
        get hasGold() {
            return this.securityType.includes('Gold');
        },

        // Helper: Is Silver Active?
        get hasSilver() {
            return this.securityType.includes('Silver');
        },

        // Helper: Is Guarantor Active?
        get hasGuarantor() {
            return this.securityType.includes('Guarantor') || this.securityType === 'Guarantor Secured';
        },

        // Add Gold Item
        addGoldItem() {
            this.goldItems.push({
                item_name: '', quantity: 1, gross_weight: 0, stone_weight: 0, net_weight: 0, purity_preset: '24K', purity_percentage: 100.00, market_value: 0, manual_market_value_override: '', rk_number: '', remarks: ''
            });
        },
        removeGoldItem(index) {
            this.goldItems.splice(index, 1);
        },

        // Add Silver Item
        addSilverItem() {
            this.silverItems.push({
                item_name: '', quantity: 1, gross_weight: 0, net_weight: 0, purity_preset: '100% (Pure)', purity_percentage: 100.00, market_value: 0, manual_market_value_override: '', rk_number: '', remarks: ''
            });
        },
        removeSilverItem(index) {
            this.silverItems.splice(index, 1);
        },

        // Helper to get active rate per 10g
        getGoldRateForPreset(preset) {
            let baseRate = parseFloat(this.base100Gold || (this.base24k / 0.999) || 80000);
            return this.goldRates[preset] || (baseRate * 0.9167);
        },

        getSilverRateForPreset(preset) {
            let baseRate = parseFloat(this.base100Silver || (this.base999 / 0.999) || 90);
            return this.silverRates[preset] || (baseRate * 0.925);
        },

        // Calculate Gold Item Market Value (100% Base Rate in ₹/10g => Value = Net Wt * (Base / 10) * (Purity% / 100))
        getGoldMarketValue(item) {
            if (item.manual_market_value_override && parseFloat(item.manual_market_value_override) > 0) {
                item.market_value = parseFloat(item.manual_market_value_override);
                return item.market_value;
            }
            let net = (parseFloat(item.gross_weight) || 0) - (parseFloat(item.stone_weight) || 0);
            item.net_weight = net > 0 ? net : 0;
            if (net <= 0) {
                item.market_value = 0;
                return 0;
            }

            let baseRate10g = parseFloat(this.base100Gold || (this.base24k / 0.999) || 80000);
            let purityPct = parseFloat(item.purity_percentage) || 100.00;

            // Gold valuation: Net Wt * (Base 100% Rate / 10) * (Purity % / 100)
            let applicableRatePerGram = (baseRate10g * (purityPct / 100.0)) / 10.0;
            let val = item.net_weight * applicableRatePerGram;
            item.market_value = isNaN(val) ? 0 : Math.round(val * 100) / 100;
            return item.market_value;
        },

        // Calculate Silver Item Market Value (100% Base Rate in ₹/g => Value = Net Wt * Base * (Purity% / 100))
        getSilverMarketValue(item) {
            if (item.manual_market_value_override && parseFloat(item.manual_market_value_override) > 0) {
                item.market_value = parseFloat(item.manual_market_value_override);
                return item.market_value;
            }
            let net = parseFloat(item.gross_weight) || 0;
            item.net_weight = net > 0 ? net : 0;
            if (net <= 0) {
                item.market_value = 0;
                return 0;
            }

            let baseRate100 = parseFloat(this.base100Silver || (this.base999 / 0.999) || 90);
            let purityPct = parseFloat(item.purity_percentage) || 100.00;

            // Silver valuation: Net Wt * Base 100% Rate * (Purity % / 100)
            let applicableRatePerGram = baseRate100 * (purityPct / 100.0);
            let val = item.net_weight * applicableRatePerGram;
            item.market_value = isNaN(val) ? 0 : Math.round(val * 100) / 100;
            return item.market_value;
        },

        // Total Gold Market Value
        get totalGoldValue() {
            if (!this.hasGold) return 0;
            return this.goldItems.reduce((sum, item) => sum + this.getGoldMarketValue(item), 0);
        },

        // Total Silver Market Value
        get totalSilverValue() {
            if (!this.hasSilver) return 0;
            return this.silverItems.reduce((sum, item) => sum + this.getSilverMarketValue(item), 0);
        },

        // Total Combined Collateral Value
        get totalCollateralValue() {
            return this.totalGoldValue + this.totalSilverValue;
        },

        // Calculated LTV Ratio %
        get ltvRatio() {
            let p = parseFloat(this.principalAmount) || 0;
            let c = this.totalCollateralValue;
            if (c <= 0) return 0;
            return (p / c) * 100;
        }
    };
}
</script>

<div class="max-w-5xl mx-auto space-y-6" x-data="loanFormEngine()">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-plus-circle text-amber-500"></i> Issue New Loan (Unified Loan Form)
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">One Loan &bull; One Ledger &bull; Mixed Collateral (Gold + Silver + Guarantor)</p>
        </div>
        <a href="<?= $baseUrl ?>/loans" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Directory
        </a>
    </div>

    <!-- Loan Creation Form -->
    <form action="<?= $baseUrl ?>/loans/store" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="gold_items" :value="JSON.stringify(hasGold ? goldItems : [])">
        <input type="hidden" name="silver_items" :value="JSON.stringify(hasSilver ? silverItems : [])">

        <!-- SECTION A: PRIMARY LOAN DETAILS -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-file-contract"></i> Section A: Loan & Security Parameters
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Loan Number (Auto Generated) -->
                <div>
                    <label for="loan_number" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Loan Number</label>
                    <input type="text" id="loan_number" name="loan_number" value="<?= htmlspecialchars($nextLoanNumber) ?>" required readonly
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-amber-600 dark:text-amber-400 font-mono font-bold cursor-not-allowed">
                </div>

                <!-- Customer Selector -->
                <div>
                    <label for="customer_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Customer <span class="text-rose-500">*</span></label>
                    <select id="customer_id" name="customer_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($preSelectedCustId == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['customer_id']) ?> &bull; Mobile: <?= htmlspecialchars($c['mobile']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Loan Date -->
                <div>
                    <label for="loan_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Loan Date</label>
                    <input type="date" id="loan_date" name="loan_date" value="<?= date('Y-m-d') ?>" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Security Type Selection & Financials -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 pt-2">
                <div>
                    <label for="security_type" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Security Type <span class="text-rose-500">*</span></label>
                    <select id="security_type" name="security_type" x-model="securityType" required
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 focus:border-amber-500 focus:outline-none">
                        <option value="Gold Secured">Gold Secured</option>
                        <option value="Silver Secured">Silver Secured</option>
                        <option value="Gold + Silver Secured">Gold + Silver Secured (Mixed)</option>
                        <option value="Guarantor Secured">Guarantor Secured (Cash Loan)</option>
                        <option value="Gold + Guarantor">Gold + Guarantor</option>
                        <option value="Silver + Guarantor">Silver + Guarantor</option>
                        <option value="Gold + Silver + Guarantor">Gold + Silver + Guarantor (Maximum Security)</option>
                    </select>
                </div>

                <div>
                    <label for="principal_amount" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Principal Amount (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" id="principal_amount" name="principal_amount" x-model="principalAmount" required placeholder="e.g. 10000.00"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs font-black text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label for="interest_rate" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Monthly Interest Rate (% / Month) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" id="interest_rate" name="interest_rate" x-model="interestRate" required placeholder="e.g. 10.00"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label for="interest_cycle" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Calculation Cycle <span class="text-rose-500">*</span></label>
                    <select id="interest_cycle" name="interest_cycle" x-model="interestCycle" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 focus:border-amber-500 focus:outline-none">
                        <option value="15 Days" selected>15 Days Cycle (Half-Monthly)</option>
                        <option value="30 Days (Monthly)">30 Days Cycle (Monthly)</option>
                    </select>
                </div>

                <div>
                    <label for="interest_method" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Interest Method <span class="text-rose-500">*</span></label>
                    <select id="interest_method" name="interest_method" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 focus:border-amber-500 focus:outline-none">
                        <option value="Compound" selected>Compounding (Annual)</option>
                        <option value="Simple">Simple Interest</option>
                    </select>
                    <input type="hidden" name="compound_frequency" value="Yearly">
                </div>

                <div x-show="principalAmount > 0" class="md:col-span-5 rounded-2xl border border-amber-300 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-950/20 p-4 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase text-amber-800 dark:text-amber-300 tracking-wider">
                            ⚡ Auto-Calculated Loan Dues Preview
                        </span>
                        <span class="rounded-lg bg-amber-500/20 px-2.5 py-0.5 text-[10px] font-mono font-bold text-amber-700 dark:text-amber-300" x-text="'Monthly Rate: ' + interestRate + '% | Cycle: ' + interestCycle + ' | Annual Compounding'"></span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1">
                        <div class="bg-white dark:bg-slate-950 p-2.5 rounded-xl border border-amber-200 dark:border-amber-500/20">
                            <span class="block text-[10px] text-slate-400 font-bold">Principal Loan Amount</span>
                            <span class="font-black font-mono text-slate-900 dark:text-white" x-text="'₹' + (parseFloat(principalAmount) || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </div>
                        <div class="bg-white dark:bg-slate-950 p-2.5 rounded-xl border border-amber-200 dark:border-amber-500/20">
                            <span class="block text-[10px] text-slate-400 font-bold" x-text="'Interest / Cycle (' + (cycleDays === 15 ? (interestRate/2) + '% half-month' : interestRate + '% month') + ')'"></span>
                            <span class="font-black font-mono text-amber-600 dark:text-amber-400" x-text="'+ ₹' + oneCycleInterest.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </div>
                        <div class="bg-white dark:bg-slate-950 p-2.5 rounded-xl border border-emerald-300 dark:border-emerald-500/30">
                            <span class="block text-[10px] text-emerald-600 font-extrabold">Initial Total Dues (Cycle 1)</span>
                            <span class="font-black font-mono text-emerald-600 dark:text-emerald-400 text-sm" x-text="'₹' + initialTotalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-500 italic mt-1">
                        For ₹<span x-text="(parseFloat(principalAmount) || 0).toLocaleString('en-IN')"></span> @ <span x-text="interestRate"></span>% monthly rate (<span x-text="interestCycle"></span>): 
                        Under 15 Days (Day 1-15): ₹<span x-text="initialTotalPayable.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span> &bull; 
                        1 Full Month (Day 16-30): ₹<span x-text="(parseFloat(principalAmount) + (oneCycleInterest * 2)).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span> &bull; 
                        1.5 Months (Day 31-45): ₹<span x-text="(parseFloat(principalAmount) + (oneCycleInterest * 3)).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span> &bull;
                        <em>In Year 2 (after 1 year), interest compounds on the updated total balance.</em>
                    </p>
                </div>

                <!-- Loan Remarks / Special Notes -->
                <div class="md:col-span-5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label for="loan_remarks" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        <i class="fa-regular fa-comment-dots text-amber-500 mr-1"></i> Loan Remarks / Notes (Optional)
                    </label>
                    <input type="text" id="loan_remarks" name="remarks" placeholder="e.g. Approved special terms, customer requests, or general notes..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                    <p class="text-[11px] text-slate-400 mt-1">This remark will be stored on the loan account and recorded directly in the initial disbursement ledger entry.</p>
                </div>
            </div>
        </div>

        <!-- SECTION B: GOLD COLLATERAL (Conditionally Visible) -->
        <div x-show="hasGold" x-cloak class="rounded-2xl border border-amber-300 dark:border-yellow-500/30 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 gap-2">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-yellow-400 flex items-center gap-2">
                        <i class="fa-solid fa-gem"></i> Section B: Gold Collateral Items (Valuation Based on 100% Base Rate)
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        100% Pure Gold Base Rate: <span class="font-mono font-bold text-amber-600 dark:text-yellow-400">₹<span x-text="base100Gold.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span> / 10g</span> 
                        <span class="text-[10px] text-slate-400 font-mono">(₹<span x-text="(base100Gold / 10).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span> / g)</span>
                    </p>
                </div>
                <button type="button" @click="addGoldItem()" class="rounded-xl bg-amber-500/20 text-amber-700 dark:text-yellow-400 px-3.5 py-1.5 text-xs font-bold border border-amber-500/30 hover:bg-amber-500/30 transition flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
                    <i class="fa-solid fa-plus text-xs"></i> Add Gold Item
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, index) in goldItems" :key="index">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/70 p-3.5 space-y-2.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-start">
                            <!-- 1. Item Name (Col 4) -->
                            <div class="md:col-span-4">
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Item Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="item.item_name" placeholder="e.g. Gold Ring / Chain / Necklace / Bangles" required
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                            </div>

                            <!-- 2. Gross Weight (Col 2) -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Weight (g) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.001" min="0" x-model="item.gross_weight" placeholder="0.000" required
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500 focus:outline-none">
                                <input type="hidden" x-model="item.stone_weight" value="0">
                            </div>

                            <!-- 3. Custom Purity % Input (Col 3) -->
                            <div class="md:col-span-3 space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Purity (%) <span class="text-amber-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-mono font-bold text-amber-600 dark:text-yellow-400" 
                                          x-text="'₹' + (((base100Gold * ((parseFloat(item.purity_percentage) || 100) / 100)) / 10)).toFixed(2) + '/g'"></span>
                                </div>
                                <div class="relative">
                                    <input type="number" step="0.01" min="1" max="100" x-model="item.purity_percentage" placeholder="e.g. 80, 91.67, 75, 100" required
                                           class="w-full rounded-xl border border-amber-400/80 bg-white dark:bg-slate-900 py-2 pl-3 pr-7 text-xs text-amber-600 dark:text-amber-400 font-black font-mono focus:border-amber-500 focus:outline-none">
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-xs text-amber-600 dark:text-amber-400 font-bold">%</span>
                                </div>
                            </div>

                            <!-- 4. Live Market Value (Col 2) -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Market Value</label>
                                <div class="py-2 px-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-black text-amber-600 dark:text-yellow-400 font-mono text-right">
                                    ₹<span x-text="getGoldMarketValue(item).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </div>
                                <span class="block text-[9px] text-slate-400 text-right mt-0.5 font-mono">
                                    Net: <span x-text="(parseFloat(item.gross_weight) || 0).toFixed(3)"></span>g
                                </span>
                            </div>

                            <!-- 5. Delete Action Button (Col 1) -->
                            <div class="md:col-span-1 flex justify-end pt-5">
                                <button type="button" @click="removeGoldItem(index)" x-show="goldItems.length > 1" title="Remove Item"
                                        class="h-8 w-8 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500 hover:text-white transition flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- SECTION C: SILVER COLLATERAL (Conditionally Visible) -->
        <div x-show="hasSilver" x-cloak class="rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 gap-2">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                        <i class="fa-solid fa-ring"></i> Section C: Silver Collateral Items (Valuation Based on 100% Base Rate)
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        100% Pure Silver Base Rate: <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹<span x-text="base100Silver.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span> / g</span>
                    </p>
                </div>
                <button type="button" @click="addSilverItem()" class="rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-3.5 py-1.5 text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-200 transition flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
                    <i class="fa-solid fa-plus text-xs"></i> Add Silver Item
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, index) in silverItems" :key="index">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/70 p-3.5 space-y-2.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-start">
                            <!-- 1. Item Name (Col 3) -->
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Item Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="item.item_name" placeholder="e.g. Silver Payal / Chain / Coins / Utensils" required
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                            </div>

                            <!-- 2. Gross Weight (Col 3) -->
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Weight (g) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.001" min="0" x-model="item.gross_weight" placeholder="0.000" required
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500 focus:outline-none">
                            </div>

                            <!-- 3. Custom Purity % Input (Col 3) -->
                            <div class="md:col-span-3 space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        Purity (%) <span class="text-slate-400">*</span>
                                    </label>
                                    <span class="text-[10px] font-mono font-bold text-slate-700 dark:text-slate-300" 
                                          x-text="'₹' + ((base100Silver * ((parseFloat(item.purity_percentage) || 100) / 100))).toFixed(2) + '/g'"></span>
                                </div>
                                <div class="relative">
                                    <input type="number" step="0.01" min="1" max="100" x-model="item.purity_percentage" placeholder="e.g. 92.5, 80, 70, 100" required
                                           class="w-full rounded-xl border border-slate-400/80 bg-white dark:bg-slate-900 py-2 pl-3 pr-7 text-xs text-slate-900 dark:text-slate-100 font-black font-mono focus:border-amber-500 focus:outline-none">
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-xs text-slate-400 font-bold">%</span>
                                </div>
                            </div>

                            <!-- 4. Live Market Value (Col 2) -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Market Value</label>
                                <div class="py-2 px-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-black text-slate-800 dark:text-slate-200 font-mono text-right">
                                    ₹<span x-text="getSilverMarketValue(item).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </div>
                            </div>

                            <!-- 5. Delete Action Button (Col 1) -->
                            <div class="md:col-span-1 flex justify-end pt-5">
                                <button type="button" @click="removeSilverItem(index)" x-show="silverItems.length > 1" title="Remove Item"
                                        class="h-8 w-8 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500 hover:text-white transition flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- SECTION D: GUARANTOR DETAILS (Conditionally Visible) -->
        <div x-show="hasGuarantor" x-cloak class="rounded-2xl border border-purple-300 dark:border-purple-500/30 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-user-shield"></i> Section D: Loan Guarantor Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="guarantor_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="guarantor_name" name="guarantor_name" placeholder="Full name of guarantor" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label for="guarantor_mobile" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Mobile <span class="text-rose-500">*</span></label>
                    <input type="text" id="guarantor_mobile" name="guarantor_mobile" placeholder="10-digit mobile" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-mono">
                </div>

                <div>
                    <label for="guarantor_relationship" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Relationship</label>
                    <input type="text" id="guarantor_relationship" name="guarantor_relationship" placeholder="e.g. Friend / Relative / Partner" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label for="guarantor_address" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Complete Address</label>
                <textarea id="guarantor_address" name="guarantor_address" rows="2" placeholder="Full residential / office address" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white"></textarea>
            </div>
        </div>

        <!-- SECTION E: VIDEO PROOF RECORDING (GIVING LOAN / DISBURSEMENT) -->
        <div class="rounded-2xl border border-indigo-300 dark:border-indigo-500/30 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-video"></i> Section E: Video Proof Recording (Giving Loan / Disbursement)
            </h2>
            <div>
                <label for="sanction_video" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Upload Loan Disbursement Video Proof (MP4, WebM, MOV, AVI)
                </label>
                <input type="file" id="sanction_video" name="sanction_video" accept="video/*"
                       class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 dark:file:bg-slate-800 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer">
                <p class="text-[11px] text-slate-500 mt-1">Optional: Upload video recording of handing over cash/check, customer agreement, or collateral inspection during loan disbursement.</p>
            </div>
        </div>

        <!-- Sticky Valuation Summary Bar -->
        <div class="rounded-2xl border border-amber-300 dark:border-amber-500/30 bg-white dark:bg-slate-900 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold">Gold Valuation</span>
                    <span class="font-mono font-extrabold text-amber-600 dark:text-yellow-400">₹<span x-text="totalGoldValue.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                </div>
                <div>
                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold">Silver Valuation</span>
                    <span class="font-mono font-extrabold text-slate-800 dark:text-slate-200">₹<span x-text="totalSilverValue.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                </div>
                <div>
                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold">Total Collateral Value</span>
                    <span class="font-mono font-black text-amber-600 dark:text-amber-400 text-sm">₹<span x-text="totalCollateralValue.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span>
                </div>
                <div>
                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold">LTV Ratio %</span>
                    <span class="font-mono font-extrabold text-sm" :class="ltvRatio > 75 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'" x-text="ltvRatio.toFixed(2) + '%'"></span>
                </div>
            </div>

            <button type="submit" class="rounded-xl bg-amber-500 px-6 py-3 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                <i class="fa-solid fa-check-circle mr-1.5 text-sm"></i> Issue Loan Account
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
