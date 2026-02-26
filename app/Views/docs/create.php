<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Quill snow CSS for editor -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
#editorContainer { border: 1px solid #dee2e6; border-radius: 0 0 .375rem .375rem; background: #fff; }
#editorContainer .ql-toolbar { border-radius: .375rem .375rem 0 0; border-color: #dee2e6 !important; }
#editorContainer .ql-container { border-color: #dee2e6 !important; border-radius: 0 0 .375rem .375rem; min-height: 400px; font-size: 1rem; }
</style>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= site_url('docs') ?>" class="text-muted text-decoration-none small">
        <i class="bi bi-arrow-left me-1"></i><?= lang('Doc.back_to_docs') ?>
    </a>
    <span class="text-muted small">/</span>
    <span class="small text-muted"><?= lang('Doc.create_title') ?></span>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; ?>

<form action="<?= site_url('docs') ?>" method="post" id="docForm">
    <?= csrf_field() ?>
    <input type="hidden" name="content" id="contentInput">

    <div class="row g-4">
        <div class="col-lg-9">
            <!-- Title -->
            <div class="mb-3">
                <input type="text" name="title" id="docTitle"
                       class="form-control form-control-lg fw-bold border-0 border-bottom rounded-0 ps-0"
                       placeholder="Titre du document…"
                       value="<?= esc(old('title')) ?>" required
                       style="font-size:1.6rem;box-shadow:none">
            </div>

            <!-- Description -->
            <div class="mb-3">
                <input type="text" name="description"
                       class="form-control text-muted border-0 rounded-0 ps-0"
                       placeholder="Description courte (facultatif)…"
                       value="<?= esc(old('description')) ?>"
                       style="box-shadow:none">
            </div>

            <!-- Quill editor -->
            <div id="editorContainer">
                <div id="editor"></div>
            </div>
        </div>

        <div class="col-lg-3">
            <!-- Metadata panel -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold text-muted text-uppercase mb-3" style="font-size:.72rem;letter-spacing:.06em">
                        Propriétés
                    </h6>

                    <label class="form-label small fw-semibold"><?= lang('Doc.field_category') ?></label>
                    <select name="category" class="form-select form-select-sm mb-3">
                        <?php foreach (['policy'=>'Politique','procedure'=>'Procédure','guide'=>'Guide','reference'=>'Référence','template'=>'Modèle','other'=>'Autre'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= old('category') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small fw-semibold"><?= lang('Doc.field_summary') ?></label>
                    <input type="text" name="change_summary" class="form-control form-control-sm"
                           placeholder="<?= lang('Doc.field_summary_ph') ?>"
                           value="<?= esc(old('change_summary', 'Version initiale')) ?>">
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="saveBtn">
                    <i class="bi bi-floppy me-1"></i><?= lang('Doc.save_btn') ?>
                </button>
                <a href="<?= site_url('docs') ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function () {
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Rédigez le contenu du document ici…',
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

    // Populate hidden input before submit
    document.getElementById('docForm').addEventListener('submit', function (e) {
        document.getElementById('contentInput').value = quill.root.innerHTML;
    });
})();
</script>

<?= $this->endSection() ?>
