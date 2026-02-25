<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="fw-semibold mb-4">Platform Overview</h4>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Organisations',     'value' => $stats['tenants'],       'icon' => '🏢', 'color' => 'primary',  'link' => 'admin/tenants'],
        ['label' => 'Users',             'value' => $stats['users'],         'icon' => '👤', 'color' => 'success',  'link' => 'admin/users'],
        ['label' => 'Active memberships','value' => $stats['memberships'],   'icon' => '🔗', 'color' => 'info',     'link' => null],
        ['label' => 'Pending invites',   'value' => $stats['invitations'],   'icon' => '✉️', 'color' => 'warning',  'link' => null],
        ['label' => 'Pending join reqs', 'value' => $stats['join_requests'], 'icon' => '📋', 'color' => 'secondary','link' => null],
    ];
    foreach ($cards as $c):
    ?>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span style="font-size:2rem;"><?= $c['icon'] ?></span>
                <div>
                    <div class="fs-4 fw-bold text-<?= $c['color'] ?>"><?= $c['value'] ?></div>
                    <div class="text-muted small"><?= $c['label'] ?></div>
                </div>
                <?php if ($c['link']): ?>
                <a href="<?= site_url($c['link']) ?>" class="stretched-link"></a>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>

<div class="row g-4">
    <!-- Latest Organisations -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold border-0 pt-3">
                🏢 Latest organisations
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Status</th><th>Created</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($latestTenants as $t): ?>
                        <tr>
                            <td><?= esc($t['name']) ?></td>
                            <td>
                                <span class="badge <?= $t['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?>">
                                    <?= esc($t['status']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= esc(date('d M Y', strtotime($t['created_at']))) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 text-end">
                <a href="<?= site_url('admin/tenants') ?>" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
        </div>
    </div>

    <!-- Latest Users -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold border-0 pt-3">
                👤 Latest users
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Status</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($latestUsers as $u): ?>
                        <tr>
                            <td>
                                <?= esc(trim($u['first_name'] . ' ' . $u['last_name'])) ?>
                                <div class="text-muted" style="font-size:.75rem;"><?= esc($u['email']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?>">
                                    <?= esc($u['status']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= esc(date('d M Y', strtotime($u['created_at']))) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-0 text-end">
                <a href="<?= site_url('admin/users') ?>" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
