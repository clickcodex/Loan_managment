<?php
$pageTitle = 'Centralized Documents Vault';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ uploadModal: false, previewModal: false, previewSrc: '', previewName: '', isPdf: false, isImg: false, isVid: false }">

    <!-- Page Header & Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-folder-open text-indigo-500"></i> Centralized Documents Vault
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage KYC identities, Aadhaar, PAN, agreements, and video proof recordings</p>
        </div>

        <button @click="uploadModal = true" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition">
            <i class="fa-solid fa-cloud-arrow-up text-sm"></i> Upload Vault Document / Video
        </button>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-1 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Total Vault Files</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono"><?= number_format($stats['total'] ?? 0) ?></div>
            <span class="text-[10px] text-slate-500 block">Stored across all categories</span>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-1 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">KYC Documents</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono"><?= number_format($stats['kyc'] ?? 0) ?></div>
            <span class="text-[10px] text-slate-500 block">Aadhaar, PAN & Address</span>
        </div>

        <div class="rounded-2xl border border-purple-300 dark:border-purple-500/30 bg-purple-50 dark:bg-purple-950/20 p-4 space-y-1 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-purple-800 dark:text-purple-300 tracking-wider">Video Proof Recordings</span>
            <div class="text-2xl font-black text-purple-700 dark:text-purple-300 font-mono"><?= number_format($stats['videos'] ?? 0) ?></div>
            <span class="text-[10px] text-purple-700/80 dark:text-purple-400 block font-bold">Giving loan & loan closure videos</span>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-1 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Vault Storage Health</span>
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <i class="fa-solid fa-shield-check"></i> Encrypted Vault Safe
                </span>
            </div>
            <span class="text-[10px] text-slate-500 block">Local storage directory</span>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-sm">
        
        <!-- Category Filter Pills -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto text-xs">
                <?php
                    $cats = [
                        'all'                     => 'All Documents',
                        'video'                   => '📹 Video Proofs',
                        'photo'                   => 'Customer Photo',
                        'aadhaar'                 => 'Aadhaar Card',
                        'pan'                     => 'PAN Card',
                        'address proof'           => 'Address Proof',
                        'loan agreement'          => 'Loan Agreement',
                        'valuation certificate'   => 'Valuation Cert'
                    ];
                ?>
                <?php foreach ($cats as $k => $lbl): ?>
                    <a href="<?= $baseUrl ?>/documents?category=<?= $k ?>&customer_id=<?= $customerId ?>&search=<?= urlencode($search) ?>"
                       class="rounded-xl px-3 py-1.5 font-bold transition whitespace-nowrap <?= $category === $k ? 'bg-indigo-600 text-white shadow' : 'bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' ?>">
                        <?= $lbl ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Form -->
            <form action="<?= $baseUrl ?>/documents" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                
                <select name="customer_id" onchange="this.form.submit()" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 px-3 text-xs text-slate-900 dark:text-white font-bold">
                    <option value="0">All Customers</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="relative w-48">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search document..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-1.5 pl-8 pr-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-slate-400 text-xs"></i>
                </div>
                <button type="submit" class="rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-200">Filter</button>
            </form>
        </div>

        <!-- Documents Directory Grid -->
        <?php if (empty($documents)): ?>
            <div class="py-12 text-center text-slate-500 space-y-2">
                <i class="fa-solid fa-folder-open text-4xl text-slate-400"></i>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No documents found in vault</p>
                <p class="text-xs text-slate-500">Click "Upload Vault Document / Video" to add customer KYC files or video recordings.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($documents as $doc): ?>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-4 space-y-3 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between">
                        
                        <!-- Header Badge & Customer -->
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="rounded-lg px-2.5 py-0.5 text-[10px] font-bold uppercase truncate max-w-[170px] <?= $doc['is_video'] ? 'bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20' ?>">
                                    <?= htmlspecialchars($doc['document_type']) ?>
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono shrink-0"><?= date('d M Y', strtotime($doc['created_at'])) ?></span>
                            </div>
                            
                            <a href="<?= $baseUrl ?>/customers/<?= $doc['customer_id'] ?>" class="block text-xs font-bold text-slate-900 dark:text-white truncate hover:text-amber-600 dark:hover:text-amber-400">
                                <?= htmlspecialchars($doc['customer_name']) ?>
                            </a>
                            <span class="block text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($doc['cust_code']) ?> &bull; <?= htmlspecialchars($doc['customer_mobile']) ?></span>
                        </div>

                        <!-- Preview Thumbnail Box -->
                        <div class="h-32 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden relative group">
                            <?php if ($doc['is_video']): ?>
                                <video class="h-full w-full object-cover">
                                    <source src="<?= $baseUrl ?>/<?= htmlspecialchars($doc['file_path']) ?>" type="video/mp4">
                                </video>
                                <button type="button" @click="previewSrc = '<?= $baseUrl ?>/<?= htmlspecialchars($doc['file_path']) ?>'; previewName = '<?= htmlspecialchars(addslashes($doc['original_name'])) ?>'; isPdf = false; isImg = false; isVid = true; previewModal = true;"
                                        class="absolute inset-0 bg-slate-950/40 flex items-center justify-center text-white text-3xl opacity-90 group-hover:opacity-100 transition">
                                    <i class="fa-solid fa-circle-play text-purple-400 drop-shadow-lg"></i>
                                </button>
                            <?php elseif ($doc['is_image']): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($doc['file_path']) ?>" alt="Doc" class="h-full w-full object-cover">
                                <button type="button" @click="previewSrc = '<?= $baseUrl ?>/<?= htmlspecialchars($doc['file_path']) ?>'; previewName = '<?= htmlspecialchars(addslashes($doc['original_name'])) ?>'; isPdf = false; isImg = true; isVid = false; previewModal = true;"
                                        class="absolute inset-0 bg-slate-950/40 flex items-center justify-center text-white text-xl opacity-0 group-hover:opacity-100 transition">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            <?php elseif ($doc['is_pdf']): ?>
                                <div class="text-center p-3">
                                    <i class="fa-solid fa-file-pdf text-4xl text-rose-500 mb-1"></i>
                                    <span class="block text-[10px] text-slate-500 font-mono">PDF Document</span>
                                </div>
                                <button type="button" @click="previewSrc = '<?= $baseUrl ?>/<?= htmlspecialchars($doc['file_path']) ?>'; previewName = '<?= htmlspecialchars(addslashes($doc['original_name'])) ?>'; isPdf = true; isImg = false; isVid = false; previewModal = true;"
                                        class="absolute inset-0 bg-slate-950/40 flex items-center justify-center text-white text-xl opacity-0 group-hover:opacity-100 transition">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            <?php else: ?>
                                <i class="fa-solid fa-file-lines text-3xl text-slate-400"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="font-bold text-slate-700 dark:text-slate-300 truncate max-w-[120px]" title="<?= htmlspecialchars($doc['original_name']) ?>">
                                <?= htmlspecialchars($doc['original_name']) ?>
                            </span>

                            <div class="flex items-center gap-2">
                                <a href="<?= $baseUrl ?>/documents/<?= $doc['id'] ?>/download" title="Download File" class="text-amber-600 dark:text-amber-400 font-bold hover:underline">
                                    <i class="fa-solid fa-download"></i>
                                </a>

                                <form action="<?= $baseUrl ?>/documents/<?= $doc['id'] ?>/delete" method="POST" onsubmit="return confirm('Permanently delete this document from vault?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="text-slate-400 hover:text-rose-500">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Document Upload Modal -->
    <div x-show="uploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="uploadModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-amber-500"></i> Upload Vault Document / Video Proof
                </h3>
                <button @click="uploadModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="<?= $baseUrl ?>/documents/store" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label for="customer_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Customer <span class="text-rose-500">*</span></label>
                    <select id="customer_id" name="customer_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['customer_id']) ?> &bull; <?= htmlspecialchars($c['mobile']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="document_type" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Document Category</label>
                    <select id="document_type" name="document_type" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500 focus:outline-none">
                        <option value="Loan Sanction Video Proof (Disbursement)">📹 Loan Sanction Video Proof (Giving Loan / Disbursement)</option>
                        <option value="Loan Closure Video Proof (Collateral Release)">🎬 Loan Closure Video Proof (After Loan Complete / Release)</option>
                        <option value="Customer Photo">Customer Photo</option>
                        <option value="Aadhaar Card" selected>Aadhaar Card</option>
                        <option value="PAN Card">PAN Card</option>
                        <option value="Address Proof">Address Proof</option>
                        <option value="Bank Passbook / Cheque">Bank Passbook / Cheque</option>
                        <option value="Loan Agreement">Loan Agreement</option>
                        <option value="Valuation Certificate">Valuation Certificate</option>
                        <option value="Other Vault Document">Other Vault Document</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Attach Document / Proof File</label>
                    <div class="space-y-2.5">
                        <input type="file" id="vault_document_file" name="document_file" accept="image/*,application/pdf,video/*"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 cursor-pointer border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-950">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="uploadModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow">
                        Upload to Vault
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inline Preview Modal (Supports Video, Image & PDF) -->
    <div x-show="previewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-md">
        <div class="w-full max-w-4xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="previewModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" x-text="previewName"></h3>
                <button @click="previewModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="h-[75vh] w-full bg-black rounded-2xl overflow-hidden flex items-center justify-center">
                <template x-if="isVid">
                    <video controls autoplay class="max-h-full max-w-full">
                        <source :src="previewSrc" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </template>

                <template x-if="isImg">
                    <img :src="previewSrc" alt="Preview" class="max-h-full max-w-full object-contain">
                </template>

                <template x-if="isPdf">
                    <iframe :src="previewSrc" class="h-full w-full border-0"></iframe>
                </template>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
