<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">📊 Gap Analysis</h1>
        <p class="text-muted mb-0">Évaluez la conformité de votre organisation à vos référentiels ISO.</p>
    </div>
    <a href="<?= site_url('catalog') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-journals me-1"></i>Catalogue
    </a>
</div>

<?php if (empty($standards)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>
        <p class="mb-2">Aucun référentiel souscrit.</p>
        <a href="<?= site_url('catalog') ?>" class="btn btn-primary">Accéder au catalogue</a>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php foreach ($standards as $s):
            $st = $s['stats'];
            $score = $st['score'];
            $scoreClass = $score === null ? 'bg-secondary' : ($score >= 70 ? 'bg-success' : ($score >= 40 ? 'bg-warning text-dark' : 'bg-danger'));
        ?>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3 text-center" style="min-width:64px">
                            <span class="fw-bold text-primary"><?= esc($s['standard_code']) ?></span><br>
                            <small class="text-muted"><?= esc($s['version_code']) ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1 fw-semibold"><?= esc($s['standard_name']) ?></h5>
                            <?php if ($score !== null): ?>
                                <span class="badge <?= $scoreClass ?> fs-6">Score : <?= $score ?>%</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Non commencé</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Évaluation</span>
                            <span><?= $st['assessed'] ?> / <?= $st['total'] ?> contrôles</span>
                        </div>
                        <div class="progress" style="height:10px">
                            <div class="progress-bar bg-primary" style="width:<?= $st['progress'] ?>%" title="<?= $st['progress'] ?>% évalué"></div>
                        </div>
                    </div>

                    <!-- Status breakdown -->
                    <?php if ($st['assessed'] > 0): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($st['conforme'] > 0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">✅ <?= $st['conforme'] ?> Conforme</span>
                        <?php endif; ?>
                        <?php if ($st['partiel'] > 0): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">⚠️ <?= $st['partiel'] ?> Partiel</span>
                        <?php endif; ?>
                        <?php if ($st['non_conforme'] > 0): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">❌ <?= $st['non_conforme'] ?> Non-conforme</span>
                        <?php endif; ?>
                        <?php if ($st['na'] > 0): ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">○ <?= $st['na'] ?> N/A</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <a href="<?= site_url('gap/' . $s['id']) ?>" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-pencil-square me-1"></i><?= $st['assessed'] > 0 ? 'Continuer' : 'Démarrer' ?> l'évaluation
                    </a>
                    <?php if ($st['assessed'] > 0): ?>
                    <a href="<?= site_url('gap/' . $s['id'] . '/summary') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-bar-chart me-1"></i>Résumé
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
