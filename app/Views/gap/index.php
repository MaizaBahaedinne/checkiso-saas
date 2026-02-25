<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">📊 Gap Analysis</h1>
        <p class="text-muted mb-0">Évaluez la conformité de votre organisation via un questionnaire guidé.</p>
    </div>
    <a href="<?= site_url('catalog') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-journals me-1"></i>Catalogue
    </a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (empty($standards)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>
        <p class="mb-2">Aucun référentiel souscrit.</p>
        <a href="<?= site_url('catalog') ?>" class="btn btn-primary">Accéder au catalogue</a>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php foreach ($standards as $s):
            $gs         = $s['gap_session'];
            $answered   = $gs ? (int)$gs['answered_controls'] : 0;
            $total      = $gs ? (int)$gs['total_controls']    : 0;
            $score      = $gs ? (float)$gs['score']           : 0;
            $status     = $gs ? $gs['status']                 : null;
            $pct        = ($total > 0) ? round($answered / $total * 100) : 0;
            $scoreClass = $score >= 75 ? 'bg-success' : ($score >= 50 ? 'bg-warning text-dark' : 'bg-danger');
        ?>
        <div class="col">
            <div class="card h-100 shadow-sm <?= $status === 'submitted' ? 'border-success' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3 text-center" style="min-width:68px">
                            <span class="fw-bold text-primary"><?= esc($s['standard_code']) ?></span><br>
                            <small class="text-muted"><?= esc($s['version_code']) ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1 fw-semibold"><?= esc($s['standard_name']) ?></h5>
                            <?php if ($status === 'submitted'): ?>
                                <span class="badge bg-success fs-6">✅ Soumis — Score : <?= number_format($score, 1) ?>%</span>
                            <?php elseif ($gs && $answered > 0): ?>
                                <span class="badge <?= $scoreClass ?> fs-6">Score partiel : <?= number_format($score, 1) ?>%</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Non commencé</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Progression</span>
                            <span><?= $answered ?> / <?= $total ?> contrôles répondus</span>
                        </div>
                        <div class="progress" style="height:10px">
                            <div class="progress-bar <?= $status === 'submitted' ? 'bg-success' : 'bg-primary' ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <?php if ($status !== 'submitted'): ?>
                        <a href="<?= site_url('gap/' . $s['id']) ?>" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-pencil-square me-1"></i><?= $answered > 0 ? 'Continuer' : 'Démarrer' ?> le questionnaire
                        </a>
                    <?php endif; ?>
                    <?php if ($gs): ?>
                        <a href="<?= site_url('gap/' . $s['id'] . '/summary') ?>" class="btn btn-outline-secondary btn-sm <?= $status !== 'submitted' ? '' : 'flex-grow-1' ?>">
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
