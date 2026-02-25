<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$gs         = $gapSession;
$answered   = (int)$gs['answered_controls'];
$total      = (int)$gs['total_controls'];
$score      = (float)$gs['score'];
$isLocked   = $gs['status'] === 'submitted';
$pct        = $total > 0 ? round($answered / $total * 100) : 0;
$scoreClass = $score >= 75 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
$versionId  = $sv['id'];

// Quick totals from domain breakdown
$totalAnswered   = 0;
$totalManual     = 0;
$countConforme   = 0;
$countPartiel    = 0;
$countNonConf    = 0;

foreach ($domainBreakdown as $d) {
    $totalAnswered += (int)$d['answered'];
    $totalManual   += (int)$d['manual_review'];
    // Determine status from avg_score per domain (rough)
}
?>

<!-- ── Header ────────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <a href="<?= site_url('gap') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i><?= lang('Gap.back_to_gap') ?>
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">
            <?= esc($sv['standard_code']) ?>
            <span class="text-muted fw-normal fs-5"><?= esc($sv['version_code']) ?></span>
            — <?= lang('Gap.summary_title') ?>
        </h1>
        <p class="text-muted mb-0"><?= esc($sv['standard_name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (! $isLocked): ?>
        <a href="<?= site_url('gap/' . $versionId) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil-square me-1"></i><?= lang('Gap.continue_btn') ?>
        </a>
        <?php else: ?>
        <span class="badge bg-success d-flex align-items-center gap-1 px-3">
            <i class="bi bi-lock-fill"></i> <?= lang('Gap.evaluation_submitted') ?>
        </span>
        <?php endif; ?>
        <a href="<?= site_url('gap/' . $versionId . '/export-pdf') ?>" class="btn btn-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Exporter PDF
        </a>
        <a href="<?= site_url('action-plan/create?session_id=' . $gs['id']) ?>" class="btn btn-outline-primary btn-sm">
            🎯 <?= lang('ActionPlan.btn_create_from_gap') ?>
        </a>
    </div>
</div>

<!-- ── Global KPI cards ──────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-primary"><?= $total ?></div>
            <div class="small text-muted"><?= lang('Gap.total_controls') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-primary"><?= $answered ?></div>
            <div class="small text-muted"><?= lang('Gap.answered') ?> (<?= $pct ?>%)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-<?= $answered > 0 ? $scoreClass : 'secondary' ?>">
                <?= $answered > 0 ? number_format($score, 1) . '%' : '—' ?>
            </div>
            <div class="small text-muted"><?= lang('Gap.compliance_score') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center shadow-sm py-3">
            <div class="fs-2 fw-bold text-info"><?= count($manualItems) ?></div>
            <div class="small text-muted"><?= lang('Gap.manual_evaluations') ?></div>
        </div>
    </div>
</div>

<!-- ── Progress bar ──────────────────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1 small text-muted">
            <span><?= lang('Gap.global_progress') ?></span>
            <span><?= $answered ?> / <?= $total ?> <?= lang('Gap.controls_label') ?></span>
        </div>
        <div class="progress mb-2" style="height:18px">
            <div class="progress-bar bg-<?= $answered > 0 ? $scoreClass : 'secondary' ?>"
                 style="width:<?= $pct ?>%">
                <?= $pct >= 10 ? $pct . '%' : '' ?>
            </div>
        </div>
        <?php if ($score > 0): ?>
        <p class="mb-0 text-muted small">
            <?= lang('Gap.avg_score') ?> : <strong class="text-<?= $scoreClass ?>"><?= number_format($score, 1) ?>%</strong>
            <?= $score >= 75 ? lang('Gap.score_good') : ($score >= 50 ? lang('Gap.score_medium') : lang('Gap.score_poor')) ?>
        </p>
        <?php endif; ?>
    </div>
</div>

<!-- ── Per-domain breakdown ──────────────────────────────────────────────── -->
<h5 class="fw-semibold mb-3"><?= lang('Gap.domain_detail') ?></h5>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= lang('Gap.domain_col') ?></th>
                    <th class="text-center" style="width:80px"><?= lang('Gap.answered_col') ?></th>
                    <th class="text-center" style="width:60px">🔍 <?= lang('Gap.manual_col') ?></th>
                    <th style="min-width:200px"><?= lang('Gap.avg_score_col') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($domainBreakdown as $d):
                $dPct = round((float)$d['avg_score'], 1);
                $dCls = $dPct >= 75 ? 'success' : ($dPct >= 50 ? 'warning' : 'danger');
            ?>
                <tr>
                    <td>
                        <span class="badge bg-primary me-2 font-monospace"><?= esc($d['domain_code']) ?></span>
                        <span class="fw-medium"><?= esc($d['display_name']) ?></span>
                    </td>
                    <td class="text-center"><?= (int)$d['answered'] ?></td>
                    <td class="text-center text-info fw-semibold"><?= (int)$d['manual_review'] ?: '—' ?></td>
                    <td>
                        <?php if ((int)$d['answered'] > 0): ?>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:10px">
                                <div class="progress-bar bg-<?= $dCls ?>" style="width:<?= $dPct ?>%"></div>
                            </div>
                            <span class="badge bg-<?= $dCls ?>"><?= number_format($dPct, 1) ?>%</span>
                        </div>
                        <?php else: ?>
                        <span class="text-muted small"><?= lang('Gap.not_answered') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Manual review items ───────────────────────────────────────────────── -->
<?php if (! empty($manualItems)): ?>
<h5 class="fw-semibold mb-3">
    <i class="bi bi-flag text-info me-2"></i><?= lang('Gap.manual_review_section') ?>
</h5>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($manualItems as $m): ?>
        <div class="list-group-item">
            <div class="d-flex align-items-start gap-3">
                <span class="badge bg-secondary font-monospace mt-1"><?= esc($m['control_code']) ?></span>
                <div class="flex-grow-1">
                    <p class="mb-1 fw-semibold small"><?= esc($m['display_title']) ?></p>
                    <?php if ($m['other_text']): ?>
                    <p class="mb-0 text-muted small"><i class="bi bi-chat-left-text me-1"></i><?= nl2br(esc($m['other_text'])) ?></p>
                    <?php elseif ($m['justification']): ?>
                    <p class="mb-0 text-muted small"><i class="bi bi-pencil me-1"></i><?= nl2br(esc($m['justification'])) ?></p>
                    <?php else: ?>
                    <p class="mb-0 text-muted small fst-italic"><?= lang('Gap.no_response') ?></p>
                    <?php endif; ?>
                </div>
                <span class="badge bg-info-subtle text-info border border-info-subtle"><?= lang('Gap.manual_col') ?></span>
                <a href="<?= site_url('action-plan/create?session_id=' . $gs['id'] . '&control_id=' . $m['control_id']) ?>"
                   class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:.72rem" title="<?= lang('ActionPlan.btn_create_from_gap') ?>">
                    🎯
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>