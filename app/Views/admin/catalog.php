<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-semibold mb-0">📚 ISO Catalogue</h4>
</div>

<?php if ($flash = session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible py-2 mb-3">
    <?= esc($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<div class="row g-4">
<?php foreach ($standards as $sv): ?>
<div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center gap-3 py-3">
            <span class="badge bg-primary fs-6 font-monospace px-3 py-2"><?= esc($sv['code']) ?></span>
            <div class="flex-grow-1">
                <span class="fw-bold"><?= esc($sv['name']) ?></span>
                <span class="text-muted ms-2 small"><?= esc($sv['version_title'] ?? '') ?></span>
            </div>
            <?php if ($sv['is_active']): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
            <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
            <?php endif ?>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col text-center">
                    <div class="fs-4 fw-bold text-primary"><?= (int)$sv['domain_count'] ?></div>
                    <div class="small text-muted">Domaines</div>
                </div>
                <div class="col text-center">
                    <div class="fs-4 fw-bold text-info"><?= (int)$sv['clause_count'] ?></div>
                    <div class="small text-muted">Clauses</div>
                </div>
                <div class="col text-center">
                    <div class="fs-4 fw-bold text-success"><?= (int)$sv['control_count'] ?></div>
                    <div class="small text-muted">Contrôles</div>
                </div>
                <div class="col text-center">
                    <div class="fs-4 fw-bold text-warning"><?= (int)$sv['subscription_count'] ?></div>
                    <div class="small text-muted">Abonnés</div>
                </div>
                <?php if ($sv['gap_stats']): ?>
                <div class="col text-center">
                    <div class="fs-4 fw-bold text-secondary"><?= (int)$sv['gap_stats']['total_sessions'] ?></div>
                    <div class="small text-muted">Sessions Gap</div>
                </div>
                <div class="col text-center">
                    <div class="fs-4 fw-bold text-success"><?= (int)$sv['gap_stats']['submitted_sessions'] ?></div>
                    <div class="small text-muted">Soumises</div>
                </div>
                <?php endif ?>
            </div>
        </div>
        <div class="card-footer bg-transparent text-end border-0">
            <a href="<?= site_url('admin/catalog/' . $sv['version_id']) ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i>Gérer le catalogue
            </a>
        </div>
    </div>
</div>
<?php endforeach ?>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<?= $this->endSection() ?>
