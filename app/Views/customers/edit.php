<?php
$pageTitle = 'Edit Customer - ' . htmlspecialchars($customer['full_name']);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-user-pen text-amber-500"></i> Edit Customer Profile
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                <?= htmlspecialchars($customer['full_name']) ?> &bull; <span class="font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($customer['customer_id']) ?></span>
            </p>
        </div>
        <a href="<?= $baseUrl ?>/customers/<?= $customer['id'] ?>" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Profile
        </a>
    </div>

    <!-- Edit Form Card -->
    <form action="<?= $baseUrl ?>/customers/<?= $customer['id'] ?>/update" method="POST" enctype="multipart/form-data" class="space-y-6">
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
                    <input type="text" value="<?= htmlspecialchars($customer['customer_id']) ?>" readonly 
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-amber-600 dark:text-amber-400 font-mono font-bold cursor-not-allowed">
                </div>

                <!-- Account Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Account Number</label>
                    <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars($customer['account_number'] ?? '') ?>" readonly 
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 py-2.5 px-3.5 text-xs text-amber-600 dark:text-amber-400 font-mono font-bold cursor-not-allowed">
                    <span class="text-[10px] text-slate-500">Auto-generated account reference</span>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Customer Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                        <option value="Active" <?= $customer['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Closed" <?= $customer['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                        <option value="Blocked" <?= $customer['status'] === 'Blocked' ? 'selected' : '' ?>>Blocked</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="full_name" name="full_name" required value="<?= htmlspecialchars($customer['full_name']) ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Father / Spouse Name -->
                <div>
                    <label for="father_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Father / Guardian Name</label>
                    <input type="text" id="father_name" name="father_name" value="<?= htmlspecialchars($customer['father_name'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Profile Photo Upload / Live Camera -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Update Customer Photo (Upload or Live Camera Capture)</label>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <?php if (!empty($customer['photo'])): ?>
                        <div class="relative shrink-0">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($customer['photo']) ?>" alt="Current Photo" class="h-12 w-12 rounded-xl object-cover border border-slate-300 dark:border-slate-700 shadow-2xs">
                            <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-[9px] text-white font-bold" title="Current Active Photo">✓</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-2 flex-1 w-full">
                        <input type="file" id="customer_edit_photo_input" name="photo" accept="image/jpeg,image/png,image/webp"
                               onchange="const h = document.getElementById('customer_edit_cam_base64'); if (h && this.files && this.files.length) h.value = '';"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-950">
                        
                        <button type="button" 
                                onclick="window.openCameraModal({ fileInput: 'customer_edit_photo_input', hiddenInput: 'customer_edit_cam_base64', previewImg: 'customer_edit_photo_preview', onCapture: () => document.getElementById('customer_edit_preview_box').classList.remove('hidden') })"
                                class="shrink-0 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-slate-950 px-3.5 py-2 text-xs font-bold shadow transition flex items-center gap-1.5 cursor-pointer"
                                title="Take Photo Directly with Device Camera">
                            <i class="fa-solid fa-camera"></i>
                            <span>Live Camera</span>
                        </button>
                    </div>
                </div>

                <input type="hidden" id="customer_edit_cam_base64" name="photo_camera_base64" value="">

                <div id="customer_edit_preview_box" class="mt-2.5 hidden flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <img id="customer_edit_photo_preview" src="" alt="Customer Photo Preview" class="h-16 w-16 object-cover rounded-xl border-2 border-amber-400">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> New Live Photo Captured
                        </span>
                        <p class="text-[11px] text-slate-400 mt-0.5">Will replace existing photo when saved</p>
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
                    <input type="text" id="mobile" name="mobile" required maxlength="15" value="<?= htmlspecialchars($customer['mobile']) ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Alternate Mobile -->
                <div>
                    <label for="alt_mobile" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alternate Mobile</label>
                    <input type="text" id="alt_mobile" name="alt_mobile" maxlength="15" value="<?= htmlspecialchars($customer['alt_mobile'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Aadhaar Card Number -->
                <div>
                    <label for="aadhaar" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Aadhaar Card Number</label>
                    <input type="text" id="aadhaar" name="aadhaar" maxlength="14" value="<?= htmlspecialchars($customer['aadhaar'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- PAN Card Number -->
                <div>
                    <label for="pan" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        PAN Card Number <span class="text-[11px] font-normal text-slate-400 dark:text-slate-500">(Optional)</span>
                    </label>
                    <input type="text" id="pan" name="pan" maxlength="10" value="<?= htmlspecialchars($customer['pan'] ?? '') ?>" placeholder="10-character PAN number (Optional)"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 uppercase focus:border-amber-500 focus:outline-none">
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
                <textarea id="address" name="address" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Village -->
                <div>
                    <label for="village" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Village / Locality</label>
                    <input type="text" id="village" name="village" value="<?= htmlspecialchars($customer['village'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">City / Town</label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($customer['city'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- State -->
                <div>
                    <label for="state" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">State</label>
                    <input type="text" id="state" name="state" value="<?= htmlspecialchars($customer['state'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Pincode -->
                <div>
                    <label for="pincode" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">PIN Code</label>
                    <input type="text" id="pincode" name="pincode" maxlength="10" value="<?= htmlspecialchars($customer['pincode'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Section 4: Default Guarantor Details -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 4: Default Guarantor Reference
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Guarantor Name -->
                <div>
                    <label for="guarantor_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Name</label>
                    <input type="text" id="guarantor_name" name="guarantor_name" value="<?= htmlspecialchars($customer['guarantor_name'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>

                <!-- Guarantor Mobile -->
                <div>
                    <label for="guarantor_mobile" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Mobile</label>
                    <input type="text" id="guarantor_mobile" name="guarantor_mobile" maxlength="15" value="<?= htmlspecialchars($customer['guarantor_mobile'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Guarantor Address -->
            <div>
                <label for="guarantor_address" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Guarantor Address</label>
                <textarea id="guarantor_address" name="guarantor_address" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none"><?= htmlspecialchars($customer['guarantor_address'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Section 5: Remarks -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-2">
                Section 5: Additional Remarks
            </h2>
            <div>
                <textarea id="remarks" name="remarks" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-slate-100 focus:border-amber-500 focus:outline-none"><?= htmlspecialchars($customer['remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="<?= $baseUrl ?>/customers/<?= $customer['id'] ?>" class="rounded-xl px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                <i class="fa-solid fa-save text-sm"></i> Save Profile Updates
            </button>
        </div>

    </form>
</div>

<script src="<?= $baseUrl ?>/assets/js/camera-capture.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
