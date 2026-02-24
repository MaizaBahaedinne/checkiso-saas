<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-semibold mb-0">Members</h4>
    <div class="d-flex gap-2">
        <?php if (session()->get('role_code') === 'org.admin'): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
            ✉️ Invite member
        </button>
        <a href="<?= site_url('org/requests') ?>" class="btn btn-outline-secondary btn-sm">
            📋 Join requests
        </a>
        <?php endif ?>
    </div>
</div>

<!-- Invite Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= site_url('org/invite') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-semibold">Invite a new member</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Email address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               placeholder="colleague@example.com" required autofocus>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-medium">Role</label>
                        <select name="role_code" class="form-select">
                            <option value="org.member" selected>Member</option>
                            <option value="org.admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Send invitation</button>
                </div>
            </form>
        </div>
    </div>
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
                        <?php if (session()->get('role_code') === 'org.admin' && ! $isSelf && $m['status'] === 'active'): ?>
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

<!-- Pending Invitations -->
<?php
$pendingInvitations = array_filter($invitations ?? [], fn($i) => $i['status'] === 'pending');
if (! empty($pendingInvitations)):
?>
<h6 class="fw-semibold mt-4 mb-3 text-muted">Pending invitations</h6>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Invited by</th>
                    <th>Expires</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingInvitations as $inv): ?>
                <tr>
                    <td class="small"><?= esc($inv['email']) ?></td>
                    <td>
                        <?php if ($inv['role_code'] === 'org.admin'): ?>
                            <span class="badge bg-primary">Admin</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border">Member</span>
                        <?php endif ?>
                    </td>
                    <td class="small text-muted">
                        <?= esc(trim(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? ''))) ?>
                    </td>
                    <td class="small text-muted">
                        <?= $inv['expires_at'] ? esc(date('d M Y', strtotime($inv['expires_at']))) : '—' ?>
                    </td>
                    <td class="text-end">
                        <form method="post"
                              action="<?= site_url('org/invite/' . $inv['id'] . '/cancel') ?>"
                              class="d-inline"
                              onsubmit="return confirm('Cancel this invitation?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary">Cancel</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
