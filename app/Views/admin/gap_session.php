<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= site_url('admin/gap') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Toutes les sessions
    </a>
    <div>
        <h4 class="fw-semibold mb-0">
            Session #<?= $session['id'] ?> —
            <span class="badge bg-primary font-monospace"><?= esc($session['standard_code']) ?></span>
            <?= esc($session['version_code']) ?>
        </h4>
        <p class="text-muted small mb-0">
            Organisation : <strong><?= esc($session['tenant_name']) ?></strong>
            · Statut :
            <?php if ($session['status'] === 'submitted'): ?>
            <span class="badge bg-success">Soumis</span>
            <?php else: ?>
            <span class="badge bg-primary">En cours / Draft</span>
            <?php endif ?>
            · <?= (int)$session['answered_controls'] ?> / <?= (int)$session['total_controls'] ?> réponses
            · Score : <?= number_format((float)$session['score'], 1) ?>%
        </p>
    </div>
</div>

<?php if (empty($byDomain)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-clipboard-x fs-1 d-block mb-3 opacity-50"></i>
        Aucune réponse enregistrée pour cette session.
    </div>
</div>
<?php else: ?>

<?php foreach ($byDomain as $domCode => $domData): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-10 border-0 py-2 d-flex align-items-center gap-2">
        <span class="badge bg-primary font-monospace"><?= esc($domCode) ?></span>
        <span class="fw-semibold">
            <?= esc($domData['name_fr'] ?: $domData['name']) ?>
        </span>
        <span class="ms-auto badge bg-secondary-subtle text-secondary border small">
            <?= count($domData['answers']) ?> réponses
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px">Code</th>
                        <th>Contrôle</th>
                        <th style="width:100px" class="text-center">Statut</th>
                        <th style="width:60px" class="text-center">Score</th>
                        <th>Réponse choisie</th>
                        <th style="width:60px" class="text-center">Flags</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($domData['answers'] as $a):
                    $statusBadge = match($a['status']) {
                        'conforme'     => '<span class="badge bg-success-subtle text-success border border-success-subtle">Conforme</span>',
                        'partiel'      => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Partiel</span>',
                        'non_conforme' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Non conforme</span>',
                        default        => '<span class="badge bg-secondary-subtle text-secondary border">Revue</span>',
                    };
                ?>
                <tr>
                    <td><code class="text-primary"><?= esc($a['control_code']) ?></code></td>
                    <td>
                        <?= esc($a['control_title_fr'] ?: $a['control_title']) ?>
                        <?php if ($a['justification']): ?>
                        <div class="text-muted mt-1" style="font-size:.72rem">
                            <i class="bi bi-chat-left-text me-1"></i><?= esc(mb_strimwidth($a['justification'], 0, 120, '…')) ?>
                        </div>
                        <?php endif ?>
                        <?php if ($a['other_text']): ?>
                        <div class="text-info mt-1" style="font-size:.72rem">
                            <i class="bi bi-info-circle me-1"></i><?= esc(mb_strimwidth($a['other_text'], 0, 120, '…')) ?>
                        </div>
                        <?php endif ?>
                    </td>
                    <td class="text-center"><?= $statusBadge ?></td>
                    <td class="text-center fw-semibold <?= (int)$a['score_pct'] >= 75 ? 'text-success' : ((int)$a['score_pct'] >= 50 ? 'text-warning' : 'text-danger') ?>">
                        <?= (int)$a['score_pct'] ?>%
                    </td>
                    <td class="text-muted">
                        <span class="badge bg-light text-dark border font-monospace me-1"><?= strtoupper(esc($a['choice_key'])) ?></span>
                        <?= esc(mb_strimwidth($a['choice_label'] ?? '—', 0, 80, '…')) ?>
                    </td>
                    <td class="text-center">
                        <?php if ($a['is_trap']): ?>
                        <span class="badge bg-danger-subtle text-danger border" title="Piège">🪤</span>
                        <?php endif ?>
                        <?php if ($a['is_manual_review']): ?>
                        <span class="badge bg-info-subtle text-info border" title="Revue manuelle"><i class="bi bi-flag"></i></span>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach ?>

<?php endif ?>

<?= $this->endSection() ?>
