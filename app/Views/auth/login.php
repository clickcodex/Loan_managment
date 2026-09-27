<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Loan Management System</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-950 font-sans antialiased flex items-center justify-center p-4 selection:bg-amber-500 selection:text-slate-950 relative overflow-hidden">

    <!-- Background Decorative Gradient Orbs -->
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10" x-data="{ showPassword: false }">

        <!-- Logo & Branding Header -->
        <div class="text-center mb-8">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 via-amber-400 to-yellow-300 text-slate-950 font-black shadow-xl shadow-amber-500/20 mb-4 transform hover:scale-105 transition-transform duration-300">
                <i class="fa-solid fa-coins text-3xl"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Golden Trust</h1>
            <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-amber-400">Loan Management System v2.0</p>
        </div>

        <!-- Login Card Container -->
        <div class="rounded-3xl border border-slate-800/80 bg-slate-900/90 p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
            
            <div class="mb-6 border-b border-slate-800 pb-4 text-center">
                <h2 class="text-lg font-bold text-white">Admin Authentication</h2>
                <p class="text-xs text-slate-400 mt-1">Sign in with your administrator credentials</p>
            </div>

            <!-- Flash Error Message -->
            <?php if (!empty($flashError)): ?>
                <div class="mb-6 flex items-center gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-medium text-rose-400">
                    <i class="fa-solid fa-circle-exclamation text-base"></i>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashSuccess)): ?>
                <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-medium text-emerald-400">
                    <i class="fa-solid fa-circle-check text-base"></i>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= $baseUrl ?>/login" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Username</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <input type="text" id="username" name="username" required autofocus
                               placeholder="Enter username"
                               class="w-full rounded-xl border border-slate-700/80 bg-slate-950/60 py-3 pl-10 pr-4 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 transition-all">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Password</label>
                    </div>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                               placeholder="Enter password"
                               class="w-full rounded-xl border border-slate-700/80 bg-slate-950/60 py-3 pl-10 pr-11 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 transition-all">
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300">
                            <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-500/20 focus:ring-offset-0">
                        <span class="text-xs text-slate-300 font-medium">Remember me on this device</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-amber-500 to-yellow-400 py-3.5 text-xs font-bold text-slate-950 uppercase tracking-wider shadow-lg shadow-amber-500/25 hover:from-amber-400 hover:to-yellow-300 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all duration-200 transform active:scale-[0.98]">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> Log In to Panel
                </button>
            </form>
        </div>

        <!-- Footer Notice -->
        <p class="text-center text-[11px] text-slate-500 mt-6">
            Protected & Audited Session &bull; LMS v2.0 Architecture
        </p>

    </div>

</body>
</html>
