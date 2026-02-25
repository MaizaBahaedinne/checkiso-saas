<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="mb-4">
    <a href="<?= site_url('action-plan') ?>" class="text-muted text-decoration-none small">
        ← <?= lang('ActionPlan.title') ?>
    </a>
    <h1 class="h3 fw-bold mt-1">🎯 <?= lang('ActionPlan.create_title') ?></h1>
</div>

<?php if (session()->getFlashdata('errors')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        <?php foreach (session()->getFlashdata('errors') as $e): ?>
        <li><?= esc($e) ?></li>
        <?php endforeach ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<div class="card border-0 shadow-sm" style="max-width:640px;">
    <div class="card-body p-4">
        <form method="post" action="<?= site_url('action-plan') ?>">
            <?= csrf_field() ?>

            <!-- Hidden context from gap summary -->
            <input type="hidden" name="gap_session_id" value="<?= esc($sessionId ?? '') ?>">
            <input type="hidden" name="control_id"     value="<?= esc($controlId ?? '') ?>">

            <!-- Pre-filled linked control badge -->
            <?php if ($controlCode): ?>
            <div class="alert alert-info py-2 small mb-3">
                <?= lang('ActionPlan.linked_control') ?> :
                <strong class="font-monospace"><?= esc($controlCode) ?></strong>
                — <?= esc($controlTitle) ?>
            </div>
            <?php endif ?>

            <!-- Title -->
            <div class="mb-3">
                <label class="form-label fw-semibold"><?= lang('ActionPlan.field_title') ?> *</label>
                <input type="text" name="title" class="form-control"
                       value="<?= esc(old('title', $controlCode ? "[$controlCode] " : '')) ?>"
                       required maxlength="255" autofocus>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label class="form-label fw-semibold"><?= lang('ActionPlan.field_description') ?></label>
                <textarea name="description" class="form-control" rows="4"><?= esc(old('description')) ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <!-- Priority -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold"><?= lang('ActionPlan.field_priority') ?></label>
                    <select name="priority" class="form-select">
                        <option value="low"    <?= old('priority') === 'low'    ? 'selected' : '' ?>><?= lang('ActionPlan.priority_low') ?></option>
                        <option value="medium" <?= (old('priority', 'medium') === 'medium') ? 'selected' : '' ?>><?= lang('ActionPlan.priority_medium') ?></option>
                        <option value="high"   <?= old('priority') === 'high'   ? 'selected' : '' ?>><?= lang('ActionPlan.priority_high') ?></option>
                    </select>
                </div>

                <!-- Due date -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold"><?= lang('ActionPlan.field_due_date') ?></label>
                    <input type="date" name="due_date" class="form-control"
                           value="<?= esc(old('due_date')) ?>">
                </div>

                <!-- Owner -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold"><?= lang('ActionPlan.field_owner') ?></label>
                    <select name="owner_user_id" class="form-select">
                        <option value=""><?= lang('ActionPlan.no_owner') ?></option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"
                            <?= old('owner_user_id') == $m['id'] ? 'selected' : '' ?>>
                            <?= esc($m['first_name'] . ' ' . $m['last_name']) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= lang('ActionPlan.btn_save') ?></button>
                <a href="<?= site_url('action-plan') ?>" class="btn btn-outline-secondary">
                    <?= lang('ActionPlan.btn_cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
