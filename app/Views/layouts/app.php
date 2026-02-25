<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'CheckISO') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .navbar-brand { letter-spacing: .03em; }
        .sidebar { width: 220px; min-height: calc(100vh - 56px); background: #fff; border-right: 1px solid #e8ecf0; }
        .sidebar .nav-link { color: #495057; border-radius: .375rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #e9f0ff; color: #0d6efd; }
        main { flex: 1; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-3" style="height:56px;">
    <a class="navbar-brand fw-bold" href="/dashboard">CheckISO</a>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small"><?= esc(session()->get('user_email')) ?></span>
        <span class="text-white fw-semibold small"><?= esc(session()->get('user_name')) ?></span>
        <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="d-flex" style="min-height: calc(100vh - 56px);">
    <aside class="sidebar p-3 d-flex flex-column gap-1">
        <a href="<?= site_url('dashboard') ?>" class="nav-link px-3 py-2 <?= uri_string() === 'dashboard' ? 'active' : '' ?>">
            🏠 Dashboard
        </a>
        <div class="text-uppercase text-muted px-3 mt-3 mb-1" style="font-size:.68rem;letter-spacing:.07em;">Organisation</div>
        <a href="<?= site_url('org/members') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'org/members') ? 'active' : '' ?>">
            👥 Members
        </a>
        <?php if (session()->get('role_code') === 'org.admin'): ?>
        <a href="<?= site_url('org/requests') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'org/requests') ? 'active' : '' ?>">
            📋 Join requests
        </a>
        <a href="<?= site_url('org/settings') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'org/settings') ? 'active' : '' ?>">
            ⚙️ Settings
        </a>
        <?php endif ?>
        <?php if (session()->get('is_platform_admin')): ?>
        <div class="text-uppercase text-muted px-3 mt-3 mb-1" style="font-size:.68rem;letter-spacing:.07em;">Platform</div>
        <a href="<?= site_url('admin') ?>" class="nav-link px-3 py-2 text-danger <?= str_starts_with(uri_string(), 'admin') ? 'active' : '' ?>">
            🛡️ Admin panel
        </a>
        <?php endif ?>
    </aside>

    <main class="p-4">
        <?= $this->renderSection('content') ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
