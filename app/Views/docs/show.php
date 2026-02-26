<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Quill snow CSS for content rendering -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
.doc-content.ql-snow .ql-editor { padding: 0; min-height: unset; border: none; }
.doc-content.ql-snow { border: none; }
</style>

<?php
$isArchive    = $isArchive ?? false;
$displayTitle = $isArchive ? $version['title']   : $doc['title'];
$displayCnt   = $isArchive ? $version['content'] : $doc['content'];
$vn           = $isArchive ? $version['version_number'] : $doc['current_version'];

$catColors = ['policy'=>'primary','procedure'=>'info','guide'=>'success','reference'=>'secondary','template'=>'warning','other'=>'dark'];
$catLabels = ['policy'=>lang('Doc.cat_policy'),'procedure'=>lang('Doc.cat_procedure'),'guide'=>lang('Doc.cat_guide'),'reference'=>lang('Doc.cat_reference'),'template'=>lang('Doc.cat_template'),'other'=>lang('Doc.cat_other')];
?>

<!-- ── Breadcrumb ───────────────────────────────────────────────────────── -->
<nav class="mb-4 small text-muted d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= site_url('docs') ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i><?= lang('Doc.back_to_docs') ?>
    </a>
    <span>/</span>
    <span class="text-dark fw-semibold"><?= esc($displayTitle) ?></span>
</nav>

<!-- ── Archive banner ───────────────────────────────────────────────────── -->
<?php if ($isArchive): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-clock-history fs-5 flex-shrink-0"></i>
        <span><?= sprintf(lang('Doc.archive_banner'), $vn) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('docs/' . $doc['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye me-1"></i>Version actuelle
        </a>
        <form action="<?= site_url('docs/' . $doc['id'] . '/restore/' . $vn) ?>" method="post"
              onsubmit="return confirm(<?= json_encode(lang('Doc.confirm_restore')) ?>)">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-warning">
                <i class="bi bi-arrow-counterclockwise me-1"></i><?= lang('Doc.restore_btn') ?>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── Flash ─────────────────────────────────────────────────────────────── -->
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show d-flex gap-2 mb-3">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Document header ───────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="badge bg-<?= $catColors[$doc['category']] ?>">
                <?= $catLabels[$doc['category']] ?>
            </span>
            <span class="badge bg-light text-dark border font-monospace">v<?= $vn ?></span>
            <?php if (! $isArchive): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-check-circle me-1"></i><?= lang('Doc.current_badge') ?>
            </span>
            <?php endif; ?>
        </div>
        <h1 class="h2 fw-bold mb-0"><?= esc($displayTitle) ?></h1>
        <?php if ($doc['description']): ?>
        <p class="text-muted mt-1 mb-0"><?= esc($doc['description']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (! $isArchive): ?>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= site_url('docs/' . $doc['id'] . '/edit') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i><?= lang('Doc.edit_btn') ?>
        </a>
        <a href="<?= site_url('docs/' . $doc['id'] . '/history') ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-clock-history me-1"></i><?= lang('Doc.history_btn') ?>
            <span class="badge bg-info ms-1">v<?= $doc['current_version'] ?></span>
        </a>
        <button type="button" class="btn btn-outline-danger btn-sm btn-del"
                data-id="<?= $doc['id'] ?>" data-title="<?= esc($doc['title']) ?>">
            <i class="bi bi-trash me-1"></i><?= lang('Doc.delete_btn') ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- ── Meta info ─────────────────────────────────────────────────────────── -->
<div class="text-muted small mb-4 d-flex flex-wrap gap-3">
    <?php if ($author): ?>
    <span>
        <i class="bi bi-person me-1"></i>
        <?= lang('Doc.created_by') ?> <strong><?= esc($author['first_name'] . ' ' . $author['last_name']) ?></strong>
        <?= lang('Doc.on') ?> <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
    </span>
    <?php endif; ?>
    <span>
        <i class="bi bi-clock me-1"></i>
        <?= lang('Doc.last_updated') ?> <?= date('d/m/Y à H:i', strtotime($doc['updated_at'] ?? $doc['created_at'])) ?>
    </span>
</div>

<!-- ── Document content ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php if (trim(strip_tags($displayCnt ?? ''))): ?>
        <div class="ql-snow doc-content">
            <div class="ql-editor" style="padding:0;min-height:unset">
                <?= $displayCnt ?>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted fst-italic mb-0"><?= lang('Doc.empty_content') ?></p>
        <?php endif; ?>
    </div>
</div>

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
                    <button type="submit" class="btn btn-danger">Supprimer</button>
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
