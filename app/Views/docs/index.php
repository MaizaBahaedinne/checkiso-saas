<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$catColors = [
    'policy'    => 'primary',
    'procedure' => 'info',
    'evidence'  => 'success',
    'template'  => 'warning',
    'other'     => 'secondary',
];
$catIcons = [
    'policy'    => 'bi-shield-check',
    'procedure' => 'bi-diagram-3',
    'evidence'  => 'bi-patch-check',
    'template'  => 'bi-file-earmark-text',
    'other'     => 'bi-file-earmark',
];

$humanSize = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
};
?>

<!-- ── Header ───────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-0">📄 <?= lang('Doc.page_title') ?></h1>
        <p class="text-muted small mb-0">Bibliothèque de preuves et documents ISO</p>
    </div>
    <a href="<?= site_url('docs/upload') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-upload me-1"></i><?= lang('Doc.btn_upload') ?>
    </a>
</div>

<!-- ── Flash messages ───────────────────────────────────────────────────── -->
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill"></i>
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── KPI strips ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-dark"><?= count($docs) ?></div>
            <div class="small text-muted"><?= lang('Doc.total_docs') ?></div>
        </div>
    </div>
    <?php foreach ($categories as $cat): ?>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-<?= $catColors[$cat] ?>"><?= $stats[$cat] ?? 0 ?></div>
            <div class="small text-muted"><?= lang('Doc.cat_' . $cat) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Filters ───────────────────────────────────────────────────────────── -->
<form method="get" action="<?= site_url('docs') ?>" class="card shadow-sm mb-4 border-0">
    <div class="card-body d-flex flex-wrap gap-3 align-items-end py-3">
        <div class="flex-grow-1" style="min-width:200px">
            <label class="form-label small fw-semibold mb-1"><?= lang('Doc.search_ph') ?></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="<?= lang('Doc.search_ph') ?>"
                       value="<?= esc($search) ?>">
            </div>
        </div>
        <div>
            <label class="form-label small fw-semibold mb-1"><?= lang('Doc.col_category') ?></label>
            <select name="category" class="form-select form-select-sm">
                <option value=""><?= lang('Doc.all_categories') ?></option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= $currentCat === $cat ? 'selected' : '' ?>>
                    <?= lang('Doc.cat_' . $cat) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm align-self-end">
            <i class="bi bi-filter me-1"></i>Filtrer
        </button>
        <?php if ($currentCat || $search): ?>
        <a href="<?= site_url('docs') ?>" class="btn btn-outline-secondary btn-sm align-self-end">
            <i class="bi bi-x-circle me-1"></i>Réinitialiser
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- ── Category tab pills ────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= site_url('docs') ?>"
       class="btn btn-sm <?= !$currentCat ? 'btn-dark' : 'btn-outline-secondary' ?>">
        Tous <span class="badge bg-white text-dark ms-1"><?= count($docs) ?></span>
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= site_url('docs?category=' . $cat) ?>"
       class="btn btn-sm <?= $currentCat === $cat ? 'btn-' . $catColors[$cat] : 'btn-outline-' . $catColors[$cat] ?>">
        <i class="bi <?= $catIcons[$cat] ?> me-1"></i><?= lang('Doc.cat_' . $cat) ?>
        <span class="badge bg-white text-<?= $catColors[$cat] ?> ms-1"><?= $stats[$cat] ?? 0 ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Documents table ───────────────────────────────────────────────────── -->
<?php if (empty($docs)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-folder2-open fs-1 d-block mb-3 text-secondary opacity-50"></i>
        <p class="mb-1 fw-semibold"><?= lang('Doc.no_docs') ?></p>
        <p class="small mb-3"><?= lang('Doc.no_docs_sub') ?></p>
        <a href="<?= site_url('docs/upload') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-upload me-1"></i><?= lang('Doc.btn_upload') ?>
        </a>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= lang('Doc.col_title') ?></th>
                    <th class="text-center" style="width:110px"><?= lang('Doc.col_category') ?></th>
                    <th style="width:90px"><?= lang('Doc.col_size') ?></th>
                    <th style="width:105px"><?= lang('Doc.col_date') ?></th>
                    <th><?= lang('Doc.col_linked') ?></th>
                    <th class="text-end" style="width:130px"><?= lang('Doc.col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($docs as $doc): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $catIcons[$doc['category']] ?? 'bi-file-earmark' ?> text-<?= $catColors[$doc['category']] ?? 'secondary' ?> fs-5"></i>
                        <div>
                            <div class="fw-semibold"><?= esc($doc['title']) ?></div>
                            <?php if ($doc['description']): ?>
                            <div class="text-muted small text-truncate" style="max-width:300px"><?= esc($doc['description']) ?></div>
                            <?php endif; ?>
                            <div class="text-muted" style="font-size:.72rem"><?= esc($doc['file_name']) ?></div>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?= $catColors[$doc['category']] ?>">
                        <?= lang('Doc.cat_' . $doc['category']) ?>
                    </span>
                </td>
                <td class="text-muted small"><?= $humanSize((int)$doc['file_size']) ?></td>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                <td class="small">
                    <?php if ($doc['linked_control_id']): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        <i class="bi bi-clipboard-check me-1"></i><?= lang('Doc.link_control') ?> #<?= $doc['linked_control_id'] ?>
                    </span>
                    <?php elseif ($doc['linked_action_plan_id']): ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                        <i class="bi bi-bullseye me-1"></i><?= lang('Doc.link_plan') ?> #<?= $doc['linked_action_plan_id'] ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= site_url('docs/' . $doc['id'] . '/download') ?>"
                       class="btn btn-outline-primary btn-sm py-0 px-2"
                       title="<?= lang('Doc.btn_download') ?>">
                        <i class="bi bi-download"></i>
                    </a>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm py-0 px-2 ms-1 btn-delete-doc"
                            data-doc-id="<?= $doc['id'] ?>"
                            data-doc-title="<?= esc($doc['title']) ?>"
                            title="<?= lang('Doc.btn_delete') ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Delete confirmation modal ─────────────────────────────────────────── -->
<div class="modal fade" id="deleteDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-trash me-2"></i><?= lang('Doc.btn_delete') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0"><?= lang('Doc.confirm_delete') ?></p>
                <p class="fw-semibold mt-2 mb-0" id="deleteDocName"></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteDocForm" method="post" action="">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete-doc').forEach(btn => {
    btn.addEventListener('click', function () {
        const id    = this.dataset.docId;
        const title = this.dataset.docTitle;
        document.getElementById('deleteDocName').textContent = title;
        document.getElementById('deleteDocForm').action = '/docs/' + id + '/delete';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteDocModal')).show();
    });
});
</script>

<?= $this->endSection() ?>
