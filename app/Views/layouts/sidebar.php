<?php
$currentRoute = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$base = defined('BASE_URL') ? BASE_URL : '';

// Helper to check active link
function isNavActive($route, $currentRoute) {
    if ($route === 'dashboard' && ($currentRoute === 'dashboard' || $currentRoute === 'home' || $currentRoute === '')) {
        return true;
    }
    return !empty($route) && strpos($currentRoute, $route) !== false;
}
?>

<!-- Top Fixed Header -->
<header class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 px-4 backdrop-blur sm:px-6 shadow-xs">
    <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Toggle Button -->
        <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white lg:hidden">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <!-- App Logo & Name -->
        <a href="<?= $base ?>/dashboard" class="flex items-center gap-2.5">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-yellow-400 text-slate-950 font-bold shadow-md shadow-amber-500/20">
                <i class="fa-solid fa-coins text-xl"></i>
            </div>
            <div>
                <span class="block text-base font-black tracking-tight text-slate-900 dark:text-white leading-tight">Golden Trust</span>
                <span class="block text-xs font-bold text-amber-600 dark:text-amber-400 tracking-wider">LMS v2.0</span>
            </div>
        </a>
    </div>

    <!-- Quick Global Search, Theme Switcher & Admin Profile Dropdown -->
    <div class="flex items-center gap-3">
        <!-- Quick Search Form -->
        <form action="<?= $base ?>/search" method="GET" class="hidden sm:flex items-center">
            <div class="relative w-64">
                <input type="text" name="q" placeholder="Search loans, RK#, customer..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 py-1.5 pl-8 pr-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-xs text-amber-500"></i>
            </div>
        </form>

        <!-- Light / Dark Theme Switcher Button -->
        <button type="button" @click="theme = (theme === 'light' ? 'dark' : 'light')"
                class="rounded-full border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-2 text-xs font-bold text-amber-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-center h-9 w-9 shadow-xs"
                title="Toggle Light / Dark Color Theme">
            <i x-show="theme === 'light'" class="fa-solid fa-moon text-indigo-500 text-sm"></i>
            <i x-show="theme === 'dark'" class="fa-solid fa-sun text-amber-400 text-sm"></i>
        </button>

        <!-- Admin Profile Menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 rounded-full border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-1 pr-3 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-xs font-bold text-white shadow">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <span class="hidden text-xs font-bold text-slate-900 dark:text-slate-100 md:inline-block">
                    <?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>
                </span>
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-500 dark:text-slate-400"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" x-cloak 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-48 origin-top-right rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-1 shadow-2xl z-50">
                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase">Logged in as</p>
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($currentUser['username'] ?? 'admin') ?></p>
                </div>
                <a href="<?= $base ?>/change-password" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <i class="fa-solid fa-key text-amber-500 w-4"></i> Change Password
                </a>
                <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                <form action="<?= $base ?>/logout" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition">
                        <i class="fa-solid fa-right-from-bracket w-4"></i> Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="flex flex-1 overflow-hidden">
    <!-- Sidebar Overlay (Mobile) -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" 
         class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"
         style="display: none;"></div>

    <!-- Sidebar Container -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-40 w-64 transform bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col justify-between pt-16 lg:pt-0 shadow-xs">
        
        <!-- Navigation Menu Links -->
        <nav class="flex-1 space-y-1 px-3 py-4 overflow-y-auto">
            
            <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Main Menu</p>

            <a href="<?= $base ?>/dashboard" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('dashboard', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-chart-pie text-sm text-amber-500"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= $base ?>/customers" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('customers', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-users text-sm text-blue-500"></i>
                <span>Customers</span>
            </a>

            <a href="<?= $base ?>/loans" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('loans', $currentRoute) && strpos($currentRoute, 'create') === false ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-hand-holding-dollar text-sm text-emerald-500"></i>
                <span>Loan Accounts</span>
            </a>

            <a href="<?= $base ?>/loans/create" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= strpos($currentRoute, 'loans/create') !== false ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-circle-plus text-sm text-amber-500"></i>
                <span>Add New Loan</span>
            </a>

            <a href="<?= $base ?>/loan-ledger" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('loan-ledger', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-book-bookmark text-sm text-amber-500"></i>
                <span>All Loans Ledger</span>
            </a>

            <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mt-6 mb-2">Operations</p>

            <a href="<?= $base ?>/search" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('search', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-magnifying-glass text-sm text-amber-500"></i>
                <span>Global Search</span>
            </a>

            <a href="<?= $base ?>/collateral" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('collateral', $currentRoute) && strpos($currentRoute, 'collateral/racks') === false ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-gem text-sm text-yellow-500"></i>
                <span>Collateral Vault</span>
            </a>

            <a href="<?= $base ?>/collateral/racks" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= strpos($currentRoute, 'collateral/racks') !== false ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-cubes text-sm text-amber-500"></i>
                <span>10-Rack Storage Visualizer</span>
            </a>

            <a href="<?= $base ?>/rates" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('rates', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-chart-line text-sm text-indigo-500"></i>
                <span>Daily Rates Management</span>
            </a>

            <a href="<?= $base ?>/payments" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('payments', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-receipt text-sm text-purple-500"></i>
                <span>Payments & Receipts</span>
            </a>

            <a href="<?= $base ?>/bills" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('bills', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-file-invoice-dollar text-sm text-teal-500"></i>
                <span>Billing System</span>
            </a>

            <a href="<?= $base ?>/transactions" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('transactions', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-scale-balanced text-sm text-emerald-500"></i>
                <span>Daily Credit & Debit</span>
            </a>

            <a href="<?= $base ?>/dues" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('dues', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-calendar-check text-sm text-rose-500"></i>
                <span>Due Sheet & Alerts</span>
            </a>

            <a href="<?= $base ?>/documents" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('documents', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-folder-open text-sm text-indigo-500"></i>
                <span>Documents Vault</span>
            </a>

            <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mt-6 mb-2">Reports & System</p>

            <a href="<?= $base ?>/reports" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('reports', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-file-invoice text-sm text-cyan-500"></i>
                <span>17 System Reports</span>
            </a>

            <a href="<?= $base ?>/backups" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('backups', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-database text-sm text-emerald-500"></i>
                <span>Backup & Restore</span>
            </a>

            <a href="<?= $base ?>/audit-logs" 
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all <?= isNavActive('audit-logs', $currentRoute) ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' ?>">
                <i class="fa-solid fa-shield-halved text-sm text-orange-500"></i>
                <span>Audit Logs & Security</span>
            </a>

        </nav>

        <!-- Footer Info -->
        <div class="border-t border-slate-200 dark:border-slate-800 p-4 font-mono text-[10px] text-slate-400 dark:text-slate-500">
            <span class="block text-slate-700 dark:text-slate-300 font-bold">LMS v2.0 System</span>
            <span class="block text-slate-400 dark:text-slate-500">&copy; 2026 Golden Trust Finance</span>
        </div>

    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
        <?php if ($flashError = \App\Helpers\Session::getFlash('error')): ?>
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/40 p-4 text-xs font-bold text-rose-700 dark:text-rose-300 shadow-sm">
                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess = \App\Helpers\Session::getFlash('success')): ?>
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-xs font-bold text-emerald-700 dark:text-emerald-300 shadow-sm">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashInfo = \App\Helpers\Session::getFlash('info')): ?>
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-blue-200 dark:border-blue-500/30 bg-blue-50 dark:bg-blue-950/40 p-4 text-xs font-bold text-blue-700 dark:text-blue-300 shadow-sm">
                <i class="fa-solid fa-circle-info text-lg"></i>
                <span><?= htmlspecialchars($flashInfo) ?></span>
            </div>
        <?php endif; ?>
