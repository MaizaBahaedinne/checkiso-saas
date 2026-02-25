<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$userName    = esc(session()->get('user_name'));
$isOrgAdmin  = session()->get('role_code') === 'org.admin';
$subCount    = count($subscriptions);
$hasManual   = $manualCount > 0;
?>

<!-- ── Page header ────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold mb-0"><?= lang('Dashboard.welcome') ?> <?= $userName ?> 👋</h4>
        <p class="text-muted small mb-0"><?= lang('Dashboard.subtitle') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= site_url('catalog') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journals me-1"></i><?= lang('Dashboard.see_catalog') ?>
        </a>
        <?php if ($isOrgAdmin): ?>
        <a href="<?= site_url('org/members') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-people me-1"></i><?= lang('Dashboard.manage_members') ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── KPI cards ──────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Référentiels souscrits -->
    <div class="col-6 col-md-3">
        <a href="<?= site_url('catalog') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3 fs-4">
                        <i class="bi bi-journals"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-primary lh-1"><?= $subCount ?></div>
                        <div class="text-muted small mt-1"><?= lang('Dashboard.standards_subscribed') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Contrôles à évaluer -->
    <div class="col-6 col-md-3">
        <a href="<?= site_url('gap') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success rounded-3 p-3 fs-4">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-success lh-1"><?= $totalControls ?></div>
                        <div class="text-muted small mt-1"><?= lang('Dashboard.controls_total') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Membres -->
    <div class="col-6 col-md-3">
        <a href="<?= site_url('org/members') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info rounded-3 p-3 fs-4">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-info lh-1"><?= $memberCount ?></div>
                        <div class="text-muted small mt-1"><?= lang('Dashboard.team_members') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Revues manuelles -->
    <div class="col-6 col-md-3">
        <a href="<?= site_url('gap') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 kpi-card <?= $hasManual ? 'border-warning' : '' ?>">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon <?= $hasManual ? 'bg-warning bg-opacity-25 text-warning' : 'bg-secondary bg-opacity-10 text-secondary' ?> rounded-3 p-3 fs-4">
                        <i class="bi bi-flag<?= $hasManual ? '-fill' : '' ?>"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold <?= $hasManual ? 'text-warning' : 'text-secondary' ?> lh-1"><?= $manualCount ?></div>
                        <div class="text-muted small mt-1"><?= lang('Dashboard.pending_reviews') ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<!-- ── Alert : revues manuelles en attente ───────────────────────────────── -->
<?php if ($hasManual): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0"></i>
    <div>
        <strong><?= lang('Dashboard.pending_alert_title') ?></strong> —
        <?= $manualCount ?> <?= lang('Dashboard.pending_alert_body') ?>
    </div>
    <a href="<?= site_url('gap') ?>" class="btn btn-sm btn-warning ms-auto text-nowrap">
        <i class="bi bi-eye me-1"></i><?= lang('Dashboard.go_to_gap') ?>
    </a>
</div>
<?php endif; ?>

<!-- ── Gap Analysis progress ──────────────────────────────────────────────── -->
<h6 class="fw-semibold text-uppercase text-muted mb-3" style="font-size:.75rem;letter-spacing:.08em;">
    <i class="bi bi-bar-chart-line me-2"></i><?= lang('Dashboard.gap_progress_title') ?>
</h6>

<?php if (empty($subscriptions)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journals fs-1 d-block mb-3 text-secondary opacity-50"></i>
        <p class="mb-1 fw-semibold"><?= lang('Dashboard.no_subscriptions') ?></p>
        <p class="small mb-3"><?= lang('Dashboard.no_subscriptions_sub') ?></p>
        <a href="<?= site_url('catalog') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i><?= lang('Dashboard.go_to_catalog') ?>
        </a>
    </div>
</div>

<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 g-3">
<?php foreach ($subscriptions as $sv):
    $gs       = $sv['gap_session'];
    $answered = $gs ? (int)$gs['answered_controls'] : 0;
    $total    = $gs ? (int)$gs['total_controls']    : 0;
    $score    = $gs ? (float)$gs['score']           : 0;
    $status   = $gs ? $gs['status']                 : null;
    $pct      = ($total > 0) ? round($answered / $total * 100) : 0;

    if (!$gs || $answered === 0) {
        $statusLabel = lang('Dashboard.not_started');
        $statusClass = 'bg-secondary';
        $barClass    = 'bg-secondary';
    } elseif ($status === 'submitted') {
        $statusLabel = lang('Dashboard.submitted');
        $statusClass = 'bg-success';
        $barClass    = 'bg-success';
    } else {
        $statusLabel = lang('Dashboard.in_progress');
        $statusClass = 'bg-primary';
        $barClass    = 'bg-primary';
    }
?>
<div class="col">
    <div class="card border-0 shadow-sm h-100 <?= $status === 'submitted' ? 'border-start border-4 border-success' : ($answered > 0 ? 'border-start border-4 border-primary' : '') ?>">
        <div class="card-body">
            <!-- Standard identity -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="bg-primary bg-opacity-10 rounded-3 px-3 py-2 text-center" style="min-width:60px">
                    <span class="fw-bold text-primary d-block lh-1"><?= esc($sv['standard_code']) ?></span>
                    <small class="text-muted"><?= esc($sv['version_code']) ?></small>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= esc($sv['standard_name']) ?></div>
                    <span class="badge <?= $statusClass ?> mt-1"><?= $statusLabel ?></span>
                    <?php if ($status === 'submitted' && $gs['submitted_at']): ?>
                    <span class="text-muted small d-block mt-1">
                        <?= lang('Dashboard.submitted_on') ?> <?= date('d/m/Y', strtotime($gs['submitted_at'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="mb-1 d-flex justify-content-between small text-muted">
                <span><?= lang('Dashboard.progress_label') ?></span>
                <span><?= $answered ?> / <?= $total > 0 ? $total : '—' ?> <?= lang('Dashboard.controls_answered') ?></span>
            </div>
            <div class="progress mb-0" style="height:8px">
                <div class="progress-bar <?= $barClass ?>" style="width:<?= $pct ?>%"></div>
            </div>
        </div>

        <div class="card-footer bg-transparent border-top d-flex gap-2">
            <?php if ($status !== 'submitted'): ?>
            <a href="<?= site_url('gap/' . $sv['id']) ?>" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-pencil-square me-1"></i>
                <?= $answered > 0 ? lang('Dashboard.continue_btn') : lang('Dashboard.start_btn') ?>
            </a>
            <?php endif; ?>
            <?php if ($gs): ?>
            <a href="<?= site_url('gap/' . $sv['id'] . '/summary') ?>" class="btn btn-outline-secondary btn-sm <?= $status === 'submitted' ? 'flex-grow-1' : '' ?>">
                <i class="bi bi-bar-chart me-1"></i><?= lang('Dashboard.view_summary_btn') ?>
            </a>
            <?php endif; ?>
            <?php if (!$gs): ?>
            <a href="<?= site_url('gap/' . $sv['id']) ?>" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-play-circle me-1"></i><?= lang('Dashboard.start_btn') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.kpi-card { transition: transform .15s, box-shadow .15s; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1) !important; }
.kpi-icon { width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
</style>

<?= $this->endSection() ?>
