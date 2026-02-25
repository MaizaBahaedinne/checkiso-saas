<!DOCTYPE html>
<html lang="<?= service('language')->getLocale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'CheckISO') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; }
        .auth-wrapper { max-width: 460px; width: 100%; padding: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="auth-wrapper">
        <div class="text-center mb-4">
            <h1 class="h3 fw-bold text-primary">CheckISO</h1>
            <p class="text-muted small mb-0"><?= lang('Auth.subtitle') ?></p>
        </div>

        <!-- Language switcher -->
        <?php $currentLang = session()->get('lang') ?? 'fr'; ?>
        <div class="text-center mb-3">
            <a href="<?= site_url('lang/fr') ?>"
               class="btn btn-sm <?= $currentLang === 'fr' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="Français">FR</a>
            <a href="<?= site_url('lang/en') ?>"
               class="btn btn-sm <?= $currentLang === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>"
               title="English">EN</a>
        </div>

        <?= $this->renderSection('content') ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
