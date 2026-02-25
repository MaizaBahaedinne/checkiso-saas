<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="fw-semibold mb-4">Users</h4>

<?php if ($flash = session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc($flash) ?></div>
<?php endif ?>
<?php if ($flash = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2"><?= esc($flash) ?></div>
<?php endif ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Orgs</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th>Registered</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $isSelf = (int) $u['id'] === (int) session()->get('user_id');
            ?>
                <tr>
                    <td class="fw-medium">
                        <?= esc(trim($u['first_name'] . ' ' . $u['last_name'])) ?>
                        <?php if ($isSelf): ?><span class="badge bg-secondary ms-1">you</span><?php endif ?>
                    </td>
                    <td class="small"><?= esc($u['email']) ?></td>
                    <td><?= (int) $u['tenant_count'] ?></td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                        <?php endif ?>
                    </td>
                    <td class="small text-muted">
                        <?= $u['last_login_at'] ? esc(date('d M Y H:i', strtotime($u['last_login_at']))) : '—' ?>
                    </td>
                    <td class="small text-muted"><?= esc(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <td class="text-end">
                        <?php if (! $isSelf): ?>
                        <form method="post"
                              action="<?= site_url('admin/users/' . $u['id'] . '/toggle') ?>"
                              class="d-inline"
                              onsubmit="return confirm('Change status of this user?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
