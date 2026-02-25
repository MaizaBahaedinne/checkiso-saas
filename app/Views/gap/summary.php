<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$g = $globalStats;
$scoreClass = $g['score'] === null ? 'bg-secondary' :
    ($g['score'] >= 70 ? 'bg-success' : ($g['score'] >= 40 ? 'bg-warning text-dark' : 'bg-danger'));
?>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <a href="<?= site_url('gap') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Gap Analysis
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">
            <?= esc($version['standard_code']) ?>
            <span class="text-muted fw-normal fs-5"><?= esc($version['version_code']) ?></span>
            — Tableau de conformité
        </h1>
        <p class="text-muted mb-0"><?= esc($version['standard_name']) ?></p>
    </div>
    <a href="<?= site_url('gap/' . $versionId) ?>" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i>Continuer l'évaluation
    </a>
</div>

<!-- ── Global KPI cards ─────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-primary"><?= $g['total'] ?></div>
            <div class="small text-muted">Contrôles total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-primary"><?= $g['assessed'] ?></div>
            <div class="small text-muted">Évalués (<?= $g['progress'] ?>%)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold <?= $g['score'] !== null ? 'text-' . ($g['score'] >= 70 ? 'success' : ($g['score'] >= 40 ? 'warning' : 'danger')) : 'text-secondary' ?>">
                <?= $g['score'] !== null ? $g['score'] . '%' : '—' ?>
            </div>
            <div class="small text-muted">Score de conformité</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-danger"><?= $g['non_conforme'] ?></div>
            <div class="small text-muted">Non-conformités</div>
        </div>
    </div>
</div>

<!-- ── Global status distribution ──────────────────────────────────────── -->
<?php if ($g['assessed'] > 0): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">Répartition globale</h6>
        <?php
        $evaluated = $g['total'] - $g['na'];
        $bars = [
            ['label' => 'Conforme',     'count' => $g['conforme'],     'class' => 'bg-success',  'pct' => $evaluated > 0 ? round($g['conforme'] / $evaluated * 100) : 0],
            ['label' => 'Partiel',      'count' => $g['partiel'],      'class' => 'bg-warning',  'pct' => $evaluated > 0 ? round($g['partiel']  / $evaluated * 100) : 0],
            ['label' => 'Non-conforme', 'count' => $g['non_conforme'], 'class' => 'bg-danger',   'pct' => $evaluated > 0 ? round($g['non_conforme'] / $evaluated * 100) : 0],
            ['label' => 'N/A',          'count' => $g['na'],           'class' => 'bg-secondary','pct' => $g['total']  > 0 ? round($g['na'] / $g['total'] * 100) : 0],
        ];
        ?>
        <div class="progress mb-3" style="height:24px">
            <?php foreach ($bars as $b): if ($b['pct'] <= 0) continue; ?>
            <div class="progress-bar <?= $b['class'] ?>"
                 style="width:<?= $b['pct'] ?>%"
                 title="<?= $b['label'] ?> : <?= $b['count'] ?> (<?= $b['pct'] ?>%)">
                <?= $b['pct'] >= 8 ? $b['pct'] . '%' : '' ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex flex-wrap gap-3 small">
            <?php foreach ($bars as $b): ?>
            <span>
                <span class="badge <?= $b['class'] ?> me-1">&nbsp;</span>
                <?= $b['label'] ?> — <strong><?= $b['count'] ?></strong>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Per-domain breakdown ─────────────────────────────────────────────── -->
<h5 class="fw-semibold mb-3">Détail par domaine</h5>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Domaine</th>
                    <th class="text-center" style="width:80px">Total</th>
                    <th class="text-center text-success" style="width:80px">✅</th>
                    <th class="text-center text-warning" style="width:80px">⚠️</th>
                    <th class="text-center text-danger" style="width:80px">❌</th>
                    <th class="text-center text-secondary" style="width:60px">N/A</th>
                    <th style="min-width:180px">Score / Progression</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domainStats as $ds): ?>
                <tr>
                    <td>
                        <span class="badge bg-primary me-2"><?= esc($ds['domain_code']) ?></span>
                        <span class="fw-medium"><?= esc($ds['domain_name']) ?></span>
                    </td>
                    <td class="text-center text-muted"><?= $ds['total'] ?></td>
                    <td class="text-center fw-semibold text-success"><?= $ds['conforme'] ?: '—' ?></td>
                    <td class="text-center fw-semibold text-warning"><?= $ds['partiel'] ?: '—' ?></td>
                    <td class="text-center fw-semibold text-danger"><?= $ds['non_conforme'] ?: '—' ?></td>
                    <td class="text-center text-secondary"><?= $ds['na'] ?: '—' ?></td>
                    <td>
                        <?php if ($ds['score'] !== null): ?>
                            <?php $cls = $ds['score'] >= 70 ? 'success' : ($ds['score'] >= 40 ? 'warning' : 'danger'); ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px">
                                    <div class="progress-bar bg-<?= $cls ?>" style="width:<?= $ds['score'] ?>%"></div>
                                </div>
                                <span class="badge bg-<?= $cls ?> text-nowrap"><?= $ds['score'] ?>%</span>
                            </div>
                        <?php elseif ($ds['assessed_total'] > 0): ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px">
                                    <div class="progress-bar bg-secondary" style="width:<?= $ds['progress'] ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $ds['progress'] ?>%</small>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">Non évalué</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
