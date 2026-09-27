<?php
$pageTitle = 'Collateral Details - ' . htmlspecialchars($item['item_name']);
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ uploadModal: false }">

    <!-- Item Header Banner -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?= htmlspecialchars($item['item_name']) ?></h1>
                <?php if ($item['item_type'] === 'GOLD'): ?>
                    <span class="rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-600 dark:text-yellow-400 border border-amber-500/30">GOLD COLLATERAL</span>
                <?php else: ?>
                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">SILVER COLLATERAL</span>
                <?php endif; ?>
                <?php if (!empty($item['rk_number'])): ?>
                    <span class="rounded-lg bg-purple-500/20 px-2.5 py-1 text-xs font-bold text-purple-700 dark:text-purple-300 font-mono border border-purple-500/30">
                        <i class="fa-solid fa-vault mr-1"></i> Storage: <?= htmlspecialchars($item['rk_number']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-600 dark:text-slate-400 mt-2 font-mono">
                <span>Pledged on Loan: <a href="<?= $baseUrl ?>/loans/<?= $item['loan_id'] ?>" class="text-amber-600 dark:text-amber-400 font-bold hover:underline"><?= htmlspecialchars($item['loan_number']) ?></a></span>
                <span>&bull;</span>
                <span>Customer: <a href="<?= $baseUrl ?>/customers/<?= $item['cust_id'] ?>" class="text-slate-900 dark:text-white font-bold hover:underline"><?= htmlspecialchars($item['customer_name']) ?></a></span>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button @click="uploadModal = true" class="rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
                <i class="fa-solid fa-camera mr-1.5"></i> Add Photo
            </button>
            <a href="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>/edit" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
                <i class="fa-solid fa-pen text-amber-500 mr-1.5"></i> Edit Specs
            </a>
            <a href="<?= $baseUrl ?>/collateral" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- Specifications & Linked Loan Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Item Specifications Card (2 cols) -->
        <div class="md:col-span-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-list-check"></i> Physical & Purity Specifications
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Quantity</span>
                    <span class="text-lg font-black text-slate-900 dark:text-white font-mono"><?= intval($item['quantity']) ?> pcs</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Gross Weight</span>
                    <span class="text-lg font-black text-slate-900 dark:text-white font-mono"><?= number_format($item['gross_weight'], 3) ?> g</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Stone Weight</span>
                    <span class="text-lg font-black text-slate-500 font-mono"><?= number_format($item['stone_weight'], 3) ?> g</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Net Weight</span>
                    <span class="text-lg font-black text-amber-600 dark:text-amber-400 font-mono"><?= number_format($item['net_weight'], 3) ?> g</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs pt-2">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Purity (%)</span>
                    <span class="text-sm font-bold text-amber-600 dark:text-amber-400 font-mono">
                        <?php
                            $purityPct = floatval($item['purity_percentage'] ?? 0);
                            echo $purityPct > 0 ? number_format($purityPct, 2) . '%' : htmlspecialchars($item['purity_preset'] ?? '—');
                        ?>
                        <?php if (!empty($item['purity_preset']) && $item['purity_preset'] !== 'Custom'): ?>
                            <span class="text-xs text-slate-400 font-normal ml-1">(<?= htmlspecialchars($item['purity_preset']) ?>)</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Calculated Valuation</span>
                    <?php $autoVal = floatval($item['market_value']); ?>
                    <span class="text-sm font-extrabold text-amber-600 dark:text-yellow-400 font-mono">₹<?= number_format($autoVal, 2) ?></span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <span class="block text-[10px] text-slate-500 font-bold uppercase">Active Valuation</span>
                    <?php $finalVal = floatval($item['manual_market_value_override'] ?: $item['market_value']); ?>
                    <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">₹<?= number_format($finalVal, 2) ?></span>
                    <?php if (!empty($item['manual_market_value_override'])): ?>
                        <span class="block text-[9px] text-amber-600 dark:text-amber-400 font-bold">(Overridden)</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 text-xs">
                <span class="block text-slate-500 font-bold mb-1">Item Notes & Hallmarks:</span>
                <p class="text-slate-700 dark:text-slate-300 italic bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                    <?= htmlspecialchars($item['remarks'] ?: 'No hallmarks or special notes recorded.') ?>
                </p>
            </div>
        </div>

        <!-- Photos & Vault Rack Specs Card (1 col) -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-camera"></i> Photos & Storage Rack
            </h3>

            <!-- Photo Gallery -->
            <?php if (empty($photos)): ?>
                <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-6 text-center text-slate-400 text-xs space-y-2">
                    <i class="fa-solid fa-image text-3xl text-slate-400"></i>
                    <p class="font-bold">No photos uploaded for this collateral item.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($photos as $photo): ?>
                        <div class="relative group rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 h-24 bg-slate-50 dark:bg-slate-950">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($photo['file_path']) ?>" alt="Collateral" class="h-full w-full object-cover">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-500/30 text-xs space-y-1">
                <span class="block text-[10px] text-purple-700 dark:text-purple-300 font-bold uppercase">Storage Rack Number</span>
                <span class="text-base font-black text-purple-800 dark:text-purple-300 font-mono"><?= htmlspecialchars($item['rk_number'] ?: 'Unassigned Rack') ?></span>
            </div>
        </div>

    </div>

    <!-- Add Photo Modal -->
    <div x-show="uploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="if (!document.getElementById('global-camera-modal')) uploadModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-camera text-amber-500"></i> Upload Collateral Photo
                </h3>
                <button @click="uploadModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="form-upload-collateral-photo" action="<?= $baseUrl ?>/collateral/<?= $item['id'] ?>/photos" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Select Photo File or Take Live Photo</label>
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <input type="file" id="collateral_photo_file" name="photo_file" accept="image/*"
                                   onchange="const h = document.getElementById('collateral_camera_base64'); if (h && this.files && this.files.length) h.value = '';"
                                   class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-950">
                            
                            <button type="button" 
                                    onclick="window.openCameraModal({ form: 'form-upload-collateral-photo', autoSubmit: true, fileInput: 'collateral_photo_file', hiddenInput: 'collateral_camera_base64', previewImg: 'collateral_camera_preview', onCapture: () => { const box = document.getElementById('collateral_preview_box'); if (box) box.classList.remove('hidden'); } })"
                                    class="shrink-0 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-slate-950 px-3.5 py-2 text-xs font-bold shadow transition flex items-center gap-1.5"
                                    title="Capture Live Photo with Device Camera">
                                <i class="fa-solid fa-camera"></i>
                                <span>Take Photo</span>
                            </button>
                        </div>

                        <input type="hidden" id="collateral_camera_base64" name="camera_photo_base64" value="">

                        <div id="collateral_preview_box" class="relative hidden p-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                            <img id="collateral_camera_preview" src="" alt="Captured Collateral Photo" class="h-16 w-16 object-cover rounded-lg border border-amber-400">
                            <div class="text-xs">
                                <span class="block font-bold text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-circle-check mr-1"></i> Live Photo Ready</span>
                                <span class="text-[11px] text-slate-400">Click Upload Photo to save</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="uploadModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" id="btn-submit-collateral-photo" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow">
                        Upload Photo
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/camera-capture.js?v=<?= time() ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
