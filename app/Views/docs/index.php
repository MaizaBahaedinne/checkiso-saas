<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$catColors = ['policy'=>'primary','procedure'=>'info','guide'=>'success','reference'=>'secondary','template'=>'warning','other'=>'dark'];
$catIcons  = ['policy'=>'bi-shield-check','procedure'=>'bi-diagram-3','guide'=>'bi-book','reference'=>'bi-journal-text','template'=>'bi-file-earmark-text','other'=>'bi-file-earmark'];
?>

<!-- ── Header ───────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-0">📄 <?= lang('Doc.page_title') ?></h1>
        <p class="text-muted small mb-0">Base de connaissances et documents ISO</p>
    </div>
    <a href="<?= site_url('docs/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i><?= lang('Doc.create_btn') ?>
    </a>
</div>

<!-- ── Flash ─────────────────────────────────────────────────────────────── -->
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show d-flex gap-2 mb-3">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show d-flex gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── KPI cards ─────────────────────────────────────────────────────────── -->
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

<!-- ── Search bar ────────────────────────────────────────────────────────── -->
<form method="get" action="<?= site_url('docs') ?>" class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" class="form-control border-start-0"
               placeholder="<?= lang('Doc.search_ph') ?>" value="<?= esc($search) ?>">
        <?php if ($currentCat): ?>
        <input type="hidden" name="category" value="<?= esc($currentCat) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-outline-secondary">Filtrer</button>
        <?php if ($search || $currentCat): ?>
        <a href="<?= site_url('docs') ?>" class="btn btn-outline-danger" title="Réinitialiser">
            <i class="bi bi-x-lg"></i>
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- ── Category tabs ─────────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="<?= site_url('docs' . ($search ? '?q=' . urlencode($search) : '')) ?>"
       class="btn btn-sm <?= !$currentCat ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= lang('Doc.all_categories') ?>
        <span class="badge ms-1 <?= !$currentCat ? 'bg-white text-dark' : 'bg-secondary' ?>"><?= count($docs) ?></span>
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= site_url('docs?category=' . $cat . ($search ? '&q=' . urlencode($search) : '')) ?>"
       class="btn btn-sm <?= $currentCat === $cat ? 'btn-' . $catColors[$cat] : 'btn-outline-' . $catColors[$cat] ?>">
        <i class="bi <?= $catIcons[$cat] ?> me-1"></i><?= lang('Doc.cat_' . $cat) ?>
        <span class="badge ms-1 <?= $currentCat === $cat ? 'bg-white text-' . $catColors[$cat] : 'bg-' . $catColors[$cat] ?>"><?= $stats[$cat] ?? 0 ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Document list ─────────────────────────────────────────────────────── -->
<?php if (empty($docs)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-text fs-1 d-block mb-3 text-secondary opacity-50"></i>
        <p class="mb-1 fw-semibold"><?= lang('Doc.no_docs') ?></p>
        <p class="small mb-3"><?= lang('Doc.no_docs_sub') ?></p>
        <a href="<?= site_url('docs/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i><?= lang('Doc.create_btn') ?>
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
                    <th class="text-center" style="width:80px"><?= lang('Doc.col_version') ?></th>
                    <th style="width:110px"><?= lang('Doc.col_updated') ?></th>
                    <th class="text-end" style="width:130px"><?= lang('Doc.col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($docs as $doc): ?>
            <tr>
                <td>
                    <a href="<?= site_url('docs/' . $doc['id']) ?>"
                       class="fw-semibold text-decoration-none text-dark">
                        <i class="bi <?= $catIcons[$doc['category']] ?> text-<?= $catColors[$doc['category']] ?> me-2"></i>
                        <?= esc($doc['title']) ?>
                    </a>
                    <?php if ($doc['description']): ?>
                    <div class="text-muted small text-truncate" style="max-width:420px;padding-left:1.5rem">
                        <?= esc($doc['description']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?= $catColors[$doc['category']] ?>">
                        <?= lang('Doc.cat_' . $doc['category']) ?>
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border font-monospace">
                        v<?= $doc['current_version'] ?>
                    </span>
                </td>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($doc['updated_at'] ?? $doc['created_at'])) ?></td>
                <td class="text-end">
                    <a href="<?= site_url('docs/' . $doc['id']) ?>"
                       class="btn btn-outline-primary btn-sm py-0 px-2" title="<?= lang('Doc.view_btn') ?>">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= site_url('docs/' . $doc['id'] . '/edit') ?>"
                       class="btn btn-outline-secondary btn-sm py-0 px-2 ms-1" title="<?= lang('Doc.edit_btn') ?>">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="<?= site_url('docs/' . $doc['id'] . '/history') ?>"
                       class="btn btn-outline-info btn-sm py-0 px-2 ms-1" title="<?= lang('Doc.history_btn') ?>">
                        <i class="bi bi-clock-history"></i>
                    </a>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm py-0 px-2 ms-1 btn-del"
                            data-id="<?= $doc['id'] ?>"
                            data-title="<?= esc($doc['title']) ?>"
                            title="<?= lang('Doc.delete_btn') ?>">
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

<!-- ── Delete modal ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="delModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i><?= lang('Doc.delete_btn') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0"><?= lang('Doc.confirm_delete') ?></p>
                <p class="fw-semibold mt-2 mb-0" id="delTitle"></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="delForm" method="post" action="">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-del').forEach(b => {
    b.addEventListener('click', function () {
        document.getElementById('delTitle').textContent = this.dataset.title;
        document.getElementById('delForm').action = '/docs/' + this.dataset.id + '/delete';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('delModal')).show();
    });
});
</script>

<?= $this->endSection() ?>
