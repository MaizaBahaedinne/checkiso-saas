<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4 class="fw-semibold mb-4">Join Requests</h4>

<?php if ($success = session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc($success) ?></div>
<?php endif ?>

<?php if (empty($requests)): ?>
    <p class="text-muted">No pending requests.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Message</th>
                <th>Requested</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $req): ?>
            <tr>
                <td><?= esc($req['first_name'] . ' ' . $req['last_name']) ?></td>
                <td><?= esc($req['email']) ?></td>
                <td class="text-muted small"><?= esc($req['message'] ?? '—') ?></td>
                <td class="small text-muted"><?= esc($req['created_at']) ?></td>
                <td class="text-end">
                    <form method="post" action="/org/requests/<?= $req['id'] ?>/approve" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="post" action="/org/requests/<?= $req['id'] ?>/reject" class="d-inline ms-1">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger btn-sm">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>

<?= $this->endSection() ?>
