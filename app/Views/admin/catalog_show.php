<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= site_url('admin/catalog') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Catalogue
    </a>
    <div>
        <h4 class="fw-semibold mb-0">
            <span class="badge bg-primary font-monospace me-2"><?= esc($version['standard_code']) ?></span>
            <?= esc($version['version_title']) ?>
        </h4>
        <p class="text-muted small mb-0">Gérez les libellés FR/EN des domaines, clauses et contrôles.</p>
    </div>
</div>

<?php if ($flash = session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible py-2 mb-3">
    <?= esc($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<!-- Domain accordion -->
<div class="accordion" id="domainAccordion">
<?php foreach ($domains as $di => $domain): ?>

<div class="accordion-item border-0 shadow-sm mb-3 rounded">
    <h2 class="accordion-header">
        <button class="accordion-button <?= $di > 0 ? 'collapsed' : '' ?> fw-semibold rounded" type="button"
                data-bs-toggle="collapse" data-bs-target="#domain-<?= $domain['id'] ?>">
            <span class="badge bg-primary font-monospace me-3"><?= esc($domain['code']) ?></span>
            <span><?= esc($domain['name_fr'] ?: $domain['name']) ?></span>
            <span class="ms-2 text-muted fw-normal small"><?= esc($domain['name']) ?></span>
        </button>
    </h2>
    <div id="domain-<?= $domain['id'] ?>" class="accordion-collapse collapse <?= $di === 0 ? 'show' : '' ?>">
        <div class="accordion-body">

            <!-- Edit domain names -->
            <div class="card border-warning mb-4 bg-warning-subtle">
                <div class="card-header border-warning fw-semibold small text-warning-emphasis bg-transparent">
                    <i class="bi bi-pencil me-1"></i>Modifier le domaine
                </div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('admin/catalog/domain/' . $domain['id'] . '/save') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nom EN</label>
                                <input type="text" name="name" class="form-control form-control-sm"
                                       value="<?= esc($domain['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nom FR</label>
                                <input type="text" name="name_fr" class="form-control form-control-sm"
                                       value="<?= esc($domain['name_fr'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="bi bi-floppy me-1"></i>Enregistrer le domaine
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Clauses + Controls -->
            <?php foreach ($domain['clauses'] as $clause): ?>
            <div class="card border-0 border-start border-3 border-primary mb-3 shadow-sm">
                <div class="card-header bg-light d-flex align-items-center gap-2 py-2">
                    <code class="text-primary"><?= esc($clause['code']) ?></code>
                    <strong class="small flex-grow-1">
                        <?= esc($clause['title_fr'] ?: $clause['title']) ?>
                        <?php if ($clause['title_fr']): ?>
                        <span class="text-muted fw-normal ms-1">(<?= esc($clause['title']) ?>)</span>
                        <?php endif ?>
                    </strong>
                    <span class="badge bg-secondary-subtle text-secondary border small">
                        <?= count($clause['controls']) ?> contrôles
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:90px">Code</th>
                                <th>Titre EN</th>
                                <th>Titre FR</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clause['controls'] as $ctrl): ?>
                            <tr id="ctrl-row-<?= $ctrl['id'] ?>">
                                <td><code class="small"><?= esc($ctrl['code']) ?></code></td>
                                <td class="small ctrl-title-en"><?= esc($ctrl['title']) ?></td>
                                <td class="small ctrl-title-fr <?= empty($ctrl['title_fr']) ? 'text-danger fst-italic' : '' ?>">
                                    <?= esc($ctrl['title_fr'] ?: '— manquant') ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0"
                                            onclick="openEditModal(<?= $ctrl['id'] ?>, <?= json_encode($ctrl['title']) ?>, <?= json_encode($ctrl['title_fr'] ?? '') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach ?>

        </div>
    </div>
</div>

<?php endforeach ?>
</div>

<!-- Edit control modal -->
<div class="modal fade" id="editControlModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="editControlForm">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Modifier le contrôle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre EN</label>
                        <input type="text" name="title" id="editTitleEn" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre FR</label>
                        <input type="text" name="title_fr" id="editTitleFr" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const baseUrl = '<?= site_url('admin/catalog/control') ?>';

function openEditModal(controlId, titleEn, titleFr) {
    document.getElementById('editTitleEn').value = titleEn;
    document.getElementById('editTitleFr').value = titleFr;
    document.getElementById('editControlForm').action = baseUrl + '/' + controlId + '/save';
    new bootstrap.Modal(document.getElementById('editControlModal')).show();
}
</script>

<?= $this->endSection() ?>
