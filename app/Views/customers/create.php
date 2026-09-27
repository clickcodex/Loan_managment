<?php
$pageTitle = 'Register New Customer';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-user-plus text-amber-500"></i> Register New Customer
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Create a customer profile for loan accounts and KYC documentation</p>
        </div>
        <a href="<?= $baseUrl ?>/customers" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Directory
        </a>
    </div>

    <!-- Registration Form Card -->
    <form action="<?= $baseUrl ?>/customers/store" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Section 1: Basic Information -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 1: Basic Account Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Customer ID -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Customer System ID</label>
                    <input type="text" value="<?= htmlspecialchars($nextCustomerId) ?>" readonly 
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-amber-600 dark:text-amber-400 font-mono font-bold cursor-not-allowed">
                    <span class="text-[10px] text-slate-500">Auto-generated system reference</span>
                </div>

                <!-- Account Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Account Number</label>
                    <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars($nextAccountNumber ?? '') ?>" readonly 
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-amber-600 dark:text-amber-400 font-mono font-bold cursor-not-allowed">
                    <span class="text-[10px] text-slate-500">Auto-generated system reference</span>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Customer Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                        <option value="Active" selected>Active</option>
                        <option value="Closed">Closed</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Enter customer's full name"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Father / Spouse Name -->
                <div>
                    <label for="father_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Father / Guardian Name</label>
                    <input type="text" id="father_name" name="father_name" placeholder="Enter father's or spouse's name"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Profile Photo Upload / Live Camera -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Customer Photo (Upload or Live Camera Capture)</label>
                <div class="flex items-center gap-2">
                    <input type="file" id="customer_photo_input" name="photo" accept="image/jpeg,image/png,image/webp"
                           onchange="const h = document.getElementById('customer_photo_cam_base64'); if (h && this.files && this.files.length) h.value = '';"
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-950">
                    
                    <button type="button" 
                            onclick="window.openCameraModal({ fileInput: 'customer_photo_input', hiddenInput: 'customer_photo_cam_base64', previewImg: 'customer_photo_preview', onCapture: () => document.getElementById('customer_preview_box').classList.remove('hidden') })"
                            class="shrink-0 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-slate-950 px-3.5 py-2 text-xs font-bold shadow transition flex items-center gap-1.5 cursor-pointer"
                            title="Take Photo Directly with Device Camera">
                        <i class="fa-solid fa-camera"></i>
                        <span>Live Camera</span>
                    </button>
                </div>
                
                <input type="hidden" id="customer_photo_cam_base64" name="photo_camera_base64" value="">

                <div id="customer_preview_box" class="mt-2.5 hidden flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <img id="customer_photo_preview" src="" alt="Customer Photo Preview" class="h-16 w-16 object-cover rounded-xl border-2 border-amber-400">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> Live Customer Photo Attached
                        </span>
                        <p class="text-[11px] text-slate-400 mt-0.5">Ready to be saved with registration</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Contact & Identity (KYC) -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 2: Contact Details & KYC Identification
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Mobile Number -->
                <div>
                    <label for="mobile" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Primary Mobile <span class="text-rose-500">*</span></label>
                    <input type="text" id="mobile" name="mobile" required maxlength="15" placeholder="10-digit mobile number"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Alternate Mobile -->
                <div>
                    <label for="alt_mobile" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alternate Mobile</label>
                    <input type="text" id="alt_mobile" name="alt_mobile" maxlength="15" placeholder="Secondary contact number"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Aadhaar Card Number -->
                <div>
                    <label for="aadhaar" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Aadhaar Card Number</label>
                    <input type="text" id="aadhaar" name="aadhaar" maxlength="14" placeholder="12-digit Aadhaar number"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- PAN Card Number -->
                <div>
                    <label for="pan" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PAN Card Number <span class="text-[11px] font-normal text-slate-400 dark:text-slate-500">(Optional)</span>
                    </label>
                    <input type="text" id="pan" name="pan" maxlength="10" placeholder="10-character PAN number (Optional)"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 uppercase focus:border-amber-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Section 3: Address & Location -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 3: Address & Location Information
            </h2>

            <div>
                <label for="address" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Full Residential Address</label>
                <textarea id="address" name="address" rows="2" placeholder="House/Flat No., Street, Landmark..."
                          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Village / Locality -->
                <div>
                    <label for="village" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Village / Locality</label>
                    <input type="text" id="village" name="village" placeholder="Village name"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">City / Town</label>
                    <input type="text" id="city" name="city" placeholder="City name"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- State -->
                <div>
                    <label for="state" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">State</label>
                    <input type="text" id="state" name="state" placeholder="State"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Pincode -->
                <div>
                    <label for="pincode" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">PIN Code</label>
                    <input type="text" id="pincode" name="pincode" maxlength="10" placeholder="6-digit PIN"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Section 4: Default Guarantor Details -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 4: Default Guarantor Reference (Optional)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Guarantor Name -->
                <div>
                    <label for="guarantor_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Name</label>
                    <input type="text" id="guarantor_name" name="guarantor_name" placeholder="Full name of guarantor"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Guarantor Mobile -->
                <div>
                    <label for="guarantor_mobile" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Mobile</label>
                    <input type="text" id="guarantor_mobile" name="guarantor_mobile" maxlength="15" placeholder="Guarantor contact number"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Guarantor Address -->
            <div>
                <label for="guarantor_address" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Residential Address</label>
                <textarea id="guarantor_address" name="guarantor_address" rows="2" placeholder="Address of guarantor..."
                          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none"></textarea>
            </div>
        </div>

        <!-- Section 5: Remarks -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 5: Additional Remarks & Notes
            </h2>
            <div>
                <textarea id="remarks" name="remarks" rows="2" placeholder="Any internal notes or references about this customer..."
                          class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none"></textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="<?= $baseUrl ?>/customers" class="rounded-xl px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                <i class="fa-solid fa-check-circle text-sm"></i> Save & Register Customer
            </button>
        </div>

    </form>
</div>

<script src="<?= $baseUrl ?>/assets/js/camera-capture.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
