<?php
$pageTitle = 'Generate New Bill';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="max-w-5xl mx-auto space-y-6" x-data="billingForm()">

    <!-- Header Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= $baseUrl ?>/bills" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-2xs">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-amber-500"></i>
                    Generate New Bill
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Add multiple products, auto-calculate totals, and print invoice.</p>
            </div>
        </div>
        <div class="text-right">
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bill Identifier</span>
            <span class="font-mono font-bold text-amber-600 dark:text-amber-400 text-sm"><?= htmlspecialchars($nextBillNumber) ?></span>
        </div>
    </div>

    <!-- Main Billing Form -->
    <form action="<?= $baseUrl ?>/bills/store" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Section 1: Bill Header & Parties -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xs space-y-5">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <i class="fa-solid fa-building text-amber-500"></i> 1. Bill Details & Parties
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Company Name -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Company Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="company_name" x-model="companyName" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-semibold focus:border-amber-500 focus:outline-none shadow-2xs">
                </div>

                <!-- Bill Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Bill / Invoice # <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="bill_number" x-model="billNumber" required
                           class="w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none shadow-2xs">
                </div>

                <!-- Bill Date -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Bill Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="bill_date" x-model="billDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                </div>
            </div>

            <!-- Customer Details Grid -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Customer Information</span>
                    
                    <!-- Quick Customer Selector -->
                    <?php if (!empty($customers)): ?>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400">Quick autofill:</span>
                            <select @change="selectCustomer($event)" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1 px-2 text-[11px] text-slate-700 dark:text-slate-200 focus:border-amber-500 focus:outline-none">
                                <option value="">-- Choose Existing Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= htmlspecialchars(json_encode([
                                        'name'    => $c['full_name'],
                                        'mobile'  => $c['mobile'],
                                        'address' => $c['address']
                                    ])) ?>">
                                        <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['mobile']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Customer Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="customer_name" x-model="customerName" required placeholder="e.g. Rajesh Kumar"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Customer Mobile Phone</label>
                        <input type="text" name="customer_mobile" x-model="customerMobile" placeholder="e.g. 9876543210"
                               class="w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Customer Address</label>
                        <input type="text" name="customer_address" x-model="customerAddress" placeholder="e.g. 42 Bazar Ward, City"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            GST No. <span class="text-[10px] font-normal text-slate-400">(Optional)</span>
                        </label>
                        <input type="text" name="gst_number" x-model="gstNumber" placeholder="e.g. 27AAACG1234F1Z5"
                               class="w-full font-mono uppercase rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Multi-Product Items Table (Calculates Totals Automatically) -->
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-amber-500"></i> 2. Products & Line Items (Single Table Multi-Product)
                </h2>
                <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 px-3 py-1.5 text-xs font-bold hover:bg-amber-500/20 transition">
                    <i class="fa-solid fa-plus"></i> Add Product
                </button>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-[11px] font-bold text-slate-400 uppercase border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-2 w-10 text-center">#</th>
                            <th class="py-2.5 px-3 min-w-[220px]">Product Name / Description <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-3 w-28">Quantity <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-3 w-36">Price / Rate (₹) <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-3 w-36 text-right">Line Total (₹)</th>
                            <th class="py-2.5 px-2 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-2 text-center font-mono font-bold text-slate-400" x-text="index + 1"></td>
                                <td class="py-3 px-3">
                                    <input type="text" :name="'items[' + index + '][product_name]'" x-model="item.product_name" required placeholder="e.g. Gold Ring 22K, Silver Coin, Box..."
                                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                                </td>
                                <td class="py-3 px-3">
                                    <input type="number" step="any" min="0.001" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" @input="calculateRow(index)" required placeholder="1"
                                           class="w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none text-right">
                                </td>
                                <td class="py-3 px-3">
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-slate-400 font-mono text-xs">₹</span>
                                        <input type="number" step="any" min="0" :name="'items[' + index + '][price]'" x-model.number="item.price" @input="calculateRow(index)" required placeholder="0.00"
                                               class="w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 pl-7 pr-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none text-right">
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <span class="font-mono font-bold text-slate-900 dark:text-white text-xs">
                                        ₹<span x-text="item.total.toFixed(2)"></span>
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-center">
                                    <button type="button" @click="removeItem(index)" :disabled="items.length <= 1"
                                            :class="items.length <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30'"
                                            class="rounded-lg p-1.5 text-slate-400 transition" title="Remove Item">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Add Row Button Bottom -->
            <div class="pt-2">
                <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:border-amber-500 hover:text-amber-500 transition w-full justify-center">
                    <i class="fa-solid fa-plus"></i> Add Another Product Line
                </button>
            </div>
        </div>

        <!-- Section 3: Summary, Discounts, Payment Mode & Notes -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left 2 Cols: Notes & Payment Mode -->
            <div class="lg:col-span-2 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xs space-y-4">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <i class="fa-solid fa-credit-card text-amber-500"></i> 3. Payment Mode & Notes
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Mode</label>
                        <select name="payment_mode" x-model="paymentMode" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI / QR Code</option>
                            <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Credit / Due">Credit / On Account</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Discount (₹)</label>
                        <input type="number" step="any" min="0" name="discount_amount" x-model.number="discount" placeholder="0.00"
                               class="w-full font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Invoice Notes / Terms & Conditions</label>
                    <textarea name="notes" rows="3" placeholder="e.g. Goods once sold will not be taken back. Subject to local jurisdiction."
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none shadow-2xs"></textarea>
                </div>
            </div>

            <!-- Right Col: Totals Box (Automatically Computed) -->
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-950 p-6 shadow-xs flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                        Summary Calculations
                    </h3>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Subtotal (<span x-text="items.length"></span> items)</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">
                                ₹<span x-text="subtotal.toFixed(2)"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Discount</span>
                            <span class="font-mono font-bold text-rose-500">
                                -₹<span x-text="(parseFloat(discount) || 0).toFixed(2)"></span>
                            </span>
                        </div>

                        <div class="my-2 border-t border-slate-200 dark:border-slate-800"></div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-sm font-black text-slate-900 dark:text-white uppercase">Grand Total</span>
                            <span class="font-mono font-black text-xl sm:text-2xl text-emerald-600 dark:text-emerald-400">
                                ₹<span x-text="grandTotal.toFixed(2)"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                    <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 py-3 px-4 text-xs font-black text-slate-950 shadow-lg hover:from-amber-400 hover:to-amber-500 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-check-circle text-base"></i>
                        <span>Save & View Printable Bill</span>
                    </button>
                    <a href="<?= $baseUrl ?>/bills" class="block text-center py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition">
                        Cancel & Discard
                    </a>
                </div>
            </div>

        </div>

    </form>

</div>

<script>
function billingForm() {
    return {
        companyName: <?= json_encode($defaultCompany) ?>,
        billNumber: <?= json_encode($nextBillNumber) ?>,
        customerName: '',
        customerMobile: '',
        customerAddress: '',
        gstNumber: '',
        billDate: '<?= date('Y-m-d') ?>',
        paymentMode: 'Cash',
        discount: 0,
        tax: 0,
        items: [
            { product_name: '', quantity: 1, price: '', total: 0 }
        ],

        addItem() {
            this.items.push({ product_name: '', quantity: 1, price: '', total: 0 });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        calculateRow(index) {
            const row = this.items[index];
            const qty = parseFloat(row.quantity) || 0;
            const prc = parseFloat(row.price) || 0;
            row.total = Math.round(qty * prc * 100) / 100;
        },

        get subtotal() {
            return this.items.reduce((sum, it) => sum + (parseFloat(it.total) || 0), 0);
        },

        get grandTotal() {
            const sub = this.subtotal;
            const disc = parseFloat(this.discount) || 0;
            const tx = parseFloat(this.tax) || 0;
            return Math.max(0, Math.round((sub - disc + tx) * 100) / 100);
        },

        selectCustomer(event) {
            const val = event.target.value;
            if (!val) return;
            try {
                const data = JSON.parse(val);
                this.customerName = data.name || '';
                this.customerMobile = data.mobile || '';
                this.customerAddress = data.address || '';
            } catch (e) {
                console.error(e);
            }
        }
    };
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
