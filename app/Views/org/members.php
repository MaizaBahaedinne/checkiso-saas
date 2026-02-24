<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-semibold mb-0">Members</h4>
    <a href="<?= site_url('org/requests') ?>" class="btn btn-outline-primary btn-sm">
        📋 Join requests
    </a>
</div>

<?php if ($flash = session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc($flash) ?></div>
<?php endif ?>
<?php if ($flash = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2"><?= esc($flash) ?></div>
<?php endif ?>

<?php if (empty($members)): ?>
    <p class="text-muted">No members found.</p>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $m):
                $isSelf  = (int) $m['user_id'] === (int) session()->get('user_id');
                $isAdmin = $m['role_code'] === 'org.admin';
            ?>
                <tr class="<?= $m['status'] !== 'active' ? 'text-muted' : '' ?>">
                    <td>
                        <?= esc($m['first_name'] . ' ' . $m['last_name']) ?>
                        <?php if ($isSelf): ?>
                            <span class="badge bg-secondary ms-1">you</span>
                        <?php endif ?>
                    </td>
                    <td class="small"><?= esc($m['email']) ?></td>
                    <td>
                        <?php if ($m['role_code'] === 'org.admin'): ?>
                            <span class="badge bg-primary">Admin</span>
                        <?php elseif ($m['role_code'] === 'org.member'): ?>
                            <span class="badge bg-light text-dark border">Member</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">—</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($m['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                        <?php endif ?>
                    </td>
                    <td class="small text-muted"><?= esc(date('d M Y', strtotime($m['joined_at']))) ?></td>
                    <td class="text-end">
                        <?php if (! $isSelf && $m['status'] === 'active'): ?>
                            <!-- Role toggle -->
                            <form method="post"
                                  action="<?= site_url('org/members/' . $m['membership_id'] . '/role') ?>"
                                  class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="role"
                                       value="<?= $isAdmin ? 'org.member' : 'org.admin' ?>">
                                <button class="btn btn-sm <?= $isAdmin ? 'btn-outline-warning' : 'btn-outline-primary' ?>"
                                        title="<?= $isAdmin ? 'Demote to Member' : 'Promote to Admin' ?>">
                                    <?= $isAdmin ? '↓ Member' : '↑ Admin' ?>
                                </button>
                            </form>
                            <!-- Remove -->
                            <form method="post"
                                  action="<?= site_url('org/members/' . $m['membership_id'] . '/remove') ?>"
                                  class="d-inline ms-1"
                                  onsubmit="return confirm('Remove this member?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
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
<?php endif ?>

<?= $this->endSection() ?>
