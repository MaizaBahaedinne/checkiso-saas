<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$today = date('Y-m-d');

$priorityBadge = [
    'high'   => 'danger',
    'medium' => 'warning',
    'low'    => 'secondary',
];
$priorityLabel = [
    'high'   => lang('ActionPlan.priority_high'),
    'medium' => lang('ActionPlan.priority_medium'),
    'low'    => lang('ActionPlan.priority_low'),
];
$statusOptions = [
    'todo'        => lang('ActionPlan.status_todo'),
    'in_progress' => lang('ActionPlan.status_in_progress'),
    'done'        => lang('ActionPlan.status_done'),
];
$colConfig = [
    'todo'        => ['label' => lang('ActionPlan.col_todo'),        'border' => 'border-primary'],
    'in_progress' => ['label' => lang('ActionPlan.col_in_progress'), 'border' => 'border-warning'],
    'done'        => ['label' => lang('ActionPlan.col_done'),        'border' => 'border-success'],
];
$totalPlans = count($columns['todo']) + count($columns['in_progress']) + count($columns['done']);
?>

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-0">🎯 <?= lang('ActionPlan.title') ?></h1>
        <p class="text-muted mb-0 small">
            <?= $totalPlans ?> action<?= $totalPlans > 1 ? 's' : '' ?>
            &nbsp;·&nbsp;
            <?= count($columns['done']) ?> <?= lang('ActionPlan.stat_done') ?>
            <?php
            $overdue = 0;
            foreach (array_merge($columns['todo'], $columns['in_progress']) as $p) {
                if ($p['due_date'] && $p['due_date'] < $today) $overdue++;
            }
            ?>
            <?php if ($overdue > 0): ?>
            &nbsp;·&nbsp;<span class="text-danger fw-semibold">⚠️ <?= $overdue ?> <?= lang('ActionPlan.stat_overdue') ?></span>
            <?php endif ?>
        </p>
    </div>
    <a href="<?= site_url('action-plan/create') ?>" class="btn btn-primary">
        <?= lang('ActionPlan.btn_create') ?>
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    ✅ <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<?php if ($totalPlans === 0): ?>
<div class="text-center py-5 text-muted">
    <div class="fs-1 mb-3">🎯</div>
    <p><?= lang('ActionPlan.empty_board') ?></p>
    <a href="<?= site_url('action-plan/create') ?>" class="btn btn-primary">
        <?= lang('ActionPlan.btn_create') ?>
    </a>
</div>
<?php else: ?>

<!-- ── Kanban board ──────────────────────────────────────────────────────── -->
<div class="row g-3" style="align-items:flex-start;">
<?php foreach ($colConfig as $colKey => $col): ?>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-top border-3 <?= $col['border'] ?> h-100">
            <div class="card-header bg-transparent fw-semibold d-flex align-items-center justify-content-between">
                <span><?= $col['label'] ?></span>
                <span class="badge bg-secondary rounded-pill"><?= count($columns[$colKey]) ?></span>
            </div>
            <div class="card-body p-2 d-flex flex-column gap-2">

                <?php if (empty($columns[$colKey])): ?>
                <p class="text-muted small text-center py-3 mb-0"><?= lang('ActionPlan.empty') ?></p>
                <?php endif ?>

                <?php foreach ($columns[$colKey] as $plan): ?>
                <?php
                $isOverdue = $plan['due_date'] && $plan['due_date'] < $today && $colKey !== 'done';
                ?>
                <div class="card border-0 shadow-sm" id="plan-<?= $plan['id'] ?>">
                    <div class="card-body p-3">

                        <!-- Priority + overdue -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-<?= $priorityBadge[$plan['priority']] ?> bg-opacity-75">
                                <?= $priorityLabel[$plan['priority']] ?>
                            </span>
                            <?php if ($isOverdue): ?>
                            <span class="badge bg-danger"><?= lang('ActionPlan.overdue_badge') ?></span>
                            <?php endif ?>
                            <?php if ($plan['control_code']): ?>
                            <span class="badge bg-light text-dark border font-monospace ms-auto">
                                <?= esc($plan['control_code']) ?>
                            </span>
                            <?php endif ?>
                        </div>

                        <!-- Title -->
                        <p class="fw-semibold mb-1 small"><?= esc($plan['title']) ?></p>

                        <!-- Description excerpt -->
                        <?php if ($plan['description']): ?>
                        <p class="text-muted small mb-2" style="white-space:pre-line;overflow:hidden;max-height:3.5em;">
                            <?= esc(mb_strimwidth($plan['description'], 0, 80, '…')) ?>
                        </p>
                        <?php endif ?>

                        <!-- Due date + owner -->
                        <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                            <?php if ($plan['due_date']): ?>
                            <span class="<?= $isOverdue ? 'text-danger fw-semibold' : '' ?>">
                                📅 <?= date('d/m/Y', strtotime($plan['due_date'])) ?>
                            </span>
                            <?php else: ?>
                            <span class="fst-italic"><?= lang('ActionPlan.no_due_date') ?></span>
                            <?php endif ?>
                            <?php if ($plan['owner_name']): ?>
                            <span class="ms-auto">👤 <?= esc($plan['owner_name']) ?></span>
                            <?php endif ?>
                        </div>

                        <!-- Quick status buttons -->
                        <div class="d-flex gap-1 flex-wrap">
                            <?php foreach ($statusOptions as $s => $label): ?>
                                <?php if ($s !== $colKey): ?>
                                <button class="btn btn-outline-secondary btn-sm py-0 px-2"
                                        style="font-size:.72rem"
                                        onclick="changeStatus(<?= $plan['id'] ?>, '<?= $s ?>', this)"
                                        title="<?= $label ?>">
                                    → <?= $label ?>
                                </button>
                                <?php endif ?>
                            <?php endforeach ?>
                            <button class="btn btn-outline-primary btn-sm py-0 px-2 ms-auto"
                                    style="font-size:.72rem"
                                    onclick="openEditModal(<?= $plan['id'] ?>)">
                                ✏️
                            </button>
                            <form method="post" action="<?= site_url('action-plan/' . $plan['id'] . '/delete') ?>"
                                  style="display:inline"
                                  onsubmit="return confirm('<?= lang('ActionPlan.delete_confirm') ?>')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm py-0 px-2"
                                        style="font-size:.72rem">🗑</button>
                            </form>
                        </div>

                    </div>
                </div>
                <?php endforeach ?>

            </div>
        </div>
    </div>
<?php endforeach ?>
</div>

<?php endif ?>

<!-- ── Edit Modal ─────────────────────────────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= lang('ActionPlan.edit_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="editForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('ActionPlan.field_title') ?> *</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('ActionPlan.field_description') ?></label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= lang('ActionPlan.field_priority') ?></label>
                            <select name="priority" id="edit_priority" class="form-select">
                                <option value="low"><?= lang('ActionPlan.priority_low') ?></option>
                                <option value="medium"><?= lang('ActionPlan.priority_medium') ?></option>
                                <option value="high"><?= lang('ActionPlan.priority_high') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= lang('ActionPlan.field_status') ?></label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="todo"><?= lang('ActionPlan.status_todo') ?></option>
                                <option value="in_progress"><?= lang('ActionPlan.status_in_progress') ?></option>
                                <option value="done"><?= lang('ActionPlan.status_done') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= lang('ActionPlan.field_due_date') ?></label>
                            <input type="date" name="due_date" id="edit_due_date" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold"><?= lang('ActionPlan.field_owner') ?></label>
                        <select name="owner_user_id" id="edit_owner" class="form-select">
                            <option value=""><?= lang('ActionPlan.no_owner') ?></option>
                            <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= esc($m['first_name'] . ' ' . $m['last_name']) ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= lang('ActionPlan.btn_cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary"><?= lang('ActionPlan.btn_update') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= site_url() ?>';

// Quick status change (AJAX)
function changeStatus(id, status, btn) {
    btn.disabled = true;
    const csrfName = document.querySelector('[name^="csrf_"]')?.name;
    const csrfVal  = document.querySelector('[name^="csrf_"]')?.value;

    fetch(BASE_URL + 'action-plan/' + id + '/status', {
        method : 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body   : csrfName + '=' + encodeURIComponent(csrfVal) + '&status=' + status,
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            // Refresh page to reflect new column
            window.location.reload();
        }
    })
    .catch(() => { btn.disabled = false; });
}

// Open edit modal — fetch current data via JSON endpoint
function openEditModal(id) {
    fetch(BASE_URL + 'action-plan/' + id + '/edit', {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(plan => {
        document.getElementById('edit_title').value       = plan.title ?? '';
        document.getElementById('edit_description').value = plan.description ?? '';
        document.getElementById('edit_priority').value    = plan.priority ?? 'medium';
        document.getElementById('edit_status').value      = plan.status ?? 'todo';
        document.getElementById('edit_due_date').value    = plan.due_date ?? '';
        document.getElementById('edit_owner').value       = plan.owner_user_id ?? '';

        document.getElementById('editForm').action = BASE_URL + 'action-plan/' + id + '/update';
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
}
</script>

<?= $this->endSection() ?>
