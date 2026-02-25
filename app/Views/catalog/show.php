<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$totalClauses  = 0;
$totalControls = 0;
foreach ($clausesByDomain as $cls) { $totalClauses += count($cls); }
foreach ($controlsByClause as $ctrls) { $totalControls += count($ctrls); }
?>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <a href="<?= site_url('catalog') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Catalogue
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">
            <?= esc($version['standard_code']) ?>
            <span class="text-muted fw-normal fs-5"><?= esc($version['version_code']) ?></span>
        </h1>
        <p class="text-muted mb-0"><?= esc($version['standard_name']) ?></p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if ($subscribed): ?>
            <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle me-1"></i>Abonné</span>
            <form action="<?= site_url('catalog/' . $version['id'] . '/unsubscribe') ?>" method="post">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Se désabonner de ce référentiel ?')">
                    <i class="bi bi-dash-circle me-1"></i>Désabonner
                </button>
            </form>
        <?php else: ?>
            <form action="<?= site_url('catalog/' . $version['id'] . '/subscribe') ?>" method="post">
                <?= csrf_field() ?>
                <button class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i>S'abonner
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats bar -->
<div class="row g-3 mb-4">
    <div class="col-auto">
        <div class="card border-0 bg-primary bg-opacity-10 text-center px-4 py-3">
            <div class="fw-bold fs-4 text-primary"><?= count($domains) ?></div>
            <div class="small text-muted">Domaine<?= count($domains) > 1 ? 's' : '' ?></div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card border-0 bg-info bg-opacity-10 text-center px-4 py-3">
            <div class="fw-bold fs-4 text-info"><?= $totalClauses ?></div>
            <div class="small text-muted">Clause<?= $totalClauses > 1 ? 's' : '' ?></div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card border-0 bg-success bg-opacity-10 text-center px-4 py-3">
            <div class="fw-bold fs-4 text-success"><?= $totalControls ?></div>
            <div class="small text-muted">Contrôle<?= $totalControls > 1 ? 's' : '' ?></div>
        </div>
    </div>
</div>

<!-- Description -->
<?php if (!empty($version['standard_description']) || !empty($version['description'])): ?>
    <div class="alert alert-light border mb-4">
        <?= esc($version['standard_description'] ?? $version['description']) ?>
    </div>
<?php endif; ?>

<!-- Toolbar: expand / collapse all -->
<div class="d-flex justify-content-end mb-3 gap-2">
    <button class="btn btn-sm btn-outline-secondary" id="expandAll">
        <i class="bi bi-arrows-expand me-1"></i>Tout déplier
    </button>
    <button class="btn btn-sm btn-outline-secondary" id="collapseAll">
        <i class="bi bi-arrows-collapse me-1"></i>Tout replier
    </button>
</div>

<!-- Accordion: Domains → Clauses → Controls -->
<div class="accordion" id="catalogAccordion">
    <?php foreach ($domains as $di => $domain): ?>
        <?php
        $domainClauses  = $clausesByDomain[$domain['id']] ?? [];
        $domainCollapseId = 'domain-' . $domain['id'];
        $domainControlCount = 0;
        foreach ($domainClauses as $clause) {
            $domainControlCount += count($controlsByClause[$clause['id']] ?? []);
        }
        ?>
        <div class="accordion-item mb-2 border rounded shadow-sm">
            <h2 class="accordion-header" id="heading-<?= $domainCollapseId ?>">
                <button class="accordion-button <?= $di > 0 ? 'collapsed' : '' ?> fw-semibold"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $domainCollapseId ?>"
                    aria-expanded="<?= $di === 0 ? 'true' : 'false' ?>"
                    aria-controls="<?= $domainCollapseId ?>">
                    <span class="badge bg-primary me-3 fs-6"><?= esc($domain['code']) ?></span>
                    <?= esc($domain['title']) ?>
                    <span class="ms-auto me-3 badge bg-light text-dark border small">
                        <?= count($domainClauses) ?> clause<?= count($domainClauses) > 1 ? 's' : '' ?>
                        · <?= $domainControlCount ?> contrôle<?= $domainControlCount > 1 ? 's' : '' ?>
                    </span>
                </button>
            </h2>
            <div id="<?= $domainCollapseId ?>"
                class="accordion-collapse collapse <?= $di === 0 ? 'show' : '' ?>"
                aria-labelledby="heading-<?= $domainCollapseId ?>"
                data-bs-parent="#catalogAccordion">
                <div class="accordion-body p-0">

                    <?php if (empty($domainClauses)): ?>
                        <p class="text-muted p-3 mb-0">Aucune clause dans ce domaine.</p>
                    <?php else: ?>
                        <div class="accordion" id="clauseAccordion-<?= $domain['id'] ?>">
                            <?php foreach ($domainClauses as $ci => $clause): ?>
                                <?php
                                $clauseControls   = $controlsByClause[$clause['id']] ?? [];
                                $clauseCollapseId = 'clause-' . $clause['id'];
                                ?>
                                <div class="accordion-item border-0 border-top rounded-0">
                                    <h3 class="accordion-header" id="heading-<?= $clauseCollapseId ?>">
                                        <button class="accordion-button collapsed ps-4 py-2 bg-light text-dark fw-medium"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?= $clauseCollapseId ?>"
                                            aria-expanded="false"
                                            aria-controls="<?= $clauseCollapseId ?>">
                                            <code class="me-3 text-secondary"><?= esc($clause['code']) ?></code>
                                            <?= esc($clause['title']) ?>
                                            <span class="ms-auto me-3 badge bg-white text-muted border small">
                                                <?= count($clauseControls) ?> contrôle<?= count($clauseControls) > 1 ? 's' : '' ?>
                                            </span>
                                        </button>
                                    </h3>
                                    <div id="<?= $clauseCollapseId ?>"
                                        class="accordion-collapse collapse"
                                        aria-labelledby="heading-<?= $clauseCollapseId ?>">
                                        <div class="accordion-body ps-5 py-3">
                                            <?php if (!empty($clause['description'])): ?>
                                                <p class="text-muted small mb-3"><?= esc($clause['description']) ?></p>
                                            <?php endif; ?>

                                            <?php if (empty($clauseControls)): ?>
                                                <p class="text-muted small mb-0">Aucun contrôle.</p>
                                            <?php else: ?>
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($clauseControls as $control): ?>
                                                        <div class="list-group-item list-group-item-action px-3 py-2 border-0 border-bottom">
                                                            <div class="d-flex align-items-start gap-3">
                                                                <span class="badge bg-secondary bg-opacity-75 mt-1 text-nowrap">
                                                                    <?= esc($control['code']) ?>
                                                                </span>
                                                                <div>
                                                                    <div class="fw-medium"><?= esc($control['title']) ?></div>
                                                                    <?php if (!empty($control['description'])): ?>
                                                                        <div class="text-muted small mt-1"><?= esc($control['description']) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.getElementById('expandAll').addEventListener('click', () => {
    document.querySelectorAll('#catalogAccordion .accordion-collapse').forEach(el => {
        bootstrap.Collapse.getOrCreateInstance(el).show();
    });
});
document.getElementById('collapseAll').addEventListener('click', () => {
    document.querySelectorAll('#catalogAccordion .accordion-collapse').forEach(el => {
        bootstrap.Collapse.getOrCreateInstance(el).hide();
    });
});
</script>

<?= $this->endSection() ?>
