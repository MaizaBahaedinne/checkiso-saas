<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$sectors = [
    'Technology & Software', 'Finance & Banking', 'Healthcare & Pharma',
    'Manufacturing & Industry', 'Retail & E-commerce', 'Education',
    'Government & Public Sector', 'Energy & Utilities', 'Transport & Logistics',
    'Professional Services', 'Media & Communication', 'Other',
];
$employeeRanges = ['1-10', '11-50', '51-200', '201-500', '500+'];
$countries = [
    'DZ' => 'Algeria', 'FR' => 'France', 'MA' => 'Morocco', 'TN' => 'Tunisia',
    'GB' => 'United Kingdom', 'DE' => 'Germany', 'US' => 'United States',
    'CA' => 'Canada', 'AE' => 'UAE', 'SA' => 'Saudi Arabia',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-semibold mb-0">Organisation Settings</h4>
</div>

<?php if ($success = session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc($success) ?></div>
<?php endif ?>
<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
    </div>
<?php endif ?>

<div class="card border-0 shadow-sm" style="max-width:640px;">
    <div class="card-body p-4">
        <form method="post" action="<?= site_url('org/settings') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label fw-medium">Organisation name <span class="text-danger">*</span></label>
                <input type="text" name="org_name" class="form-control"
                       value="<?= esc(old('org_name', $tenant['name'])) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Sector</label>
                <select name="sector" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($sectors as $s): ?>
                        <option value="<?= esc($s) ?>"
                            <?= old('sector', $tenant['sector'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= esc($s) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Number of employees</label>
                <select name="employees_range" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($employeeRanges as $r): ?>
                        <option value="<?= esc($r) ?>"
                            <?= old('employees_range', $tenant['employees_range'] ?? '') === $r ? 'selected' : '' ?>>
                            <?= esc($r) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Street address</label>
                <input type="text" name="address_line" class="form-control"
                       value="<?= esc(old('address_line', $tenant['address_line'] ?? '')) ?>">
            </div>

            <div class="row g-2 mb-3">
                <div class="col-5">
                    <label class="form-label fw-medium">City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= esc(old('city', $tenant['city'] ?? '')) ?>">
                </div>
                <div class="col-3">
                    <label class="form-label fw-medium">Postal code</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="<?= esc(old('postal_code', $tenant['postal_code'] ?? '')) ?>">
                </div>
                <div class="col-4">
                    <label class="form-label fw-medium">Country</label>
                    <select name="country_code" class="form-select">
                        <option value="">—</option>
                        <?php foreach ($countries as $code => $label): ?>
                            <option value="<?= esc($code) ?>"
                                <?= old('country_code', $tenant['country_code'] ?? '') === $code ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Website</label>
                <input type="url" name="website" class="form-control"
                       value="<?= esc(old('website', $tenant['website'] ?? '')) ?>"
                       placeholder="https://example.com">
            </div>

            <div class="mb-4">
                <label class="form-label fw-medium">Contact email</label>
                <input type="email" name="contact_email" class="form-control"
                       value="<?= esc(old('contact_email', $tenant['contact_email'] ?? '')) ?>">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
