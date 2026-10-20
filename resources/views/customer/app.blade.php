<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="robots" content="noindex, nofollow">
    <link rel="manifest" href="/customer/manifest.webmanifest">
    <link rel="icon" href="/customer/icon.svg" type="image/svg+xml">
    <title>LaundryLink</title>
    <link rel="stylesheet" href="/customer/app.css?v={{ filemtime(public_path('customer/app.css')) }}">
</head>
<body>
    <header class="top">
        <span class="logo-mark">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
        </span>
        <span class="logo">LaundryLink</span>
    </header>

    <main id="app" class="view">
        <p class="muted center">Memuat...</p>
    </main>

    <nav id="nav" class="nav" hidden>
        <a href="#/services" data-page="services">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
            <span>Layanan</span>
        </a>
        <a href="#/order" data-page="order">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span>Pesan</span>
        </a>
        <a href="#/history" data-page="history">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Riwayat</span>
        </a>
        <a href="#/account" data-page="account">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Akun</span>
        </a>
    </nav>

    <div id="toast" class="toast" hidden></div>

    <script src="/customer/app.js?v={{ filemtime(public_path('customer/app.js')) }}"></script>
</body>
</html>