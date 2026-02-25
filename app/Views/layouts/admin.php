<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin — CheckISO') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .admin-navbar { background: #1a1a2e; height: 56px; }
        .admin-navbar .navbar-brand { color: #fff; font-weight: 700; letter-spacing: .03em; }
        .admin-navbar .badge-admin { background: #e63946; font-size: .65rem; vertical-align: middle; }
        .sidebar { width: 220px; min-height: calc(100vh - 56px); background: #16213e; }
        .sidebar .nav-link { color: #adb5bd; border-radius: .375rem; padding: .5rem .75rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar .section-label { font-size: .65rem; letter-spacing: .08em; text-transform: uppercase; color: #6c757d; padding: .75rem .75rem .25rem; }
        main { flex: 1; }
    </style>
</head>
<body>

<nav class="admin-navbar navbar px-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <a class="navbar-brand" href="<?= site_url('admin') ?>">CheckISO</a>
        <span class="badge badge-admin">PLATFORM ADMIN</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small"><?= esc(session()->get('user_email')) ?></span>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-light btn-sm">← App</a>
        <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="d-flex" style="min-height: calc(100vh - 56px);">
    <aside class="sidebar p-2 d-flex flex-column gap-1">
        <div class="section-label">Platform</div>
        <a href="<?= site_url('admin') ?>" class="nav-link <?= uri_string() === 'admin' ? 'active' : '' ?>">
            📊 Dashboard
        </a>
        <div class="section-label mt-2">Management</div>
        <a href="<?= site_url('admin/tenants') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/tenants') ? 'active' : '' ?>">
            🏢 Organisations
        </a>
        <a href="<?= site_url('admin/users') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/users') ? 'active' : '' ?>">
            👤 Users
        </a>
        <div class="section-label mt-2">ISO Content</div>
        <a href="<?= site_url('admin/catalog') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/catalog') ? 'active' : '' ?>">
            📚 Catalogue
        </a>
        <a href="<?= site_url('admin/gap') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/gap') ? 'active' : '' ?>">
            🔍 Gap Sessions
        </a>
    </aside>

    <main class="p-4">
        <?php if ($flash = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 mb-3"><?= esc($flash) ?></div>
        <?php endif ?>
        <?= $this->renderSection('content') ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
