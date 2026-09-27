<?php
$pageTitle = 'Change Admin Password';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="max-w-2xl mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2.5">
                <i class="fa-solid fa-key text-amber-400"></i> Change Password
            </h1>
            <p class="text-xs text-slate-400 mt-1">Update your administrator account password</p>
        </div>
        <a href="<?= $baseUrl ?>/dashboard" class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Dashboard
        </a>
    </div>

    <!-- Form Container -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-xl">
        <form action="<?= $baseUrl ?>/change-password" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label for="current_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full rounded-xl border border-slate-700 bg-slate-950 py-3 px-4 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            </div>

            <div>
                <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6"
                       class="w-full rounded-xl border border-slate-700 bg-slate-950 py-3 px-4 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
                <p class="text-[11px] text-slate-500 mt-1">Minimum 6 characters long</p>
            </div>

            <div>
                <label for="confirm_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                       class="w-full rounded-xl border border-slate-700 bg-slate-950 py-3 px-4 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="<?= $baseUrl ?>/dashboard" class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition">Cancel</a>
                <button type="submit" class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-amber-500/20 hover:bg-amber-400 transition">
                    <i class="fa-solid fa-check mr-1.5"></i> Update Password
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
