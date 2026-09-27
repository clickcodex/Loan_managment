<?php
$pageTitle = 'Database Backup & Restore System';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="space-y-6" x-data="{ restoreModal: false, selectedRestoreId: null, selectedFilename: '', eraseModal: false }">

    <!-- Page Header & Title -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2.5">
                <i class="fa-solid fa-database text-amber-400"></i> Database Backup & Restore Engine
            </h1>
            <p class="text-xs text-slate-400 mt-1">Manual on-demand backups, SQL restoration, auto-download, and safety system reset</p>
        </div>

        <div class="flex items-center gap-2">
            <form action="<?= $baseUrl ?>/backups/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-amber-500/20 hover:bg-amber-400 transition">
                    <i class="fa-solid fa-download text-sm"></i> Take On-Demand Backup
                </button>
            </form>

            <button type="button" @click="eraseModal = true" class="inline-flex items-center gap-2 rounded-xl bg-rose-500/20 border border-rose-500/40 px-4 py-2.5 text-xs font-bold text-rose-400 hover:bg-rose-500 hover:text-white transition shadow-lg shadow-rose-500/10">
                <i class="fa-solid fa-trash-can text-sm"></i> Erase DB & Auto-Download Backup
            </button>
        </div>
    </div>

    <!-- Backup Status Reminder Alert (If Due) -->
    <?php if ($isDue): ?>
        <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 shadow-xl flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/20 text-rose-400 font-bold text-lg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Database Backup Reminder Alert</h3>
                    <p class="text-xs text-rose-300/80">
                        <?= $lastBackup ? 'Last backup was generated on ' . date('M d, Y', strtotime($lastBackup)) . ' (More than 7 days ago).' : 'No database backup has been taken yet for this system.' ?>
                        Generating regular backups prevents data loss.
                    </p>
                </div>
            </div>
            <form action="<?= $baseUrl ?>/backups/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-xs font-bold text-white hover:bg-rose-600 transition whitespace-nowrap">
                    Backup Now
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Last Backup Date</span>
            <div class="text-base font-extrabold text-white font-mono">
                <?= $lastBackup ? date('M d, Y - h:i A', strtotime($lastBackup)) : 'Never' ?>
            </div>
            <span class="text-[10px] text-slate-500 block">Most recent successful dump</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Backups Recorded</span>
            <div class="text-2xl font-black text-amber-400 font-mono"><?= count($backups) ?></div>
            <span class="text-[10px] text-slate-500 block">Stored in safety directory</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-1">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Backup Health Status</span>
            <div>
                <?php if ($isDue): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/20 px-3 py-1 text-xs font-bold text-rose-400 border border-rose-500/30">
                        <i class="fa-solid fa-clock-rotate-left"></i> Backup Due
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-400 border border-emerald-500/30">
                        <i class="fa-solid fa-check-circle"></i> Protected
                    </span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] text-slate-500 block">7-day backup threshold</span>
        </div>
    </div>

    <!-- Actions Section: 3 Feature Action Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Manual Backup Card -->
        <div class="rounded-3xl border border-amber-500/30 bg-gradient-to-r from-slate-900 via-slate-900 to-amber-950/30 p-6 space-y-4 shadow-xl">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-400 shrink-0">
                    <i class="fa-solid fa-file-export text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">Manual Database Dump</h2>
                    <p class="text-xs text-slate-400">Generates full SQL dump</p>
                </div>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                Inspects all tables, extracts schemas and row data, creating a standalone `.sql` backup file in the system.
            </p>

            <form action="<?= $baseUrl ?>/backups/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="w-full rounded-xl bg-amber-500 py-3 text-xs font-bold text-slate-950 shadow hover:bg-amber-400 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-database"></i> Generate Backup Now
                </button>
            </form>
        </div>

        <!-- Restore Uploaded SQL File Card -->
        <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-4 shadow-xl">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-500/20 text-blue-400 shrink-0">
                    <i class="fa-solid fa-file-import text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">Restore from SQL File</h2>
                    <p class="text-xs text-slate-400">Upload external `.sql` backup file</p>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/backups/upload-restore" method="POST" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Select .sql File <span class="text-rose-400">*</span></label>
                    <input type="file" name="sql_file" accept=".sql" required
                           class="block w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-amber-400 hover:file:bg-slate-700 cursor-pointer">
                </div>

                <button type="submit" onclick="return confirm('WARNING: Restoring from an uploaded file will overwrite existing database records! Are you sure you want to proceed?')"
                        class="w-full rounded-xl border border-slate-700 bg-slate-800 py-2.5 text-xs font-bold text-slate-200 hover:bg-slate-700 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-rotate-left text-amber-400"></i> Upload & Restore
                </button>
            </form>
        </div>

        <!-- Erase Complete Database Card -->
        <div class="rounded-3xl border border-rose-500/40 bg-gradient-to-r from-slate-900 via-slate-900 to-rose-950/30 p-6 space-y-4 shadow-xl">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-400 shrink-0">
                    <i class="fa-solid fa-trash-can text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-rose-400">Erase DB & Auto Backup</h2>
                    <p class="text-xs text-rose-300/70">Wipe operational data safely</p>
                </div>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                Generates a fresh SQL backup file, triggers an <strong class="text-amber-400">automatic file download</strong> to your PC, and resets operational database tables.
            </p>

            <button type="button" @click="eraseModal = true"
                    class="w-full rounded-xl bg-rose-500/20 border border-rose-500/40 py-3 text-xs font-bold text-rose-300 hover:bg-rose-500 hover:text-white transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Erase Database & Auto Download
            </button>
        </div>

    </div>

    <!-- Backup History Log Directory Table -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900 overflow-hidden shadow-xl space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-amber-400"></i> Backup File History Directory
            </h2>
            <span class="text-xs text-slate-500 font-mono"><?= count($backups) ?> Backup Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Filename</th>
                        <th class="py-3 px-4">Size</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                    <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                No database backup files recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($backups as $b): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-400">
                                    <i class="fa-solid fa-file-code text-slate-500 mr-1.5"></i>
                                    <?= htmlspecialchars($b['filename']) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-300">
                                    <?= number_format($b['file_size'] / 1024, 2) ?> KB
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold bg-slate-800 text-slate-300">
                                        <?= htmlspecialchars($b['backup_type']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    <?= date('Y-m-d H:i:s', strtotime($b['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($b['physical_exists']): ?>
                                        <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-bold text-emerald-400 border border-emerald-500/30">Available</span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-rose-500/20 px-2.5 py-0.5 text-[10px] font-bold text-rose-400 border border-rose-500/30">File Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <?php if ($b['physical_exists']): ?>
                                        <!-- Download -->
                                        <a href="<?= $baseUrl ?>/backups/<?= $b['id'] ?>/download" 
                                           class="inline-flex items-center gap-1 rounded-lg bg-amber-500/20 px-2.5 py-1 text-xs font-bold text-amber-400 border border-amber-500/30 hover:bg-amber-500/30 transition">
                                            <i class="fa-solid fa-download"></i> Download
                                        </a>

                                        <!-- Restore -->
                                        <button type="button" @click="restoreModal = true; selectedRestoreId = <?= $b['id'] ?>; selectedFilename = '<?= htmlspecialchars($b['filename']) ?>'"
                                                class="inline-flex items-center gap-1 rounded-lg bg-blue-500/20 px-2.5 py-1 text-xs font-bold text-blue-400 border border-blue-500/30 hover:bg-blue-500/30 transition">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                    <?php endif; ?>

                                    <!-- Delete -->
                                    <form action="<?= $baseUrl ?>/backups/<?= $b['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Delete backup log and file?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="rounded-lg bg-slate-800 p-1 px-2 text-slate-400 hover:text-rose-400 transition">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation Restore Modal -->
    <div x-show="restoreModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-rose-500/40 bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="restoreModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-rose-400 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i> Confirm Database Restore
                </h3>
                <button @click="restoreModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed">
                Are you sure you want to restore the database from backup <span class="font-mono font-bold text-amber-400" x-text="selectedFilename"></span>?
            </p>
            <p class="text-[11px] text-rose-400 bg-rose-500/10 p-3 rounded-xl border border-rose-500/20">
                ⚠️ WARNING: Restoring a backup will overwrite existing customer, loan, ledger, and payment records in the database with the state of the backup file!
            </p>

            <form :action="'<?= $baseUrl ?>/backups/' + selectedRestoreId + '/restore'" method="POST" class="flex items-center justify-end gap-3 pt-2">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="button" @click="restoreModal = false" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400">Cancel</button>
                <button type="submit" class="rounded-xl bg-rose-500 px-5 py-2 text-xs font-bold text-white hover:bg-rose-600 shadow-lg">
                    Confirm & Restore Database
                </button>
            </form>
        </div>
    </div>

    <!-- Confirmation Erase & Reset Modal -->
    <div x-show="eraseModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-md">
        <div class="w-full max-w-lg rounded-3xl border-2 border-rose-500 bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="eraseModal = false">
            <div class="flex items-center justify-between border-b border-rose-500/30 pb-3">
                <h3 class="text-base font-extrabold text-rose-500 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i> ERASE COMPLETE DATABASE (SAFETY RESET)
                </h3>
                <button @click="eraseModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="space-y-2 text-xs text-slate-300">
                <p class="font-bold text-white">This process will execute the following automatic safety workflow:</p>
                <ol class="list-decimal list-inside space-y-1.5 text-slate-300 font-medium bg-slate-950 p-3 rounded-2xl border border-slate-800">
                    <li><strong class="text-amber-400">Automatic Backup:</strong> A complete <code>.sql</code> backup dump of current data will be generated.</li>
                    <li><strong class="text-amber-400">Automatic Download:</strong> Your browser will automatically download the safety <code>.sql</code> backup file to your computer.</li>
                    <li><strong class="text-rose-400">System Wipe:</strong> Operational tables (Customers, Loans, Collateral Items, Payments, Ledger) will be cleared. Admin credentials & system settings are safely preserved.</li>
                </ol>
            </div>

            <p class="text-[11px] font-bold text-rose-400 bg-rose-500/10 p-3 rounded-xl border border-rose-500/30">
                ⚠️ CRITICAL DANGER: This action will erase all operational data from the system. You can restore this data anytime using the downloaded <code>.sql</code> file!
            </p>

            <form action="<?= $baseUrl ?>/backups/erase" method="POST" class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="button" @click="eraseModal = false" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">Cancel</button>
                <button type="submit" class="rounded-xl bg-rose-600 px-6 py-2.5 text-xs font-black text-white hover:bg-rose-500 shadow-xl shadow-rose-600/30">
                    <i class="fa-solid fa-trash-can mr-1"></i> Confirm Erase & Download Backup
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
