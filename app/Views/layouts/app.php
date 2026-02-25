<!DOCTYPE html>
<html lang="<?= service('language')->getLocale() ?>">
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

<?php $currentLang = session()->get('lang') ?? 'fr'; ?>

<nav class="navbar navbar-dark bg-primary px-3" style="height:56px;">
    <a class="navbar-brand fw-bold" href="/dashboard">CheckISO</a>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small"><?= esc(session()->get('user_email')) ?></span>
        <a href="<?= site_url('profile') ?>" class="text-white fw-semibold small text-decoration-none" title="<?= lang('Nav.profile') ?>">
            <?= esc(session()->get('user_name')) ?>
        </a>
        <!-- Language switcher -->
        <div class="d-flex gap-1">
            <a href="<?= site_url('lang/fr') ?>"
               class="btn btn-sm <?= $currentLang === 'fr' ? 'btn-light' : 'btn-outline-light' ?>"
               title="Français">FR</a>
            <a href="<?= site_url('lang/en') ?>"
               class="btn btn-sm <?= $currentLang === 'en' ? 'btn-light' : 'btn-outline-light' ?>"
               title="English">EN</a>
        </div>
        <a href="/logout" class="btn btn-outline-light btn-sm"><?= lang('Nav.logout') ?></a>
    </div>
</nav>

<div class="d-flex" style="min-height: calc(100vh - 56px);">
    <aside class="sidebar p-3 d-flex flex-column gap-1">
        <a href="<?= site_url('dashboard') ?>" class="nav-link px-3 py-2 <?= uri_string() === 'dashboard' ? 'active' : '' ?>">
            🏠 <?= lang('Nav.dashboard') ?>
        </a>
        <a href="<?= site_url('profile') ?>" class="nav-link px-3 py-2 <?= uri_string() === 'profile' ? 'active' : '' ?>">
            👤 <?= lang('Nav.profile') ?>
        </a>
        <div class="text-uppercase text-muted px-3 mt-3 mb-1" style="font-size:.68rem;letter-spacing:.07em;"><?= lang('Nav.organisation') ?></div>
        <a href="<?= site_url('org/members') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'org/members') ? 'active' : '' ?>">
            👥 <?= lang('Nav.members') ?>
        </a>
        <a href="<?= site_url('catalog') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'catalog') ? 'active' : '' ?>">
            📚 <?= lang('Nav.standards') ?>
        </a>
        <a href="<?= site_url('gap') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'gap') ? 'active' : '' ?>">
            📊 <?= lang('Nav.gap_analysis') ?>
        </a>
        <?php if (session()->get('role_code') === 'org.admin'): ?>
        <a href="<?= site_url('org/requests') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'org/requests') ? 'active' : '' ?>">
            📋 <?= lang('Nav.join_requests') ?>
        </a>
        <a href="<?= site_url('org/settings') ?>" class="nav-link px-3 py-2 <?= str_starts_with(uri_string(), 'org/settings') ? 'active' : '' ?>">
            ⚙️ <?= lang('Nav.settings') ?>
        </a>
        <?php endif ?>
        <?php if (session()->get('is_platform_admin')): ?>
        <div class="text-uppercase text-muted px-3 mt-3 mb-1" style="font-size:.68rem;letter-spacing:.07em;"><?= lang('Nav.platform') ?></div>
        <a href="<?= site_url('admin') ?>" class="nav-link px-3 py-2 text-danger <?= str_starts_with(uri_string(), 'admin') ? 'active' : '' ?>">
            🛡️ <?= lang('Nav.admin_panel') ?>
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
