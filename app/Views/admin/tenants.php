<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="fw-semibold mb-4">Organisations</h4>

<?php if ($flash = session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc($flash) ?></div>
<?php endif ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Sector</th>
                    <th>City</th>
                    <th>Members</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
                <tr>
                    <td class="fw-medium"><?= esc($t['name']) ?></td>
                    <td class="small text-muted"><?= esc($t['sector'] ?? '—') ?></td>
                    <td class="small text-muted"><?= esc($t['city'] ?? '—') ?></td>
                    <td><?= (int) $t['member_count'] ?></td>
                    <td>
                        <?php if ($t['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php elseif ($t['status'] === 'suspended'): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border"><?= esc($t['status']) ?></span>
                        <?php endif ?>
                    </td>
                    <td class="small text-muted"><?= esc(date('d M Y', strtotime($t['created_at']))) ?></td>
                    <td class="text-end">
                        <form method="post"
                              action="<?= site_url('admin/tenants/' . $t['id'] . '/toggle') ?>"
                              class="d-inline"
                              onsubmit="return confirm('Change status of this organisation?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm <?= $t['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= $t['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
