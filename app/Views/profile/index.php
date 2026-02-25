<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$initials = strtoupper(
    substr($user['first_name'] ?? 'U', 0, 1) .
    substr($user['last_name']  ?? '',  0, 1)
);
$memberSince = isset($user['created_at'])
    ? date('d/m/Y', strtotime($user['created_at']))
    : '—';
$lastLogin = isset($user['last_login_at']) && $user['last_login_at']
    ? date('d/m/Y H:i', strtotime($user['last_login_at']))
    : lang('Profile.never');
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <!-- Avatar initials -->
    <div style="width:64px;height:64px;border-radius:50%;background:#0d6efd;
                display:flex;align-items:center;justify-content:center;
                font-size:1.5rem;font-weight:700;color:#fff;flex-shrink:0;">
        <?= esc($initials) ?>
    </div>
    <div>
        <h4 class="mb-0"><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></h4>
        <div class="text-muted small"><?= esc($user['email']) ?></div>
        <div class="text-muted small mt-1">
            <?= lang('Profile.member_since') ?>: <strong><?= $memberSince ?></strong>
            &nbsp;·&nbsp;
            <?= lang('Profile.last_login') ?>: <strong><?= $lastLogin ?></strong>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        ⚠️ <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="profileTabs">
    <li class="nav-item">
        <button class="nav-link <?= $active_tab === 'info' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-info">
            👤 <?= lang('Profile.tab_info') ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $active_tab === 'password' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-password">
            🔒 <?= lang('Profile.tab_password') ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $active_tab === 'lang' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-lang">
            🌐 <?= lang('Profile.tab_lang') ?>
        </button>
    </li>
</ul>

<div class="tab-content" style="max-width:520px;">

    <!-- ── Tab: Personal info ─────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'info' ? 'show active' : '' ?>" id="tab-info">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= site_url('profile/info') ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold"><?= lang('Profile.first_name') ?></label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold"><?= lang('Profile.last_name') ?></label>
                            <input type="text" name="last_name" class="form-control"
                                   value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold"><?= lang('Profile.email') ?></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= lang('Profile.save_info') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Tab: Password ─────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'password' ? 'show active' : '' ?>" id="tab-password">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= site_url('profile/password') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('Profile.current_password') ?></label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('Profile.new_password') ?></label>
                        <input type="password" name="new_password" class="form-control" required
                               autocomplete="new-password" minlength="8">
                        <div class="form-text"><?= lang('Profile.new_password_hint') ?></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold"><?= lang('Profile.confirm_password') ?></label>
                        <input type="password" name="confirm_password" class="form-control" required
                               autocomplete="new-password" minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= lang('Profile.save_password') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Tab: Language ─────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'lang' ? 'show active' : '' ?>" id="tab-lang">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <p class="text-muted mb-3"><?= lang('Profile.lang_label') ?></p>
                <form method="post" action="<?= site_url('profile/lang') ?>">
                    <?= csrf_field() ?>

                    <?php
                    $savedLang = $user['lang_preference'] ?? 'fr';
                    ?>
                    <div class="d-flex gap-3 mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="lang" id="lang_fr"
                                   value="fr" <?= $savedLang === 'fr' ? 'checked' : '' ?>>
                            <label class="form-check-label fs-5" for="lang_fr">
                                <?= lang('Profile.lang_fr') ?>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="lang" id="lang_en"
                                   value="en" <?= $savedLang === 'en' ? 'checked' : '' ?>>
                            <label class="form-check-label fs-5" for="lang_en">
                                <?= lang('Profile.lang_en') ?>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= lang('Profile.save_lang') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
