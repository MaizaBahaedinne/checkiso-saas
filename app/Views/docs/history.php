<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<nav class="mb-4 small text-muted d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= site_url('docs') ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i><?= lang('Doc.back_to_docs') ?>
    </a>
    <span>/</span>
    <a href="<?= site_url('docs/' . $doc['id']) ?>" class="text-muted text-decoration-none">
        <?= esc($doc['title']) ?>
    </a>
    <span>/</span>
    <span class="text-dark"><?= lang('Doc.history_title') ?></span>
</nav>

<!-- ── Header ───────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-0">
            <i class="bi bi-clock-history me-2 text-info"></i><?= lang('Doc.history_title') ?>
        </h1>
        <p class="text-muted mb-0"><?= esc($doc['title']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('docs/' . $doc['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i><?= lang('Doc.view_btn') ?>
        </a>
        <a href="<?= site_url('docs/' . $doc['id'] . '/edit') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i><?= lang('Doc.edit_btn') ?>
        </a>
    </div>
</div>

<!-- ── Version timeline ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:80px"><?= lang('Doc.col_version') ?></th>
                    <th><?= lang('Doc.col_summary') ?></th>
                    <th style="width:150px"><?= lang('Doc.col_by') ?></th>
                    <th style="width:120px"><?= lang('Doc.col_updated') ?></th>
                    <th class="text-end" style="width:130px"><?= lang('Doc.col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($versions as $v):
                $isCurrent = (int)$v['version_number'] === (int)$doc['current_version'];
                $authorName = trim(($v['first_name'] ?? '') . ' ' . ($v['last_name'] ?? '')) ?: '—';
            ?>
            <tr class="<?= $isCurrent ? 'table-primary' : '' ?>">
                <td>
                    <span class="badge font-monospace <?= $isCurrent ? 'bg-primary' : 'bg-light text-dark border' ?>">
                        v<?= $v['version_number'] ?>
                    </span>
                    <?php if ($isCurrent): ?>
                    <span class="badge bg-success ms-1" style="font-size:.65rem"><?= lang('Doc.current_badge') ?></span>
                    <?php endif; ?>
                </td>
                <td class="small"><?= esc($v['change_summary'] ?? '—') ?></td>
                <td class="small text-muted"><?= esc($authorName) ?></td>
                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                <td class="text-end">
                    <a href="<?= site_url('docs/' . $doc['id'] . '/version/' . $v['version_number']) ?>"
                       class="btn btn-outline-secondary btn-sm py-0 px-2" title="<?= lang('Doc.view_btn') ?>">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if (! $isCurrent): ?>
                    <form action="<?= site_url('docs/' . $doc['id'] . '/restore/' . $v['version_number']) ?>"
                          method="post" class="d-inline"
                          onsubmit="return confirm(<?= json_encode(lang('Doc.confirm_restore')) ?>)">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-warning btn-sm py-0 px-2 ms-1"
                                title="<?= lang('Doc.restore_btn') ?>">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
