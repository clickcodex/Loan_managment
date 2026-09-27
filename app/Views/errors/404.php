<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full font-sans antialiased flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-400 border border-amber-500/20 text-3xl mb-6">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight">404</h1>
        <p class="text-lg font-semibold text-slate-300 mt-2">Page Not Found</p>
        <p class="text-xs text-slate-500 mt-1 mb-6">The requested page or endpoint does not exist on this server.</p>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/dashboard" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
            <i class="fa-solid fa-house"></i> Return to Dashboard
        </a>
    </div>
</body>
</html>