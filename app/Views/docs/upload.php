<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- ── Header ───────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <a href="<?= site_url('docs') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i><?= lang('Doc.page_title') ?>
        </a>
        <h1 class="h3 fw-bold mt-1 mb-0">
            <i class="bi bi-upload me-2 text-primary"></i><?= lang('Doc.upload_title') ?>
        </h1>
    </div>
</div>

<!-- ── Flash error ───────────────────────────────────────────────────────── -->
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="<?= site_url('docs/upload') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- Drag & Drop zone -->
                    <div id="dropZone" class="border border-2 border-dashed rounded-3 p-5 text-center mb-4"
                         style="border-color:#c7d2fe !important;cursor:pointer;transition:background .2s">
                        <i class="bi bi-cloud-upload fs-1 text-primary d-block mb-2"></i>
                        <p class="fw-semibold mb-1"><?= lang('Doc.drop_hint') ?></p>
                        <p class="text-muted small mb-3"><?= lang('Doc.allowed_types') ?></p>
                        <p class="text-muted small"><?= lang('Doc.field_file') ?></p>
                        <input type="file" id="fileInput" name="document" class="d-none"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.png,.jpg,.jpeg,.gif,.zip">
                        <div id="filePreview" class="alert alert-primary py-2 px-3 mt-2 mb-0 d-none text-start">
                            <i class="bi bi-file-earmark me-2"></i>
                            <span id="fileName" class="fw-semibold"></span>
                            <span id="fileSize" class="text-muted small ms-2"></span>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('Doc.field_title') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= esc(old('title')) ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('Doc.field_desc') ?></label>
                        <textarea name="description" class="form-control" rows="2"><?= esc(old('description')) ?></textarea>
                    </div>

                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('Doc.field_category') ?> <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <?php foreach (['policy','procedure','evidence','template','other'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= old('category') === $cat ? 'selected' : '' ?>>
                                <?= lang('Doc.cat_' . $cat) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Link to control -->
                    <?php if (! empty($controls)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('Doc.field_control') ?></label>
                        <select name="linked_control_id" class="form-select">
                            <option value=""><?= lang('Doc.none') ?></option>
                            <?php foreach ($controls as $c):
                                $locale = session()->get('lang') ?? 'fr';
                                $label  = ($locale === 'fr' && !empty($c['title_fr'])) ? $c['title_fr'] : $c['title'];
                            ?>
                            <option value="<?= $c['id'] ?>" <?= old('linked_control_id') == $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['code']) ?> — <?= esc(mb_strimwidth($label, 0, 60, '…')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Link to action plan -->
                    <?php if (! empty($plans)): ?>
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><?= lang('Doc.field_plan') ?></label>
                        <select name="linked_action_plan_id" class="form-select">
                            <option value=""><?= lang('Doc.none') ?></option>
                            <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('linked_action_plan_id') == $p['id'] ? 'selected' : '' ?>>
                                <?= esc(mb_strimwidth($p['title'], 0, 70, '…')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-upload me-1"></i><?= lang('Doc.btn_upload') ?>
                        </button>
                        <a href="<?= site_url('docs') ?>" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help panel -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10 mb-3">
            <div class="card-body">
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Catégories</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><span class="badge bg-primary me-2">Politique</span>Politiques ISO (SMSI, sécurité…)</li>
                    <li class="mb-2"><span class="badge bg-info me-2">Procédure</span>Procédures opérationnelles</li>
                    <li class="mb-2"><span class="badge bg-success me-2">Preuve</span>Logs, rapports, attestations</li>
                    <li class="mb-2"><span class="badge bg-warning text-dark me-2">Modèle</span>Templates à remplir</li>
                    <li><span class="badge bg-secondary me-2">Autre</span>Tout autre document</li>
                </ul>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-file-earmark-check me-2 text-success"></i>Types acceptés</h6>
                <p class="small text-muted mb-0"><?= lang('Doc.allowed_types') ?> — max 20 Mo</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const drop     = document.getElementById('dropZone');
    const input    = document.getElementById('fileInput');
    const preview  = document.getElementById('filePreview');
    const nameEl   = document.getElementById('fileName');
    const sizeEl   = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');

    function humanSize(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
        return (b/1048576).toFixed(1) + ' MB';
    }

    function showFile(file) {
        nameEl.textContent = file.name;
        sizeEl.textContent = humanSize(file.size);
        preview.classList.remove('d-none');
        submitBtn.disabled = false;
        drop.style.background = '#eef2ff';
    }

    drop.addEventListener('click', () => input.click());
    drop.addEventListener('dragover', e => { e.preventDefault(); drop.style.background = '#e0e7ff'; });
    drop.addEventListener('dragleave', () => { drop.style.background = ''; });
    drop.addEventListener('drop', e => {
        e.preventDefault();
        drop.style.background = '';
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            showFile(e.dataTransfer.files[0]);
        }
    });
    input.addEventListener('change', () => {
        if (input.files.length) showFile(input.files[0]);
    });

    // Auto-fill title from file name
    const titleInput = document.querySelector('input[name="title"]');
    input.addEventListener('change', () => {
        if (input.files.length && !titleInput.value.trim()) {
            titleInput.value = input.files[0].name.replace(/\.[^.]+$/, '').replace(/[_-]/g, ' ');
        }
    });
})();
</script>

<?= $this->endSection() ?>
