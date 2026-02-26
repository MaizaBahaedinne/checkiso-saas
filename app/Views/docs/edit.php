<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
#editorContainer .ql-toolbar  { border-radius: .375rem .375rem 0 0; border-color: #dee2e6 !important; }
#editorContainer .ql-container { border-color: #dee2e6 !important; border-radius: 0 0 .375rem .375rem; min-height: 420px; font-size: 1rem; background: #fff; }
</style>

<nav class="mb-4 small text-muted d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= site_url('docs/' . $doc['id']) ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i><?= esc($doc['title']) ?>
    </a>
    <span>/</span>
    <span class="text-dark"><?= lang('Doc.edit_title') ?></span>
</nav>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; ?>

<form action="<?= site_url('docs/' . $doc['id'] . '/update') ?>" method="post" id="docForm">
    <?= csrf_field() ?>
    <input type="hidden" name="content" id="contentInput">

    <div class="row g-4">
        <div class="col-lg-9">
            <div class="mb-3">
                <input type="text" name="title" id="docTitle"
                       class="form-control form-control-lg fw-bold border-0 border-bottom rounded-0 ps-0"
                       placeholder="Titre du document…"
                       value="<?= esc(old('title', $doc['title'])) ?>" required
                       style="font-size:1.6rem;box-shadow:none">
            </div>
            <div class="mb-3">
                <input type="text" name="description"
                       class="form-control text-muted border-0 rounded-0 ps-0"
                       placeholder="Description courte…"
                       value="<?= esc(old('description', $doc['description'])) ?>"
                       style="box-shadow:none">
            </div>
            <div id="editorContainer">
                <div id="editor"></div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold text-muted text-uppercase mb-3" style="font-size:.72rem;letter-spacing:.06em">
                        Propriétés
                    </h6>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Version actuelle</label>
                        <div class="badge bg-light text-dark border font-monospace fs-6">
                            v<?= $doc['current_version'] ?> → v<?= (int)$doc['current_version'] + 1 ?>
                        </div>
                    </div>

                    <label class="form-label small fw-semibold"><?= lang('Doc.field_category') ?></label>
                    <select name="category" class="form-select form-select-sm mb-3">
                        <?php foreach (['policy'=>lang('Doc.cat_policy'),'procedure'=>lang('Doc.cat_procedure'),'guide'=>lang('Doc.cat_guide'),'reference'=>lang('Doc.cat_reference'),'template'=>lang('Doc.cat_template'),'other'=>lang('Doc.cat_other')] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= (old('category', $doc['category']) === $val) ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small fw-semibold"><?= lang('Doc.field_summary') ?></label>
                    <input type="text" name="change_summary" class="form-control form-control-sm"
                           placeholder="<?= lang('Doc.field_summary_ph') ?>"
                           value="<?= esc(old('change_summary')) ?>">
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-floppy me-1"></i><?= lang('Doc.save_btn') ?>
                </button>
                <a href="<?= site_url('docs/' . $doc['id']) ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function () {
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block'],
                [{ color: [] }, { background: [] }],
                [{ align: [] }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Pre-fill with existing content
    const existing = <?= json_encode($doc['content'] ?? '') ?>;
    if (existing) {
        quill.clipboard.dangerouslyPasteHTML(existing);
    }

    document.getElementById('docForm').addEventListener('submit', function () {
        document.getElementById('contentInput').value = quill.root.innerHTML;
    });
})();
</script>

<?= $this->endSection() ?>
