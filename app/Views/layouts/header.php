<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - LMS' : 'Loan Management System' ?></title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d',
                        },
                        gold: {
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                        },
                        silver: {
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- LMS Professional Navy Theme CSS -->
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        [x-cloak] { display: none !important; }
        /* Custom smooth scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ==========================================================================
           PROPER DUAL-THEME ENGINE (LIGHT & DARK MODE)
           ========================================================================== */

        /* --------------------------------------------------------------------------
           1. DARK MODE SPECIFICATIONS (Rich Sleek Dark Aesthetic)
           -------------------------------------------------------------------------- */
        body.dark {
            background-color: #090d16 !important;
            color: #f8fafc !important;
        }

        body.dark header {
            background-color: rgba(15, 23, 42, 0.95) !important;
            border-bottom-color: #1e293b !important;
        }

        body.dark aside {
            background-color: #0f172a !important;
            border-right-color: #1e293b !important;
        }

        body.dark aside a {
            color: #94a3b8 !important;
        }

        body.dark aside a:hover {
            background-color: rgba(30, 41, 59, 0.8) !important;
            color: #f8fafc !important;
        }

        body.dark aside a.bg-amber-500\/10,
        body.dark aside a.bg-amber-500\/20 {
            background-color: rgba(245, 158, 11, 0.1) !important;
            color: #fbbf24 !important;
            border-color: rgba(245, 158, 11, 0.2) !important;
        }

        body.dark .bg-slate-955,
        body.dark .bg-slate-950,
        body.dark .bg-slate-900 {
            background-color: #0f172a !important;
            border-color: #1e293b !important;
        }

        body.dark .bg-slate-800 {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark input,
        body.dark select,
        body.dark textarea {
            background-color: #020617 !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        body.dark input::placeholder,
        body.dark textarea::placeholder {
            color: #64748b !important;
        }

        body.dark thead {
            background-color: #020617 !important;
            color: #94a3b8 !important;
            border-bottom: 2px solid #1e293b !important;
        }

        body.dark tbody tr:hover {
            background-color: rgba(30, 41, 59, 0.4) !important;
        }

        /* --------------------------------------------------------------------------
           2. LIGHT MODE SPECIFICATIONS (Crisp High-Contrast Aesthetic)
           -------------------------------------------------------------------------- */
        body.light {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        /* Header Bar */
        body.light header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
        }
        body.light header span.text-white,
        body.light header span.text-slate-200 {
            color: #0f172a !important;
        }

        /* Sidebar Navigation */
        body.light aside {
            background-color: #ffffff !important;
            border-right: 1px solid #e2e8f0 !important;
            box-shadow: 1px 0 3px 0 rgba(0, 0, 0, 0.03) !important;
        }
        body.light aside a {
            color: #334155 !important;
        }
        body.light aside a:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        body.light aside a.bg-amber-500\/10,
        body.light aside a.bg-amber-500\/20 {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
            border-color: #fde68a !important;
            font-weight: 700 !important;
        }

        /* Page Headers & Card Banners */
        body.light [class*="bg-gradient-to"] {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%) !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        body.light [class*="bg-gradient-to"] h1,
        body.light [class*="bg-gradient-to"] h2,
        body.light [class*="bg-gradient-to"] h3,
        body.light [class*="bg-gradient-to"] p,
        body.light [class*="bg-gradient-to"] span {
            color: #0f172a !important;
        }

        /* Cards & Sub-Containers */
        body.light .bg-slate-955,
        body.light .bg-slate-950,
        body.light .bg-slate-900,
        body.light .bg-slate-900\/90,
        body.light .bg-slate-900\/80,
        body.light .bg-slate-900\/60,
        body.light .bg-slate-900\/40,
        body.light .bg-slate-950\/90,
        body.light .bg-slate-950\/85,
        body.light .bg-slate-950\/80,
        body.light .bg-slate-950\/70,
        body.light .bg-slate-950\/60 {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04) !important;
        }

        body.light .bg-slate-800,
        body.light .bg-slate-800\/80,
        body.light .bg-slate-800\/60,
        body.light .bg-slate-800\/40,
        body.light .bg-slate-800\/20 {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }

        /* High-Contrast Typography */
        body.light h1, body.light h2, body.light h3, body.light h4, body.light h5, body.light h6,
        body.light .text-white,
        body.light .text-slate-100,
        body.light .text-slate-200,
        body.light .text-slate-300 {
            color: #0f172a !important;
        }

        body.light .text-slate-400 {
            color: #475569 !important;
        }

        body.light .text-slate-500 {
            color: #64748b !important;
        }

        /* High-Contrast Vibrant Accent Text Colors in Light Mode */
        body.light .text-yellow-400,
        body.light .text-amber-400 {
            color: #d97706 !important; /* Deep Rich Amber */
        }
        body.light .text-emerald-400 {
            color: #059669 !important; /* Deep Emerald */
        }
        body.light .text-blue-400 {
            color: #2563eb !important; /* Deep Blue */
        }
        body.light .text-indigo-400 {
            color: #4f46e5 !important; /* Deep Indigo */
        }
        body.light .text-purple-400 {
            color: #9333ea !important; /* Deep Purple */
        }
        body.light .text-rose-400 {
            color: #e11d48 !important; /* Deep Rose */
        }

        /* Table Headers & Rows */
        body.light thead,
        body.light table thead tr {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        body.light thead th {
            color: #475569 !important;
            font-weight: 700 !important;
        }
        body.light tbody tr {
            border-bottom: 1px solid #f1f5f9 !important;
        }
        body.light tbody tr:hover {
            background-color: #f8fafc !important;
        }
        body.light tbody td {
            color: #1e293b !important;
        }

        /* Inputs, Selects & Forms */
        body.light input,
        body.light select,
        body.light textarea {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
        }
        body.light input::placeholder,
        body.light textarea::placeholder {
            color: #94a3b8 !important;
        }

        /* Action Buttons */
        body.light .bg-amber-500 {
            background-color: #eab308 !important;
            color: #0f172a !important;
        }
        body.light .bg-emerald-500 {
            background-color: #10b981 !important;
            color: #ffffff !important;
        }
    </style>

    <script>
        // Apply initial theme immediately to prevent FOUC
        (function() {
            const theme = localStorage.getItem('lms_theme') || 'light';
            document.documentElement.classList.add(theme);
        })();
    </script>
</head>
<body class="h-full font-sans antialiased flex flex-col light"
      x-data="{ sidebarOpen: false, theme: localStorage.getItem('lms_theme') || 'light' }"
      x-init="
        document.body.className = 'h-full font-sans antialiased flex flex-col ' + theme;
        $watch('theme', val => {
            localStorage.setItem('lms_theme', val);
            document.body.className = 'h-full font-sans antialiased flex flex-col ' + val;
            document.documentElement.className = 'h-full ' + val;
        });
      ">
