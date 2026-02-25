<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<h4 class="fw-semibold mb-4">🔍 Gap Analysis — Toutes les sessions</h4>

<?php if ($flash = session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible py-2 mb-3">
    <?= esc($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-primary"><?= $totalSessions ?></div>
            <div class="small text-muted">Sessions totales</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-success"><?= $submittedCount ?></div>
            <div class="small text-muted">Soumises</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-primary"><?= $inProgressCount ?></div>
            <div class="small text-muted">En cours</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-secondary"><?= $notStartedCount ?></div>
            <div class="small text-muted">Non commencées</div>
        </div>
    </div>
</div>

<!-- Sessions table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="sessionsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Organisation</th>
                        <th>Référentiel</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Progression</th>
                        <th class="text-center">Score</th>
                        <th>Modifié le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune session.</td></tr>
                <?php endif ?>
                <?php foreach ($sessions as $s):
                    $answered = (int)$s['answered_controls'];
                    $total    = (int)$s['total_controls'];
                    $pct      = $total > 0 ? round($answered / $total * 100) : 0;
                    $score    = (float)$s['score'];

                    if ($s['status'] === 'submitted') {
                        $statusBadge = '<span class="badge bg-success">Soumis</span>';
                    } elseif ($answered > 0) {
                        $statusBadge = '<span class="badge bg-primary">En cours</span>';
                    } else {
                        $statusBadge = '<span class="badge bg-secondary">Non commencé</span>';
                    }
                    $barClass = $score >= 75 ? 'bg-success' : ($score >= 50 ? 'bg-warning' : 'bg-danger');
                ?>
                <tr>
                    <td class="text-muted"><?= $s['id'] ?></td>
                    <td>
                        <span class="fw-medium"><?= esc($s['tenant_name']) ?></span>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace"><?= esc($s['standard_code']) ?></span>
                        <span class="text-muted"><?= esc($s['version_code']) ?></span>
                    </td>
                    <td class="text-center"><?= $statusBadge ?></td>
                    <td style="min-width:140px">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:8px">
                                <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="text-muted" style="font-size:.75rem;white-space:nowrap">
                                <?= $answered ?>/<?= $total ?>
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <?php if ($answered > 0): ?>
                        <span class="badge bg-<?= $barClass === 'bg-success' ? 'success' : ($barClass === 'bg-warning' ? 'warning text-dark' : 'danger') ?>">
                            <?= number_format($score, 1) ?>%
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif ?>
                    </td>
                    <td class="text-muted"><?= date('d/m/Y H:i', strtotime($s['updated_at'])) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/gap/' . $s['id']) ?>" class="btn btn-outline-primary btn-sm py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($s['status'] !== 'submitted' || true): // allow reset always ?>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 ms-1"
                                onclick="confirmReset(<?= $s['id'] ?>, '<?= esc($s['tenant_name']) ?>')">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reset confirmation modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="post" id="resetForm">
            <?= csrf_field() ?>
            <div class="modal-content border-danger">
                <div class="modal-header border-danger bg-danger-subtle">
                    <h6 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i>Réinitialiser la session ?
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    Toutes les réponses de <strong id="resetTenantName"></strong> seront supprimées et la session
                    sera remise à zéro. Cette action est <strong>irréversible</strong>.
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger btn-sm">Réinitialiser</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function confirmReset(sessionId, tenantName) {
    document.getElementById('resetForm').action = '<?= site_url('admin/gap/reset') ?>/' + sessionId;
    document.getElementById('resetTenantName').textContent = tenantName;
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}
</script>

<?= $this->endSection() ?>
