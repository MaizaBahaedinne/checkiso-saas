<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">📚 <?= lang('Catalog.title') ?></h1>
        <p class="text-muted mb-0"><?= lang('Catalog.subtitle') ?></p>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($versions)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
        <?= lang('Catalog.no_standards') ?>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($versions as $v): ?>
            <?php $sub = $v['subscribed']; ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-<?= $sub ? 'success' : '0' ?>">
                    <?php if ($sub): ?>
                        <div class="card-header bg-success text-white d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <small class="fw-semibold"><?= lang('Catalog.subscribed_label') ?></small>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3 text-center" style="min-width:64px">
                                <span class="fw-bold text-primary fs-6"><?= esc($v['standard_code']) ?></span>
                                <br>
                                <small class="text-muted"><?= esc($v['published_year']) ?></small>
                            </div>
                            <div>
                                <h5 class="card-title mb-1 fw-semibold"><?= esc($v['standard_name']) ?></h5>
                                <span class="badge bg-secondary"><?= esc($v['version_code']) ?></span>
                            </div>
                        </div>
                        <?php if (!empty($v['standard_description'])): ?>
                            <p class="card-text text-muted small"><?= esc($v['standard_description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 d-flex gap-2">
                        <a href="<?= site_url('catalog/' . $v['id']) ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i><?= lang('Catalog.consult_btn') ?>
                        </a>
                        <?php if ($sub): ?>
                            <form action="<?= site_url('catalog/' . $v['id'] . '/unsubscribe') ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('<?= lang('Catalog.confirm_unsubscribe') ?>')">
                                    <i class="bi bi-dash-circle me-1"></i><?= lang('Catalog.unsubscribe_btn') ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="<?= site_url('catalog/' . $v['id'] . '/subscribe') ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i><?= lang('Catalog.subscribe_btn') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
