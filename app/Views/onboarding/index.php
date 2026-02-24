<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<?php
// Sectors list
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

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-4">
        <h5 class="card-title mb-1">Set up your organisation</h5>
        <p class="text-muted small mb-4">You can create a new one or join an existing one.</p>

        <?php if ($error = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2"><?= esc($error) ?></div>
        <?php endif ?>
        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
            </div>
        <?php endif ?>

        <!-- Duplicate warning + quick join -->
        <?php if ($dup = session()->getFlashdata('duplicate')): ?>
        <div class="alert alert-warning py-2">
            An organisation named <strong><?= esc($dup['name']) ?></strong> already exists.
            <a href="#join-section" class="alert-link">Request to join it instead</a>.
        </div>
        <?php endif ?>

        <!-- TAB SWITCHER -->
        <ul class="nav nav-pills nav-fill mb-4" id="onboardingTab">
            <li class="nav-item">
                <button class="nav-link active" data-bs-target="#create" data-bs-toggle="pill">
                    ➕ Create new
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-target="#join" data-bs-toggle="pill">
                    🔍 Join existing
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ---- CREATE ---- -->
            <div class="tab-pane fade show active" id="create">
                <form method="post" action="/onboarding/create">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Organisation name <span class="text-danger">*</span></label>
                        <input type="text" name="org_name" id="orgNameInput" class="form-control"
                               value="<?= esc(old('org_name')) ?>" required autofocus
                               placeholder="e.g. Acme Corp">
                        <div class="form-text text-warning" id="dupWarning" style="display:none">
                            ⚠️ An organisation with a similar name exists — check before creating.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Sector</label>
                        <select name="sector" class="form-select">
                            <option value="">— Select —</option>
                            <?php foreach ($sectors as $s): ?>
                                <option value="<?= esc($s) ?>" <?= old('sector') === $s ? 'selected' : '' ?>>
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
                                <option value="<?= esc($r) ?>" <?= old('employees_range') === $r ? 'selected' : '' ?>>
                                    <?= esc($r) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Street address</label>
                        <input type="text" name="address_line" class="form-control"
                               value="<?= esc(old('address_line')) ?>" placeholder="123 Main Street">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label fw-medium">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= esc(old('city')) ?>">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-medium">Postal code</label>
                            <input type="text" name="postal_code" class="form-control"
                                   value="<?= esc(old('postal_code')) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-medium">Country</label>
                            <select name="country_code" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($countries as $code => $label): ?>
                                    <option value="<?= esc($code) ?>" <?= old('country_code') === $code ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Website</label>
                        <input type="url" name="website" class="form-control"
                               value="<?= esc(old('website')) ?>" placeholder="https://example.com">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Contact email</label>
                        <input type="email" name="contact_email" class="form-control"
                               value="<?= esc(old('contact_email')) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Create organisation</button>
                </form>
            </div>

            <!-- ---- JOIN ---- -->
            <div class="tab-pane fade" id="join" id="join-section">
                <form method="post" action="/onboarding/join">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Search organisation</label>
                        <input type="text" id="joinSearch" class="form-control"
                               placeholder="Type organisation name…" autocomplete="off">
                        <div id="joinResults" class="list-group mt-1" style="display:none;max-height:200px;overflow-y:auto;"></div>
                        <input type="hidden" name="tenant_id" id="joinTenantId">
                        <div class="form-text" id="joinSelected"></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Message <span class="text-muted fw-normal small">(optional)</span></label>
                        <textarea name="message" class="form-control" rows="3"
                                  placeholder="Briefly explain why you want to join this organisation…"><?= esc(old('message')) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-outline-primary w-100"
                            id="joinBtn" disabled>Send join request</button>
                </form>
            </div>

        </div><!-- tab-content -->
    </div>
</div>

<p class="text-center text-muted small">
    Logged in as <strong><?= esc(session()->get('user_name')) ?></strong> —
    <a href="/logout">Logout</a>
</p>

<script>
// Autocomplete for the join search
const joinSearch   = document.getElementById('joinSearch');
const joinResults  = document.getElementById('joinResults');
const joinTenantId = document.getElementById('joinTenantId');
const joinSelected = document.getElementById('joinSelected');
const joinBtn      = document.getElementById('joinBtn');

let debounce;
joinSearch.addEventListener('input', () => {
    clearTimeout(debounce);
    const q = joinSearch.value.trim();
    if (q.length < 2) { joinResults.style.display = 'none'; return; }
    debounce = setTimeout(async () => {
        const res  = await fetch('/onboarding/search?q=' + encodeURIComponent(q));
        const data = await res.json();
        joinResults.innerHTML = '';
        if (!data.length) {
            joinResults.innerHTML = '<div class="list-group-item text-muted small">No results</div>';
        } else {
            data.forEach(t => {
                const btn = document.createElement('button');
                btn.type      = 'button';
                btn.className = 'list-group-item list-group-item-action small';
                btn.textContent = t.name + (t.city ? ' — ' + t.city : '');
                btn.onclick = () => {
                    joinTenantId.value    = t.id;
                    joinSearch.value      = t.name;
                    joinSelected.textContent = '✔ Selected: ' + t.name;
                    joinBtn.disabled      = false;
                    joinResults.style.display = 'none';
                };
                joinResults.appendChild(btn);
            });
        }
        joinResults.style.display = 'block';
    }, 300);
});

// Duplicate live-check for the create form
const orgNameInput = document.getElementById('orgNameInput');
const dupWarning   = document.getElementById('dupWarning');
let dupDebounce;
orgNameInput.addEventListener('input', () => {
    clearTimeout(dupDebounce);
    const q = orgNameInput.value.trim();
    if (q.length < 3) { dupWarning.style.display = 'none'; return; }
    dupDebounce = setTimeout(async () => {
        const res  = await fetch('/onboarding/search?q=' + encodeURIComponent(q));
        const data = await res.json();
        const exact = data.some(t => t.name.toLowerCase() === q.toLowerCase());
        dupWarning.style.display = exact ? 'block' : 'none';
    }, 400);
});
</script>

<?= $this->endSection() ?>
